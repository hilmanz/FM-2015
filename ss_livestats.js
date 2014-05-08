/*
* ss-livestats.js
* ss livestats worker

*/
var crypto = require('crypto');
var fs = require('fs');
var path = require('path');
var xmlparser = require('xml2json');
var async = require('async');
var config = require(path.resolve('./config_ucl')).config;
var mysql = require('mysql');
var dateFormat = require('dateformat');
var redis = require('redis');
var player_stats_category = require(path.resolve('./libs/game_config')).player_stats_category;
var S = require('string');
var argv = require('optimist').argv;
var request = require('request');
var url = require('url');

var pool  = mysql.createPool({
   host     : config.database.host,
   user     : config.database.username,
   password : config.database.password,
});



var redisClient = redis.createClient(config.redis.port,config.redis.host);
redisClient.on("error", function (err) {
    console.log("Error " + err);
});

pool.getConnection(function(err,conn){
		async.waterfall([
			function(cb){
				//get the current matchday
				if(typeof argv.matchday === 'undefined'){
					getCurrentMatchday(conn,cb);	
				}else{
					cb(null,argv.matchday);
				}
				
			},
			function(matchday,cb){
				console.log('matchday -> ',matchday);
				//get the list of game_ids of those matchday
				getGameIdsByMatchday(conn,matchday,cb);
			},
			function(matchday,game_id,cb){
				processData(conn,matchday,game_id,function(err,rs){
					cb(err,rs);
				});
			},
			
		],
		function(err,rs){
			conn.end(function(err){
				pool.end(function(err){
					console.log('done');
					redisClient.quit(function(err){
						console.log('redis session ended');
					});
				});
			});
		});
});

function processData(conn,matchday,game_id,cb){
	async.eachSeries(
	game_id,
	function(game,next){
		processGameId(conn,game.game_id,function(e,rs){
			next();
		});
		
	},function(err){
		cb(err,game_id);	
	});
	
}
function processGameId(conn,game_id,done){
	async.waterfall([
		function(cb){
			console.log('processGameId',game_id);
			getMatchInfo(conn,game_id,function(err,rs){
				cb(err,rs);
			});
		},
		function(match_info,cb){
			getLineup(conn,match_info,function(err,lineup){
				cb(err,match_info,lineup);	
			});
		},
		function(match_info,lineup,cb){
			getSubstitutions(conn,match_info,function(err,subs){
				cb(err,match_info,lineup,subs);
			});
		},
		function(match_info,lineup,subs,cb){
			getGoals(conn,match_info,function(err,goals){
				cb(err,match_info,lineup,subs,goals);
			});
		},
		function(match_info,lineup,subs,goals,cb){
			getStats(conn,match_info,function(err,stats,allstats){
				cb(err,match_info,lineup,subs,goals,stats,allstats);
			});
		},
		function(match_info,lineup,subs,goals,stats,allstats,cb){
			generateResultData(match_info,lineup,subs,goals,stats,allstats,function(err,result){
				cb(null,result);
			});
		},
		function(result,cb){
			saveDataAndGenerateEvents(result,function(err,rs){
				cb(err,rs);
			});
		}
	],
	function(err,rs){
		//console.log(rs);
		done(err,rs);
	});
	
}
/*
* 1. we will check the existing data in redis,
* the data will be in these format :  lvrt_[game_id]  
* lvrt -> live realtime
*/
function saveDataAndGenerateEvents(resultData,done){
	var oldData = null;
	async.waterfall([
		function(cb){
			//check if the data is exists in redis already
			redisClient.get('lvrt_'+resultData.match_info.game_id,function(err,data){
				if(data!=null){
					oldData = JSON.parse(data);
				}
				cb(err);
			});
		},
		function(cb){
			//save the new data into redis
			redisClient.set('lvrt_'+resultData.match_info.game_id,
							JSON.stringify(resultData),
							function(err,rs){
								cb(err,rs);
							});
			//cb(null,1);
		},
		function(rs,cb){
			triggerEvents(resultData,oldData,function(err,new_resultData){
				cb(null,new_resultData);
			});
			
		}
	],
	function(err,new_resultData){
		done(err,new_resultData);
	});
}
function triggerEvents(newData,oldData,done){
	var has_new_events = false;
	var live_events = {	
		goal:0,
		shot:0,
		corner:0,
		throwin:0,
		FirstHalf:0,
		SecondHalf:0,
		Substitutions:0,
		YellowCard:0,
		RedCard:0,
		GKSave:0,
		FullTime:0,
		ExtraTimeFirstHalf:0,
		ExtraTimeSecondHalf:0,
		PenaltyShootout:0
	};
	if(oldData==null){
		//then the match has just begin
		if(newData.match_info.period=='FirstHalf'){
			live_events.FirstHalf = 1;
		}else if(newData.match_info.period=='SecondHalf'){
			live_events.SecondHalf = 1;
		}else if(newData.match_info.period=='ExtraFirstHalf'){
			live_events.ExtraTimeFirstHalf = 1;
		}else if(newData.match_info.period=='ExtraSecondHalf'){
			live_events.ExtraTimeSecondHalf = 1;
		}else if(newData.match_info.period=='ShootOut'){
			live_events.PenaltyShootout = 1;
		}else if(newData.match_info.period=='FullTime'){
			live_events.FullTime = 1;
		}else{}
		
	}
	
	live_events = checkGoalEvents(newData,oldData,live_events);
	live_events = checkSubsEvents(newData,oldData,live_events);
	live_events = checkShotEvents(newData,oldData,live_events);
	live_events = checkCornerEvents(newData,oldData,live_events);
	live_events = checkThrowsEvents(newData,oldData,live_events);
	live_events = checkYellowEvents(newData,oldData,live_events);
	live_events = checkRedEvents(newData,oldData,live_events);
	live_events = checkGKSaveEvents(newData,oldData,live_events);

	for(var n in live_events){
		if(live_events[n] != 0){
			has_new_events = true;
		}
	}
	//bungkus
	live_events.matchtime = newData.match_info.matchtime;
	newData.events = live_events;
	
	if(has_new_events){
		//send events
		/*var uri = url.format({
			protocol:'http',
			host:'fast-peak-8943.herokuapp.com',
			pathname:'fm_receiver',
			query:live_events
		});
		console.log(uri);*/
		var options = {
			url:'http://10.0.1.86:3000/fm_receiver',
			body:JSON.stringify(live_events),
			method:'POST'
		};
		console.log('SENDING to ',options);
		request(options,
				function(err,response,body){
					console.log('response : ',body);
					done(null,newData);
				});
	}else{
		console.log(live_events);
		done(null,newData);
	}
	
	
}

function checkGKSaveEvents(new_data,old_data,live_events){
	var has_event = false;
	if(old_data == null){
		if(new_data.home.stats.gk_save > 0 || new_data.away.stats.gk_save > 0){
			has_event = true;
		}
	}else{
		if(old_data.home.stats.gk_save != new_data.home.stats.gk_save){
			has_event = true;
		}
		if(old_data.away.stats.gk_save != new_data.away.stats.gk_save){
			has_event = true;
		}
	}
	if(has_event){
		
		var home_event = comparePlayerStats(new_data.home.team_id,'gk_save',new_data,old_data);
		var away_event = comparePlayerStats(new_data.away.team_id,'gk_save',new_data,old_data);
		live_events.GKSave = {
			home:home_event,
			away:away_event
		};
		
	}
	return live_events;
}
function checkRedEvents(new_data,old_data,live_events){
	var has_event = false;
	if(old_data == null){
		if(new_data.home.stats.red_card > 0 || new_data.away.stats.red_card > 0){
			has_event = true;
		}
	}else{
		if(old_data.home.stats.red_card != new_data.home.stats.red_card){
			has_event = true;
		}
		if(old_data.away.stats.red_card != new_data.away.stats.red_card){
			has_event = true;
		}
	}
	if(has_event){
		
		var home_event = comparePlayerStats(new_data.home.team_id,'yellow_card',new_data,old_data);
		var away_event = comparePlayerStats(new_data.away.team_id,'yellow_card',new_data,old_data);
		live_events.RedCard = {
			home:home_event,
			away:away_event
		};
		
	}
	return live_events;
}
function checkYellowEvents(new_data,old_data,live_events){
	var has_event = false;
	if(old_data == null){
		if(new_data.home.stats.yellow_card > 0 || new_data.away.stats.yellow_card > 0){
			has_event = true;
		}
	}else{
		if(old_data.home.stats.yellow_card != new_data.home.stats.yellow_card){
			has_event = true;
		}
		if(old_data.away.stats.yellow_card != new_data.away.stats.yellow_card){
			has_event = true;
		}
	}
	if(has_event){
		
		var home_event = comparePlayerStats(new_data.home.team_id,'yellow_card',new_data,old_data);
		var away_event = comparePlayerStats(new_data.away.team_id,'yellow_card',new_data,old_data);
		live_events.YellowCard = {
			home:home_event,
			away:away_event
		};
		
	}
	return live_events;
}
function checkThrowsEvents(new_data,old_data,live_events){
	var has_event = false;
	if(old_data == null){
		if(new_data.home.stats.throwin > 0 || new_data.away.stats.throwin > 0){
			has_event = true;
		}
	}else{
		if(old_data.home.stats.throwin != new_data.home.stats.throwin){
			has_event = true;
		}
		if(old_data.away.stats.throwin != new_data.away.stats.throwin){
			has_event = true;
		}
	}
	if(has_event){
		
		var home_event = comparePlayerStats(new_data.home.team_id,'total_throws',new_data,old_data);
		var away_event = comparePlayerStats(new_data.away.team_id,'total_throws',new_data,old_data);
		live_events.throwin = {
			home:home_event,
			away:away_event
		};
		
	}
	return live_events;
}
function checkShotEvents(new_data,old_data,live_events){
	var has_event = false;
	if(old_data == null){
		if(new_data.home.stats.shot > 0 || new_data.away.stats.shot > 0){
			has_event = true;
		}
	}else{
		if(old_data.home.stats.shot != new_data.home.stats.shot){
			has_event = true;
		}
		if(old_data.away.stats.shot != new_data.away.stats.shot){
			has_event = true;
		}
	}
	if(has_event){
		
		var home_event = comparePlayerStats(new_data.home.team_id,'total_scoring_att',new_data,old_data);
		var away_event = comparePlayerStats(new_data.away.team_id,'total_scoring_att',new_data,old_data);
		live_events.shot = {
			home:home_event,
			away:away_event
		};
		
	}
	return live_events;
}

function checkCornerEvents(new_data,old_data,live_events){
	var has_event = false;
	if(old_data == null){
		if(new_data.home.stats.corner > 0 || new_data.away.stats.corner > 0){
			has_event = true;
		}
	}else{
		if(old_data.home.stats.corner != new_data.home.stats.corner){
			has_event = true;
		}
		if(old_data.away.stats.corner != new_data.away.stats.corner){
			has_event = true;
		}
	}
	if(has_event){
		
		var home_event = comparePlayerStats(new_data.home.team_id,'corner',new_data,old_data);
		var away_event = comparePlayerStats(new_data.away.team_id,'corner',new_data,old_data);
		live_events.corner = {
			home:home_event,
			away:away_event
		};
		
	}
	return live_events;
}
function checkGoalEvents(new_data,old_data,live_events){
	if(old_data == null){

		if(new_data.home.goals.length > 0 || new_data.away.goals.length > 0){
			if(live_events.goal==0){
				live_events.goal = [];
			}
			for(var i in new_data.home.goals){
				live_events.goal.push(new_data.home.goals[i]);
			}
			for(var i in new_data.away.goals){
				live_events.goal.push(new_data.away.goals[i]);
			}
			
		}

	}else{
		if(old_data.home.goals.length < new_data.home.goals.length || 
			old_data.away.goals.length < new_data.away.goals.length
			){
			//then there's a new goal
			var new_goals = [];
			for(var i in new_data.home.goals){
				for(var j in old_data.goals){
					if(new_data.home.goals[i].id != old_data.home.goals[j].id){
						new_goals.push(new_data.home.goals[i]);
					}
				}
			}
			for(var i in new_data.away.goals){
				for(var j in old_data.goals){
					if(new_data.away.goals[i].id != old_data.away.goals[j].id){
						new_goals.push(new_data.away.goals[i]);
					}
				}
			}
			live_events.goal = new_goals;
		}
	}
	return live_events;
}
function comparePlayerStats(team_id,stats_name,new_data,old_data){
	//get new_data players
	if(stats_name == 'corner'){
		console.log('corner !');
		players1 = getPlayerCorners(team_id,new_data);
		players2 = getPlayerCorners(team_id,old_data);
	}else{
		players1 = getPlayerStats(team_id,stats_name,new_data);
		players2 = getPlayerStats(team_id,stats_name,old_data);	
	}
	
	
	console.log('players1',players1);
	console.log('players2',players2);
	var new_stats = [];
	for(var i in players1){
		var is_found = false;
		for(var j in players2){
			if(players1[i].player_id == players2[j].player_id){
				is_found = true;
				//compare the total
				if(players1[i].total > players2[j].total){
					new_stats.push(players1[i]);
				}
			}
		}
		if(!is_found){
			//kalo data lama gak ada,  kita push data player ybs langsung ke events
			if(players1[i].total > 0){
				new_stats.push(players1[i]);
			}
		}
		
	}
	return new_stats;
}
function getPlayerCorners(team_id,data){
	var stats1 = getPlayerStats(team_id,'total_cross',data);
	var stats2 = getPlayerStats(team_id,'total_cross_nocorner',data);
	var rs = [];
	for(var i in stats1){
		for(var j in stats2){
			if(stats2[j].player_id == stats1[i].player_id){
				stats1[i].stats_name = 'corner';
				stats1[i].total = stats1[i].total - stats2[j].total;
				if(stats1[i].total > 0){
					rs.push(stats1[i]);
				}
			}
		}
	}
	return rs;
}
function getPlayerStats(team_id,stats_name,data){
	var players = [];
	
	console.log('team_id',team_id,'stats_name',stats_name);
	if(data!=null){
		for(var i in data.overall){
			console.log(data[i]);
			console.log('check',data.overall[i].team_id,'==',team_id);
			if(data.overall[i].team_id == team_id){
				console.log('check',data.overall[i].stats_name,'==',stats_name);
				if(data.overall[i].stats_name == stats_name){
					players.push(data.overall[i]);
				}
			}
		}
	}
	return players;
}
function checkSubsEvents(new_data,old_data,live_events){
	if(old_data == null){

		if(new_data.home.subs.length > 0 || new_data.away.subs.length > 0){
			if(live_events.Substitutions==0){
				live_events.Substitutions = [];
			}
			for(var i in new_data.home.subs){
				live_events.Substitutions.push(new_data.home.subs[i]);
			}
			for(var i in new_data.away.goals){
				live_events.Substitutions.push(new_data.away.subs[i]);
			}
			
		}

	}else{
		if(old_data.home.subs.length < new_data.home.subs.length || 
			old_data.away.subs.length < new_data.away.subs.length
			){
			//then there's a new goal
			var new_subs = [];
			for(var i in new_data.home.subs){
				for(var j in old_data.subs){
					if(new_data.home.subs[i].id != old_data.home.subs[j].id){
						new_subs.push(new_data.home.subs[i]);
					}
				}
			}
			for(var i in new_data.away.subs){
				for(var j in old_data.subs){
					if(new_data.away.subs[i].id != old_data.away.subs[j].id){
						new_subs.push(new_data.away.subs[i]);
					}
				}
			}
			live_events.Substitutions = new_subs;
		}
	}
	return live_events;
}
function generateResultData(match_info,lineup,subs,goals,stats,allstats,done){
	console.log(match_info);
	var result = {
		match_info:match_info,
		events:{
			goal:0,
			corner:0,
			throwin:0,
			FirstHalf:0,
			SecondHalf:0,
			Substitutions:0,
			YellowCard:0,
			RedCard:0,
			GKSave:0,
			ThrowIn:0,
			FullTime:0,
			ExtraTimeFirstHalf:0,
			ExtraTimeSecondHalf:0,
			PenaltyShootout:0
		},
		home:{
			team_id:match_info.home_id,
			team_name:match_info.home_name,
			team_logo:"http://widgets-images.s3.amazonaws.com/football/team/badges_65/"+
					  match_info.home_id.replace("t","")+".png",
			stats:stats.home,
			lineup:lineup.home,
			subs:subs.home,
			goals:goals.home
		},
		away:{
			team_id:match_info.away_id,
			team_name:match_info.away_name,
			team_logo:"http://widgets-images.s3.amazonaws.com/football/team/badges_65/"+
					 match_info.away_id.replace("t","")+".png",
			stats:stats.away,
			lineup:lineup.away,
			subs:subs.away,
			goals:goals.away
		},
		overall: allstats
		
	};
	done(null,result);
}
function getStats(conn,match_info,done){
	async.waterfall([
			function(cb){
				getMatchStats(conn,match_info.game_id,function(err,all_stats){
					cb(err,all_stats);
				});
			},
			function(all_stats,cb){
				distributePoints(match_info,all_stats,function(err,rs){
					cb(err,rs,all_stats);
				});
			}
		],
		function(err,stats,all_stats){
			//final product
			//console.log(stats);
			done(err,stats,all_stats);
		});
	
}
function distributePoints(match_info,all_stats,done){
	var home_id = match_info.home_id;
	var away_id = match_info.away_id;
	var stats = {
		home:{
			goals:0,
			corner:0,
			throwin:0,
			yellow_card:0,
			red_card:0,
			shot:0,
			gk_save:0,
			cross:0,
			cross_nocorner:0
		},
		away:{
			goals:0,
			corner:0,
			throwin:0,
			yellow_card:0,
			red_card:0,
			shot:0,
			gk_save:0,
			cross:0,
			cross_nocorner:0
		}
	};
	for(var i in all_stats){
		if(all_stats[i].team_id == home_id){
			switch(all_stats[i].stats_name){
				case 'goals':
					stats.home.goals += all_stats[i].total;
				break;
				case 'total_scoring_att':
					stats.home.shot += all_stats[i].total;
				break;
				case 'total_cross':
					stats.home.cross += all_stats[i].total;
				break;
				case 'total_cross_nocorner':
					stats.home.cross_nocorner += all_stats[i].total;
				break;
				case 'saves':
					stats.home.gk_save += all_stats[i].total;
				break;
				case 'yellow_card':
					stats.home.yellow_card += all_stats[i].total;
				break;
				case 'red_card':
					stats.home.red_card += all_stats[i].total;
				break;
				case 'total_throws':
					stats.home.throwin += all_stats[i].total;
				break;
				default:
				//do nothing
				break;
			}
		}else{
			switch(all_stats[i].stats_name){
				case 'goals':
					stats.away.goals += all_stats[i].total;
				break;
				case 'total_scoring_att':
					stats.away.shot += all_stats[i].total;
				break;
				case 'total_cross':
					stats.away.cross += all_stats[i].total;
				break;
				case 'total_cross_nocorner':
					stats.away.cross_nocorner += all_stats[i].total;
				break;
				case 'saves':
					stats.away.gk_save += all_stats[i].total;
				break;
				case 'yellow_card':
					stats.away.yellow_card += all_stats[i].total;
				break;
				case 'red_card':
					stats.away.red_card += all_stats[i].total;
				break;
				case 'total_throws':
					stats.away.throwin += all_stats[i].total;
				break;
				default:
				//do nothing
				break;
			}
		}

	}
	//ok we need the last corner counts
	stats.home.corner = stats.home.cross - stats.home.cross_nocorner;
	stats.away.corner = stats.away.cross - stats.away.cross_nocorner;
	
	done(null,stats);
}
function getMatchStats(conn,game_id,done){

	conn.query(
		"SELECT player_id,b.team_id,b.first_name,b.last_name,b.known_name,stats_name,SUM(stats_value) AS total\
		FROM optadb.player_stats a\
		INNER JOIN optadb.master_player b\
		ON a.player_id = b.uid\
		WHERE \
		game_id = ?\
		AND stats_name \
		IN ('goals','total_cross','total_cross_nocorner',\
			'total_throws','total_scoring_att','saves','yellow_card','red_card') \
		GROUP BY player_id,stats_name LIMIT 10000",
		[game_id],
		function(err,rs){
			done(err,rs);
		});
}
function getGoals(conn,match_info,done){
	var home_id = match_info.home_id;
	var away_id = match_info.away_id;
	async.waterfall([
		function(cb){
			conn.query("SELECT a.*,\
			b.name AS g_name,\
			b.known_name AS g_known_name,\
			b.first_name AS g_first_name,\
			b.last_name AS g_last_name,\
			c.name AS assist_name,\
			c.known_name AS assist_known_name,\
			c.first_name AS assist_first_name,\
			c.last_name AS assist_last_name,\
			d.uid AS team_id,\
			d.name AS team_name\
			FROM optadb.goals a \
			INNER JOIN optadb.master_player b\
			ON a.player_id = b.uid\
			LEFT JOIN optadb.master_player c\
			ON a.assist_player_id = c.uid\
			INNER JOIN optadb.master_team d\
			ON a.team_id = d.uid\
			WHERE a.game_id= ? LIMIT 30",
				[match_info.game_id],
				function(err,rs){
					cb(err,rs);
				}
			);
		},
		function(rs,cb){
			var goals = {
				home:[],
				away:[]
			};
			for(var i in rs){
				if(rs[i].team_id == home_id){
					goals.home.push(rs[i]);
				}else{
					goals.away.push(rs[i]);
				}
			}
			cb(null,goals);
		}
	],
	function(err,rs){
		done(err,rs);
	});
	
}
function getSubstitutions(conn,match_info,done){
	var home_id = match_info.home_id;
	var away_id = match_info.away_id;
	
	async.waterfall([
		function(cb){
			conn.query("SELECT a.*,\
				b.first_name AS suboff_first_name,b.last_name AS suboff_last_name,b.known_name AS suboff_known_name,\
				c.first_name AS subon_first_name,c.last_name AS subon_last_name,c.known_name AS subon_known_name \
				FROM optadb.substitutions a\
				INNER JOIN optadb.master_player b\
				ON a.SubOff = b.uid \
				INNER JOIN optadb.master_player c\
				ON a.SubOn = c.uid \
				WHERE game_id=? LIMIT 10;",
				[match_info.game_id],
				function(err,rs){
					cb(err,rs);
				}
			);
		},
		function(changes,cb){
			var subs = {
				home:[],
				away:[]
			};
			for(var i in changes){
				if(changes[i].team_id == home_id){
					subs.home.push(changes[i]);
				}else{
					subs.away.push(changes[i]);
				}
			}
			cb(null,subs);
		}
	],

	function(err,rs){
		done(err,rs);
	});
	
}
function getLineup(conn,match_info,done){
	var lineup = {};
	async.waterfall([
			function(cb){
				getLineupByTeamId(conn,match_info.game_id,match_info.home_id,function(err,l){
					lineup.home = l;
					cb(err);
				});
			},
			function(cb){
				getLineupByTeamId(conn,match_info.game_id,match_info.away_id,function(err,l){
					lineup.away = l;
					cb(err);
				});	
			}
		],
		function(err,rs){
			//console.log(lineup);
			done(err,lineup);
		});
}
function getLineupByTeamId(conn,game_id,team_id,cb){
	conn.query("SELECT * FROM optadb.playerrefs\
				 WHERE game_id=? AND team_id=? ORDER BY position;",
				 [game_id,team_id],
				 function(err,rs){
				 	cb(err,rs);
				 });
}
function getMatchInfo(conn,game_id,cb){
	conn.query("\
				SELECT a.game_id,a.home_team as home_id,a.away_team as away_id,\
				a.home_score,a.away_score,a.period,a.matchtime,a.matchdate,\
				a.venue_name,b.name AS home_name,c.name AS away_name,a.referee\
				FROM optadb.matchinfo a\
				INNER JOIN optadb.master_team b\
				ON a.home_team = b.uid\
				INNER JOIN optadb.master_team c\
				ON a.away_team = c.uid\
				WHERE a.game_id = ? LIMIT 1;",
				[game_id],
				function(err,rs){
					if(err){console.log(err.message);}
					//console.log(S(this.sql).collapseWhitespace().s);
					cb(err,rs[0]);
				});
}
function getCurrentMatchday(conn,done){
	conn.query("SELECT matchday FROM \
				optadb.game_fixtures \
				WHERE is_processed = 0 \
				ORDER BY id ASC LIMIT 1;",
				[],function(err,rs){
					if(rs!=null&&rs.length==1){
						done(err,rs[0].matchday);					
					}else{
						done(new Error('no matchday found'),0);
					}
				});
}

function getGameIdsByMatchday(conn,matchday,done){
	conn.query("SELECT game_id,period FROM \
				optadb.game_fixtures \
				WHERE competition_id = ? AND session_id = ? AND matchday = ? \
				ORDER BY id ASC LIMIT 10;",
				[config.competition.id,config.competition.year,matchday],function(err,rs){
					//console.log(S(this.sql).collapseWhitespace().s);
					if(rs != null
						 && rs.length > 0){
						done(err,matchday,rs);					
					}else{
						done(new Error('no matchday found'),matchday,[]);
					}
				});
}

