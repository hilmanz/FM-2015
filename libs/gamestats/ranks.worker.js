/**
* module for updating game ranks.
* worker mode.
*/

/**
* the module to read match_results file.
*/
var fs = require('fs');
var path = require('path');
var async = require('async');
var xmlparser = require('xml2json');
var config = require(path.resolve('./config')).config;
var stadium_earning_category = require(path.resolve('./libs/game_config')).stadium_earning_category;
var stadium_earnings = require(path.resolve('./libs/game_config')).stadium_earnings;
var cost_mods = require(path.resolve('./libs/game_config')).cost_modifiers;
var S = require('string');
var mysql = require('mysql');
var pool = {};

var punishment = require(path.resolve('./libs/gamestats/punishment_rules'));
var cash = require(path.resolve('./libs/gamestats/game_cash'));

var frontend_schema = config.database.frontend_schema;
var total_teams = 0;
var limit  = 100;
console.log('ranks updater : pool opened');

var league = 'epl';
exports.setLeague = function(l){
	league = l;
}
exports.setConfig = function(c){
	config = c;
	cash.setConfig(config);
	punishment.setConfig(config);
}

exports.setPool = function(p){
	pool = p;
}
exports.update = function(conn,since_id,until_id,update_rank,game_id,done){
	var start = 0;
	var limit = 100;//we deal with 100 teams at a time
	var doLoop = true;


	//pool.getConnection(function(err,conn){
		async.waterfall([
			function(cb){
				//first, we check the matchday
				conn.query("SELECT matchday FROM "+config.database.database+".game_fixtures WHERE game_id=? LIMIT 1",
							[game_id],function(err,rs){
								cb(err,rs[0].matchday);
				});
			},
			function(matchday,cb){
					console.log('Distribute overall,weekly,and monthly POINTS');
					conn.query("SELECT a.id AS team_id,b.paid_plan,b.fb_id,d.team_id as original_team_id,d.id as game_team_id\
					FROM "+frontend_schema+".teams a \
					INNER JOIN "+frontend_schema+".users b\
					ON a.user_id = b.id \
					INNER JOIN "+config.database.database+".game_users c\
					ON b.fb_id = c.fb_id\
					INNER JOIN "+config.database.database+".game_teams d\
					ON d.user_id = c.id\
					WHERE a.league = ? AND d.id BETWEEN ? AND ?\
					LIMIT ?;",
					[league,since_id,until_id,limit],
					function(err,rs){
						console.log('update_rank',S(this.sql).collapseWhitespace().s);
						if(typeof rs !== 'undefined' && rs.length>0){
							populate(conn,rs,matchday,function(err){
								console.log('DONE POPULATING');
								cb(err,matchday);
							});
						}else{

							console.log('NO DATA AVAILABLE, WE SKIP IT');
							cb(err,matchday);
						}
					});
					
			},
			function(matchday,cb){
				//update_rank = false;
				if(update_rank){
					console.log('update_rank','NOW WE RECALCULATE THE RANKS');
					recalculate_ranks(conn,matchday,function(err){
						update_rank_history(conn,function(e){
							cb(err,matchday);
						});
						
					});
				}else{
					console.log('update_rank','Not need to recalculate the ranks');
					cb(null,matchday);
				}
			},
			function(matchday,cb){
				
				give_weekly_cash(since_id,until_id,matchday,conn,function(err){
					cb(err,true);
				});
				//cb(err,true);
			}
		],
		function(err,isDone){
			//conn.end(function(err){
				//console.log('connection end');
				console.log('rank update done');
				//pool.end(function(err){
					//console.log('pool closed');
					
					done(err,null);
				//});
			//});
		});
		
	//});
}
function update_rank_history(conn,done){
	conn.query("INSERT INTO "+config.database.statsdb+".rank_update_history(last_update)\
				VALUES(NOW());",[],function(err,rs){
					done(err);
				});
}
function recalculate_ranks(conn,matchday,done){
	console.log('recalculate ranks');
	async.waterfall([
		function(callback){
			console.log('recalculate_ranks skipped');
			/*
			conn.query("CALL "+frontend_schema+".recalculate_rank(?);",[league],function(err,rs){
				if(err) console.log(err);
				callback(err);
			});
			*/
			callback(null);
		},
		function(callback){
			conn.query("SELECT matchday FROM "+frontend_schema+".weekly_points \
                            GROUP BY matchday ORDER BY matchday DESC \
                            LIMIT 1;",[],function(err,matchdays){
                            	callback(err,matchdays);
                            });
		},
		function(matchdays,callback){
			//now we only need to recalculate 1 matchday
			console.log('recalculate_weekly_rank skipped');
			/*conn.query("CALL "+frontend_schema+".recalculate_weekly_rank(?,?);",
								[league,matchday],function(err,rs){
									console.log('recalculating matchday #',matchday,' ranks');
									callback(err);
								});
			*/
			callback(null);
		},
		function(callback){
			console.log('PRO WEEKLY RANK RECALCULATE');
				/*
				conn.query("CALL "+frontend_schema+".recalculate_weekly_rank_pro(?,?);",
							[league,matchday],function(err,rs){
								console.log('recalculating matchday #',matchday,' pro weekly  ranks');
								callback(err);	
							});*/
			callback(null);
			
		},
		function(callback){

			conn.query("SELECT YEAR(matchdate) AS thn,MONTH(matchdate) AS bln\
                            FROM "+frontend_schema+".weekly_points GROUP BY thn,bln;",[],
                            function(err,monthset){
                            	//console.log(S(this.sql).collapseWhitespace().s);
                            	callback(err,monthset);
                            });
		},
		function(months,callback){
			//console.log(months);
			callback(null);
			/*
			async.eachSeries(months,function(m,next){
				var mth = m.bln;
				var yr = m.thn;
		        conn.query("CALL "+frontend_schema+".recalculate_monthly_rank(?,?,?);",[league,mth,yr],
		        	function(err,rs){
		        		console.log('recalculate monthly rank ',mth,yr);
		        		next();
		        });
			},
			function(err){
				callback(err);

			});
			*/
		},
	],

	function(err,rs){
		done(null);	
	});
	
}
function populate(conn,teams,matchday,done){
	
	async.eachSeries(teams,function(team,next){
		//console.log(team.fb_id);
		if(typeof team.paid_plan !== 'undefined' && team.paid_plan == 'pro2'){
			async.waterfall([
				function(cb){
					console.log('PRO BONUS','this is PRO50 user',team.game_team_id);
					proleagueRandomBonusPoints(conn,team.game_team_id,team.original_team_id,matchday,function(err){
						cb(err);
					});
				},
				function(cb){
					getUserTeamPoints(conn,team.fb_id,matchday,function(err,stats){
						console.log('update_rank','RESULT',team,stats);
						updatePoints(conn,team,stats,function(err){
							cb(err);	
						});
					});
				}
			],
			function(err,rs){
				next();
			});
		}else{
			getUserTeamPoints(conn,team.fb_id,matchday,function(err,stats){
				console.log('update_rank','RESULT',team,stats);
				updatePoints(conn,team,stats,function(err){
					next();	
				});
			});
		}
		
		
	},function(err){
		done(null);	
	});
	
	/*
	console.log('fiuh');
	var team = {
		fb_id:'100002073115789',
		team_id:22509
	}
	getUserTeamPoints(conn,'100002073115789',function(err,stats){
		//console.log(stats);
		updatePoints(conn,team,stats,function(err){
			done(null);
		});
	});*/
	
}
function proleagueRandomBonusPoints(conn,game_team_id,original_team_id,matchday,done){
	var point_type = ['defending','passing_and_attacking','goalkeeper','mistakes_and_errors'];
	async.waterfall([
		function(cb){
			var bonus_type = Math.floor(Math.random()*(point_type.length-1));
			console.log('PRO BONUS',game_team_id,point_type[bonus_type]);
			conn.query("SELECT SUM(points) AS total FROM \
						"+config.database.statsdb+".game_team_player_weekly \
						WHERE game_team_id=? \
						AND game_id IN (\
						SELECT game_id FROM "+config.database.database+".game_fixtures WHERE matchday=?\
						) AND stats_category=?;",[game_team_id,matchday,point_type[bonus_type]],
			function(err,rs){
				console.log('PRO BONUS',game_team_id,S(this.sql).collapseWhitespace().s);
				var bonus_point = 0;
				if(typeof rs !== 'undefined' && rs.length > 0){
					bonus_point = 0.1 * parseFloat(rs[0].total);
				}
				console.log('PRO BONUS',game_team_id,'bonus : ',bonus_point);
				cb(err,bonus_point,bonus_type);
				
			});
		},
		function(bonus_point,bonus_type,cb){
			conn.query("SELECT game_id FROM "+config.database.database+".game_fixtures \
						WHERE (home_id=? OR away_id=?) AND matchday = ? LIMIT 1",
						[original_team_id,original_team_id,matchday],function(err,rs){
				console.log('PRO BONUS',game_team_id,S(this.sql).collapseWhitespace().s);
				
				cb(err,rs[0].game_id,bonus_point,bonus_type);
			});
		},
		function(game_id,bonus_point,bonus_type,cb){
			var bonus_point = Math.abs(bonus_point); //the point must be positive.
			conn.query("INSERT IGNORE INTO "+config.database.statsdb+".game_team_extra_points\
						(game_id,matchday,game_team_id,modifier_name,extra_points)\
						VALUES\
						(?,?,?,?,?)\
						ON DUPLICATE KEY UPDATE\
						extra_points = VALUES(extra_points)",[
							game_id,
							matchday,
							game_team_id,
							'bonus pro50',
							bonus_point
						],function(err,rs){
							console.log('PRO BONUS',game_team_id,S(this.sql).collapseWhitespace().s);
							cb(err,game_id,bonus_point,bonus_type);
						});
		},
		function(game_id,bonus_point,bonus_type,cb){
			conn.query("INSERT IGNORE INTO "+config.database.database+".pro_booster\
						(game_team_id,matchday,booster_type,bonus_points)\
						VALUES\
						(?,?,?,?)\
						ON DUPLICATE KEY UPDATE\
						bonus_points = VALUES(bonus_points),\
						booster_type=VALUES(booster_type)",[
							game_team_id,
							matchday,
							point_type[bonus_type],
							bonus_point
						],function(err,rs){
							console.log('PRO BONUS',game_team_id,S(this.sql).collapseWhitespace().s);
							cb(err,rs);
						});
		}
	],
	function(err,rs){
		done(err);
	});
}
function updatePoints(conn,team,stats,done){
	console.log('update_rank','updatePoints',team,stats);
	async.waterfall([
		function(cb){
			if(typeof stats !== 'undefined'){
				var points = (typeof stats.points !== 'undefined') ? stats.points : 0;
				var extra_points = (typeof stats.extra_points !== 'undefined') ? stats.extra_points : 0;

				if(points == null){
					points = 0;
				}
				if(extra_points == null){
					extra_points = 0;
				}
				//update overall points
				if(team.team_id > 0){
					console.log(team.team_id,' overall points.');
					conn.query("INSERT INTO "+frontend_schema+".points\
						          (team_id,points,extra_points,league)\
						          VALUES\
						          (?,?,?,?)\
						          ON DUPLICATE KEY UPDATE\
						          points = VALUES(points),\
						          extra_points = VALUES(extra_points);",
						          [team.team_id,points,extra_points,league],
						          function(err,rs){
						          	console.log('update_rank',S(this.sql).collapseWhitespace().s);
						          	cb(err);
						          });
				}else{
					cb(null);
				}
			}else{
				console.log(team.team_id,' has no stats yet');
				cb(null);
			}
			
		},
		function(cb){
			if(typeof stats !== 'undefined'){
				updateWeeklyPoints(conn,team.team_id,stats.game_points,function(err){
					cb(err,stats.game_points);
				});	
			}else{
				cb(null,null);
			}
		},
		function(game_points,cb){
			conn.query("SELECT * FROM "+config.database.database+".game_matchstats_modifier Modifier LIMIT 100",
						[],
						function(err,rs){
							var modifier = [];
							for(var i in rs){
								modifier[rs[i].name] = {
									goalkeeper: rs[i].g,
									defender: rs[i].d,
									midfielder: rs[i].m,
									forward: rs[i].f
								}
							}
							cb(err,modifier);
			});
		},
		function(modifier,cb){
			//di matiin dulu, kita liat klo ini di skip, lebih kenceng gak.
			/*generate_summary(conn,team,modifier,function(err){
				console.log('generate summary for team #',team.team_id,' -> DONE');
				cb(err,null);
			});*/
			cb(null,null);
		}
	],
	function(err,rs){
		done(err);
	});
}

function updateWeeklyPoints(conn,team_id,game_points,done){
	var sql = "INSERT INTO "+frontend_schema+".weekly_points\
	                (team_id,game_id,matchday,matchdate,points,extra_points,league,t_month,t_year)\
	                VALUES ?";
	try{
		if(game_points.length > 0){
			var bulks = [];
			for(var i in game_points){
				var params = [];
				var weekly = game_points[i];
				params.push(team_id);
				console.log(weekly);
				params.push(weekly.game_id);
				params.push(weekly.matchday);
				params.push(weekly.match_date);
				params.push(weekly.total_points);
				params.push(weekly.extra_points);
				params.push(league);
				params.push((new Date(weekly.match_date)).getMonth()+1);
				params.push((new Date(weekly.match_date)).getFullYear());
				bulks.push(params);
			}
			console.log(bulks);
			sql+=" \
				 ON DUPLICATE KEY UPDATE\
		        points = VALUES(points),\
		        matchdate = VALUES(matchdate),\
		        extra_points = VALUES(extra_points);";
			
				conn.query(sql,
			                [bulks],
			                function(err,rs){
			                	console.log('weekly_points',S(this.sql).collapseWhitespace().s);
			                	console.log("updating #",team_id," week #",weekly.matchday,'--->',weekly.total_points);
			                	done(err);
			                });
		}else{
			done(null);
		}

	}catch(e){
		console.log('error',e.message,'team#',team_id,game_points,'no weekly points for these team');
		done(null);
	}
	
}


function getUserTeamPoints(conn,fb_id,matchday,done){
	
	async.waterfall(
		[	
			function(callback){
				//get overall points
				conn.query("SELECT a.fb_id,b.user_id,b.id,b.team_id,c.points,0 as extra_points \
							FROM "+config.database.database+".game_users a\
							INNER JOIN "+config.database.database+".game_teams b\
							ON a.id = b.user_id\
							LEFT JOIN "+config.database.statsdb+".game_team_points c\
							ON b.id = c.game_team_id\
							WHERE a.fb_id = ?;",
							[fb_id],
							function(err,rs){
								console.log('getUserTeamPoints','1',S(this.sql).collapseWhitespace().s);
								if(rs!=null){
									callback(null,rs[0]);
								}else{
									callback(null,null);
								}
							});
				
			},
			function(rs,callback){
				if(rs!=null&&rs.id!=null){
					var original_team_id = rs.team_id;
					//extra points
					conn.query("SELECT SUM(extra_points) AS extra_point \
								FROM "+config.database.statsdb+".game_team_extra_points \
								WHERE game_team_id=?;",[rs.id],function(err,r){
									console.log('getUserTeamPoints','2',S(this.sql).collapseWhitespace().s);
									if(!err){
										if(r!=null){
											//rs.points += r[0].extra_point;

											rs.extra_points = r[0].extra_point;
										}
									}else{
										console.log('getUserTeamPoints','3',S(this.sql).collapseWhitespace().s);
									}
									callback(err,original_team_id,rs);
					});
				}else{
					callback(null,original_team_id,rs);
				}
			},
			function(original_team_id,rs,callback){
				console.log('getUserTeamPoints','2a',original_team_id,rs);
				if(rs!=null){
					//get per game stats
					if(rs.id!=null){
						conn.query("SELECT a.game_id,SUM(a.points) AS total_points,\
									0 AS extra_points,\
									a.matchday,b.match_date\
									FROM \
									"+config.database.statsdb+".game_team_player_weekly a\
									INNER JOIN "+config.database.database+".game_fixtures b\
									ON a.game_id = b.game_id\
									WHERE a.game_team_id = ?\
									AND a.matchday = ?\
									GROUP BY a.game_id LIMIT 400;",
									[rs.id,matchday],
									function(err,result){
										console.log('getUserTeamPoints','3',S(this.sql).collapseWhitespace().s);
										rs.game_points = result;
										callback(null,original_team_id,rs);
									});
					}
				}else{
					callback(null,original_team_id,rs);
				}
			},
			function(original_team_id,rs,callback){

				var matchdays = {};
				console.log('all game_points',rs.game_points);
				//matchdays[8] = 1;//hard code for matchday 8 bug solution

				if(rs!=null && typeof rs.game_points !== 'undefined'){
					for(var i in rs.game_points){
						matchdays[rs.game_points[i].matchday] = 1;
					}
					var week = [];
					
					
					
					for(var i in matchdays){
						week.push(i);
					}
					

					console.log(week);
					//we do these logic to make sure that unplayed match will have a default data 
					//(all stats will be temporarily 0 for those match until the match is played) 
					conn.query("SELECT game_id,matchday,match_date \
								FROM "+config.database.database+".game_fixtures WHERE matchday IN (?) AND (home_id = ? OR away_id=?)"
								,[week,original_team_id,original_team_id],
								function(err,matches){
									console.log('getUserTeamPoints','4',S(this.sql).collapseWhitespace().s);
									console.log('update_rank',S(this.sql).collapseWhitespace().s);
									console.log(matches);
									if(typeof rs.game_points!=='undefined' && matches!=null){
										//console.log(rs.game_points);
										var other_games = [];
										for(var i in matches){
											var is_found = false;
											for(var j in rs.game_points){
												//console.log(matches[i].game_id+'--'+rs.game_points[j].game_id);
												if(rs.game_points[j].game_id == matches[i].game_id){
													is_found = true;
													console.log(matches[i].game_id+'--'+rs.game_points[j].game_id,'-->found');
													break;
												}else{
													console.log(matches[i].game_id+'--'+rs.game_points[j].game_id,'-->not found');
												}
											}
											if(!is_found){
												try{
													other_games.push({
														game_id:matches[i].game_id,
														matchday:matches[i].matchday,
														match_date:matches[i].match_date,
														total_points:0,
														extra_points:0
													});	
												}catch(e){
													console.log("ERROR","cannot push to other_games[]");
												}
												
											}
										}
										console.log(other_games);
										while(other_games.length>0){
											console.log('PUSH OTHER GAMES');
											rs.game_points.push(other_games.shift());
										}
									}else if(rs!=null && matches!=null){
										rs.game_points = [];	
										while(other_games.length>0){
											rs.game_points.push(other_games.shift());
										}
									}
									console.log('update_rank','current_game_points',rs.game_points);
									callback(err,rs);
								});
				}else{
					callback(null,rs);
				}
				
			},
			function(rs,callback){
				//extra weekly points
				if(rs!=null&&rs.id!=null){
					//extra points
					conn.query("SELECT game_id,SUM(extra_points) AS extra \
									FROM "+config.database.statsdb+".game_team_extra_points \
									WHERE game_team_id=? GROUP BY game_id LIMIT 400",
									[rs.id],function(err,r){
										console.log('getUserTeamPoints','5',S(this.sql).collapseWhitespace().s);
									if(!err){
										console.log(S(this.sql).collapseWhitespace().s);
										if(r!=null){
											for(var i in rs.game_points){
												for(var j in r){
													if(rs.game_points[i].game_id==r[j].game_id){
														console.log(rs.game_points[i].game_id,'->',r[j].extra);
														//rs.game_points[i].total_points += r[j].extra;
														rs.game_points[i].extra_points = r[j].extra;
														break;
													}
												}
											}
										}
									}
									callback(err,rs);
					});
				}else{
					callback(null,rs);
				}
			}
		],
		function(err,result){
			console.log('update_rank','RESULT',result);
			console.log('update_result',result);
			done(err,result);	
		}
	);
	
}
function getStatsCategories(){
   games = {
      'game_started':'game_started',
      'sub_on':'total_sub_on'
  };

  passing_and_attacking = {
          'Freekick_Goal':'att_freekick_goal',
          'Goal_inside_the_box':'att_ibox_goal',
          'Goal_Outside_the_Box':'att_obox_goal',
          'Penalty_Goal':'att_pen_goal',
          'Freekick_Shots':'att_freekick_post',
          'On_Target_Scoring_Attempt':'ontarget_scoring_att',
          'Shot_From_Outside_the_Box':'att_obox_target',
          'big_chance_created':'big_chance_created',
          'big_chance_scored':'big_chance_scored',
          'goal_assist':'goal_assist',
          'total_assist_attempt':'total_att_assist',
          'Second_Goal_Assist':'second_goal_assist',
          'final_third_entries':'final_third_entries',
          'fouled_final_third':'fouled_final_third',
          'pen_area_entries':'pen_area_entries',
          'won_contest':'won_contest',
          'won_corners':'won_corners',
          'penalty_won':'penalty_won',
          'last_man_contest':'last_man_contest',
          'accurate_corners_intobox':'accurate_corners_intobox',
          'accurate_cross_nocorner':'accurate_cross_nocorner',
          'accurate_freekick_cross':'accurate_freekick_cross',
          'accurate_launches':'accurate_launches',
          'long_pass_own_to_opp_success':'long_pass_own_to_opp_success',
          'successful_final_third_passes':'successful_final_third_passes',
          'accurate_flick_on':'accurate_flick_on'
      };


  defending = {
          'aerial_won':'aerial_won',
          'ball_recovery':'ball_recovery',
          'duel_won':'duel_won',
          'effective_blocked_cross':'effective_blocked_cross',
          'effective_clearance':'effective_clearance',
          'effective_head_clearance':'effective_head_clearance',
          'interceptions_in_box':'interceptions_in_box',
          'interception_won' : 'interception_won',
          'possession_won_def_3rd' : 'poss_won_def_3rd',
          'possession_won_mid_3rd' : 'poss_won_mid_3rd',
          'possession_won_att_3rd' : 'poss_won_att_3rd',
          'won_tackle' : 'won_tackle',
          'offside_provoked' : 'offside_provoked',
          'last_man_tackle' : 'last_man_tackle',
          'outfielder_block' : 'outfielder_block'
      };

  goalkeeper = {
                  'dive_catch': 'dive_catch',
                  'dive_save': 'dive_save',
                  'stand_catch': 'stand_catch',
                  'stand_save': 'stand_save',
                  'cross_not_claimed': 'cross_not_claimed',
                  'good_high_claim': 'good_high_claim',
                  'punches': 'punches',
                  'good_one_on_one': 'good_one_on_one',
                  'accurate_keeper_sweeper': 'accurate_keeper_sweeper',
                  'gk_smother': 'gk_smother',
                  'saves': 'saves',
                  'goals_conceded':'goals_conceded'
                      };


  mistakes_and_errors = {
              'penalty_conceded':'penalty_conceded',
              'red_card':'red_card',
              'yellow_card':'yellow_card',
              'challenge_lost':'challenge_lost',
              'dispossessed':'dispossessed',
              'fouls':'fouls',
              'overrun':'overrun',
              'total_offside':'total_offside',
              'unsuccessful_touch':'unsuccessful_touch',
              'error_lead_to_shot':'error_lead_to_shot',
              'error_lead_to_goal':'error_lead_to_goal'
              };
  map = {'games':games,
                'passing_and_attacking':passing_and_attacking,
                'defending':defending,
                'goalkeeping':goalkeeper,
                'mistakes_and_errors':mistakes_and_errors
               };

  return map;
}
function is_in_category(map,category,stats_name){
	for(var n in map[category]){
		var v = map[category][n];
		if(v==stats_name){
			return true;
		}
	}
}
function implode (glue, pieces) {
  // http://kevin.vanzonneveld.net
  // +   original by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
  // +   improved by: Waldo Malqui Silva
  // +   improved by: Itsacon (http://www.itsacon.net/)
  // +   bugfixed by: Brett Zamir (http://brett-zamir.me)
  // *     example 1: implode(' ', ['Kevin', 'van', 'Zonneveld']);
  // *     returns 1: 'Kevin van Zonneveld'
  // *     example 2: implode(' ', {first:'Kevin', last: 'van Zonneveld'});
  // *     returns 2: 'Kevin van Zonneveld'
  var i = '',
    retVal = '',
    tGlue = '';
  if (arguments.length === 1) {
    pieces = glue;
    glue = '';
  }
  if (typeof pieces === 'object') {
    if (Object.prototype.toString.call(pieces) === '[object Array]') {
      return pieces.join(glue);
    }
    for (i in pieces) {
      retVal += tGlue + pieces[i];
      tGlue = glue;
    }
    return retVal;
  }
  return pieces;
}
function generate_summary(conn,user,modifier,done){
	async.waterfall([
		function(callback){
			conn.query("SELECT GameTeam.id AS game_team_id FROM "+config.database.database+".game_users GameUser\
                              INNER JOIN "+config.database.database+".game_teams GameTeam\
                              ON GameTeam.user_id = GameUser.id\
                              WHERE GameUser.fb_id = ? LIMIT 1",
                          [user.fb_id],function(err,rs){
                          	console.log(S(this.sql).collapseWhitespace().s);
	                          try{
	                          	callback(err,rs[0].game_team_id);
	                          }catch(e){
	                          	callback(e,0);
	                          }
                          	
                          });
		},
		function(game_team_id,callback){
			console.log('Game-team_id',game_team_id);
			if(game_team_id!=null){
				conn.query("SELECT SUM(start_budget+transactions) AS balance FROM \
                                (SELECT budget AS start_budget,0 AS transactions\
                                FROM "+config.database.database+".game_team_purse WHERE game_team_id=? LIMIT 1\
                                UNION ALL\
                                SELECT 0,SUM(amount) AS transactions\
                                FROM "+config.database.database+".game_team_expenditures\
                                WHERE game_team_id=?\
                                ) Finance;",
						[game_team_id,game_team_id],
						function(err,rs){
							//console.log(S(this.sql).collapseWhitespace().s);
							//console.log(rs);
							callback(err,game_team_id,rs[0].balance);
						});
			}else{
				callback(new Error('no game_team_id found'),0,0);
			}
			
		},
		function(game_team_id,money,callback){
			conn.query("SELECT game_team_id,COUNT(id) AS total \
                                  FROM "+config.database.database+".game_transfer_history \
                                  WHERE game_team_id = ?\
                                  AND transfer_type=1 LIMIT 10;",
                                  [game_team_id],
                                  function(err,rs){
                                  //	console.log(S(this.sql).collapseWhitespace().s);
                                  //	console.log(rs);
                                  	callback(err,game_team_id,money,rs[0].total);
                                  });
		},
		function(game_team_id,money,import_player_counts,callback){
			getStatsGroupValues(conn,game_team_id,modifier,function(err,rs){
				callback(err,game_team_id,money,import_player_counts,rs);
			});
		},
		function(game_team_id,money,import_player_counts,statsgroup,callback){
			conn.query("INSERT INTO "+frontend_schema+".team_summary\
                      (game_team_id,money,import_player_counts,games,passing_and_attacking,\
                      	defending,goalkeeping,mistakes_and_errors,last_update,league)\
                      VALUES\
                      (?,\
                        ?,\
                        ?,\
                        ?,\
                        ?,\
                        ?,\
                        ?,\
                        ?,\
                        NOW(),?)\
                      ON DUPLICATE KEY UPDATE\
                      money = VALUES(money),\
                      import_player_counts = VALUES(import_player_counts),\
                      games = VALUES(games),\
                      passing_and_attacking = VALUES(passing_and_attacking),\
                      defending = VALUES(defending),\
                      goalkeeping = VALUES(goalkeeping),\
                      mistakes_and_errors = VALUES(mistakes_and_errors),\
                      last_update = VALUES(last_update);",
                      [game_team_id,money,import_player_counts,statsgroup.games,statsgroup.passing_and_attacking,
                      statsgroup.defending,statsgroup.goalkeeper,statsgroup.mistakes_and_errors,league],
                      function(err,rs){
                      	console.log(S(this.sql).collapseWhitespace().s);
                      	callback(err,rs);

			});
		}
	],
	function(err,rs){
		done(err,rs);
	});
}

function give_weekly_cash(since_id,until_id,matchday,conn,done){
	console.log('GIVING WEEKLY CASH');

	conn.query("SELECT a.id AS game_team_id,b.fb_id \
				FROM "+config.database.database+".game_teams a\
				INNER JOIN "+config.database.database+".game_users b\
				ON a.user_id = b.id\
				WHERE a.id BETWEEN ? AND ?\
				ORDER BY a.id ASC\
				LIMIT 100;",[since_id,until_id],function(err,rs){
					console.log('CASH',S(this.sql).collapseWhitespace().s);
					if(rs!=null && rs.length > 0){
						distribute_weekly_cash(conn,rs,matchday,function(err){
							done(err);
						});
					}else{
						console.log('CASH','nothing to distribute');
						done(err);
					}
				});
	
}

// 02/01/2014
// jumlah koin selalu 1 poin lebih besar daripada poin.
// ini karena kita round. jadi otomatis ke atas ternyata.
// jadinya kita set pembulatan ke bawah saja.

//we no longer last_matchday for the moment.
function distribute_weekly_cash(conn,teams,matchday,done){
	async.waterfall([
		function(cb){
			//get the latest matchday
			conn.query("SELECT matchday \
						FROM "+config.database.statsdb+".game_team_player_weekly \
						ORDER BY id DESC LIMIT 1",
						[],
			function(err,rs){
				console.log('CASH',S(this.sql).collapseWhitespace().s);
				//cb(err,rs[0].matchday);
				cb(err,matchday); //ganti pake matchday sesuai game_id dulu.
			});
		},
		function(last_matchday,cb){
			async.eachSeries(teams,function(team,next){
				//process each team
				async.waterfall([
					function(c){
						//get the week total points + extra points
						conn.query("SELECT matchday,SUM(points + extra_points) AS total_points \
									FROM "+frontend_schema+".users a\
									INNER JOIN "+frontend_schema+".teams b\
									ON a.id = b.user_id\
									INNER JOIN "+frontend_schema+".weekly_points c\
									ON b.id = c.team_id\
									WHERE a.fb_id= ? AND b.league = ?\
									AND c.matchday = ?;",
									[team.fb_id,league,last_matchday],
									function(err,rs){
										console.log('CASH',S(this.sql).collapseWhitespace().s);
										if(rs!=null && rs.length == 1){
											c(err,rs[0]);	
										}else{
											c(err,{total_points:0});
										}
										
									});
					},
					function(points,c){
						var cash_bonus = 1.0;
						console.log('Weekly_cash','adding #',team.game_team_id,' matchday#',last_matchday,
									 'points:',points.total_points, 'cash bonus : ',cash_bonus);

						if(points.total_points == null || points.total_points < 0){
							points.total_points = 0;
						}
						//adding cash
						cash.adding_cash(conn,
							team.fb_id,
							team.game_team_id+'_matchday_'+last_matchday,
							Math.floor(parseFloat(points.total_points) * cash_bonus),
							'weekly cash',
							function(err,rs){
								if(err){
									console.log(team.game_team_id,'--->',err);
								}
								c(null);
							}
						);
					},
					function(c){
						console.log('Weekly_cash','updating #',team.game_team_id,' matchday#',last_matchday);
						//updating the team's cash
						cash.update_cash_summary(conn,team.fb_id,function(err,rs){
							c(err);
						});
					}
				],
				function(err){
					next();
				});
			},function(err){
				cb(err);
			});
		}
	],
	function(err){
		done(err);
	});
}
function getStatsGroupValues(conn,game_team_id,modifier,done){

    var stats = {
    		games:0,
    		passing_and_attacking:0,
    		defending:0,
    		goalkeeper:0,
    		mistakes_and_errors:0
    	}

    conn.query("SELECT stats_category,SUM(points) as total\
    			FROM "+config.database.statsdb+".game_team_player_weekly \
    			WHERE game_team_id = ? GROUP BY stats_category;",
    			[game_team_id],
    			function(err,rs){
    				if(rs!=null&&rs.length>0){
    					for(var i in rs){
    						stats[rs[i].stats_category] = rs[i].total;
    					}	
    				}
    				done(err,stats);				
    			});
    
    /*
    async.waterfall([
    	function(callback){//step 1 dapetin game_id
    		conn.query("SELECT game_id FROM "+config.database.statsdb+".game_match_player_points a\
                                        WHERE game_team_id=? GROUP BY game_id;",
                        [game_team_id],function(err,rs){
                        		var game_id = [];
					    		for(var i in rs){
					    			game_id.push(rs[i].game_id);
					    		}
					    		callback(null,game_id);
                        });
    	},
    	
    	function(game_id,callback){//step 2 dapetin daftar players
    		conn.query("SELECT player_id FROM "+config.database.statsdb+".game_match_player_points bb\
                                          WHERE bb.game_team_id=? GROUP BY player_id;",
                        [game_team_id],function(err,rs){
                        	var players = [];
				    		for(var i in rs){
				    			players.push(rs[i].player_id);
				    		}
				    		callback(null,players,game_id);
                        });
    	},
    	function(player,game_id,callback){
    		var has_data = true;
    		var st = 0;
    		
    		async.doWhilst(
				function(callback){
					conn.query("SELECT a.stats_name,SUM(a.stats_value) AS frequency,\
								SUM(a.points) AS total_points,\
								b.position \
                              FROM "+config.database.statsdb+".game_team_player_weekly a\
                              INNER JOIN "+config.database.database+".master_player b\
                              ON a.player_id = b.uid\
                              WHERE a.game_id IN (?) AND a.player_id IN (?)\
                              AND a.game_team_id = ?\
                              GROUP BY a.stats_name,a.player_id\
                              LIMIT ?,100;",
						[(game_id),(player),game_team_id,st],
						function(err,rs){
							console.log(S(this.sql).collapseWhitespace().s);
							if(rs!=null && rs.length>0){
								 for(j in rs ){
								 	var r = rs[j];
				                    var stats_name = r.stats_name;
				                    var pos = r.position.toLowerCase();
				                    var poin = r.total_points;
				                    if(is_in_category(map,'games',stats_name)){
				                      games += poin;
				                    }
				                    if(is_in_category(map,'passing_and_attacking',stats_name)){
				                      passing_and_attacking += poin;
				                    }
				                    if(is_in_category(map,'defending',stats_name)){
				                      defending += poin;
				                    }
				                    if(is_in_category(map,'goalkeeping',stats_name)){
				                      goalkeeping += poin;
				                    }
				                    if(is_in_category(map,'mistakes_and_errors',stats_name)){
				                      mistakes_and_errors += poin;
				                    }
				                    
				                  }
									st+=100;
									callback();
									
							}else{
								has_data=false;
								callback();
							}
					});
				}, function(){
					return has_data;
				}, function(err){
				callback(err,null);
			});
    	}
    ],
    function(err,rs){
    	done(err,{
    		games:games,
    		passing_and_attacking:passing_and_attacking,
    		defending:defending,
    		goalkeeping:goalkeeping,
    		mistakes_and_errors:mistakes_and_errors
    	});
    });*/
}
