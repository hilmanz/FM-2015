/**
api related to gameplay
*/

var crypto = require('crypto');
var fs = require('fs');
var path = require('path');
var xmlparser = require('xml2json');
var async = require('async');
var mysql = require('mysql');
var dateFormat = require('dateformat');
var redis = require('redis');
var S = require('string');
var formations = require(path.resolve('./libs/game_config')).formations;
var player_stats_category = require(path.resolve('./libs/game_config')).player_stats_category;
var pool = {};
var frontend_schema = "";
var PHPUnserialize = require('php-unserialize');
var redisClient = {};

var config = {};
var league = 'epl';

exports.setLeague = function(l){
	league = l;
}
var match = require(path.resolve('./libs/api/match'));


var officials = require(path.resolve('./libs/api/officials'));


var sponsorship = require(path.resolve('./libs/api/sponsorship'));

exports.setConfig = function(c){
	config = c;
	frontend_schema = config.database.frontend_schema;
	officials.setConfig(config);
	sponsorship.setConfig(config);
	match.setConfig(config);
}

function prepareDb(callback){
	pool.getConnection(function(err,conn){
		callback(conn);
	});
}

//
//get current lineup setup
function getLineup(redisClient,game_team_id,callback){
	prepareDb(function(conn){
		async.waterfall(
			[
				function(callback){
					//check if we have the cached data
					console.log('LINEUP','CHECKING LINEUP');
					console.log('LINEUP','REDIS KEY : ','game_team_lineup_'+league+'_'+game_team_id);
					redisClient.get('game_team_lineup_'+league+'_'+game_team_id,function(err,lineup){
						var rs = JSON.parse(lineup);
						console.log('LINEUP','-->',rs);
						callback(err,rs);
					});
					//
				},
				function(cachedData,callback){
					if(cachedData!=null){
						callback(null,cachedData);	
					}else{
						conn.query("SELECT a.matchday,a.player_id,a.position_no,\
						b.name,b.position,b.known_name \
						FROM "+config.database.database+".game_team_lineups a\
						INNER JOIN "+config.database.database+".master_player b\
						ON a.player_id = b.uid\
						INNER JOIN "+config.database.database+".game_team_players c\
						ON c.game_team_id = a.game_team_id AND\
						c.player_id = a.player_id\
						WHERE a.game_team_id=? \
						LIMIT 16;",
						[game_team_id],
						function(err,rs){
								redisClient.set(
									'game_team_lineup_'+league+'_'+game_team_id
									,JSON.stringify(rs)
									,function(err,lineup){
										console.log('LINEUP','store to cache',rs);
										callback(err,rs);
									});
						});
					}

					
				},
				function(result,callback){
					conn.query("SELECT formation FROM "+config.database.database+".game_team_formation\
								WHERE game_team_id = ? LIMIT 1",
					[game_team_id],
					function(err,rs){
							var formation = '4-4-2'; //default formation
							if(rs.length>0){
								formation = rs[0].formation;	
							}
							callback(err,{
									lineup:result,
									formation:formation
								});
					});
				}
			],
		function(err,result){
			conn.release();
			
			callback(err,result);
			
		});
	});
	
}
function setLineup(redisClient,game_team_id,setup,formation,done){
	prepareDb(function(conn){
		var players = [];
		for(var i in setup){
			players.push(setup[i].player_id);
		}
		async.waterfall(
			[
				function(callback){

					//first, make sure that the players are actually owned by the team
					conn.query("SELECT player_id,b.position \
								FROM "+config.database.database+".game_team_players a \
								INNER JOIN "+config.database.database+".master_player b\
								ON a.player_id = b.uid\
								WHERE a.game_team_id = ? AND a.player_id IN (?) LIMIT 16",
								[game_team_id,players],
								function(err,rs){
									//console.log(S(this.sql).collapseWhitespace().s);
									console.log('LINEUP','game_team_lineup_'+game_team_id,S(this.sql).collapseWhitespace().s);
									console.log(rs);
									callback(null,rs);
								});
					
				},
				function(players,callback){
					if(players.length==16){
						//make sure that the composition is correct
						//like position 1 must be placed by goalkeeper.
						//the rest is optional
						if(position_valid(players,setup,formation)){
							//player exists
							//then remove the existing lineup
							conn.query("DELETE FROM "+config.database.database+".game_team_lineups WHERE game_team_id = ? ",
								[game_team_id],function(err,rs){
									callback(err,rs);
								});
						}else{
							console.log('LINEUP','game_team_lineup_'+game_team_id,'invalid positions');
							callback(new Error('invalid player positions'),[]);
						}
					}else{
						console.log('LINEUP','game_team_lineup_'+game_team_id,'one or more player doesnt belong to the team');
						callback(new Error('one or more player doesnt belong to the team'),[]);
					}
				},
				function(rs,callback){
					//get the upcoming matchday
					conn.query("SELECT * FROM (SELECT matchday,MAX(is_processed) AS match_status \
								FROM "+config.database.database+".game_fixtures GROUP BY matchday) a \
								WHERE match_status = 0 ORDER BY matchday ASC LIMIT 1;",
								[],
								function(err,matchday){
								console.log('LINEUP','game_team_lineup_'+game_team_id,'matchday : ',matchday[0].matchday);
								callback(err,rs,matchday[0].matchday);
					});	
				},
				function(rs,upcoming_matchday,callback){
					//get the current running matchday
					conn.query("SELECT * FROM (SELECT matchday,MIN(is_processed) AS match_status \
								FROM "+config.database.database+".game_fixtures GROUP BY matchday) a \
								WHERE match_status = 0 ORDER BY matchday ASC LIMIT 1;",
								[],
								function(err,matchday){
								console.log('LINEUP','game_team_lineup_'+game_team_id,'matchday : ',matchday[0].matchday);
								callback(err,rs,upcoming_matchday,matchday[0].matchday);
					});	
				},
				function(rs,upcoming_matchday,ongoing_matchday,callback){
					//check the register date of the team
					conn.query("SELECT UNIX_TIMESTAMP(created_date) AS ts \
								FROM "+config.database.database+".game_teams \
								WHERE id = ? LIMIT 1",
								[game_team_id],
								function(err,team_info){
						var created_ts = team_info[0].ts * 1000;
						var current_ts = (new Date()).getTime();
						var is_new_user = false;
						if((current_ts - created_ts) < (24*60*60*1000)){
							is_new_user = true;
						}
						callback(err,rs,upcoming_matchday,ongoing_matchday,is_new_user);
					});
				},
				function(rs,upcoming_matchday,ongoing_matchday,is_new_user,callback){
					var the_matchday = upcoming_matchday;
					
					//if new user, make sure they can use the ongoing matchday.
					if(is_new_user){
						the_matchday = ongoing_matchday;
					}


					var sql = "INSERT INTO "+config.database.database+".game_team_lineups\
								(game_team_id,player_id,position_no,matchday)\
								VALUES\
								";
					var data = [];
					for(var i in setup){
						if(i>0){
							sql+=',';
						}
						sql+='(?,?,?,?)';
						data.push(game_team_id);
						data.push(setup[i].player_id);
						data.push(setup[i].no);
						data.push(the_matchday);
					}
					conn.query(sql,data,function(err,rs){
						console.log('LINEUP','game_team_lineup_'+game_team_id,' saving lineup for matchday : ',upcoming_matchday);
						console.log('LINEUP','game_team_lineup_'+game_team_id,S(this.sql).collapseWhitespace().s);
						callback(err,rs,the_matchday);
					});
				},
				function(result,upcoming_matchday,callback){
					//save formation
					conn.query("INSERT INTO "+config.database.database+".game_team_formation\
								(game_team_id,formation,last_update)\
								VALUES(?,?,NOW())\
								ON DUPLICATE KEY UPDATE\
								formation = VALUES(formation),\
								last_update = VALUES(last_update)",
								[game_team_id,formation],
								function(err,rs){
									console.log('LINEUP','game_team_lineup_'+game_team_id,S(this.sql).collapseWhitespace().s);
									callback(err,result,upcoming_matchday);
								});
				},
				function(result,upcoming_matchday,callback){
					//reset the cache
					redisClient.set(
									'game_team_lineup_'+league+'_'+game_team_id
									,JSON.stringify(null)
									,function(err,lineup){
										console.log('LINEUP','reset the cache',lineup);
										callback(err,result,upcoming_matchday);
									});
				}
			],
			function(err,result,upcoming_matchday){
				conn.release();
				done(err,result,upcoming_matchday);	
				
			}
		);
	});
	

}
//check if the player's formation is valid
//saat ini kita cuman memastikan bahwa nomor 1 itu harus kiper.
//nomor yg lain mau penyerang semua sih gak masalah (untuk sementara waktu)
function position_valid(players,setup,formation){
	console.log(players);
	console.log('setup',setup);
	console.log('formation',formation);
	if(formation=='Pilih Formasi'){
		formation = '4-4-2';
		console.log('user didnt specified a formation, we choose the default 4-4-2');
	}

	var my_formation = formations[formation];
	
	for(var i in setup){
		for(var j in players){
			if(players[j].player_id == setup[i].player_id){
				console.log('--',setup[i].no,' ',players[j].position,' vs ',my_formation[setup[i].no]);
				if(setup[i].no<=11){
					if(my_formation[setup[i].no]=='Forward/Midfielder'){
						//console.log('check : '+setup[i].no,' ',players[j].position,' vs ',my_formation[setup[i].no]);
						if(players[j].position != 'Forward' 
								&& players[j].position != 'Midfielder'){
							return false;
						}

					}else{
						//console.log('foo : '+setup[i].no,' ',players[j].position,' vs ',my_formation[setup[i].no]);
						if(players[j].position != my_formation[setup[i].no]){
							return false;
						}
					}
				}
				break;
			}
		}
	}
	return true;
}
//get user's players
function getPlayers(game_team_id,callback){
	
	redisClient.get('getPlayers_'+league+'_'+game_team_id,function(err,rs){
		if(JSON.parse(rs)==null){
			console.log('getPlayers',game_team_id,'get from db');
			prepareDb(function(conn){
				async.waterfall([
					function(callback){
						conn.query("SELECT b.* \
						FROM "+config.database.database+".game_team_players a\
						INNER JOIN "+config.database.database+".master_player b \
						ON a.player_id = b.uid\
						WHERE game_team_id = ? ORDER BY b.position ASC,b.last_name ASC \
						LIMIT 200;",
						[game_team_id],
						function(err,rs){
							callback(err,rs);
						});
					},
					function(players,callback){
						var results = [];
						async.eachSeries(players,function(player,done){

							async.waterfall([
								function(cb){
									conn.query("SELECT SUM(points) AS total_points\
												FROM "+config.database.statsdb+".game_team_player_weekly\
												WHERE game_team_id = ? AND player_id = ?;",
												[
												 game_team_id,
												 player.uid
												],
												function(err,rs){
													try{
														cb(err,rs[0].total_points);
													}catch(e){
														cb(err,0);
													}
												});
								},
								function(total_points,cb){
									
									if(typeof total_points !== 'number'){
										total_points = 0;
									}
									conn.query("SELECT performance \
												FROM "+config.database.statsdb+".game_match_player_points a\
												WHERE game_team_id=? AND player_id=?\
												AND points <> 0\
												AND EXISTS (SELECT 1 FROM "+config.database.database+".game_fixtures b\
												WHERE b.game_id = a.game_id AND (b.home_id = ? OR b.away_id=?)\
												LIMIT 1) ORDER BY id DESC LIMIT 1;",
												[
													game_team_id,
													player.uid,
													player.team_id,
													player.team_id
												],
												function(err,rs){
													try{
														cb(err,total_points,rs[0].performance);
													}catch(e){
														cb(err,total_points,0);
													}
												});
								},
								function(total_points,performance,cb){
									if(typeof performance !== 'number'){
										performance = 0;
									}
									player.points = parseFloat(total_points);
									player.last_performance = parseFloat(performance);
									
									results.push(player);
									cb(null,results);
								}

							],
							function(err,rs){
								done();
							});
						},function(err){
							callback(err,results);
						});
					}
				],
				function(err,result){
					console.log('getPlayers',result);
					
					conn.release();
					redisClient.set('getPlayers_'+league+'_'+game_team_id,
									JSON.stringify(result),
									function(err,redis_status){
						callback(err,result);	
					});
						
					
				});
			});
		}else{
			console.log('getPlayers',game_team_id,'get from redis');
			callback(err,JSON.parse(rs));
		}
	});
	
	
}

//get user's budget
function getBudget(game_team_id,callback){
	var sql = "SELECT SUM(initial_budget+total) AS budget \
			FROM (SELECT budget AS initial_budget,0 AS total FROM "+config.database.database+".game_team_purse WHERE game_team_id = ?\
			UNION ALL\
			SELECT 0,SUM(amount) AS total FROM "+config.database.database+".game_team_expenditures WHERE game_team_id = ?) a;";
	prepareDb(function(conn){
		conn.query(sql,
				[game_team_id,game_team_id],
				function(err,rs){
					console.log(S(this.sql).collapseWhitespace().s);
					conn.release();
					callback(err,rs);	
					
				});
	});
	
}

/**
* get player master detail
*/
function getPlayerDetail(player_id,callback){
	
	prepareDb(function(conn){

		conn.query("SELECT a.uid AS player_id,a.name,a.position,\
		a.first_name,a.last_name,a.known_name,a.birth_date,\
		a.weight,a.height,a.jersey_num,a.real_position,a.real_position_side,\
		a.country,team_id AS original_team_id,\
		b.name AS original_team_name,a.salary,a.transfer_value\
		FROM "+config.database.database+".master_player a\
		INNER JOIN "+config.database.database+".master_team b\
		ON a.team_id = b.uid\
		WHERE a.uid = ? LIMIT 1;",
				[player_id],
				function(err,rs){
					console.log('getPlayers',player_id,'getPlayerDetail',S(this.sql).collapseWhitespace().s);
					
					conn.release();
					if(rs!=null && rs.length==1){
						callback(err,rs[0]);	
					}else{
						callback(err,null);
					}
					
				});
	});
	
}
/**
* get team's player detail
*/
function getTeamPlayerDetail(game_team_id,player_id,callback){
	var sql = "SELECT a.uid AS player_id,a.name,a.position,\
			a.first_name,a.last_name,a.known_name,a.birth_date,\
			a.weight,a.height,a.jersey_num,a.real_position,a.real_position_side,\
			a.country,team_id AS original_team_id,\
			b.name AS original_team_name,a.salary,a.transfer_value\
			FROM "+config.database.database+".master_player a\
			LEFT JOIN "+config.database.database+".master_team b\
			ON a.team_id = b.uid\
			WHERE a.uid IN (\
				SELECT player_id FROM "+config.database.database+".game_team_players \
				WHERE game_team_id=? AND player_id=?\
			)\
			LIMIT 1;";
	prepareDb(function(conn){
		conn.query(sql,
				[game_team_id,player_id],
				function(err,rs){
					conn.release();
						try{
							if(rs.length==1){
								callback(err,rs[0]);	
							}else{
								callback(err,null);
							}
						}catch(e){
							callback(err,null);
						}
						
					
				});
	});
	
}
/**
* get player master stats
*/
function getPlayerStats(player_id,callback){
	console.log('getPlayerStats');
	var sql = "SELECT a.game_id,a.points,a.performance,b.matchday\
			FROM "+config.database.statsdb+".master_player_performance a\
			INNER JOIN "+config.database.database+".game_fixtures b\
			ON a.game_id = b.game_id \
			WHERE player_id = ? ORDER BY a.id ASC;";
	prepareDb(function(conn){
		conn.query(sql,
				[player_id],
				function(err,rs){
					conn.release();
					callback(err,rs);	
					
				});
	});
	
}
/**
*	get player's overall stats
*/
function getPlayerOverallStats(game_team_id,player_id,callback){
	console.log('getPlayerOverallStats',game_team_id,player_id);
	var sql = "";
	if(game_team_id!=0){
		sql = "SELECT stats_name,stats_category,SUM(stats_value) AS total,SUM(a.points) as points\
				FROM "+config.database.statsdb+".game_team_player_weekly a\
				WHERE a.player_id=? AND a.game_team_id=?\
				GROUP BY a.stats_name LIMIT 20000;";
	}else{
		sql = "SELECT stats_name,'' as stats_category,SUM(stats_value) AS total,0 as points\
				FROM "+config.database.statsdb+".master_player_stats a\
				INNER JOIN "+config.database.database+".game_fixtures b\
				ON a.game_id = b.game_id\
				WHERE a.player_id=?\
				GROUP BY stats_name;";
	}
	prepareDb(function(conn){
		conn.query(sql,
				[player_id,game_team_id,game_team_id],
				function(err,rs){
					conn.release();
					callback(err,rs);	
					
				});
	});
	
}
/*
* get player's team stats
*/
function getPlayerTeamStats(game_team_id,player_id,callback){
	redisClient.get('getPlayerTeamStats_'+league+'_'+game_team_id+'_'+player_id,function(err,rs){
		if(JSON.parse(rs)==null){
			console.log('getPlayerTeamStats','query from db');
			var sql = "SELECT a.game_id,SUM(b.points) AS points,a.performance,b.matchday\
						FROM "+config.database.statsdb+".game_match_player_points a\
						INNER JOIN\
						"+config.database.statsdb+".game_team_player_weekly b\
						ON a.game_id = b.game_id  \
						AND a.player_id = b.player_id\
						AND a.game_team_id = b.game_team_id\
						WHERE a.game_team_id = ?\
						AND a.player_id = ? \
						GROUP BY a.game_id\
						ORDER BY a.game_id LIMIT 300;";
						prepareDb(function(conn){
							conn.query(sql,
									[game_team_id,player_id],
									function(err,rs){
										console.log(S(this.sql).collapseWhitespace().s);
										conn.release();
										redisClient.set('getPlayerTeamStats_'+league+'_'+game_team_id+'_'+player_id,
														JSON.stringify(rs),
														function(err,result){
											callback(err,rs);	
										});
										
								});
						});
		}else{
			console.log('getPlayerTeamStats','we take from cache');
			callback(err,JSON.parse(rs));
		}
	});
	
	
}
function isEmptyObject(obj) {
  for(var prop in obj) {
    if (Object.prototype.hasOwnProperty.call(obj, prop)) {
      return false;
    }
  }
  return true;
}
/**get player daily stats relative to game_team
*/
function getPlayerDailyTeamStats(game_team_id,player_id,player_pos,done){
	redisClient.get('getPlayerDailyTeamStats_'+league+'_'+game_team_id+'_'+player_id,function(err,rs){
		
		rs = JSON.parse(rs);
		console.log('getPlayerDailyTeamStats',typeof rs);
		if(rs == null || (typeof rs === 'string') || isEmptyObject(rs)){
			console.log('getPlayerDailyTeamStats','query data from db');
			var pos = 'g';
			switch(player_pos){
				case 'Forward':
					pos = 'f';
				break;
				case 'Midfielder':
					pos = 'm';
				break;
				case 'Defender':
					pos = 'd';
				break;
				default:
					pos = 'g';
				break;
			}
			var sql = "";
			if(game_team_id!=0){
				sql = "SELECT a.game_id,stats_name,stats_category,SUM(stats_value) AS total,\
						SUM(points) as points\
						FROM "+config.database.statsdb+".game_team_player_weekly a\
						WHERE a.player_id=? AND a.game_team_id=?\
						GROUP BY a.game_id,a.stats_name\
						ORDER BY a.game_id ASC LIMIT 20000;";
			}else{
				sql = "SELECT a.game_id,stats_name,'' as stats_category,SUM(stats_value) AS total,\
						0 as points\
						FROM "+config.database.statsdb+".master_player_stats a \
						INNER JOIN "+config.database.database+".game_fixtures b\
						ON a.game_id = b.game_id\
						WHERE a.player_id=?\
						GROUP BY a.game_id,stats_name \
						ORDER BY game_id ASC LIMIT 20000;";
			}
			prepareDb(function(conn){
				async.waterfall([
					function(callback){
						conn.query(sql,
							[player_id,game_team_id,game_team_id],
							function(err,rs){
								//console.log(this.sql);			
								callback(err,rs);	
								
							});
					},
					function(result,callback){
						conn.query("SELECT * FROM "+config.database.database+".game_matchstats_modifier;",
						[],
						function(err,rs){
							callback(err,rs,result);	
							
						});
					},
					function(modifiers,result,callback){
						//mapping
						var daily = {};
						
						if(result.length>0){
							daily['stats'] = result;
							for(var i in result){
								if(typeof daily[result[i].game_id] === 'undefined'){
									daily[result[i].game_id] = {
										games:0,
										passing_and_attacking:0,
										defending:0,
										goalkeeper:0,
										mistakes_and_errors:0
									};
								}
								if(result[i].stats_category!=''){
									
									daily[result[i].game_id][result[i].stats_category] += result[i].points;

								}else{
									//distributed the counts for each categories
									for(var category in player_stats_category){
										for(var j in player_stats_category[category]){

											if(player_stats_category[category][j] == result[i].stats_name){
												
												daily[result[i].game_id][category] += (parseInt(result[i].total) 
																					  * getModifierValue(modifiers,
																					  					result[i].stats_name,
																					  					pos));
											}

										}
									}
								}
								
							}
						}
						console.log('player daily stats ',player_id,daily);
						callback(null,daily);
					}
				],
				function(err,result){
					conn.release();
					redisClient.set('getPlayerDailyTeamStats_'+league+'_'+game_team_id+'_'+player_id,
									JSON.stringify(result),
									function(err,rs){
										done(err,result);
									});
						
					
				});	
			});
		}else{
			console.log('getPlayerDailyTeamStats','query data from redis');
			console.log('getPlayerDailyTeamStats_'+game_team_id+'_'+player_id);
			done(err,rs);
		}

	});
	
	
}
/**
* get modifier value based on player's position
*/
function getModifierValue(modifiers,stats_name,position){
	
	for(var i in modifiers){
		if(modifiers[i].name==stats_name){
			return (parseInt(modifiers[i][position]));
		}
	}
	return 0;
}
/**
* get user's financial statement
*/
function getFinancialStatement(game_team_id,done){
	var async = require('async');
	prepareDb(function(conn){
		async.waterfall(
			[
				function(callback){
					//starting budget
					conn.query("SELECT budget FROM "+config.database.database+".game_team_purse WHERE game_team_id = ?;",
							[game_team_id],function(err,rs){
								if(!err){
									try{
										callback(err,rs[0].budget);
									}catch(e){
										callback(err,10000000);
									}
									
								}else{
									callback(err,10000000);
								}
							});
				},
				function(starting_budget,callback){
					//get the total matches by these team
					conn.query("SELECT COUNT(*) AS total_matches FROM (SELECT game_id\
								FROM "+config.database.database+".game_team_expenditures WHERE game_team_id = ?\
								GROUP BY game_id) a;",
						[game_team_id],
						function(err,result){
						if(typeof result !== 'undefined'){
							callback(err,starting_budget,result[0]);
						}else{
							callback(err,starting_budget,null);
						}
					});
				},
				function(starting_budget,matches,callback){
					//weekly balance
					conn.query("SELECT game_id,SUM(amount) AS total_income,match_day,\
								SUM(item_total) AS item_total\
								FROM "+config.database.database+".game_team_expenditures \
								WHERE game_team_id=?\
								GROUP BY game_id \
								ORDER BY match_day LIMIT 100;",
								[game_team_id],
								function(err,rs){
									if(!err){
										callback(err,starting_budget,matches,rs);
									}else{
										callback(err,null,null,null);
									}
								});
				},
				function(starting_budget,matches,rs,callback){
					var weekly_balance = [];
					var current_balance = starting_budget;
					if(rs.length>0){
						for(var i in rs){
							current_balance += parseInt(rs[i].total_income);
							weekly_balance.push({week:rs[i].match_day,
												balance:current_balance,
												total_items:rs[i].item_total});
						}
					}else{
						weekly_balance.push({week:1,balance:starting_budget});
					}
					callback(null,starting_budget,weekly_balance,matches,rs);
				},
				function(starting_budget,weekly_balance,matches,expenditures,callback){
					//get weekly ticket sold
					conn.query("SELECT game_id,SUM(amount) AS total_income,match_day,\
								SUM(item_total) AS item_total\
								FROM "+config.database.database+".game_team_expenditures \
								WHERE game_team_id=? AND item_name='tickets_sold'\
								GROUP BY game_id \
								ORDER BY match_day LIMIT 100;",
								[game_team_id],
								function(err,rs){
									if(!err){
										callback(err,starting_budget,weekly_balance,matches,expenditures,rs);
									}else{
										callback(err,null,null,null,null,null);
									}
								});
				},
				function(starting_budget,weekly_balance,matches,expenditures,tickets_sold,callback){
					if(matches!=null){
						conn.query("SELECT item_name,item_type,SUM(amount) AS total,\
									SUM(item_total) AS item_total\
									FROM "+config.database.database+".game_team_expenditures\
									WHERE game_team_id=?\
									GROUP BY item_name;",
							[game_team_id],
							function(err,result){
								callback(err,{starting_budget:starting_budget,
											  weekly_balances:weekly_balance,
											  total_matches:matches.total_matches,
											  report:result,
											  expenditures:expenditures,
											  tickets_sold:tickets_sold});
						});
					}else{	
						callback(null,null);
					}
				}
			],
			function(err,result){
				conn.release();
				done(err,result);
				
			}
		);
	});
	
}
function getWeeklyFinance(game_team_id,week,done){
	var async = require('async');
	prepareDb(function(conn){
		async.waterfall([
				function(callback){
						conn.query("SELECT game_team_id,item_name,item_type,item_total,amount,game_id,match_day \
							FROM "+config.database.database+".game_team_expenditures \
							WHERE game_team_id=? AND match_day = ?;",
							[game_team_id,week],
							function(err,rs){
								callback(err,rs);
						});
				}
			],
			function(err,result){
				conn.release();
				done(err,result);
				
			});
	});
}
function next_match(team_id,done){
	var async = require('async');
	prepareDb(function(conn){
		async.waterfall(
			[
				function(callback){
					conn.query("SELECT a.id,\
							a.game_id,a.home_id,b.name AS home_name,a.away_id,\
							c.name AS away_name,a.home_score,a.away_score,\
							a.matchday,a.period,a.session_id,a.attendance,match_date\
							FROM "+config.database.database+".game_fixtures a\
							INNER JOIN "+config.database.database+".master_team b\
							ON a.home_id = b.uid\
							INNER JOIN "+config.database.database+".master_team c\
							ON a.away_id = c.uid\
							WHERE (home_id = ? OR away_id=?) AND period <> 'FullTime'\
							ORDER BY a.matchday\
							LIMIT 1;\
							",[team_id,team_id],function(err,rs){
								
								if(err){
									console.log(err.message);
								}
								callback(err,rs);
							});
				},
				function(rs,callback){
					
					try{
						if(rs.length > 0){
							conn.query("SELECT match_date \
									FROM \
									"+config.database.database+".game_fixtures \
									WHERE matchday = ? \
									ORDER BY match_date DESC \
									LIMIT 1;",
									[(rs[0].matchday - 1)],
									function(err,last_match){
										console.log(S(this.sql).collapseWhitespace().s);
										if(last_match!=null && last_match.length > 0){
											rs[0].last_match = last_match[0].match_date;
										}else{
											rs[0].last_match = '';
										}
										
										callback(err,rs);
									});
						}else{
							rs = [];
							console.log('next_match','no match for these team, se we guessing the matchday');
							conn.query("SELECT matchday FROM "+config.database.database+".game_fixtures \
										WHERE period IN ('FullTime') ORDER BY matchday DESC LIMIT 1;",
										[],function(err,m){
											console.log(S(this.sql).collapseWhitespace().s);
											if(m.length==0){
												m = [{matchday:0}];
											}
											
											rs.push({next_match:null,matchday:m[0].matchday+1});	
											
											conn.query("SELECT match_date \
											FROM \
											"+config.database.database+".game_fixtures \
											WHERE matchday = ? \
											ORDER BY match_date DESC \
											LIMIT 1;",
											[m[0].matchday],
											function(err,last_match){
												console.log(S(this.sql).collapseWhitespace().s);
												if(last_match!=null && last_match.length > 0){
													rs[0].last_match = last_match[0].match_date;
												}else{
													rs[0].last_match = '';
												}
												callback(err,rs);
											});
							});
						}
						
					}catch(e){
						callback(null,rs);
					}
				},
				function(rs,callback){
					//get the manual formation closing time.
					try{
						conn.query("SELECT * \
									FROM \
									"+config.database.database+".master_matchdays \
									WHERE matchday = ? \
									LIMIT 1;",
									[(rs[0].matchday)],
									function(err,setup){
										rs[0].matchday_setup = setup[0];
										callback(err,rs);
									});
					}catch(e){
						callback(null,rs);
					}
				},
				function(rs,callback){
					//get the manual formation closing time for previous match.
					try{
						conn.query("SELECT * \
									FROM \
									"+config.database.database+".master_matchdays \
									WHERE matchday = ? \
									LIMIT 1;",
									[(rs[0].matchday - 1)],
									function(err,setup){
										rs[0].previous_setup = setup[0];
										callback(err,rs);
									});
					}catch(e){
						callback(null,rs);
					}
				},
				function(rs,callback){
					//get the manual formation closing time for previous match.
					try{
						conn.query("SELECT * \
									FROM \
									"+config.database.database+".master_matchdays \
									WHERE matchday = ? \
									LIMIT 1;",
									[(rs[0].matchday) + 1],
									function(err,setup){
										rs[0].future_setup = setup[0];
										callback(err,rs);
									});
					}catch(e){
						callback(null,rs);
					}
				}
			],
			function(err,result){
				conn.release();
				done(err,result);
			}
		);
	});
	
}
function getVenue(team_id,done){
	var async = require('async');
	prepareDb(function(conn){
		async.waterfall(
			[
				function(callback){
					conn.query("SELECT stadium_name AS name,stadium_capacity AS capacity \
									FROM "+config.database.database+".master_team WHERE uid=? LIMIT 1;",
									[team_id],function(err,rs){
								callback(err,rs[0]);
							});
				}
			],
			function(err,result){
				conn.release();
				done(err,result);
				
			}
		);
	});
	
}
function best_match(game_team_id,done){
	var async = require('async');
	prepareDb(function(conn){
		async.waterfall(
			[
				function(callback){
					conn.query("SELECT d.id AS user_team_id,a.id AS game_team_id,a.team_id\
								FROM "+config.database.database+".game_teams a\
								INNER JOIN "+config.database.database+".game_users b\
								ON a.user_id = b.id\
								INNER JOIN "+frontend_schema+".users c\
								ON c.fb_id = b.fb_id\
								INNER JOIN "+frontend_schema+".teams d\
								ON d.user_id = c.id\
								WHERE a.id = ? AND d.league = ? LIMIT 1;",
								[game_team_id,league],function(err,rs){
									console.log(S(this.sql).collapseWhitespace().s);
									callback(err,rs[0]);
								});
				},
				function(team_data,callback){
					try{
						conn.query("SELECT matchday,SUM(`Weekly_point`.`points`) AS TotalPoints\
								FROM "+frontend_schema+".weekly_points AS `Weekly_point` \
								INNER JOIN "+frontend_schema+".`teams` AS `Team` \
								ON (`Weekly_point`.`team_id` = `Team`.`id`) \
								WHERE `Weekly_point`.`team_id` = ? \
								GROUP BY `Weekly_point`.`matchday` \
								ORDER BY TotalPoints DESC LIMIT 1;",
								[team_data.user_team_id],function(err,rs){
								console.log(S(this.sql).collapseWhitespace().s);
								if(err){
									callback(new Error('no data'),{});
								}else{
									if(typeof rs[0] !== 'undefined'){
										console.log(rs[0]);
										callback(err,team_data,rs[0]);	
									}else{
										callback(new Error('no data'),team_data,{});
									}
								}
							});
					}catch(e){
						callback(new Error('no data'),team_data,{});
					}
					
				},
				function(team_data,best_match,callback){
					conn.query("SELECT a.game_id,a.home_score,a.away_score,a.home_id,a.away_id,b.name AS home_name,c.name AS away_name\
								FROM "+config.database.database+".game_fixtures a\
								INNER JOIN "+config.database.database+".master_team b\
								ON a.home_id = b.uid\
								INNER JOIN "+config.database.database+".master_team c\
								ON a.away_id = c.uid\
								WHERE (a.home_id = ? OR a.away_id = ?) AND a.matchday=?\
								AND period='FullTime'\
								LIMIT 1;",
								[team_data.team_id,
								 team_data.team_id,
								 best_match.matchday],
								function(err,rs){
									console.log(S(this.sql).collapseWhitespace().s);
									
									callback(err,{match:rs[0],
												  points:best_match.TotalPoints});
								});
					
				}
			],
			function(err,result){
				conn.release();
				done(err,result);
				
			}
		);
	});
}
/**
*	getting team's last week's earnings.
*/
function last_earning(game_team_id,done){
	var async = require('async');
	prepareDb(function(conn){
		async.waterfall(
			[
				function(callback){
					//get team_id
					conn.query("SELECT team_id FROM "+config.database.database+".game_teams WHERE id = ? LIMIT 1",
						[game_team_id],function(err,rs){
							try{
								callback(err,rs[0].team_id);
							}catch(e){
								callback(err,'');
							}
					});
				},
				function(team_id,callback){
					//get next match's game_id
					conn.query("SELECT \
								a.game_id\
								FROM "+config.database.database+".game_fixtures a\
								INNER JOIN "+config.database.database+".master_team b\
								ON a.home_id = b.uid\
								INNER JOIN "+config.database.database+".master_team c\
								ON a.away_id = c.uid\
								WHERE (home_id = ? OR away_id=?) AND period <> 'FullTime'\
								ORDER BY a.matchday\
								LIMIT 1;\
							",[team_id,team_id],function(err,rs){
								console.log(S(this.sql).collapseWhitespace().s);
								try{
									callback(err,rs[0].game_id);
								}catch(e){
									callback(err,'');
								}
								
							});
				},
				function(next_game_id,callback){
					conn.query("SELECT game_id FROM "+config.database.database+".game_team_expenditures \
								WHERE game_team_id=? AND game_id <> ? ORDER BY id DESC \
								LIMIT 1;\
							",[game_team_id,next_game_id],function(err,rs){
								console.log(S(this.sql).collapseWhitespace().s);
								if(err){
									callback(new Error('no data'),{});
								}else{
									if(typeof rs[0] !== 'undefined'){
										callback(err,rs[0].game_id);	
									}else{
										callback(new Error('no data'),{});
									}
								}
							});
				},
				function(game_id,callback){
					conn.query("SELECT SUM(amount) AS total_earnings \
								FROM "+config.database.database+".game_team_expenditures \
								WHERE game_team_id = ? \
								AND \
								game_id = ? AND item_name='tickets_sold'",
								[game_team_id,game_id],
								function(err,rs){

									if(!err){
										if(typeof rs[0] !== 'undefined'){
											callback(err,rs[0]);
										}else{
											callback(new Error('no data'),{});
										}
									}else{
										callback(err,{});
									}
								});
				}
			],
			function(err,result){
				conn.release();
				done(err,result);
				
			}
		);
	});
	
}
/**
*	getting team's last week's expenses.
*/
function last_expenses(game_team_id,done){
	var async = require('async');
	prepareDb(function(conn){
		async.waterfall(
			[
				function(callback){
					//get team_id
					conn.query("SELECT team_id FROM "+config.database.database+".game_teams WHERE id = ? LIMIT 1",
						[game_team_id],function(err,rs){
							try{
								callback(err,rs[0].team_id);
							}catch(e){
								callback(err,'');
							}
					});
				},
				function(team_id,callback){
					//get next match's game_id
					conn.query("SELECT \
								a.game_id\
								FROM "+config.database.database+".game_fixtures a\
								INNER JOIN "+config.database.database+".master_team b\
								ON a.home_id = b.uid\
								INNER JOIN "+config.database.database+".master_team c\
								ON a.away_id = c.uid\
								WHERE (home_id = ? OR away_id=?) AND period <> 'FullTime'\
								ORDER BY a.matchday\
								LIMIT 1;\
							",[team_id,team_id],function(err,rs){
								console.log(S(this.sql).collapseWhitespace().s);
								try{
									callback(err,rs[0].game_id);
								}catch(e){
									callback(err,'');
								}
								
							});
				},
				function(next_game_id,callback){
					conn.query("SELECT game_id FROM "+config.database.database+".game_team_expenditures \
								WHERE game_team_id=? AND game_id <> ? ORDER BY id DESC \
								LIMIT 1;\
							",[game_team_id,next_game_id],function(err,rs){
								
								if(err){
									callback(new Error('no data'),{});
								}else{
									if(typeof rs[0] !== 'undefined'){
										callback(err,rs[0].game_id);	
									}else{
										callback(new Error('no data'),{});
									}
								}
							});
				},
				function(game_id,callback){
					conn.query("SELECT SUM(amount) AS total_expenses \
								FROM "+config.database.database+".game_team_expenditures \
								WHERE game_team_id = ? \
								AND \
								game_id = ? AND item_name <> 'buy_player' AND amount <= 0;",
								[game_team_id,game_id],
								function(err,rs){

									if(!err){
										if(typeof rs[0] !== 'undefined'){
											callback(err,rs[0]);
										}else{
											callback(new Error('no data'),{});
										}
									}else{
										callback(err,{});
									}
								});
				}
			],
			function(err,result){
				conn.release();
				done(err,result);
				
			}
		);
	});
	
}
/**
*	getting team's best player
*/
function best_player(game_team_id,done){
	var async = require('async');
	prepareDb(function(conn){
		async.waterfall(
			[
				function(callback){
					conn.query("SELECT b.uid as player_id,SUM(a.points) AS total_points,b.first_name,\
								b.known_name,b.last_name,b.name,b.position \
								FROM "+config.database.statsdb+".game_match_player_points a\
								INNER JOIN "+config.database.database+".master_player b\
								ON a.player_id = b.uid\
								WHERE game_team_id=? GROUP BY player_id \
								ORDER BY total_points DESC LIMIT 1;	\
							",[game_team_id],function(err,rs){
								if(err){
									callback(new Error('no data'),{});
								}else{
									if(typeof rs[0] !== 'undefined'){
										callback(err,rs[0]);	
									}else{
										callback(new Error('no data'),{});
									}
								}
							});
				},
				
			],
			function(err,result){
				conn.release();
				done(err,result);
			
			}
		);
	});
	
}

//get transfer window id
function getTransferWindow(done){
	var async = require('async');
	prepareDb(function(conn){
		async.waterfall([
				function(callback){
					conn.query("SELECT * \
								FROM "+config.database.database+".master_transfer_window \
								WHERE MONTH(tw_open) = MONTH(NOW())\
								AND NOW() BETWEEN tw_open AND tw_close;",
								[],function(err,rs){
									try{
										callback(err,rs[0]);
									}catch(e){
										callback(err,{});
									}	
								});
				}
			],
			function(err,result){
				conn.release();
				done(err,result);
				
			});
	});
}

/*sale player
* step 1 - make sure that the player_id is owned by the team.
* step 2 - count the transfer value accordingly
* step 3 - remove the player from possession and from lineups
* step 4 - add a transfer money to game_team_expenditures
* 
* Change - 14/10/2013 
* player who already bought cannot be sold on the same transfer window.
*/
function sale(redisClient,window_id,game_team_id,player_id,done){
	var async = require('async');
	prepareDb(function(conn){
		async.waterfall(
			[
				function(callback){
					console.log('selling player #',player_id,'from team #',game_team_id);
					conn.query(
						"SELECT COUNT(id) AS total\
						FROM \
						"+config.database.database+".game_team_players \
						WHERE game_team_id=? AND player_id = ? LIMIT 1;",
						[game_team_id,player_id],
						function(err,rs){
							if(!err && rs[0]['total']==1){
								callback(err,true);	
							}else{
								callback(err,false);
							}
						});
				},
				function(is_valid,callback){
					console.log('player is owned by the club ? ',is_valid);
					if(is_valid){
						//check for transfer value
						conn.query(
						"SELECT name,transfer_value FROM "+config.database.database+".master_player WHERE uid = ? LIMIT 1;",
						[player_id],
						function(err,rs){
							if(!err){
								callback(err,rs[0]['name'],rs[0]['transfer_value']);
							}else{
								callback(new Error('player not in master data'),null);
							}
						});
						
					}else{
						callback(new Error('no player in the club'),null);
					}
				},
				function(name,transfer_value,callback){
					//check if these player can be transfered in current transfer window.
					async.waterfall([
							function(cb){
								conn.query("SELECT COUNT(*) AS total FROM "+config.database.database+".game_transfer_history \
											WHERE tw_id=? AND game_team_id = ? AND player_id = ?;",
											[window_id,game_team_id,player_id],
											function(err,rs){
												
												var can_transfer = true;
												try{
													if(rs[0].total>0){
														can_transfer = false;
														
													}
												}catch(e){}

												console.log('can transfer ?',can_transfer);
												cb(err,can_transfer);
											});
							}
						],
					function(err,r){
						callback(null,r,name,transfer_value);
					});
				},
				function(can_transfer,name,transfer_value,callback){
					if(can_transfer){
						//check for player's latest performances
						var performance_diff = 0;
						conn.query(
						"SELECT points,performance FROM "+config.database.statsdb+".game_match_player_points \
						 WHERE game_team_id=? AND player_id=? ORDER BY id DESC;",
						[game_team_id,player_id],
						function(err,rs){

							if(!err){
								//@TODO we need to calculate the player's performance value to affect
								//the latest transfer value
								if(rs.length>0){
									rs[0].performance = rs[0].performance || 0;
									if(rs[0].performance!=0){
										transfer_value = transfer_value + ((((rs[0].performance / 10) * 1)/100)*transfer_value);	
									} 
								}
								callback(err,name,transfer_value);
							}else{
								callback(new Error('player_got no performance'),null);
							}
						});
					}else{
						callback(new Error('INVALID_TRANSFER_WINDOW'),null);
					}
				},
				function(name,transfer_value,callback){
					console.log('the price',transfer_value);
					
					async.waterfall(
						[
							function(callback){
								//remove player from lineup
								conn.query(
										"DELETE FROM "+config.database.database+".game_team_lineups\
										 WHERE game_team_id=? AND player_id=?",
										 [game_team_id,player_id],function(err,rs){
										 	callback(err,rs);
										 });
										 
							},
							function(rs,callback){
								//remove player from team's rooster
								if(rs!=null){
									conn.query(
										"DELETE FROM "+config.database.database+".game_team_players \
										 WHERE game_team_id=? AND player_id=?;",
										 [game_team_id,player_id],
										 function(err,rs){
										 	callback(err,rs);
										 });
								}
								
							},
							function(rs,callback){
								if(rs!=null){
									//we need to know the next week game_id
									conn.query("SELECT team_id FROM "+config.database.database+".game_teams WHERE id=? LIMIT 1",
										 [game_team_id],
										 function(err,rs){
										 	
										 	callback(err,name,transfer_value,rs[0]['team_id']);
										 	
										 });
								}
							},
							function(name,transfer_value,team_id,callback){
								//we need to know next match's game_id and matchdate
								conn.query("SELECT a.id,\
								a.game_id,a.home_id,b.name AS home_name,a.away_id,\
								c.name AS away_name,a.home_score,a.away_score,\
								a.matchday,a.period,a.session_id,a.attendance,match_date\
								FROM "+config.database.database+".game_fixtures a\
								INNER JOIN "+config.database.database+".master_team b\
								ON a.home_id = b.uid\
								INNER JOIN "+config.database.database+".master_team c\
								ON a.away_id = c.uid\
								WHERE (home_id = ? OR away_id=?) AND period <> 'FullTime'\
								ORDER BY a.matchday\
								LIMIT 1;\
								",[team_id,team_id],function(err,rs){
									if(rs.length==0){
										rs = [];
										conn.query("SELECT matchday FROM "+config.database.database+".game_fixtures \
										WHERE period NOT IN ('FullTime') ORDER BY matchday ASC LIMIT 1;",
													[],function(err,m){
														rs.push({matchday:m[0].matchday,game_id:''});
														callback(err,name,transfer_value,rs[0]['game_id'],rs[0]['matchday']);
													});
									}else{
										callback(err,name,transfer_value,rs[0]['game_id'],rs[0]['matchday']);
									}
									
								});
							},
							function(name,transfer_value,game_id,matchday,callback){
								//ok now we have all the ingridients..  
								//lets insert into financial expenditure
								conn.query("INSERT INTO "+config.database.database+".game_team_expenditures\
											(game_team_id,item_name,item_type,amount,game_id,match_day)\
											VALUES\
											(?,?,?,?,?,?)\
											ON DUPLICATE KEY UPDATE\
											amount = amount + VALUES(amount);",
											[game_team_id,
											'player_sold',
											 1,
											 transfer_value,
											 game_id,
											 matchday
											],
											function(err,rs){
												//console.log(this.sql);
												callback(err,{name:name,transfer_value:transfer_value});
								});
							},
							function(transfer_result,callback){
								conn.query("INSERT IGNORE INTO "+config.database.database+".game_transfer_history\
											(tw_id,game_team_id,player_id,transfer_value,\
											transfer_date,transfer_type)\
											VALUES\
											(?,?,?,?,NOW(),2)",
											[window_id,
											 game_team_id,
											 player_id,
											 transfer_result.transfer_value
											 ],
											function(err,rs){
												callback(err,transfer_result);
											}
								);
							}
						],
						function(err,result){
							//we're done :D
							callback(err,result);
						}
					);
				},
				function(result,callback){
					//reset the cache
					async.waterfall([
						function(cb){
							redisClient.set(
								'game_team_lineup_'+league+'_'+game_team_id
								,JSON.stringify(null)
								,function(err,lineup){
									console.log('LINEUP',game_team_id,'sale- reset the cache',lineup);
									cb(err);
								});
						},
						function(cb){
							redisClient.set(
								'getPlayers_'+league+'_'+game_team_id
								,JSON.stringify(null)
								,function(err,lineup){
									console.log('LINEUP',game_team_id,'sale - reset getPlayers',lineup);
									cb(err);
								});
						},
						function(cb){
							//when we sale a player.. the cache must be destroyed
							
							redisClient.del(
								'getPlayerTeamStats_'+league+'_'+game_team_id+'_'+player_id
								,function(err,lineup){
									console.log('LINEUP',
												'sale - remove getPlayerTeamStats_'+game_team_id+'_'+player_id,
												lineup);
									cb(err);
								});
						},
						function(cb){
							//when we sale a player.. the cache must be destroyed
							
							redisClient.del(
								'getPlayerDailyTeamStats_'+league+'_'+game_team_id+'_'+player_id
								,function(err,lineup){
									console.log('LINEUP',
												'sale - remove getPlayerDailyTeamStats_'+game_team_id+'_'+player_id,
												lineup);
									cb(err);
								});
						}
					],
					function(err,rs){
						callback(err,result);

					});
					
				}
			],
			function(err,result){
				conn.release();
				done(err,result);
				
			}
		);
	});
	
}

/*buy player
* step 1 - make sure that the player is not in the club yet
* step 2 - count the transfer value accordingly
* step 3 - check the club's balance, if the money is sufficient, go to step 4
* step 4 - add the player to the club
* step 5 - deduct a transfer money from game_team_expenditures
* 
*/
function buy(redisClient,window_id,game_team_id,player_id,done){
	var async = require('async');
	prepareDb(function(conn){
		async.waterfall(
			[
				function(callback){
					console.log('buying player #',player_id,'from team #',game_team_id);
					conn.query(
						"SELECT COUNT(id) AS total\
						FROM \
						"+config.database.database+".game_team_players \
						WHERE game_team_id=? AND player_id = ? LIMIT 1;",
						[game_team_id,player_id],
						function(err,rs){
							if(!err && rs[0]['total']==0){
								callback(err,true);	
							}else{
								callback(err,false);
							}
						});
				},
				function(is_valid,callback){

					console.log('player is not owned by the club ? ',is_valid);
					if(is_valid){
						//check for transfer value
						conn.query(
						"SELECT name,transfer_value \
						 FROM "+config.database.database+".master_player WHERE uid = ? LIMIT 1;",
						[player_id],
						function(err,rs){
							//console.log(this.sql);
							if(!err){
								callback(err,rs[0]['name'],rs[0]['transfer_value']);
							}else{
								callback(new Error('player not in master data'),null);
							}
						});
						
					}else{
						callback(new Error('the player is already in the club'),null);
					}
				},
				function(name,transfer_value,callback){
					//check if these player can be transfered in current transfer window.
					async.waterfall([
							function(cb){
								conn.query("SELECT COUNT(*) AS total FROM "+config.database.database+".game_transfer_history \
											WHERE tw_id=? AND game_team_id = ? AND player_id = ?;",
											[window_id,game_team_id,player_id],
											function(err,rs){
												console.log(this.sql);
												var can_transfer = true;
												try{
													if(rs[0].total>0){
														can_transfer = false;
														
													}
												}catch(e){}
												
												console.log('can transfer ?',can_transfer);
												cb(err,can_transfer);
											});
							}
						],
					function(err,r){
						callback(null,r,name,transfer_value);
					});
				},
				function(can_transfer,name,transfer_value,callback){
					if(can_transfer){
						//check for player's latest performances
						var performance_diff = 0;
						conn.query(
						"SELECT points,performance FROM "+config.database.statsdb+".master_player_performance \
						 WHERE player_id=? ORDER BY id DESC;",
						[player_id],
						function(err,rs){
							if(!err){
								//console.log(this.sql);
								//@TODO we need to calculate the player's performance value to affect
								//the latest transfer value
								if(rs.length>0){
									rs[0].performance = rs[0].performance || 0;
									if(rs[0].performance!=0){
										transfer_value = transfer_value + ((((rs[0].performance / 10) * 1)/100)*transfer_value);
									}
								}
								callback(err,name,transfer_value);

							}else{
								callback(new Error('player got no performance'),null);
							}
						});
					}else{
						callback(new Error('INVALID_TRANSFER_WINDOW'),null);
					}
					
				},
				function(name,transfer_value,callback){
					console.log('the price',transfer_value);
					
					async.waterfall(
						[
							function(callback){
								//check for the budget
								conn.query("SELECT SUM(budget+balance) AS money FROM (\
												SELECT budget, 0 AS balance \
												FROM "+config.database.database+".game_team_purse \
												WHERE game_team_id=?\
													UNION\
												SELECT 0 AS budget,SUM(amount) AS balance \
												FROM "+config.database.database+".game_team_expenditures \
												WHERE game_team_id = ?) a;",
								[game_team_id,game_team_id],function(err,rs){
									if(rs!=null){
										if(rs[0]['money'] >= transfer_value){
											callback(null,true);	
										}else{
											callback(new Error('no money'),false);
										}
									}else{
										callback(new Error('no money'),false);
									}
								});
							},
							function(has_money,callback){
								//remove player from team's rooster
								if(has_money){
									conn.query(
										"INSERT INTO "+config.database.database+".game_team_players \
										 (game_team_id,player_id)\
										 VALUES(?,?);",
										 [game_team_id,player_id],
										 function(err,rs){
										 	callback(err,rs);
										 });
								}
								
							},
							function(rs,callback){
								if(rs!=null){
									//we need to know the next week game_id
									conn.query("SELECT team_id FROM "+config.database.database+".game_teams WHERE id=? LIMIT 1",
										 [game_team_id],
										 function(err,rs){
										 	//console.log(this.sql);
										 	callback(err,name,transfer_value,rs[0]['team_id']);
										 	
										 });
								}
							},
							function(name,transfer_value,team_id,callback){
								//we need to know next match's game_id and matchdate
								conn.query("SELECT a.id,\
								a.game_id,a.home_id,b.name AS home_name,a.away_id,\
								c.name AS away_name,a.home_score,a.away_score,\
								a.matchday,a.period,a.session_id,a.attendance,match_date\
								FROM "+config.database.database+".game_fixtures a\
								INNER JOIN "+config.database.database+".master_team b\
								ON a.home_id = b.uid\
								INNER JOIN "+config.database.database+".master_team c\
								ON a.away_id = c.uid\
								WHERE (home_id = ? OR away_id=?) AND period <> 'FullTime'\
								ORDER BY a.matchday\
								LIMIT 1;\
								",[team_id,team_id],function(err,rs){
									//console.log(this.sql);
									if(rs.length==0){
										rs = [];
										conn.query("SELECT matchday FROM "+config.database.database+".game_fixtures \
										WHERE period NOT IN ('FullTime') ORDER BY matchday ASC LIMIT 1;",
										[],function(err,m){
														rs.push({matchday:m[0].matchday,game_id:''});
														callback(err,name,transfer_value,rs[0]['game_id'],rs[0]['matchday']);
													});
									}else{
										callback(err,name,transfer_value,rs[0]['game_id'],rs[0]['matchday']);
									}
									
								});
							},
							function(name,transfer_value,game_id,matchday,callback){
								//ok now we have all the ingridients..  
								//lets insert into financial expenditure
								conn.query("INSERT INTO "+config.database.database+".game_team_expenditures\
											(game_team_id,item_name,item_type,amount,game_id,match_day)\
											VALUES\
											(?,?,?,?,?,?)\
											ON DUPLICATE KEY UPDATE\
											amount = amount + VALUES(amount);",
											[game_team_id,
											'buy_player',
											 1,
											 (transfer_value*-1),
											 game_id,
											 matchday
											],
											function(err,rs){
												console.log(this.sql);
												callback(err,{name:name,transfer_value:transfer_value});
								});
							},
							function(transfer_result,callback){
								conn.query("INSERT IGNORE INTO "+config.database.database+".game_transfer_history\
											(tw_id,game_team_id,player_id,transfer_value,\
											transfer_date,transfer_type)\
											VALUES\
											(?,?,?,?,NOW(),1)",
											[window_id,
											 game_team_id,
											 player_id,
											 transfer_result.transfer_value
											 ],
											function(err,rs){
												callback(err,transfer_result);
											}
								);
							}
						],
						function(err,result){
							//we're done :D
							callback(err,result);
						}
					);
				},
				function(result,callback){

					//reset the caches
					async.waterfall([
						function(cb){
							redisClient.set(
								'game_team_lineup_'+league+'_'+game_team_id
								,JSON.stringify(null)
								,function(err,lineup){
									console.log('LINEUP',game_team_id,'buy reset the cache',lineup);
									cb(err);
							});
						},
						function(cb){
							redisClient.set(
								'getPlayers_'+league+'_'+game_team_id
								,JSON.stringify(null)
								,function(err,lineup){
									console.log('LINEUP',game_team_id,'buy reset getPlayers',lineup);
									cb(err);
							});
						}
					],

					function(err,rs){
						callback(err,result);
					});
					
				}
			],
			function(err,result){
				if(err){
					console.log(err.message);
				}
				conn.release();
				done(err,result);
				
			}
		);
	});
	
}
/**
* get master leaderboard
*/
function leaderboard(done){
	var async = require('async');
	prepareDb(function(conn){
		async.waterfall(
			[
				function(callback){
					//get team list
					conn.query("SELECT uid AS team_id,name FROM "+config.database.database+".master_team;",
						[],function(err,rs){
						callback(err,rs);
					});
				},
				function(teams,callback){
					//foreach team, get its summaries
					var team_stats = [];
					async.eachSeries(teams,function(team,done){
						getTeamResultStats(conn,team.team_id,function(err,result){
							team_stats.push({team:team,stats:result});
							done();	
						});
					},function(err){
						callback(err,team_stats);
					});
				}
			],
			function(err,result){
				console.log(result);
				conn.release();
				done(err,result);
				
			}
		);
	});
	
}
/**
* get match status. return 1 if its finished, return 0 if its not finished yet.
*/
function matchstatus(matchday,done){
	var async = require('async');
	prepareDb(function(conn){
		async.waterfall(
			[
				function(callback){
					//get team list
					conn.query("SELECT game_id FROM "+config.database.database+".game_fixtures WHERE matchday=? LIMIT 10;",
						[matchday],function(err,rs){
						var matches = [];
						if(rs!=null && rs.length > 0){
							for(var i in rs){
								matches.push(rs[i].game_id);
							}
						}
						callback(err,matches);
					});
				},
				function(matches,callback){
					//check the finished match
					conn.query("SELECT COUNT(*) AS total FROM\
						(SELECT game_id FROM "+config.database.statsdb+".job_queue \
						WHERE game_id IN (?)\
						AND  \
						n_status = 2 GROUP BY game_id) a;",[matches],function(err,rs){
						console.log(S(this.sql).collapseWhitespace().s);
						callback(err,matches,rs[0].total);
					});
				},
				function(matches,total_done,callback){
					//check the in-queue match
					conn.query("SELECT COUNT(*) AS total FROM\
						(SELECT game_id FROM "+config.database.statsdb+".job_queue \
						WHERE game_id IN (?)\
						AND  \
						n_status IN (0,1) GROUP BY game_id) a;",[matches],function(err,rs){
						console.log(S(this.sql).collapseWhitespace().s);
						callback(err,matches,total_done,rs[0].total);
					});
				},
				function(matches,total_done,total_queue,callback){
					var rs = 0;
					/* ini cuman berlaku di EPL, di worldcup tidak berlaku */
					/*
					if(total_queue==0 && total_done==10){
						rs = 1;
					}
					*/
					if(total_queue==0 && total_done > 0){
						rs = 1;
					}
					callback(null,rs);
				}
			],
			function(err,result){
				console.log('rs -> '+result);
				conn.release();
				done(err,result);
				
			}
		);
	});
	
}
function getTeamResultStats(conn,team_id,callback){
	var stats = {
		games:0,
		wins:0,
		loses:0,
		draws:0,
		goals:0,
		conceded:0,
		top_score:{},
		top_assist:{}
	};
	async.waterfall([
			function(callback){
				//count how many wins, how many lost
				//how many goals, how many goals conceded.
				conn.query("SELECT home_id,away_id,home_score,away_score,period\
							FROM \
							"+config.database.database+".game_fixtures \
							WHERE (home_id = ? OR away_id= ?) \
							AND is_processed=1 AND period='FullTime' AND session_id=?;",
							[team_id,team_id,config.competition.year],
							function(err,rs){
								if(rs.length>0){
									callback(err,rs);
								}else{
									callback(new Error('no data yet'),null);
								}
							});
			},
			function(games,callback){
				if(games!=null){
					for(var i in games){
						if(games[i].home_id==team_id){
							if(games[i].home_score > games[i].away_score){
								stats.wins++;
							}else if(games[i].home_score < games[i].away_score){
								stats.loses++;
							}else{
								stats.draws++;
							}
							stats.goals += games[i].home_score;
							stats.conceded += games[i].away_score;
						}else{
							if(games[i].home_score < games[i].away_score){
								stats.wins++;
							}else if(games[i].home_score > games[i].away_score){
								stats.loses++;
							}else{
								stats.draws++;
							}
							stats.goals += games[i].away_score;
							stats.conceded += games[i].home_score;
						}
						stats.games++;
					}

					callback(null,stats);
				}else{
					callback(null,stats);
				}
			},
			function(stats,callback){
				//find for top goals
				conn.query("SELECT a.player_id,SUM(stats_value) AS goals,b.* \
							FROM "+config.database.statsdb+".master_match_result_stats a\
							INNER JOIN\
							"+config.database.database+".master_player b ON a.player_id = b.uid\
							WHERE a.team_id=? AND stats_name='goals'\
							GROUP BY a.player_id\
							ORDER BY goals DESC LIMIT 1",
							[team_id],
							function(err,rs){
								if(rs.length>0){
									stats.top_score = rs[0];
								}
								callback(err,stats);
							});
			},
			function(stats,callback){
				//find for top assist
				conn.query("SELECT a.player_id,SUM(stats_value) AS assist,b.* \
							FROM "+config.database.statsdb+".master_match_result_stats a\
							INNER JOIN\
							"+config.database.database+".master_player b ON a.player_id = b.uid\
							WHERE a.team_id=? AND stats_name='goal_assist'\
							GROUP BY a.player_id\
							ORDER BY assist DESC;",
							[team_id],
							function(err,rs){
								if(rs.length>0){
									stats.top_assist = rs[0];
								}
								callback(err,stats);
							});
			}
		],
		function(err,result){
			callback(err,result);
		}
	);
}
function getCash(fb_id,done){
	prepareDb(function(conn){
		async.waterfall([
			function(callback){
				conn.query("SELECT cash FROM "+config.database.frontend_schema+".game_team_cash \
							WHERE fb_id=? LIMIT 1;",
				[fb_id],
				function(err,rs){
					console.log("CASH",S(this.sql).collapseWhitespace().s);
					console.log('CASH',rs);
					try{
						if(rs.length==0){
							//insert transactions
							conn.query("INSERT IGNORE INTO "+config.database.frontend_schema+".game_transactions\
										(fb_id,transaction_dt,transaction_name,amount,details)\
										VALUES(?,NOW(),'START',0,'START')",[fb_id],function(err,rs){
											
											//update total cash
											conn.query("INSERT INTO "+config.database.frontend_schema+".game_team_cash\
														(fb_id,cash)\
														SELECT fb_id,SUM(amount) AS cash \
														FROM "+config.database.frontend_schema+".game_transactions\
														WHERE fb_id = ?\
														GROUP BY fb_id\
														ON DUPLICATE KEY UPDATE\
														cash = VALUES(cash);",[fb_id],function(err,rs){
															
															conn.query("SELECT cash FROM "+config.database.frontend_schema+".game_team_cash \
																WHERE fb_id=? LIMIT 1;",
																[fb_id],function(err,last_rs){
																	if(last_rs.length > 0){
																		callback(err,{status:1,cash:last_rs[0].cash});
																	}else{
																		callback(err,{status:1,cash:0});
																	}
																	
																});
														});
											
										});
						}else{
							callback(err,{status:1,cash:rs[0].cash});
						}
					}catch(e){
						callback(err,{status:1,cash:0});
					}
				});
			},
		],
		function(err,result){
			conn.release();
			done(err,result);	
			
		});
	});
}
function redeemCode(game_team_id,coupon_code,done){
	prepareDb(function(conn){
		async.waterfall([
			function(callback){
				conn.query("SELECT coin_amount,ss_dollar \
							FROM "+config.database.database+".coupons a\
							INNER JOIN "+config.database.database+".coupon_codes b\
							ON a.id = b.coupon_id\
							WHERE \
							b.coupon_code = ?\
							AND \
							b.game_team_id = ? \
							LIMIT 1;",
				[coupon_code,game_team_id],
				function(err,rs){
					console.log(S(this.sql).collapseWhitespace().s);
					try{
						callback(err,rs[0]);
					}catch(e){
						callback(err,null);
					}
				});
			},
			function(redeemed,callback){
				if(redeemed!=null){
					//we need to know the next week game_id
					conn.query("SELECT team_id FROM "+config.database.database+".game_teams WHERE id=? LIMIT 1",
						 [game_team_id],
						 function(err,rs){
						 	console.log(S(this.sql).collapseWhitespace().s);
						 	callback(err,redeemed,rs[0]['team_id']);
						 });
				}else{
					callback(err,redeemed,0);
				}
			},
			function(redeemed,team_id,callback){
				if(redeemed!=null){
					//we need to know next match's game_id and matchdate
					conn.query("SELECT a.id,\
					a.game_id,a.home_id,b.name AS home_name,a.away_id,\
					c.name AS away_name,a.home_score,a.away_score,\
					a.matchday,a.period,a.session_id,a.attendance,match_date\
					FROM "+config.database.database+".game_fixtures a\
					INNER JOIN "+config.database.database+".master_team b\
					ON a.home_id = b.uid\
					INNER JOIN "+config.database.database+".master_team c\
					ON a.away_id = c.uid\
					WHERE (home_id = ? OR away_id=?) AND period <> 'FullTime'\
					ORDER BY a.matchday\
					LIMIT 1;\
					",[team_id,team_id],function(err,rs){
						console.log(S(this.sql).collapseWhitespace().s);
						callback(err,redeemed,team_id,rs[0]['game_id'],rs[0]['matchday']);
					});
				}else{
					callback(err,redeemed,0,0,0);
				}
			},
			function(redeemed,team_id,game_id,matchday,callback){
				if(redeemed!=null){
					conn.query("INSERT INTO "+config.database.database+".game_team_expenditures\
							(game_team_id,item_name,item_type,amount,game_id,match_day)\
							VALUES\
							(?,?,?,?,?,?)\
							ON DUPLICATE KEY UPDATE\
							amount = VALUES(amount);",
							[game_team_id,
							'redeem_code_'+coupon_code,
							 1,
							 redeemed.ss_dollar,
							 game_id,
							 matchday
							],
							function(err,rs){
								console.log(S(this.sql).collapseWhitespace().s);
								callback(err,redeemed);
							});
				}else{
					callback(err,redeemed);
				}
			}
		],
		function(err,result){
			conn.release();
			done(err,result);
			
		});
	});
}

/*
* when applying a perk to player, we need to make sure that
* the perk bonus in the same category cannot be stacked.
* so we need to make sure only 1 perk category active at a time.
*/
function apply_perk(game_team_id,perk_id,done){
	prepareDb(function(conn){
		async.waterfall([
			function(cb){
				//first we get the perk detail
				conn.query("SELECT * FROM "+config.database.database+".master_perks WHERE id = ? LIMIT 1;",
							[perk_id],
							function(err,rs){
								console.log('GET_PERK',S(this.sql).collapseWhitespace().s);
								var perk = rs[0];
								perk.data = PHPUnserialize.unserialize(perk.data);
								cb(err,perk);
							});
			},
			function(perk,cb){
				/*
				//check if the team has apply the perk. if it has, make sure all of it are disabled.
				conn.query("SELECT * FROM "+config.database.database+".digital_perks \
							WHERE game_team_id=?\
							AND master_perk_id=?\
							AND available > 0\
							AND n_status=1 LIMIT 1",
							[game_team_id,perk_id],
							function(err,rs){
								console.log('GET_PERK',S(this.sql).collapseWhitespace().s);
								if(rs!=null && rs.length > 0){
									cb(err,perk,false);
								}else{
									cb(err,perk,true);
								}
							});
				*/
				cb(null,perk,true);
			},
			function(perk,canAddPerk,cb){
				conn.query("SELECT * FROM "+config.database.database+".digital_perks_group WHERE master_perk_id = ? LIMIT 1",
					[perk_id],function(err,perk_group){
						console.log('GET_PERK',S(this.sql).collapseWhitespace().s);
						var group_name = '';
						if(perk_group!=null){
							try{
								group_name = perk_group[0].category;
							}catch(e){
								group_name = '';
							}
						}
						cb(err,perk,canAddPerk,group_name);
				});
			},
			function(perk,canAddPerk,group_name,cb){
				//cari apakah perk dari category ini uda pernah dibeli apa belum.
				if(group_name!=''){
					conn.query("SELECT * FROM "+config.database.database+".digital_perks a\
								INNER JOIN "+config.database.database+".digital_perks_group b \
								ON a.master_perk_id = b.master_perk_id\
								WHERE category = ? \
								AND game_team_id=? AND a.available > 0\
								AND a.n_status = 1 LIMIT 1;",
					[group_name,game_team_id],
					function(err,rs){
						console.log('GET_PERK',game_team_id,S(this.sql).collapseWhitespace().s);
						if(rs!=null && rs.length==1){
							console.log('GET_PERK',game_team_id,"cannot add perk");
							canAddPerk =false;
						}
						cb(err,perk,canAddPerk);
					});
				}else{
					console.log('GET_PERK',game_team_id,'group name empty');
					cb(null,perk,canAddPerk);
				}
			},
			function(perk,canAddPerk,cb){
				if(canAddPerk){
					if(perk.data.duration==null){
						perk.data.duration = 0;
					}
					conn.query("INSERT INTO "+config.database.database+".digital_perks\
								(game_team_id,master_perk_id,redeem_dt,available,n_status)\
								VALUES\
								(?,?,NOW(),?,1);",
								[game_team_id,
									perk_id,
									perk.data.duration],
								function(err,rs){
									console.log('GET_PERK',game_team_id,S(this.sql).collapseWhitespace().s);
									if(!err){
										cb(null,{perk:perk,can_add:canAddPerk,success:true});
									}else{
										cb(null,{perk:perk,can_add:canAddPerk,success:false});
									}
								});
				}else{
					cb(null,{perk:perk,can_add:canAddPerk,success:false});
				}
			}
		],
		function(err,rs){
			conn.release();
			done(err,rs);	
			
			
		});
	});
}
/*
* similar to apply_perk, but it just checks if we can apply the perk or not.
* returns false if we cannot apply the perk, otherwise returns true.
*/
function check_perk(game_team_id,perk_id,done){
	prepareDb(function(conn){
		async.waterfall([
			function(cb){
				//first we get the perk detail
				conn.query("SELECT * FROM "+config.database.database+".master_perks WHERE id = ? LIMIT 1;",
							[perk_id],
							function(err,rs){
								console.log('GET_PERK',S(this.sql).collapseWhitespace().s);
								var perk = rs[0];
								perk.data = PHPUnserialize.unserialize(perk.data);
								cb(err,perk);
							});
			},
			function(perk,cb){
				//check if the team has apply the perk. if it has, make sure all of it are disabled.
				conn.query("SELECT * FROM "+config.database.database+".digital_perks \
							WHERE game_team_id=?\
							AND master_perk_id=?\
							AND available > 0\
							AND n_status=1 LIMIT 1",
							[game_team_id,perk_id],
							function(err,rs){
								console.log('GET_PERK',S(this.sql).collapseWhitespace().s);
								if(rs!=null && rs.length > 0){
									cb(err,perk,false);
								}else{
									cb(err,perk,true);
								}
							});
			},
			function(perk,canAddPerk,cb){
				conn.query("SELECT * FROM "+config.database.database+".digital_perks_group WHERE master_perk_id = ? LIMIT 1",
					[perk_id],function(err,perk_group){
						console.log('GET_PERK',S(this.sql).collapseWhitespace().s);
						var group_name = '';
						if(perk_group!=null){
							try{
								group_name = perk_group[0].category;
							}catch(e){
								group_name = '';
							}
							
						}
						cb(err,perk,canAddPerk,group_name);
				});
			},
			function(perk,canAddPerk,group_name,cb){
				//cari apakah perk dari category ini uda pernah dibeli apa belum.
				if(group_name!=''){
					conn.query("SELECT * FROM "+config.database.database+".digital_perks a\
								INNER JOIN "+config.database.database+".digital_perks_group b \
								ON a.master_perk_id = b.master_perk_id\
								WHERE category = ? \
								AND game_team_id=? AND a.available > 0\
								AND a.n_status = 1 LIMIT 1;",
					[group_name,game_team_id],
					function(err,rs){
						console.log('GET_PERK',S(this.sql).collapseWhitespace().s);
						if(rs!=null && rs.length==1){
							canAddPerk =false;
						}
						cb(err,perk,canAddPerk);
					});
				}else{
					cb(null,perk,canAddPerk);
				}
			},
			function(perk,canAddPerk,cb){
				if(canAddPerk){
					cb(null,true);
				}else{
					cb(null,false);
				}
			}
		],
		function(err,rs){
			conn.release();
			done(err,rs);	
			
			
		});
	});
}
//method to postponed the match
exports.setPostponedStatus = function(redisClient,game_id,toggle,callback){
	if(toggle==1){
		prepareDb(function(conn){
			async.waterfall([
				function(cb){
					console.log('updating fixtures');
					//update game fixtures, set to FullTime and is_perocessed = 1
					conn.query("UPDATE "+config.database.database+".game_fixtures \
								SET is_processed = 1, period='FullTime'\
								WHERE game_id=?",
								[game_id],
								function(err,rs){
									console.log(S(this.sql).collapseWhitespace().s);
									if(err){
										console.log('postponed','ERROR',err.message);
									}
									cb(err,rs);
								});
				},
				function(result,cb){
					console.log('insert dummy entry to job_queue');
					//then insert a dummy entry to make job_queue stops the job.
					conn.query("INSERT IGNORE INTO "+config.database.statsdb+".job_queue\
						        (game_id,since_id,until_id)\
						        VALUES(?,0,0)",[game_id],function(err,rs){
						console.log(S(this.sql).collapseWhitespace().s);
						cb(err);
					});
				}
			],
			function(err,rs){
				conn.release();
				redisClient.set('postponed-'+league+'_'+game_id,1,function(err,rs){
					console.log('postponed-'+game_id+'->'+rs);
					callback(err);
				});
				
			});
		});
		
	}else{
		prepareDb(function(conn){
			async.waterfall([
				function(cb){
					//update game fixtures, set to FullTime and is_perocessed = 1
					conn.query("UPDATE "+config.database.database+".game_fixtures \
								SET is_processed = 0, period='PreMatch'\
								WHERE game_id=?",[game_id],function(err,rs){
						if(err){
							console.log('postpone',err.message);
						}
						console.log(S(this.sql).collapseWhitespace().s);
						cb(err);
					});
				}
			],
			function(err,rs){
				conn.release();
				redisClient.del('postponed-'+league+'_'+game_id,
				function(err,rs){
					callback(err);
				});
				
			});
		});
	}
}
//-->

exports.bet_info = function(redisClient,game_id,callback){
	redisClient.get('bet_info_'+game_id,function(err,rs){
		console.log('bet_info',game_id,'result : ',rs);
		if(rs==null){
			rs = {
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
				},
				winners:[]
			};
		}else{
			rs = JSON.parse(rs);
		}
		callback(err,rs);
	});
}


function add_expenditure(game_team_id,transaction_name,amount,done){
	var async = require('async');
	prepareDb(function(conn){
		async.waterfall(
			[
				function(callback){
					
					//we need to know the next week game_id
					conn.query("SELECT team_id FROM "+config.database.database+".game_teams WHERE id=? LIMIT 1",
						 [game_team_id],
						 function(err,rs){
						 	console.log(S(this.sql).collapseWhitespace().s);
						 	callback(err,rs[0]['team_id']);
						 	
						 });
					
				},
				function(team_id,callback){
					//we need to know next match's game_id and matchdate
					conn.query("SELECT a.id,\
					a.game_id,a.home_id,b.name AS home_name,a.away_id,\
					c.name AS away_name,a.home_score,a.away_score,\
					a.matchday,a.period,a.session_id,a.attendance,match_date\
					FROM "+config.database.database+".game_fixtures a\
					INNER JOIN "+config.database.database+".master_team b\
					ON a.home_id = b.uid\
					INNER JOIN "+config.database.database+".master_team c\
					ON a.away_id = c.uid\
					WHERE (home_id = ? OR away_id=?) AND period <> 'FullTime'\
					ORDER BY a.matchday\
					LIMIT 1;\
					",[team_id,team_id],function(err,rs){
						console.log(S(this.sql).collapseWhitespace().s);
						callback(err,rs[0]['game_id'],rs[0]['matchday']);
					});
				},
				function(game_id,matchday,callback){
					//ok now we have all the ingridients..  
					//lets insert into financial expenditure
					var item_type = 1;
					if(amount < 0 ){
						item_type = 2;
					}
					conn.query("INSERT INTO "+config.database.database+".game_team_expenditures\
								(game_team_id,item_name,item_type,amount,game_id,match_day)\
								VALUES\
								(?,?,?,?,?,?)\
								ON DUPLICATE KEY UPDATE\
								amount = VALUES(amount);",
								[game_team_id,
								 transaction_name,
								 item_type,
								 amount,
								 game_id,
								 matchday
								],
								function(err,rs){
									console.log(S(this.sql).collapseWhitespace().s);
									//console.log(this.sql);
									callback(err,{name:transaction_name,amount:amount});
					});
				},
			],
			function(err,result){
				conn.release();
				done(err,result);
				
			}
		);
	});
	
}


exports.getCash = getCash;
exports.leaderboard = leaderboard;
exports.best_player = best_player;
exports.last_earning = last_earning;
exports.last_expenses = last_expenses;
exports.best_match = best_match;
exports.getVenue = getVenue;
exports.next_match = next_match;
exports.getFinancialStatement = getFinancialStatement;
exports.getPlayerDetail = getPlayerDetail;
exports.getPlayerTeamStats = getPlayerTeamStats;
exports.getPlayerStats = getPlayerStats;
exports.getLineup = getLineup;
exports.setLineup = setLineup;
exports.getPlayers = getPlayers;
exports.getBudget = getBudget;
exports.match = match;
exports.officials = officials;
exports.sponsorship = sponsorship;
exports.getPlayerOverallStats = getPlayerOverallStats;
exports.getTeamPlayerDetail = getTeamPlayerDetail;
exports.getPlayerDailyTeamStats = getPlayerDailyTeamStats;
exports.sale = sale;
exports.buy = buy;
exports.getWeeklyFinance = getWeeklyFinance;
exports.getTransferWindow = getTransferWindow;
exports.matchstatus = matchstatus;
exports.redeemCode = redeemCode;
exports.apply_perk = apply_perk;
exports.check_perk = check_perk;
exports.add_expenditure = add_expenditure;
exports.setPool = function(p){
	pool = p;
	match.setPool(pool);
	officials.setPool(pool);
	sponsorship.setPool(pool);
}
exports.setRedisClient = function(p){
	redisClient = p;
}