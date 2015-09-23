/**
* module for updating the match results across all users's lineups - used by updater_worker.js
*
* we only calculate unprocessed team.
* the processed team will have an entry in game_team_lineups_history.
* if there's no existing game_id on the history, we will do the calculation.
* 
* here's the rule
* 1. we only update the lineup history for team which playes in the game (game_id)
* 2. for those team which not played in the game, we only track for the player who played in the real-world.
*    and add the stats to the team.
*/

/**
* the module to read match_results file.
*/
var fs = require('fs');
var path = require('path');
var async = require('async');
var xmlparser = require('xml2json');
var config = require(path.resolve('./config')).config;
var game_config = require(path.resolve('./libs/game_config'));

var S = require('string');
var mysql = require('mysql');
var pool  = {};
var punishment = require(path.resolve('./libs/gamestats/punishment_rules'));
var perks = require(path.resolve('./libs/gamestats/perks'));
var player_stats_category = require(path.resolve('./libs/game_config')).player_stats_category;
var tactics = require(path.resolve('./libs/gamestats/tactics_bonus'));
var redisClient = null;


exports.setRedisClient = function(client){
	redisClient = client;
}
exports.setConfig = function(c){
	config = c;
	pool = mysql.createPool({
	   host     : config.database.host,
	   user     : config.database.username,
	   password : config.database.password,
	})
	perks.setConfig(c);
	punishment.setConfig(c);
	tactics.setConfig(c);

}
var league = 'epl';
exports.setLeague = function(l){
	league = l;
	punishment.setLeague(league);
}

console.log('lineup_stats - creating pool');
exports.update = function(queue_id,game_id,since_id,until_id,done){
	//the updates will run for each 100 entries.
	
	var limit = 100;
	var is_complete = false;
	
	async.waterfall(
		[
			function(callback){
				get_user_teams_by_idRange(since_id,
					until_id,
					limit,
					function(err,team){
						//if there's no more data. we stop :)
						console.log(team);
						if(team.length<limit){
							is_complete = true;
							console.log("NO MORE TEAM TO PROCESS");
						}else{
							//start+=limit;
						}
						callback(err,team);
					});	
			},
			function(team,callback){
				get_master_team_stats(game_id,
				function(err,player_stats){
					callback(err,game_id,team,player_stats);
				});
			},
			function(game_id,team,player_stats,callback){
				get_master_match_summary(game_id,
				function(err,team_summary){
					callback(err,game_id,team,player_stats,team_summary);
				});
			},
			function(game_id,team,player_stats,team_summary,callback){
				update_team_stats(queue_id,game_id,team,player_stats,team_summary,
				function(err){
					callback(err,'ok');
				});
			},
			function(result,callback){
				//update_team_points(function(err){
				//	console.log('update team points done');
				//	callback(err,'ok');
				//});
				callback(null,'ok');
			}
		],
		function(err,result){
			done(err,is_complete);
		}
	);	
		
	
	
}
exports.done = function(){
	pool.end(function(err){
		if(err) console.log('match_results','error',err.message);
		console.log('lineup_stats','pool closed');
	});
}

function get_master_team_stats(game_id,done){
	pool.getConnection(function(err,conn){
		console.log("get_master_team_stats","conn.connect");

		conn.query("SELECT * FROM "+config.database.statsdb+".master_match_player_points\
					WHERE game_id = ? LIMIT 50",
					[game_id],
		function(err,rs){
			if(err){console.log('lineupstats - ERROR - ',err.message);}
			console.log("get_master_team_stats","conn.release");
			conn.release();
			done(err,rs);	
			
		});

	});
}
function get_master_match_summary(game_id,done){
	pool.getConnection(function(err,conn){
		console.log("get_master_match_summary","conn.connect");
		conn.query("SELECT * FROM "+config.database.statsdb+".master_match_points\
					WHERE game_id = ? LIMIT 30",
					[game_id],
		function(err,rs){
			if(err){console.log(err.message);}
			console.log("get_master_match_summary","conn.release");
			conn.release();
			for(var i in rs){
				rs[i].avg_points = rs[i].overall_points / 11;
			}
			done(err,rs);	
			
		});

	});
}
function get_user_teams(start,limit,done){
	pool.getConnection(function(err,conn){
		if(err) console.log(err.message);
		console.log("get_user_teams","conn.connect");
		conn.query("SELECT * FROM "+config.database.database+".game_teams ORDER BY id ASC LIMIT ?,?",
				[start,limit],
		function(err,rs){
			if(err){console.log(err.message);}
			conn.release();
			console.log("get_user_teams","conn.release");
			done(err,rs);	
		
		});
	});
}
function get_user_teams_by_idRange(since_id,until_id,limit,done){
	pool.getConnection(function(err,conn){
		if(err) console.log(err.message);
		conn.query("SELECT * FROM "+config.database.database+".game_teams WHERE id BETWEEN ? AND ? ORDER BY id ASC LIMIT ?",
				[since_id,until_id,limit],
		function(err,rs){
			if(err){console.log(err.message);}
			conn.release();
			done(err,rs);	
		
		});
	});
}
//2014/06/19
//we need to check if the user's team is created newer than the game_id match.
//if so, we wont calculate the team's game_id stats.
function update_team_stats(queue_id,game_id,team,player_stats,team_summary,done){
	//console.log(team,player_stats,team_summary);
	console.log('team_summary',team_summary);
	var summary = {}; //team summary, this will be used for player's performance change modifier.
	for(var i in team_summary){
		summary[team_summary[i].team_id] = {points:team_summary[i].overall_points,
											average:team_summary[i].avg_points};

	}
	console.log('summary->',summary);
	console.log('team->',team);
	pool.getConnection(function(err,conn){
		async.eachSeries(team,
			function(item,callback){
				async.waterfall([
						function(next){
							//check the game fixture date
							conn.query("SELECT UNIX_TIMESTAMP(match_date) AS ts \
										FROM "+config.database.database+".game_fixtures WHERE game_id=? LIMIT 1;",
										[game_id],function(err,rs){
											console.log('ISSUE1','game_id#',game_id);
											next(err,rs[0].ts);
										});
						},
						function(game_ts,next){
							//make sure that the team is created before the match
							conn.query("SELECT UNIX_TIMESTAMP(created_date) AS ts \
										FROM "+config.database.database+".game_teams WHERE id = ? LIMIT 1;",
										[item.id],function(err,rs){
											//if the team is created at least 1 hour before the match. it's ok
											if(rs[0].ts < (game_ts - (60*60))){
												console.log('UPDATE',item.id,' #',game_id,'can be updated');
												next(err,true);
											}else{
												console.log('UPDATE',item.id,' #',game_id,'can\'t be updated');
												next(err,false);
											}
										});
						},
						function(can_get_stats,next){
							if(can_get_stats){
								update_individual_team_stats(conn,game_id,item,summary,player_stats,
								function(err,matched_players){
									next(err,matched_players);	
								});	
							}else{
								next(null,[]);
							}
							
						},
						function(matched_players,next){
							console.log('ISSUE1','check penalty if the club rooster is unbalance');
							punishment.check_violation(conn,game_id,item.id,item.team_id,
								function(err,rs){
									next(err,matched_players);
							});
						},
						function(matched_players,next){
							console.log('ISSUE1','Lets process the extra points if the week has ended....')
							//check the game's matchday
							conn.query("SELECT matchday \
										FROM "+config.database.database+".game_fixtures \
										WHERE game_id=? \
										LIMIT 1",[game_id],
										function(err,r){
											//console.log('-----',this.sql,'---');
											//console.log(r);
											var matchday = 0;
											if(!err){
												matchday = r[0].matchday;
											}
											next(err,matched_players,matchday);
										});
							
						},
						function(matched_players,matchday,next){

							async.waterfall([
									function(cb){
										console.log('ISSUE1','checking for #',team);
										conn.query("SELECT game_id FROM "+config.database.database+".game_fixtures \
												WHERE (home_id = ? OR away_id = ?) \
												AND matchday=? LIMIT 1",
												[item.team_id,item.team_id,matchday],
												function(err,match){
													//console.log(S(this.sql).collapseWhitespace().s);
													//console.log('ISSUE1','1# get the game_id : ',this.sql);
													var the_game_id = '';
													try{
														the_game_id = match[0]['game_id'];
														cb(err,the_game_id);
													}catch(e){
														conn.query("SELECT game_id FROM "+config.database.database+".game_fixtures\
														 WHERE matchday=? ORDER BY game_id ASC LIMIT 1",
														 [matchday],function(err,match){
														 	the_game_id = match[0]['game_id'];
														 	console.log('the game id : ',the_game_id,' actually not found');
														 	cb(err,the_game_id);
														 });
														//err = new Error('no game_id found');
													}

													
												});
									},
									function(t_game_id,cb){
										conn.query("SELECT a.player_id,a.position_no,b.position \
													FROM "+config.database.database+".game_team_lineups_history a\
													INNER JOIN "+config.database.database+".master_player b\
													ON a.player_id = b.uid\
													WHERE a.game_id = ?\
													AND a.game_team_id=? LIMIT 16;",
													[t_game_id,item.id],
													function(err,rs){
														console.log('ISSUE1',S(this.sql).collapseWhitespace().s);
														cb(err,rs);
													});
									},
									function(lineup_players,cb){
										console.log('ISSUE1',lineup_players);
										console.log('ISSUE1','matched_players',matched_players);
										async.eachSeries(lineup_players,function(lp,nx){
											var is_sub = false;
											if(lp.position_no>11){
												is_sub = true;
											}
											var found_matched = false;
											for(var i in matched_players){
												console.log('ISSUE1','matched_players',lp.player_id,'==',matched_players[i]);
												if(lp.player_id==matched_players[i]){
													found_matched = true;
													break;
												}
											}
											if(!found_matched){
												//we skip it.
												nx();
											}else{
												console.log('ISSUE1','UPDATE #',item.id,'-',lp.player_id)
												getPlayerDailyTeamStats(conn,
														item.id,
														lp.player_id,
														lp.position,
														matchday,
														lp.position_no,
														function(err,rs){
															nx();
														});
											}
											
										},function(err){
											cb(err,matchday);
										});
										
									}
								],
								function(err,rs){
									next(err,matchday);
							});
						},
						function(matchday,next){
							console.log('ISSUE 1','check if the week has lasted');
							//check if the week has lasted.
							async.waterfall([
								function(cb){
									conn.query("SELECT COUNT(*) AS total \
										FROM "+config.database.database+".game_fixtures \
										WHERE matchday = ?",[matchday],
										function(err,r){
											cb(err,r[0].total);
										});
								},
								function(total_fixtures,cb){
									conn.query("SELECT COUNT(*) AS total \
										FROM "+config.database.database+".game_fixtures \
										WHERE period = 'FullTime' \
										AND matchday = ? \
										AND is_processed=1",[matchday],
										function(err,r){
											//console.log('-----',this.sql,'---');
											var is_finished = false;
											if(!err){
												if(r[0].total==total_fixtures){
													is_finished = true;
												}
											}
											cb(err,is_finished);
										});
								}
							],
								function(err,is_finished){
								next(err,matchday,is_finished);
							});
							
						},

						function(matchday,is_finished,next){
							console.log('ISSUE 1','matchday : ',matchday,'is finished : ',is_finished);
							console.log('ISSUE 1','is all player started ?');
							var is_all_player_started = false;

							if(is_finished){
								async.waterfall([
										function(cb){
											conn.query("SELECT game_id \
														FROM "+config.database.database+".game_fixtures \
														WHERE (home_id =? OR away_id=?) \
														AND matchday = ?;",
														[item.team_id,item.team_id,matchday],
														function(err,r){	
															//console.log('--> we need the exact game_id',
															//			this.sql);
															var the_game_id = '';
															if(!err){
																try{
																	the_game_id = r[0].game_id;
																	cb(err,the_game_id);
																}catch(e){
																	conn.query("SELECT game_id FROM "+config.database.database+".game_fixtures\
																	 WHERE matchday=? ORDER BY game_id ASC LIMIT 1",
																	 [matchday],function(err,match){
																	 	the_game_id = match[0]['game_id'];
																	 	console.log('the game id : ',the_game_id,' actually not found');
																	 	cb(err,the_game_id);
																	 });
																}
															}else{
																cb(err,the_game_id);
															}
															
														});
										}
									],
									function(err,the_game_id){
										//check if all lineup is played in real game.
										conn.query("SELECT * FROM \
											"+config.database.database+".game_team_lineups_history a\
											INNER JOIN\
											"+config.database.statsdb+".master_player_stats b\
											ON a.player_id = b.player_id\
											INNER JOIN "+config.database.database+".game_fixtures c\
											ON b.game_id = c.game_id\
											WHERE a.game_team_id=? AND a.game_id = ?\
											AND b.stats_name = 'game_started'\
											AND c.matchday = ?\
											AND a.position_no < 12;",[
												item.id,the_game_id,matchday
											],
											function(err,r){
												console.log('ISSUE 5',S(this.sql).collapseWhitespace().s);
												if(!err){
													if(r.length==11){
														console.log('ISSUE 5 #',item.id,'all player started');
														is_all_player_started = true;
													}
												}
												next(err,the_game_id,matchday,is_finished,is_all_player_started);

										});
									}
								);
								
							}else{
								next(err,'',matchday,is_finished,is_all_player_started);
							}
						},

						function(the_game_id,matchday,is_finished,is_all_player_started,next){
							console.log('ISSUE 3','is budget below zero ?');
							var is_team_budget_below_zero = false;
							if(is_finished){
								console.log('ISSUE 3','checking budget since the week is finished');
								conn.query("SELECT SUM(budget+expenses) AS balance\
											FROM (\
												SELECT budget,0 AS expenses \
												FROM "+config.database.database+".game_team_purse \
												WHERE game_team_id=?\
											UNION ALL\
												SELECT 0,SUM(amount) AS total \
												FROM "+config.database.database+".game_team_expenditures \
												WHERE match_day <= ? AND game_team_id=?\
											) a;",
									[item.id,matchday,item.id],
									function(err,r){
									console.log('-----',this.sql,'---');
									var balance = 0;
									if(!err){
										balance = r[0].balance;
										if(r[0].balance < 0){
											is_team_budget_below_zero = true;
										}
									}
									next(err,
										the_game_id,
										matchday,
										is_finished,is_all_player_started,
										is_team_budget_below_zero,
										balance);
								});
							}else{
								next(err,
										the_game_id,
										matchday,
										is_finished,is_all_player_started,
										is_team_budget_below_zero,
										0);
							}
						},
						function(the_game_id,matchday,is_finished,is_all_player_started,
								is_team_budget_below_zero,balance,next){
							console.log('execute punishment and some perks');
							// NOTES : only perks that effect the overall points that 
							// can be executed here..


							if(is_finished){
								async.waterfall([
									function(cb){
										if(is_all_player_started){
											conn.query("INSERT INTO \
														"+config.database.statsdb+".game_team_extra_points\
														(game_id,matchday,game_team_id,\
															modifier_name,extra_points)\
														VALUES\
														(?,?,?,?,?)\
														ON DUPLICATE KEY UPDATE\
														extra_points = VALUES(extra_points);",
														[
															the_game_id,
															matchday,
															item.id,
															'ALL_LINEUP_IS_PLAYED_BONUS',
															20
														],function(err,r){
															//console.log('-----',this.sql,'---');
															cb(err);
														});
										}else{
											cb(null);
										}
									},
									function(cb){
										if(is_team_budget_below_zero){
											var penalty = Math.floor(balance/10) * 100;
											console.log('PENALTY : ',penalty);
											if(penalty < -100){
												penalty = -100;
											}
											conn.query("INSERT INTO \
														"+config.database.statsdb+".game_team_extra_points\
														(game_id,matchday,game_team_id,\
															modifier_name,extra_points)\
														VALUES\
														(?,?,?,?,?)\
														ON DUPLICATE KEY UPDATE\
														extra_points = VALUES(extra_points);",
														[
															the_game_id,
															matchday,
															item.id,
															'BALANCE_IS_BELOW_ZERO',
															penalty
														],function(err,r){
															
															cb(err,true);
														});
										}else{
											cb(null,true);
										}
									},
									function(all_ok,cb){
										//send notification that the player has recieved the extra points
										if(is_all_player_started){
											var msg_id = "ALL_PLAYER_STARTED_"+matchday;
											var msg = "Selamat, anda baru saja mendapatkan bonus poin sebesar 20 dipertandingan lalu.";
											conn.query("INSERT IGNORE INTO "+config.database.frontend_schema+".notifications\
														(content,url,dt,game_team_id,msg_id,league)\
														VALUES\
														(?,'#',NOW(),?,?)",[msg,item.id,msg_id,league],function(err,rs){
															//console.log('---',this.sql,'----');
															console.log('extra_point_notification',S(this.sql).collapseWhitespace().s);
															cb(err,true);
												});
										}else{
											cb(null,true);
										}
									},
									function(all_ok,cb){
										//send notification that the player has recieved the extra points
										if(is_team_budget_below_zero){
											var penalty = Math.floor(balance/10) * 100;
											var msg_id = "BUDGET_BELOW_ZERO_"+matchday;
											if(penalty < -100){
												penalty = -100;
											}
											var msg = "Kamu mendapatkan potongan poin sebesar "+penalty+" karena star kamu negatif";
											conn.query("INSERT IGNORE INTO "+config.database.frontend_schema+".notifications\
														(content,url,dt,game_team_id,msg_id,league)\
														VALUES\
														(?,'#',NOW(),?,?)",[msg,item.id,msg_id,league],function(err,rs){
															//console.log('---',this.sql,'----');
															console.log('extra_point_notification',S(this.sql).collapseWhitespace().s);
															cb(err,true);
												});
										}else{
											cb(null,true);
										}
									},
									function(all_ok,cb){
										//periksa apakah jumlah pemain didalam tim lebih dari 23 ?
										//jika lebih dari 23, maka pemain akan kena penalty poin 
										//sebesar 100*selisih kelebihan pemainnya
										/*
										console.log('ISSUE 4','check if tim is overcapacity ?');
										var my_team_id = item.id;
										var rooster_overlimit = false;
										conn.query("SELECT COUNT(id) as total FROM "+config.database.database+".game_team_players\
													 WHERE game_team_id = ?",
													 [my_team_id],
													 function(err,check){
													 	if(check[0].total>23){
													 		rooster_overlimit = true;
													 	}
													 	cb(err,rooster_overlimit,check[0].total);
													 });
										*/
										cb(null,false,0);
									},
									function(rooster_overlimit,total_game_players,cb){
										/*
										if(rooster_overlimit){
											//if rooster is overlimit, we give penalty to user
											var overlimit_penalty = (total_game_players - 23) * (-100);
											console.log('OVERLIMIT_CHECK',
														total_game_players,
														'>23 penalty -> ',
														overlimit_penalty);

											conn.query("INSERT INTO \
														"+config.database.statsdb+".game_team_extra_points\
														(game_id,matchday,game_team_id,\
															modifier_name,extra_points)\
														VALUES\
														(?,?,?,?,?)\
														ON DUPLICATE KEY UPDATE\
														extra_points = VALUES(extra_points);",
														[
															the_game_id,
															matchday,
															item.id,
															'LINEUP_MORE_THAN_23',
															overlimit_penalty
														],function(err,r){
															console.log('OVERLIMIT CHECK',
																		S(this.sql).collapseWhitespace().s);
															cb(err,rooster_overlimit,overlimit_penalty);
														});
										}else{
											console.log('ISSUE','OVERLIMIT_CHECK',
														total_game_players,'OK');
											cb(null,rooster_overlimit,0);
										}
										*/
										cb(null,false,0);
									},
									function(rooster_overlimit,penalty,cb){
										cb(null,true);
										/*
										//notify the user
										if(rooster_overlimit){
											var msg = "Kamu mendapatkan potongan poin sebesar `"+penalty+"` jumlah pemain tim kamu melebihi kapasitas (maksimum 23).";
											var msg_id = "23_error_"+matchday;
											conn.query("INSERT IGNORE INTO "+config.database.frontend_schema+".notifications\
														(content,url,dt,game_team_id,msg_id)\
														VALUES\
														(?,'#',NOW(),?,?)",[msg,item.id,msg_id],function(err,rs){
															//console.log('---',this.sql,'----');
															console.log('extra_point_notification',S(this.sql).collapseWhitespace().s);
															cb(err,true);
												});
										}else{
											cb(null,true);
										}*/
									},
									function(endOfProcess,cb){
										//apply jersey perks
										perks.apply_jersey_perks(
											conn,
											the_game_id,
											matchday,
											item.id,
											function(err,rs){
												cb(err,endOfProcess);
											});
									}

								],function(err,endOfProcess){
									next(err,endOfProcess);
								});
							}else{
								next(null,true);
							}
						}
					],
					function(err,wf_result){
						console.log('linup_stats','done');
						conn.query("UPDATE "+config.database.statsdb+".job_queue \
									SET current_id=?,n_done=n_done+1\
									WHERE id = ?",
									[item.id,queue_id],function(err,rs){
										console.log(S(this.sql).collapseWhitespace().s);
										callback();
									});
						
					});
				
			},
			function(err){
				conn.release();
				console.log('update_team_stats','done');
				done(null);
				
				
			}
		);

		
	});
}

function getPlayerDailyTeamStats(conn,game_team_id,player_id,player_pos,matchday,position_no,done){
	console.log('ISSUE2','updating matchday#',matchday,'stats for player #',player_id,'in team #',game_team_id);
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
	
	var sql = "SELECT a.game_id,stats_name,stats_value\
			FROM "+config.database.statsdb+".master_player_stats a \
			INNER JOIN "+config.database.database+".game_fixtures b\
			ON a.game_id = b.game_id\
			WHERE a.player_id=? AND b.matchday=?\
			LIMIT 500;";
	
	async.waterfall([
		function(callback){
			conn.query(sql,
				[player_id,matchday,game_team_id,game_team_id],
				function(err,rs){
					//console.log(S(this.sql).collapseWhitespace().s);		
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
			//get event related points
			var percentage = 1;
			async.waterfall([
				function(cb){
					conn.query("SELECT a.id,amount FROM "+config.database.database+".job_event_master_player a\
						INNER JOIN "+config.database.database+".master_events b\
						ON a.master_event_id = b.id\
						WHERE game_team_id = ? AND player_id=? AND affected_item = 2\
						AND matchday=? AND a.n_status = 0 LIMIT 1;",[game_team_id,player_id,matchday],
						function(err,rs){
							console.log('EVENT APPLIED POINTS',S(this.sql).collapseWhitespace().s);
							var id = 0;
							if(rs!=null && rs.length > 0){
								console.log('EVENT APPLIED POINTS',rs);
								percentage = rs[0].amount/100;
								console.log('EVENT APPLIED POINTS','point X ',percentage);
								id = rs[0].id;
							}
							cb(err,id);
						});
				},function(job_id,cb){
					//flag to done
					conn.query("UPDATE "+config.database.database+".job_event_master_player SET n_status=1 WHERE id = ?",
								[job_id],function(err,rs){
									console.log('EVENT APPLIED POINTS','event flagged as applied');
								cb(err);
					});
				}
			],
			function(err){
				callback(err,percentage,modifiers,result);
			});
			
		},
		function(percentage,modifiers,result,callback){
			//mapping
			var weekly = [];
			var point_modifier = 1.0;
			if(position_no > 11){
				//if substitution, all points reduced to 50%
				point_modifier = 0.5;
			}
			if(result.length>0){
				for(var i in result){
					
					//distributed the counts for each categories
					for(var category in player_stats_category){
						for(var j in player_stats_category[category]){
							if(player_stats_category[category][j] == result[i].stats_name){
								var points =  (parseInt(result[i].stats_value) * getModifierValue(modifiers,
																	  					result[i].stats_name,
																	  					pos));
								
								console.log('EVENT APPLIED POINTS',points,'*',point_modifier,'*',percentage);
								weekly.push({
									game_id:result[i].game_id,
									category:category,
									game_team_id:game_team_id,
									player_id:player_id,
									matchday:matchday,
									stats_name:result[i].stats_name,
									stats_value:result[i].stats_value,
									points: ((points * point_modifier) * percentage),
									position_no: position_no

								});
							}
						}
					}
				}
			}
			//console.log(weekly);
			callback(null,weekly);
		},
		function(weekly,callback){
			var coach_bonus = 0;
			console.log('ISSUE1','getting coach data');
			getTeamStaffs(game_team_id,function(err,data){
				
				if(player_pos =='Goalkeeper'){
					coach_bonus = game_config.staff_bonus.gk_coach[data.gk_coach];
				}
				if(player_pos =='Defender'){
					coach_bonus = game_config.staff_bonus.def_coach[data.def_coach];
				}
				if(player_pos =='Midfielder'){
					coach_bonus = game_config.staff_bonus.mid_coach[data.mid_coach];
				}
				if(player_pos =='Forward'){
					coach_bonus = game_config.staff_bonus.fw_coach[data.fwd_coach];
				}
				console.log('ISSUE1','COACH',game_team_id,player_pos,coach_bonus);
				callback(err,weekly,coach_bonus);
			});
		},
		function(weekly,coach_bonus,callback){
			console.log('ISSUE1','game_team_player_weekly');
			async.eachSeries(weekly,function(w,next){
				async.waterfall([
					function(cb){
						console.log('ISSUE1','point ',w.points,'+',w.points,'*',coach_bonus,'=',(w.points + (w.points * coach_bonus)));
						conn.query("INSERT INTO "+config.database.statsdb+".game_team_player_weekly\
								(game_id,game_team_id,matchday,player_id,stats_category,\
									stats_name,stats_value,points,coach_bonus,position_no)\
								VALUES\
								(?,?,?,?,?,?,?,?,?,?)\
								ON DUPLICATE KEY UPDATE\
								stats_value = VALUES(stats_value),\
								points = VALUES(points),\
								coach_bonus = VALUES(coach_bonus),\
								position_no = VALUES(position_no)",
								[w.game_id,
								w.game_team_id,
								 w.matchday,
								 w.player_id,
								 w.category,
								 w.stats_name,
								 w.stats_value,
								 (w.points + (w.points * coach_bonus)),
								 coach_bonus,
								 w.position_no
								 ],function(err,rs){
								 	console.log('ISSUE1',S(this.sql).collapseWhitespace().s);
								 	cb(err,w.stats_name);
								});
					},
					function(stats_name,cb){
						//jika mencetak goal, maka pemain ini dapet bonus. club harus membayar bonus tersebut.
						if(stats_name == 'goals'){
							console.log('ISSUE1','GOAL_BONUS',w.game_team_id,w.player_id);
							//find the player so we can find out if it create a goal
							console.log('ISSUE1','GOAL_BONUS',"getPlayers_"+league+"_"+w.game_team_id);
							redisClient.get("getPlayers_"+league+"_"+w.game_team_id,function(err,data){
								var goal_bonus = 0;
								console.log('ISSUE1','GOAL_BONUS',data);
								if(data!=null){
									data = JSON.parse(data);
									for(var i in data){
										console.log('GOAL_BONUS',data[i].player_id,'==',w.player_id);
										if(data[i].player_id == w.player_id){
											console.log('GOAL_BONUS','got it ->',data[i].goal_bonus);
											goal_bonus = data[i].goal_bonus;

											break;
										}
									}
								}
								console.log('ISSUE1','GOAL_BONUS',w.game_team_id,w.player_id,'-->',goal_bonus);
								cb(null,goal_bonus);
							});
						}else{
							cb(null,0);
						}
						
					},
					function(goal_bonus,cb){
						if(parseInt(goal_bonus)!=0){
							goal_bonus = parseInt(goal_bonus);
							var sql = "INSERT INTO "+config.database.database+".game_team_expenditures\
								(game_team_id,item_name,item_type,amount,game_id,match_day,item_total)\
								VALUES (?,'goal_bonus',2,?,?,?,1) ON DUPLICATE KEY UPDATE\
								amount = amount + VALUES(amount);";
							conn.query(sql,[w.game_team_id,(goal_bonus*-1),w.game_id,w.matchday],
							function(err,rs){
								console.log('GOAL_BONUS',S(this.sql).collapseWhitespace().s);
								cb(err);
							});
						}else{
							cb(null);
						}
						
					}
				],function(err){
					next();
				});
				
			},
			function(err){callback(err,weekly)});
			
		},
		function(weekly,callback){
			//apply player stats perks
			perks.apply_player_perk(
				conn,
				game_team_id,
				player_id,
				weekly,
				matchday,
			function(err,rs){
				callback(err,weekly,rs)
			});
		},
		function(weekly,result,callback){
			tactics.apply_bonus(conn,game_team_id,player_id,weekly,matchday,function(err){
				callback(err,weekly);
			});	
		},
		function(result,callback){
			//reset all the caches
			async.waterfall([
				function(cb){
					console.log('lineup_stats','reset cache','getPlayerTeamStats_'+game_team_id+'_'+player_id);
					redisClient.set('getPlayerTeamStats_'+league+'_'+game_team_id+'_'+player_id,
									JSON.stringify(null),
									function(err,rs){
										if(err){
											console.log('lineup_stats',err.message);
										}
										
										cb(err);
									});
				},
				function(cb){
					console.log('lineup_stats','reset cache','getPlayerDailyTeamStats_'+game_team_id+'_'+player_id);
					redisClient.set('getPlayerDailyTeamStats_'+league+'_'+game_team_id+'_'+player_id,
									JSON.stringify(null),
									function(err,rs){
										if(err){
											console.log('lineup_stats',err.message);
										}
										cb(err);
									});
				},
				function(cb){
					/*
					console.log('lineup_stats','reset cache','getPlayers_'+game_team_id);
					redisClient.set('getPlayers_'+league+'_'+game_team_id,
									JSON.stringify(null),
									function(err,rs){
										if(err){
											console.log('getPlayers_',err.message);
										}
										cb(err);
									});*/
					cb(null,null);
				}
			],

			function(err,rs){
				console.log('lineup_stats','reset cache completed');
				callback(err,result);
			});
			
		}
	],
	function(err,result){
		done(err,result);
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


//update user's team stats individually. (currently we need to process each of 35000++ users)

function update_individual_team_stats(conn,game_id,team,summary,player_stats,done){
		async.waterfall(
			[
				function(callback){
					console.log('UPDATE_INDIVIDUAL',team,'vs',summary);
					//if(typeof summary[team.team_id]!=='undefined'){
						console.log('HISTORY','track the lineup history for #',team.id);

						//@TODO : ini nanti kayaknya harus dieksekusi sebelum step 2.
						//agar jika kita sedang me regen data,  lineup yg dipakai adalah lineup dari history.
						//bukan lineup actual.
						addToHistory(conn,game_id,team,function(err){
							callback(err);	
						});
					//}else{
						//console.log('no need to track the lineup history for #',team.team_id);
						//callback(null);	
					//}
				},
				function(callback){
					//step 1 - get team lineups
					//@TODO perlu dipikirkan apakah kita perlu narik lineup dari lineup history ?

					getTeamLineups(conn,game_id,team,function(err,lineups){
						callback(null,lineups);
					});
					
				},
				function(lineups,callback){
					console.log('ISSUE1','updating_played_acc');
					updating_played_acc(team.id,lineups,function(err){
						console.log('ISSUE1','updating_played_acc done');
						callback(err,lineups);
					});
				},
				function(lineups,callback){
					var in_game = true;
					if(typeof summary[team.team_id] === 'undefined'){
						in_game = false;
					}
					//step 2 - update lineup stats
					updateLineupStats(conn,game_id,lineups,summary,player_stats,in_game,function(err,rs){
						callback(err,rs);
					});
					
				}
				
			],
				function(err,result){
					//returns matched_players 
					done(err,result);
				}
			);
	
	
}
function addToHistory(conn,game_id,team,done){
	var matchday = 0;
	
		async.waterfall([
			function(callback){
				conn.query("SELECT matchday FROM "+config.database.database+".game_fixtures WHERE game_id=? LIMIT 1",
							[game_id],function(err,match){
								console.log('ISSUE1',this.sql);
								try{
									matchday = match[0]['matchday'];
								}catch(e){
									matchday = 0;	
								}
								callback(err);
							});
			},
			function(callback){
				conn.query("SELECT game_id FROM "+config.database.database+".game_fixtures \
							WHERE (home_id = ? OR away_id = ?) \
							AND matchday=? LIMIT 1",
							[team.team_id,team.team_id,matchday],function(err,match){
								console.log('ISSUE1','0# get the game_id : ',this.sql);
								var the_game_id = '';
								try{
									the_game_id = match[0]['game_id'];
									console.log('the game id : ',the_game_id);
									callback(err,the_game_id);
								}catch(e){
									conn.query("SELECT game_id FROM "+config.database.database+".game_fixtures\
												 WHERE matchday=? ORDER BY game_id ASC LIMIT 1",
												 [matchday],function(err,match){
												 	the_game_id = match[0]['game_id'];
												 	console.log('the game id : ',the_game_id,' actually not found');
												 	callback(err,the_game_id);
												 });
									
								}
							});
			},
			function(the_game_id,callback){
				var can_insert = true;
				//we only do insert once
				conn.query("SELECT * FROM "+config.database.database+".game_team_lineups_history\
							WHERE game_id = ? AND game_team_id= ?",
							[the_game_id,team.id],function(err,rs){
								console.log('ISSUE1','check if theres already history',S(this.sql).collapseWhitespace().s);
								try{
									if(rs.length>0){
										can_insert = false;
									}
								}catch(e){
									can_insert = false;
								}
								callback(err,the_game_id,can_insert);
							});
			},
			function(the_game_id,can_insert,callback){
				if(can_insert){
					conn.query("INSERT IGNORE INTO \
					"+config.database.database+".game_team_lineups_history\
					(game_id,game_team_id,player_id,position_no,last_update)\
					SELECT ? AS game_id,game_team_id,player_id,position_no,NOW() AS last_update\
					FROM "+config.database.database+".game_team_lineups WHERE game_team_id=? AND matchday = ?;",
					[the_game_id,team.id,matchday],
					function(err,rs){
						console.log('ISSUE1','update lineup history ',S(this.sql).collapseWhitespace().s);
						callback(err,rs);
					});
				}else{
					console.log('ISSUE1','no insert history #',team.id,' - ',the_game_id);
					callback(null,null);
				}
				
			}
		],function(err,rs){
			
			done(err);
			
		});
	
}
function getTeamLineups(conn,game_id,team,done){
	var matchday = 0;
	
		async.waterfall([
			function(callback){
				conn.query("SELECT matchday FROM "+config.database.database+".game_fixtures \
							WHERE game_id=? LIMIT 1",
							[game_id],function(err,match){
								console.log('ISSUE1',this.sql);
								try{
									matchday = match[0]['matchday'];
								}catch(e){
									matchday = 0;	
								}
								callback(err);
							});
			},
			function(callback){
				conn.query("SELECT game_id FROM "+config.database.database+".game_fixtures \
							WHERE (home_id = ? OR away_id = ?) \
							AND matchday=? LIMIT 1",
							[team.team_id,team.team_id,matchday],function(err,match){
								console.log('ISSUE1','get the game_id : ',this.sql);
								var the_game_id = '';
								try{
									the_game_id = match[0]['game_id'];
									console.log('the game id : ',the_game_id);
									callback(err,the_game_id);
								}catch(e){
									//err = new Error('no game_id found');
									conn.query("SELECT game_id FROM "+config.database.database+".game_fixtures\
												 WHERE matchday=? ORDER BY game_id ASC LIMIT 1",
												 [matchday],function(err,match){
												 	the_game_id = match[0]['game_id'];
												 	console.log('the game id : ',the_game_id,' actually not found');
												 	callback(err,the_game_id);
												 });
								}
								
							});
			},
			function(the_game_id,callback){
				conn.query("SELECT a.*,b.position\
							 FROM "+config.database.database+".game_team_lineups_history a\
							INNER JOIN "+config.database.database+".master_player b\
							ON a.player_id = b.uid \
							WHERE a.game_id=? AND a.game_team_id = ?\
							LIMIT 20",
					[the_game_id,team.id],function(err,rs){
							console.log('ISSUE1','lineup : ',this.sql);
							callback(err,rs);
					});
			}
		],
		function(err,rs){
			
			console.log('ISSUE1','get team lineup ends');
			if(typeof rs !== 'object' && rs.length > 0){
				err = new Error('no lineups :(');
				rs = [];
			}
			console.log('ISSUE1',rs);
			done(err,rs);
			
		});
		
	
}

function updateLineupStats(conn,game_id,lineups,summary,player_stats,in_game,done){
	console.log('player stats : ',player_stats);
	var matched_players = [];
	
		async.eachSeries(lineups,
		function(item,callback){
			//console.log(item);
			var stats = {player_id:item.player_id,
						 position:item.position,
						 points: 0,
						 original_points:0,
						 performance: 0};
			var is_found = false;
			async.waterfall([
					function(callback){
						var coach_bonus = 0;
						getTeamStaffs(item.game_team_id,function(err,data){
							if(stats.position=='Goalkeeper'){
								coach_bonus = game_config.staff_bonus.gk_coach[data.gk_coach];
							}
							if(stats.position=='Defender'){
								coach_bonus = game_config.staff_bonus.def_coach[data.def_coach];
							}
							if(stats.position=='Midfielder'){
								coach_bonus = game_config.staff_bonus.mid_coach[data.mid_coach];
							}
							if(stats.position=='Forward'){
								coach_bonus = game_config.staff_bonus.fw_coach[data.fwd_coach];
							}
							console.log('ISSUE1','coach_bonus',stats.position,coach_bonus);
							callback(null,coach_bonus);
						});
					},
					function(coach_bonus,callback){
						conn.query("SELECT game_id,player_id,stats_name \
									FROM "+config.database.statsdb+".master_player_stats \
									WHERE game_id = ?  AND player_id= ? \
									AND stats_name = 'game_started' LIMIT 1;",
									[game_id,item.player_id],function(err,sqlResult){
										console.log('ISSUE1',S(this.sql).collapseWhitespace().s);
										var isStarter = false;
										
										sqlResult = sqlResult || [];
										if(sqlResult.length>0){
											isStarter = true;
										}
										console.log('check starter status',game_id,item.player_id,isStarter);
										callback(err,coach_bonus,isStarter);
									});
					},
					function(coach_bonus,isStarter,callback){
						for(var i in player_stats){
							/*console.log('ISSUE1',item.game_team_id,'check -> ',
										item.player_id,'--',player_stats[i].player_id);*/
							if(item.player_id==player_stats[i].player_id){
								matched_players.push(item.player_id);
								
								stats.points = player_stats[i].points;
								//if(!isStarter){
								//	console.log('points reduced to 75%',(stats.points*0.75));
								//	stats.points = stats.points * 0.75;
								//}
								console.log('point total : ',item.player_id,'#',stats.points,'+',stats.points,'x',coach_bonus);
								stats.original_points = stats.points;
								stats.points = stats.points + (stats.points*coach_bonus);
								console.log('point total :',item.player_id,'->',stats.points);
								var apts =  summary[player_stats[i].team_id].average;
								stats.performance = ((stats.points - apts)/apts)*100;
								is_found = true;
								break;
							}
						}
						if(!is_found && !in_game){
							//we skip it if the player is not matched one of the player_stats,
							//and the team is not involve with the game at all.
							console.log('skip #',item.player_id,' from team #',item.game_team_id);
							callback(null,null);
						}else{
							console.log('add #',item.player_id,' from team #',item.game_team_id,' stats');
							conn.query("INSERT INTO "+config.database.statsdb+".game_match_player_points\
								(game_id,game_team_id,player_id,points,performance,coach_bonus,original_points,\
								last_update)\
								VALUES(?,?,?,?,?,?,?,NOW())\
								ON DUPLICATE KEY UPDATE\
								points = VALUES(points),\
								original_points = VALUES(original_points),\
								coach_bonus = VALUES(coach_bonus),\
								performance = VALUES(performance);",
								[	game_id,
									item.game_team_id,
									stats.player_id,
									stats.points,
									stats.performance,
									coach_bonus,
									stats.original_points ],
								function(err,rs){
									console.log('ISSUE1','game_match_player_points',S(this.sql).collapseWhitespace().s);
									callback(err,rs);	
							});
						}
					}

				],
				function(err,rsWaterfall){
					callback();
				}
			);
			
			
		},
		function(err){
			
			done(null,matched_players);	
			
		});
	
}

/**
* updating the team's overall points
*/
function update_team_points(done){
	console.log('updating team points');
	pool.getConnection(function(err,conn){
		conn.query("INSERT INTO "+config.database.statsdb+".game_team_points\
					(game_team_id,points)\
					SELECT game_team_id,SUM(points) AS total_points\
					FROM "+config.database.statsdb+".game_team_player_weekly\
					GROUP BY game_team_id\
					ON DUPLICATE KEY UPDATE\
					points = VALUES(points);",
					[],function(err,rs){
						conn.release();
						done(err);	
						
					});
	});
}


//updating the team flag
/*
player.bonus = ((fitness + morale) / 200) * coach_modifier
player.fatigue -> fitness < 50 
player.morale_bonuses -> array of bonus strings they recieved
player.morale -> morale value
player.happy_with_salary -> if player_salary > old salary
player.fitness -> fitness value
player.played_avg -> total play / total matches
player.played_last_2match -> true / false
player.unplayed_acc_match -> no of unplayed since the last match. when he played, it resets to 0
player.happy_with_coach -> coach rank >= 3
player.starting -> if it starts, set to 1, if didnt, set to 0. based on current lineup. this status updated each formation saved.
player.sub -> if starts as sub, then set to 1, if didnt set to 0. based on current lineup. this status updated each formation saved.
player.personal_problem -> random
player.refused_transfer -> when the transfer offer from other club with higher rank.
player.salary_refused -> when salary renegotiation failed (random events), this events triggered where its not really happy with its current salary and morale below 50
player.vice_captain -> when it starts as vice captain
player.picking -> the value of picking order chances. based on in_rooster_mod, once its value below zero, these condition are in affect.
player.homesick -> random events if the player is foreigner

*/
function updating_played_acc(game_team_id,lineups,callback){
	console.log('ISSUE1','updating_played_acc',game_team_id,lineups);
	var player_played = {};
	async.waterfall([
		function(cb){
			for(var i in lineups){
				if(lineups[i].position_no > 11){
					player_played[lineups[i].player_id] = {type:'sub'};
				}else{
					player_played[lineups[i].player_id] = {type:'starting'};
				}
				
			}
			console.log('ISSUE1','playerPlayed',player_played);
			cb(null);
		},
		function(cb){
			console.log('ISSUE1',"getPlayers_"+league+"_"+game_team_id);
			//first get the current teams
			redisClient.get("getPlayers_"+league+"_"+game_team_id,function(err,data){
				cb(err,data);
			});
		},
		function(data,cb){
			if(data!=null){
				var players = JSON.parse(data);
				if(players!=null){
					console.log('ISSUE1','update_cache',players);
					async.eachSeries(players,function(player,next){
						if(typeof player_played[player.player_id] !== 'undefined'){
							console.log('ISSUE1','update_cache','setPlayed',player_played[player.player_id].type);
							setPlayed(game_team_id,
										player.player_id,
										player_played[player.player_id].type,
										function(err){
											next();
										});
						}else{
							console.log('ISSUE1','update_cache','setNotPlayed',player.player_id);
							setNotPlayed(game_team_id,player.player_id,
								function(err){
									next();
								});
						}
					},function(err){
						cb(err);
					});
				}else{
					console.log('ISSUE1','empty cache');
					cb(null);
				}
				
				
			}else{
				console.log('ISSUE1','no data');
				cb(null);
			}
		}
	],function(err){
		callback(err);
	});
	
}
//if played, 
//set played_accumulative counts
//reset unplayed_accumulative counts to 0
//reduce fitness level 
function setPlayed(game_team_id,player_id,type,callback){
	console.log('ISSUE1',game_team_id,player_id,'setPlayed');
	var physio_bonus = 0;
	async.waterfall([
		function(cb){
			getTeamStaffs(game_team_id,function(err,data){
				if( data.phy_coach >= 0 ){
					physio_bonus = game_config.coach_fatigue_bonus[data.phy_coach];
				}
				cb(null);
			});
		},
		function(cb){
			redisClient.get('p_'+league+'_'+game_team_id+'_'+player_id,function(err,data){
				cb(err,data);
			});
		},
		function(data,cb){
			if(data!=null){
				var o = JSON.parse(data);
				o.unplayed_acc_match = 0;
				o.played_acc_match++;
				o.fitness = o.fitness - (50 - (50*physio_bonus));
				if(o.fitness < 20){
					o.fitness = 20; //klo fitness nya 0 artinya pemainnya uda tewas ga bernyawa.
				}
				if(o.played_acc_match > 2){
					o.played_last_2match = 1;
				}
				o.sub = 0;
				o.starting = 0;
				if(type=='starting'){
					o.starting = 1;
				}else{
					o.sub = 1;
				}
				console.log('setPlayed',game_team_id,player_id,type,JSON.stringify(o));
				redisClient.set('p_'+league+'_'+game_team_id+'_'+player_id,
								JSON.stringify(o),
								function(err){
					cb(err);
				});
			}else{
				cb(null);
			}
		}
	],function(err){
		callback(err);
	});
}
function setNotPlayed(game_team_id,player_id,callback){
	console.log('ISSUE1',game_team_id,player_id,'setNotPlayed');
	async.waterfall([
		function(cb){
			redisClient.get('p_'+league+'_'+game_team_id+'_'+player_id,function(err,data){
				cb(err,data);
			});
		},
		function(data,cb){
			if(data!=null){
				var o = JSON.parse(data);
				o.unplayed_acc_match++;
				o.played_acc_match = 0;
				o.played_last_2match = 0;
				console.log('setNotPlayed',game_team_id,player_id,o);
				redisClient.set('p_'+league+'_'+game_team_id+'_'+player_id,
								JSON.stringify(o),
				function(err){
					cb(err);
				});
			}else{
				cb(null);
			}
		}
	],function(err){
		callback(err);
	});
}
function getTeamStaffs(game_team_id,callback){
	console.log('ISSUE1','getTeamStaffs','staff_'+league+"_"+game_team_id);
	redisClient.get('staff_'+league+"_"+game_team_id,
	function(err,data){
		console.log('ISSUE1',game_team_id,'staffs : ',data); 
		if(data==null){
			callback(null,
			{
				dof:0,
				marketing:0,
				security:0,
				pr:0,
				phy_coach:0,
				gk_coach:0,
				def_coach:0,
				mid_coach:0,
				fwd_coach:0,
				scout:0,
				dice:0
			});
		}else{
			callback(null,JSON.parse(data));
		}
	});
}