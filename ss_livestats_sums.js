/*
* livestats.js
* Player Livestats updater

*/
var crypto = require('crypto');
var fs = require('fs');
var path = require('path');
var xmlparser = require('xml2json');
var async = require('async');
var config = require(path.resolve('./config')).config;
var mysql = require('mysql');
var dateFormat = require('dateformat');
var redis = require('redis');
var player_stats_category = require(path.resolve('./libs/game_config')).player_stats_category;
var S = require('string');
var argv = require('optimist').argv;

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
				//foreach game_ids, retrieve the playerstats
				//and populate it into ffgame_stats.master_player_progress
				//console.log(game_id);
				if(game_id.length > 0){
					populateIntoMasterPlayerProgress(conn,matchday,game_id,cb);
				}else{
					//if there's no playerstats,  we skip it
					cb(null,matchday,game_id,cb);
				}
				
			},
			function(matchday,game_id,cb){
				//foreach game_ids load the stats into redis cache.
				//if there's no playerstats, we skip it
				if(game_id.length>0){
					storeToRedis(conn,matchday,game_id,cb);
				}else{
					cb(null,null);
				}
				
			}
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
				WHERE matchday = ? \
				ORDER BY id ASC LIMIT 10;",
				[matchday],function(err,rs){
					if(rs != null
						 && rs.length > 0){
						done(err,matchday,rs);					
					}else{
						done(new Error('no matchday found'),matchday,[]);
					}
				});
}

function populateIntoMasterPlayerProgress(conn,matchday,game_id,done){
	async.eachSeries(game_id,function(item,next){
		//console.log(item.game_id);
		async.waterfall([
			function(cb){
				getModifiers(conn,function(err,rs){
					//console.log('mod','->',rs);
					cb(err,rs);
				});
			},
			function(modifiers,cb){
				//only populate data if the match isnt FullTime Yet
				redisClient.get('livestats_ft_'+item.game_id,function(err,fulltime){
					if(fulltime==null){
						console.log('livestats','not fulltime yet',item.game_id);
						/*populateData(conn,modifiers,item.game_id,function(err){
							cb(err);
						});
						*/
						cb(null);		
					}else{
						console.log('livestats','has reached fulltime, we not proceed',item.game_id);
						cb(null);
					}
				});
				//console.log(modifiers);
				
			},
			function(cb){
				//if period is FullTime, we flag the game. so we dont update the stats anymore.
				if(item.period=='FullTime'){
					console.log('livestats','full time reached, flag redis',item);
					redisClient.set('livestats_ft_'+item.game_id,1,function(err,rs){
						cb(err);
					});
				}else{
					console.log('livestats','not fulltime yet');
					cb(null);
				}
				//-->
			}
		],
		function(err){
			next();
		});
		
	},function(err){
		done(err,matchday,game_id);
	});
}

function populateData(conn,modifiers,game_id,done){
	var has_data = true;
	var start = 0;
	var players = {};
	async.whilst(
		function(){
			return has_data;
		},
		function(next){
			conn.query("SELECT a.*,b.name,b.position,b.team_id \
						FROM optadb.player_stats a\
						INNER JOIN optadb.master_player b\
						ON a.player_id = b.uid \
						WHERE game_id=? \
						ORDER BY a.id ASC \
						LIMIT ?,100;",
						[game_id,start],
						function(err,rs){
							console.log(S(this.sql).collapseWhitespace().s);
							if(rs!=null && rs.length > 0){
								
								insertPlayerStats(conn,game_id,modifiers,rs,
								function(err,result,atk,def,error){
									//console.log(rs);
									console.log(result,atk,def,error);
									start+=100;
									//console.log(result);
									for(var s in result){
										if(typeof players[s]==='undefined'){
											players[s] = {overall:0,atk:0,def:0,error:0};
										}

										players[s].overall += result[s];
										players[s].atk += atk[s];
										players[s].def += def[s];
										players[s].error += error[s];
									}
									next();
								});
							}else{
								has_data = false;
								next();
							}
							
						});
		},
		function(err){
			var items = [];
			//console.log(players);
			for(var i in players){
				items.push({
							player_id:i,
							points:players[i].overall,
							atk:players[i].atk,
							def:players[i].def,
							error:players[i].error
				});
			}
			async.each(items,function(item,next){
				conn.query("INSERT INTO \
							ffgame_stats.master_player_progress\
							(game_id,player_id,points,atk,def,error,ts,dt)\
							VALUES\
							(?,?,?,?,?,?,UNIX_TIMESTAMP(NOW()),NOW())\
							",
							[game_id,item.player_id,item.points,item.atk,item.def,item.error],
							function(err,rs){
								console.log(S(this.sql).collapseWhitespace().s);
								next();
							});
			},
			function(err){
				done(err);
			});
		}
	);
}
/**
* insert player stats that related to FM
*/
function insertPlayerStats(conn,game_id,modifiers,data,done){
	
	var stats = getStatsCategory();
	var overall = {}; //the overall statistics
	var def = {}; //total defending point
	var atk = {}; //total attack point
	var error = {}; //total mistake and errors
	async.each(data,function(player,next){
		for(var i in stats){
			if(stats[i]==player.stats_name){
				if(player.player_id=='p12297'){
					//console.log(player.name,'---',stats[i],'------',player.stats_name,'->',modifiers[player.stats_name][player.position.toLowerCase()]);	
				}
				
				if(typeof overall[player.player_id] === 'undefined'){
					overall[player.player_id] = 0;
					atk[player.player_id] = 0;
					def[player.player_id] = 0;
					error[player.player_id] = 0;
				}

				overall[player.player_id] += (player.stats_value * modifiers[player.stats_name][player.position.toLowerCase()]);
				if(isDefStats(player.stats_name)){
					def[player.player_id] += (player.stats_value * modifiers[player.stats_name][player.position.toLowerCase()]);	
				}
				if(isAtkStats(player.stats_name)){
					atk[player.player_id] += (player.stats_value * modifiers[player.stats_name][player.position.toLowerCase()]);	
				}
				if(isErrStats(player.stats_name)){
					error[player.player_id] += (player.stats_value * modifiers[player.stats_name][player.position.toLowerCase()]);	
				}
			}
		}
		next();
	},
	function(err){
		done(err,overall,atk,def,error);
	});
	
}
                                                                 
function isDefStats(statsName){
	for(var group in player_stats_category){
		if(group=='defending' || group == 'goalkeeper'){
			for(var i in player_stats_category[group]){
				if(statsName == player_stats_category[group][i]){
					return true;
				}
				
			}	
		}
		
	}
	
}
function isAtkStats(statsName){
	for(var group in player_stats_category){
		if(group=='games' || group == 'passing_and_attacking'){
			for(var i in player_stats_category[group]){
				if(statsName == player_stats_category[group][i]){
					return true;
				}
				
			}	
		}
		
	}
}
function isErrStats(statsName){
	for(var group in player_stats_category){
		if(group == 'mistakes_and_errors'){
			for(var i in player_stats_category[group]){
				if(statsName == player_stats_category[group][i]){
					return true;
				}
			}	
		}
		
	}
}

function getModifiers(conn,done){
	conn.query("SELECT name,\
				g AS goalkeeper,\
				d AS defender,\
				m AS midfielder,\
				f AS forward \
				FROM ffgame.game_matchstats_modifier \
				LIMIT 1000;",
				[],
				function(err,rs){
					//console.log(S(this.sql).collapseWhitespace().s);
					//console.log(rs);
					if(rs!=null && rs.length > 0){
						var mods = {};
						for(var i in rs){
							mods[rs[i].name] = rs[i];
						}
						done(err,mods);
					}else{
						done(err,null);
					}
				});
}

//return the statistic categories into our desired format.
function getStatsCategory(){
	var stats = [];
	for(var group in player_stats_category){
		for(var i in player_stats_category[group]){
			stats.push(player_stats_category[group][i]);
		}
	}
	return stats;
}

/*
* greedily store all the player points into the redis.
* we store each game_id's data into specific key : match_[game_id]
* before we store the data, make sure that the data is structured these way : 
* match_[game_id] = {
	[player_id]:[
				{ts:[timestamp],points:[n_point]},
				{ts:[timestamp],points:[n_point]},
				{ts:[timestamp],points:[n_point]},
				{ts:[timestamp],points:[n_point]},
			],

	[player_id]:[
				{ts:[timestamp],points:[n_point]},
				{ts:[timestamp],points:[n_point]},
				{ts:[timestamp],points:[n_point]},
				{ts:[timestamp],points:[n_point]},
			],

}
*/
function storeToRedis(conn,matchday,game_id,done){
	console.log(game_id);
	async.each(
	game_id,
	function(item,next){
		async.waterfall([
			function(cb){
				storeGameIdPlayerPointsToRedis(conn,item.game_id,function(err,rs){
					cb(err);
				});		
			},
			function(cb){
				storeMatchInfoToRedis(conn,matchday,function(err,rs){
					cb(err);
				});			
			}
		],
		function(err){
			next();
		});
		
	},
	function(err){
		done(err);
	});
}

function storeMatchInfoToRedis(conn,matchday,done){
	async.waterfall([
		function(cb){
			conn.query("SELECT a.game_id,a.home_score,a.away_score,a.period,a.matchtime,a.matchdate,\
						a.venue_name,b.name AS home_name,c.name AS away_name,a.referee\
						FROM optadb.matchinfo a\
						INNER JOIN optadb.master_team b\
						ON a.home_team = b.uid\
						INNER JOIN optadb.master_team c\
						ON a.away_team = c.uid\
						WHERE a.matchday=? LIMIT 10;",
						[matchday],
						function(err,rs){
							console.log(S(this.sql).collapseWhitespace().s);
							cb(err,rs);
						});
		},
		function(matches,cb){
			redisClient.set('matchinfo_'+matchday,JSON.stringify(matches),function(err,rs){
				if(!err){
					console.log('matchinfo successfully stored');
				}else{
					console.log(err.message);
				}
				cb(err);
			});
		},
		

	],
	function(err){
		done(err);
	});
}
function storeGameIdPlayerPointsToRedis(conn,game_id,done){
	console.log('storeGameIdPlayerPointsToRedis','store to cache ',game_id);

	var players = {};// we store all the stats here.

	//query everything, and piled the data up into players object
	async.waterfall([
		
		function(cb){
			//save the goal stats into redis cache
			conn.query("SELECT a.time,a.team_id,a.player_id,b.name \
						FROM optadb.goals a\
						INNER JOIN optadb.master_player b \
						ON a.player_id = b.uid\
						WHERE game_id = ? LIMIT 20;",
						[game_id],function(err,rs){
							cb(err,rs);
						});
		},
		function(goals,cb){
			console.log(goals);
			redisClient.set('goals_'+game_id,JSON.stringify(goals),function(err,rs){
				if(!err){
					console.log('goals stats successfully stored');
				}else{
					console.log('setup goals',err.message);
				}
				cb(err);
			});
		},
		function(cb){
			//save the playerrefs stats into redis cache
			conn.query("SELECT a.*,b.first_name,b.last_name,b.known_name \
						FROM optadb.playerrefs a \
						INNER JOIN optadb.master_player b\
						ON a.player_id = b.uid\
						WHERE a.game_id=? ORDER BY a.position LIMIT 100;",
						[game_id],
						function(err,rs){
							console.log(S(this.sql).collapseWhitespace().s);
							cb(err,rs);
						});
		},
		function(players,cb){
			redisClient.set('playerrefs_'+game_id,JSON.stringify(players),function(err,rs){
				if(!err){
					console.log('playerrefs successfully stored');
				}else{
					console.log(err.message);
				}
				cb(err);
			});
		},
		function(cb){
			//save the team stats into redis cache
			conn.query("SELECT * FROM optadb.team_stats WHERE game_id=? LIMIT 10000;",
						[game_id],
						function(err,rs){
							console.log(S(this.sql).collapseWhitespace().s);
							cb(err,rs);
						});
		},
		function(teamstats,cb){
			redisClient.set('teamstats_'+game_id,JSON.stringify(teamstats),function(err,rs){
				if(!err){
					console.log('teamstats successfully stored');
				}else{
					console.log(err.message);
				}
				cb(err);
			});
		},
		function(cb){
			//save the match info into redis cache
			conn.query("SELECT game_id,matchday,period,timezone,matchdate,\
						matchtime,home_team,home_score,away_team,away_score \
						FROM optadb.matchinfo WHERE game_id=? LIMIT 1;",
						[game_id],
						function(err,rs){
							console.log(S(this.sql).collapseWhitespace().s);
							cb(err,rs[0]);
						});
		},
		function(matchinfo,cb){
			redisClient.set('fixture_'+game_id,JSON.stringify(matchinfo),function(err,rs){
				if(!err){
					console.log('fixture progress successfully stored');
				}else{
					console.log(err.message);
				}
				cb(err);
			});
		},
		function(cb){
			//save the player accumulative stats
			conn.query("SELECT a.team_id,a.stats_name,SUM(a.stats_value) AS total\
						FROM optadb.player_stats a\
						INNER JOIN optadb.master_player b\
						ON a.player_id = b.uid \
						WHERE game_id=? \
						GROUP BY a.team_id,stats_name\
						LIMIT 1000;",
						[game_id],
						function(err,rs){
							console.log(S(this.sql).collapseWhitespace().s);
							cb(err,rs);
						});
		},
		function(acc_stats,cb){
			redisClient.set('accstats_'+game_id,JSON.stringify(acc_stats),function(err,rs){
				if(!err){
					console.log('accumulative stats successfully stored');
				}else{
					console.log(err.message);
				}
				cb(err);
			});
		}
	],
	function(err,rs){
		console.log('storeGameIdPlayerPointsToRedis','done nih !');
		done(err,rs);	
	});
}