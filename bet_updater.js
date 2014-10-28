/*
* bet_updater.js
* bot for updating betting stats

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
var cash = require(path.resolve('./libs/gamestats/game_cash'));


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
				console.log(game_id);
				if(game_id.length > 0){
					generateStats(conn,matchday,game_id,cb);
				}else{
					//if there's no playerstats,  we skip it
					cb(null);
				}
				
			},
			function(cb){
				console.log('YEY');
				cb(null);
			}
		],
		function(err,rs){
			
			conn.release();

			pool.end(function(err){
					console.log('done');
					redisClient.quit(function(err){
						console.log('redis session ended');
					});
				});
		});
});

function generateStats(conn,matchday,game_id,done){
	console.log('generateStats',matchday,game_id);

	async.eachSeries(game_id,function(gid,next){
		redisClient.get('bet_info_done_'+gid.game_id,function(e,cek){
			if(cek!=1){
				console.log('generateStats',gid.game_id,' ONGOING');
				processGameStats(conn,matchday,gid.game_id,function(err,rs){
					next();
				});
			}else{
				console.log('generateStats',gid.game_id,' FULLTIME');
				next();
			}
		});
		
	},function(err){
		done(err);
	});
}
function processGameStats(conn,matchday,game_id,done){
	console.log('calculate','processing',game_id,matchday);
	var stats = {
				SCORE_GUESS:{
					home:0,
					away:0
				},
				CORNERS_GUESS:{
					home:0,
					away:0
				},
				SHOT_ON_TARGET_GUESS:{
					home:0,
					away:0
				},
				CROSSING_GUESS:{
					home:0,
					away:0
				},
				INTERCEPTION_GUESS:{
					home:0,
					away:0
				},
				YELLOWCARD_GUESS:{
					home:0,
					away:0
				}
			};
	async.waterfall([
		function(cb){
			conn.query("SELECT * FROM ffgame.game_fixtures WHERE game_id=? LIMIT 1;",
						[game_id],function(err,rs){
							console.log(S(this.sql).collapseWhitespace().s);
							
							cb(err,rs[0]);
						});
		},
		function(matchinfo,cb){
			conn.query("SELECT home_score,away_score,period \
						FROM optadb.matchinfo \
						WHERE game_id=?\
						LIMIT 1;",
				[game_id],
				function(err,rs){
					console.log(S(this.sql).collapseWhitespace().s);
					var goals = {};
					console.log(rs);
					if(rs!=null && rs.length > 0){
						goals = {
							home:rs[0].home_score,
							away:rs[0].away_score,
							period:rs[0].period
						};
						
					}else{
						goals = {
							home:0,
							away:0,
							period:'PreMatch'
						};
					}
					cb(err,matchinfo,goals);
				});
		},
		function(matchinfo,goals,cb){
			if(goals.period=='FullTime'){
				redisClient.set('bet_info_done_'+game_id,1,function(err,rs){
					cb(err,matchinfo,goals);
				});
			}else{
				cb(null,matchinfo,goals);
			}
		},
		function(matchinfo,goals,cb){
			console.log('goals',goals);
			stats.SCORE_GUESS.home = goals.home;
			stats.SCORE_GUESS.away = goals.away;
			console.log(matchinfo);
			//get the other stats
			conn.query("SELECT team_id,stats_name,SUM(stats_value) AS total\
						FROM optadb.player_stats \
						WHERE game_id= ?\
						AND stats_name \
						IN \
						('won_corners',\
						'interception_won',\
						'ontarget_scoring_att',\
						'accurate_cross',\
						'yellow_card',\
						'second_yellow') \
						GROUP BY \
						team_id,\
						stats_name;",
				[game_id],
				function(err,rs){
					console.log(S(this.sql).collapseWhitespace().s);
					console.log(rs);
					if(rs!=null){
						for(var i=0; i < rs.length; i++){
							switch(rs[i].stats_name){
								case 'won_corners':
									if(rs[i].team_id==matchinfo.home_id){
										stats.CORNERS_GUESS.home += rs[i].total;
									}else{
										stats.CORNERS_GUESS.away += rs[i].total;
									}
								break;
								case 'interception_won':
									if(rs[i].team_id==matchinfo.home_id){
										stats.INTERCEPTION_GUESS.home += rs[i].total;
									}else{
										stats.INTERCEPTION_GUESS.away += rs[i].total;
									}
								break;
								case 'ontarget_scoring_att':
									if(rs[i].team_id==matchinfo.home_id){
										stats.SHOT_ON_TARGET_GUESS.home += rs[i].total;
									}else{
										stats.SHOT_ON_TARGET_GUESS.away += rs[i].total;
									}
								break;
								case 'accurate_cross':
									if(rs[i].team_id==matchinfo.home_id){
										stats.CROSSING_GUESS.home += rs[i].total;
									}else{
										stats.CROSSING_GUESS.away += rs[i].total;
									}
								break;
								case 'yellow_card':
									if(rs[i].team_id==matchinfo.home_id){
										stats.YELLOWCARD_GUESS.home += rs[i].total;
									}else{
										stats.YELLOWCARD_GUESS.away += rs[i].total;
									}
								break;
								case 'second_yellow':
									if(rs[i].team_id==matchinfo.home_id){
										stats.YELLOWCARD_GUESS.home += rs[i].total;
									}else{
										stats.YELLOWCARD_GUESS.away += rs[i].total;
									}
								break;
								default:
								break;
							}
						}
					}
					cb(err,stats,goals);
				});
		},
		function(stats,goals,cb){
			console.log('BLAH',stats);
			console.log(game_id,stats);
			conn.query("UPDATE ffgame.game_bet_winners SET score=0 WHERE game_id=?",
						[game_id],function(err,rs){
							console.log('calculate',S(this.sql).collapseWhitespace().s);
							cb(err,stats,goals);
						});
		},
		function(stats,goals,cb){
			if(goals.period=='FullTime'){
				console.log('calculate','period is fulltime, we count the score');
				calculateUserBetScore(conn,game_id,stats,function(err,stats){
					cb(err,stats);
				});
			}else{
				console.log('calculate','the game still running');
				cb(null,stats);
			}
		},
		function(stats,cb){
			//send earnings notification
			sendEarningNotification(conn,game_id,function(err,rs){
				cb(err,stats);
			});
		},
		function(stats,cb){
			console.log('calculate','calculate winner',stats);
			calculateWinner(conn,game_id,function(err,rs){
				console.log('calculate','winner',rs);
				if(rs!=null){
					stats.winners = rs;	
				}
				console.log('calculate',stats);
				if(err){
					console.log(err);
				}
				cb(err,stats);
			});
		},
		function(stats,cb){
			console.log('calculate','nih',stats);
			console.log('calculate','bet_info stats');
			console.log('bet_info_'+game_id,stats);
			redisClient.set('bet_info_'+game_id,JSON.stringify(stats),
			function(err,rs){
				cb(err,stats);
			});
		}

	],
	function(err,rs){
		console.log('done',game_id,rs);
		done(err,rs);
	});
}
function sendEarningNotification(conn,game_id,done){
	var msgid = 'tbkskr-'+game_id;
	conn.query("INSERT IGNORE INTO fantasy.notifications\
				(content,url,dt,game_team_id,msg_id)\
				SELECT CONCAT('Selamat ! loe sudah memenangkan ',score,' coins dari tebak skor !') AS content,\
				'#',NOW(),game_team_id,? \
				FROM ffgame.game_bet_winners WHERE game_id=?;",
				[msgid,game_id],
				function(err,rs){
					console.log('calculate',S(this.sql).collapseWhitespace().s);
					done(err,rs);
				});
}
function calculateWinner(conn,game_id,done){
	conn.query("SELECT game_team_id,score FROM ffgame.game_bet_winners\
				 WHERE game_id=? ORDER BY score DESC LIMIT 10;",
				 [game_id],function(err,rs){
				 	done(err,rs);
				 });
}
function calculateUserBetScore(conn,game_id,stats,cb){
	console.log('calculateUserBetScore',stats);
	var since_id = 0;
	var has_data = true;

	async.whilst(
	function(){
		return has_data;
	},
	function(next){
		conn.query("SELECT * FROM ffgame.game_bets WHERE id > ? AND game_id=? ORDER BY id LIMIT 1",
					[since_id,game_id],
					function(err,rs){
			console.log('calculate',S(this.sql).collapseWhitespace().s);

			if(rs==null || rs.length==0){
				has_data = false;
				console.log('calculate','no more data');
				next();
			}else{
				since_id = rs[0].id;
				assignScore(conn,game_id,stats,rs[0],function(err,update){
					next();
				});
			}
			
		});
	},
	function(err){
		cb(null,stats);
	});
	
}

function assignScore(conn,game_id,stats,bet,done){
	console.log('calculate',bet);
	var score = 0;
	switch(bet.bet_name){
		case 'SCORE_GUESS':
			var winner = '';
			if(stats.SCORE_GUESS.home > stats.SCORE_GUESS.away){
				winner = 'HOME';
			}else if(stats.SCORE_GUESS.home < stats.SCORE_GUESS.away){
				winner = 'AWAY';
			}else{
				winner = 'DRAW';
			}
			if(stats.SCORE_GUESS.home == bet.home){
				score += bet.coins;
			}
			if(stats.SCORE_GUESS.away == bet.away){
				score += bet.coins;	
			}
			if(bet.home > bet.away && winner == 'HOME'){
				score += bet.coins;
			}else if(bet.home < bet.away && winner == 'AWAY'){
				score += bet.coins;
			}else if(bet.home == bet.away && winner == 'DRAW'){
				score += bet.coins;
			}else{
				//do nothing
			}
			console.log('calculate',bet.fb_id,'score : ',score);
		break;
		case 'CORNERS_GUESS':
			if(stats.CORNERS_GUESS.home == bet.home){
				score += bet.coins;
			}
			if(stats.CORNERS_GUESS.away == bet.away){
				score += bet.coins;	
			}
		break;
		case 'SHOT_ON_TARGET_GUESS':
			if(stats.SHOT_ON_TARGET_GUESS.home == bet.home){
				score += bet.coins;
			}
			if(stats.SHOT_ON_TARGET_GUESS.away == bet.away){
				score += bet.coins;	
			}
		break;
		case 'CROSSING_GUESS':
			if(stats.CROSSING_GUESS.home == bet.home){
				score += bet.coins;
			}
			if(stats.CROSSING_GUESS.away == bet.away){
				score += bet.coins;	
			}
		break;
		case 'INTERCEPTION_GUESS':
			if(stats.INTERCEPTION_GUESS.home == bet.home){
				score += bet.coins;
			}
			if(stats.INTERCEPTION_GUESS.away == bet.away){
				score += bet.coins;	
			}
		break;
		case 'YELLOWCARD_GUESS':
			if(stats.YELLOWCARD_GUESS.home == bet.home){
				score += bet.coins;
			}
			if(stats.YELLOWCARD_GUESS.away == bet.away){
				score += bet.coins;	
			}
		break;
	}
	async.waterfall([
		function(cb){
			conn.query("INSERT INTO ffgame.game_bet_winners\
				(game_id,game_team_id,score)\
				VALUES\
				(?,?,?)\
				ON DUPLICATE KEY UPDATE\
				score = score + VALUES(score)",
				[
				 game_id,
				 bet.game_team_id,
				 score
				],
				function(err,rs){
					console.log('calculate',S(this.sql).collapseWhitespace().s);
					cb(err,score);
				});
		},
		//we only adding cash when the game period is FullTime
		function(score,cb){

			cash.adding_cash(conn,
							 bet.game_team_id,
							 'BET_'+bet.bet_name+'_'+game_id,score,
							 'TEBAK SKOR WINS',
							 function(err,rs){
								cb(err,rs);
								console.log('calculate',game_id,bet.game_team_id, score ,'adding_cash',rs);
							});
			
		},
		function(result,cb){
			cash.update_cash_summary(conn,
							 bet.game_team_id,
							 function(err,rs){
								cb(err,rs);
								console.log('calculate',game_id,bet.game_team_id, score ,'adding_cash',rs);
							});
		}
		
	],
	function(err,rs){
		done(err,score);
	});
	
	
}

function getCurrentMatchday(conn,done){
	conn.query("SELECT matchday FROM \
				ffgame.game_fixtures \
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
				ffgame.game_fixtures \
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
