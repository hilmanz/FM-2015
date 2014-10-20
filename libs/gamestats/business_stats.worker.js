/**
* module for updating the business part of the game.
* includings : 
* - post-match incomes / expenses.
* - salaries
* - bonus calculations
* Make sure each team is not processed twice.
* please notes that the stadium income is for home team only.
* away team only get sponsorship income + sponsor_bonus, also calculate the salary only.

 @TODO : 
 - calculate away team.
  so we need to create dummy away-team first.
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
var mysql = require('mysql');
var S = require('string');
var pool  = {};
var punishment = require(path.resolve('./libs/gamestats/punishment_rules'));
var frontend_schema = "";


var league = 'epl';
exports.setLeague = function(l){
	league = l;
}
exports.setConfig = function(c){
	config = c;
	pool = mysql.createPool({
	   host     : config.database.host,
	   user     : config.database.username,
	   password : config.database.password,
	});
	punishment.setConfig(config);
	frontend_schema = config.database.frontend_schema;
}



var total_teams = 0;
console.log('business_stats : pool opened');
exports.update = function(since_id,until_id,game_id,done){
	
	async.waterfall(
		[
			function(callback){
				//get total teams participated
				pool.getConnection(function(err,conn){
					console.log('open connection');
					conn.query("SELECT COUNT(*) as total FROM "+config.database.database+".game_users",
								[],
								function(err,rs){
										conn.release();
										total_teams = rs[0].total;
										console.log('total_teams',total_teams);
										callback(err);
										
								});
				});
			},
			function(callback){
				getGameFixture(game_id,function(err,game){
					callback(err,game);
				});
			},
			function(game,callback){
				getTeamProfile(game[0],function(err,team){
					callback(null,game,team.home,team.away);
				});
			},
			function(game,home_team,away_team,callback){
				
				async.parallel([
						function(callback){
							calculateIncomeForAllHomeTeams(since_id,until_id,game_id,game,home_team,away_team,
								function(err,team_updated){
									console.log('all home team is done');
									callback(err,team_updated);
								});
						},
						function(callback){
							calculateIncomeForAllAwayTeams(since_id,until_id,game_id,game,home_team,away_team,
								function(err,team_updated){
									console.log('all away team is done');
									callback(err,team_updated);
								});
						}
					],
				function(err,result){
						callback(err,result);
				});
			}
		],
		function(err,result){
			
			done(null);			
		
			
		}

	);
	
}
exports.done = function(){
	pool.end(function(err){
		if(err) console.log('business_stats','error',err.message);
		console.log('business_stats','pool closed');
	});
}
function calculateIncomeForAllHomeTeams(since_id,until_id,game_id,game,home_team,away_team,done){
	console.log('calculate all home teams');
	var limit = 100;
	var start = 0;
	try{
		var team_id = home_team[0].uid;
		var away_team_id = away_team[0].uid;
		var home_rank = 0;
		var away_rank = 0;

		pool.getConnection(function(err,conn){
			console.log('getting the team\'s rank');
			async.waterfall(
			[
				function(callback){
					//getting home rank
					conn.query("SELECT t_position AS rank FROM "+config.database.database+".master_standings WHERE team_id=? LIMIT 1;",
							   	[team_id],
								   function(err,rs){
								   		if(!err){
								   			home_rank = rs[0].rank;
								   		}else{
								   			home_rank = 0;
								   		}
								   		callback(err,rs);
								   }
							   );
				},
				function(rs,callback){
					//getting away rank
					conn.query("SELECT t_position AS rank FROM "+config.database.database+".master_standings WHERE team_id=? LIMIT 1;",
							   	[away_team_id],
								   function(err,rs){
								   		if(!err){
								   			away_rank = rs[0].rank;
								   		}else{
								   			away_rank = 0;
								   		}
								   		callback(err,rs);
								   }
							   );
				}
				
			],
			function(err,result){
				conn.release();
				console.log('home_rank',home_rank);
				console.log('AWAY RANK',away_rank);
				processHomeTeams(since_id,until_id,start,limit,team_id,game_id,home_rank,away_rank,game,done);
				
			});
		});
	}catch(e){
		done(null,[]);
	}
	
}
function calculateIncomeForAllAwayTeams(since_id,until_id,game_id,game,home_team,away_team,done){
	console.log('calculate all away teams');

	try{
		var limit = 100;
		var start = 0;
		var team_id = away_team[0].uid;
		processAwayTeams(since_id,until_id,start,limit,team_id,game_id,0,0,game,done);
	}catch(e){
		done(null,[]);
	}
	
	
	
	
}
function processHomeTeams(since_id,until_id,start,limit,team_id,game_id,rank,away_rank,game,done){
	
	pool.getConnection(function(err,conn){
		console.log('open connection, processing home team');
		conn.query("SELECT a.*,e.rank FROM "+config.database.database+".game_teams a\
					INNER JOIN "+config.database.database+".game_users b\
					ON a.user_id = b.id\
					INNER JOIN "+frontend_schema+".users c\
					ON c.fb_id = b.fb_id\
					INNER JOIN "+frontend_schema+".teams d\
					ON d.user_id = c.id\
					LEFT JOIN\
					"+frontend_schema+".points e\
					ON e.team_id = d.id\
					WHERE a.team_id = ? AND a.n_status=1 AND a.id BETWEEN ? AND ? LIMIT ?,?;",
					[team_id,since_id,until_id,start,limit],
					function(err,rs){
						console.log(S(this.sql).collapseWhitespace().s);
							conn.release();
								console.log('processing each home teams');
								async.eachSeries(rs,
									function(team,callback){
										async.waterfall([
											function(cb){
												//no need to calculate the home revenue in brazil edition
												calculate_home_revenue_stats(team,game_id,game,rank,away_rank,function(err){
													cb(err);
												});
												
											},
											function(cb){
												punishment.execute_punishment(conn,
																			game_id,team.id,team.team_id,
																			function(err,rs){
																				cb(err,rs);	
																			});
											}
										],
										function(err,r){
											callback();
										});
										
									},function(err){
										if(rs.length==limit){
											processHomeTeams(since_id,until_id,start+100,limit,team_id,game_id,rank,away_rank,game,done)
										}else{
											done(err,[]);
										}	
								});
								
							
					});
	});
}
function processAwayTeams(since_id,until_id,start,limit,team_id,game_id,rank,away_rank,game,done){
	pool.getConnection(function(err,conn){
		console.log('open connection, processing away team');
		conn.query("SELECT a.*,e.rank FROM "+config.database.database+".game_teams a\
					INNER JOIN "+config.database.database+".game_users b\
					ON a.user_id = b.id\
					INNER JOIN "+frontend_schema+".users c\
					ON c.fb_id = b.fb_id\
					INNER JOIN "+frontend_schema+".teams d\
					ON d.user_id = c.id\
					LEFT JOIN\
					"+frontend_schema+".points e\
					ON e.team_id = d.id\
					WHERE a.team_id = ? AND a.n_status=1 AND a.id BETWEEN ? AND ? LIMIT ?,?;",
					[team_id,since_id,until_id,start,limit],
					function(err,rs){
							console.log(S(this.sql).collapseWhitespace().s);
							conn.release();
								
								console.log('processing each away teams');
								async.eachSeries(rs,
									function(team,callback){
										async.waterfall([
											function(cb){
												//brazil edition - no need to calculate away revenue
												
												calculate_away_revenue_stats(team,game_id,game,rank,away_rank,function(err){
													cb(err);		
												});
												
											},
											function(cb){
												punishment.execute_punishment(conn,
																			game_id,team.id,team.team_id,
																			function(err,rs){
																				cb(err,rs);	
																			});
											}
										],
										function(err,r){
											callback();
										});

									},function(err){
										if(rs.length==limit){
											processAwayTeams(since_id,until_id,start+100,limit,team_id,game_id,rank,away_rank,game,done)
										}else{
											done(err,[]);
										}	
								});
								
								
					});
	});
}
/**
* calculate home revenues.
* 1. calculate revenue from tickets + its bonuses
* 2. calculate revenue from sponsors
* 3. calculate expenses on salaries
* 4. calculate winning bonuses and user points bonuses (ini blm ada datanya)
*/
function calculate_home_revenue_stats(team,game_id,game,rank,away_rank,done){
	console.log('calculate_home_revenue_stats: team->',team);
	console.log('calculate_home_revenue_stats: ','away rank',away_rank);
	console.log('calculate_home_revenue_stats: ','game:',game);
	
	console.log('-----');
	var cashflow = [];
	console.log('match real attendance :',game[0].attendance);
	var attendance = game[0].attendance;

	if(team.rank==null){
		team.rank = 0;
	}
	var quadrant = getQuadrant(team.rank);
	var stadium_income_type = getStadiumIncome(away_rank);
	console.log('home team quadrant : ',quadrant);
	console.log('stadium_income_type : ',stadium_income_type);
	console.log('attendance',attendance);
	console.log(stadium_earnings[quadrant].price[stadium_income_type]);
	console.log(stadium_earnings[quadrant]);

	//var ticket_earnings = stadium_earnings[quadrant].price[stadium_income_type] * attendance;
	 						//stadium_earnings[quadrant].ratio[stadium_income_type];
							//console.log('stadium earnings : ',ticket_earnings);
	var earnings = [];
	var costs = [];
	pool.getConnection(function(err,conn){
		async.waterfall(
			[
				function(callback){
					//get the home stadium capacity
					conn.query("SELECT stadium_capacity FROM "+config.database.database+".master_team WHERE uid = ? LIMIT 1;",
							[team.team_id],
							function(err,rs){
								//console.log(this.sql);
								//console.log('rs',rs);
								try{
									attendance = rs[0].stadium_capacity;
								}catch(e){}
								console.log('the attendance : ',attendance);
								callback(err);
							});
				},
				function(callback){
					//get team's officials
					conn.query(
						"SELECT b.* FROM "+config.database.database+".game_team_officials a\
						INNER JOIN "+config.database.database+".game_officials b\
						ON a.official_id = b.id WHERE game_team_id=?\
						LIMIT 20;",
						[team.id],
						function(err,officials){

							callback(null,officials);
					});
				},
				function(officials,callback){
					conn.query("SELECT SUM(salary) AS salaries \
								FROM "+config.database.database+".game_team_players a\
								INNER JOIN "+config.database.database+".master_player b\
								ON a.player_id = b.uid\
								WHERE a.game_team_id = ?;",
								[team.id],
								function(err,rs){
									if(!err){
										callback(err,rs[0].salaries,officials);
									}else{
										callback(err,null,null);
									}
					});
				},
				function(player_salaries,officials,callback){
					console.log('officials',officials);
					//get 100% ticket guarantee from head of security.
					var official = getOfficial('Head of Security',officials);
					var final_attendance = attendance * stadium_earnings[quadrant].ratio[stadium_income_type];


					//1. TICKET EARNINGS
					var ticket_earnings = 0;
					if(official.attendance_bonus!=0){
						console.log('Head of Security bonus applied');
						//ticket_earnings = (attendance * official.attendance_bonus) *  stadium_earnings[quadrant].price[stadium_income_type];
						console.log('(',attendance,' x ',stadium_earnings[quadrant].ratio[stadium_income_type],') x',stadium_earnings[quadrant].price[stadium_income_type]);
						ticket_earnings = (attendance * stadium_earnings[quadrant].ratio[stadium_income_type]) *  stadium_earnings[quadrant].price[stadium_income_type];
					}else{
						console.log('head of security bonus not applied');
						//sementara di skip dulu penalty securitynya.
						//console.log('(',attendance,' x ',stadium_earnings[quadrant].ratio[stadium_income_type],') x',stadium_earnings[quadrant].price[stadium_income_type],' x 80% [income reduced by 20%]');
						//ticket_earnings = (attendance * stadium_earnings[quadrant].ratio[stadium_income_type]) *  stadium_earnings[quadrant].price[stadium_income_type] * 0.8;
						ticket_earnings = (attendance * stadium_earnings[quadrant].ratio[stadium_income_type]) *  stadium_earnings[quadrant].price[stadium_income_type];
						
					}
					earnings.push({name:'tickets_sold',value:ticket_earnings,total:final_attendance});
					console.log('ticket earnings ',ticket_earnings);


					//2. Earning Bonus from Commercial Director
					official = getOfficial('Commercial Director',officials);
					if(official!=null){
						console.log(ticket_earnings,'*',official.attendance_bonus);	
						earnings.push({name:'commercial_director_bonus',value:ticket_earnings*official.attendance_bonus,total:1});
					}

					//3. Earning Bonus from Marketing Manager
					official = getOfficial('Marketing Manager',officials);
					if(official!=null){
						console.log(ticket_earnings,'*',official.attendance_bonus);	
						earnings.push({name:'marketing_manager_bonus',value:ticket_earnings*official.attendance_bonus,total:1});
					}
					//4. Earning Bonus from Public Relation Officer
					official = getOfficial('Public Relation Officer',officials);
					if(official!=null){
						console.log(ticket_earnings,'*',official.attendance_bonus);	
						earnings.push({name:'public_relation_officer_bonus',value:ticket_earnings*official.attendance_bonus,total:1});
					}
					console.log('earnings : ',earnings);
					

					//5. operating costs
					var basic_cost = cost_mods.operating_cost * ticket_earnings;
					var op_cost = basic_cost;
					console.log(cost_mods.operating_cost,'*',ticket_earnings);
					console.log('basic_cost',basic_cost);

					//6. bonuses that reduce the costs.
					official = getOfficial('Finance Director',officials);
					if(official!=null){
						var deduct = basic_cost * official.op_cost_bonus;
						console.log(deduct);
						op_cost+=(basic_cost * official.op_cost_bonus);
					}
					official = getOfficial('Tax Consultant',officials);
					if(official!=null){
						var deduct = basic_cost * official.op_cost_bonus;
						console.log(deduct);
						op_cost+=(basic_cost * official.op_cost_bonus);
					}
					official = getOfficial('Accountant',officials);
					if(official!=null){
						var deduct = basic_cost * official.op_cost_bonus;
						console.log(deduct);
						op_cost+=(basic_cost * official.op_cost_bonus);
					}
					console.log('operational costs',op_cost);
					costs.push({name:'operating_cost',value:op_cost,total:1});

					//7. Player Salaries (to be added soon)
					console.log('player salaries : ',player_salaries);
					costs.push({name:'player_salaries',value:player_salaries,total:1});
					//8. Official Salaries
					for(var i in officials){
						console.log(officials[i].salary);
						costs.push({name:reformatName(officials[i].name),value:officials[i].salary,total:1});
					}
					console.log('costs',costs);
					callback(null,officials,earnings,costs);
				},
				function(officials,earnings,costs,callback){
					//sponsorship bonuses
					conn.query(
						"SELECT b.*,a.valid_for FROM "+config.database.database+".game_team_sponsors a\
							INNER JOIN "+config.database.database+".game_sponsorships b\
							ON a.sponsor_id = b.id\
							WHERE a.game_team_id = ? LIMIT 30",
						[team.id],
						function(err,sponsors){
							
							if(err){
								console.log(err.message);
							}
							callback(null,officials,earnings,costs,sponsors);
					});

				},
				function(officials,earnings,costs,sponsors,callback){
					//calculate the sponsorship bonus
					var sponsor_bonus = 0;
					for(var i in sponsors){
						sponsor_bonus += sponsors[i].value;
					}

					earnings.push({name:'sponsorship',value:sponsor_bonus,total:1});

					//winning bonuses
					if(game.home_score > game.away_score){
						console.log('home team is winning');
						earnings.push({name:'win_bonus',value:sponsor_bonus*0.01,total:1});
					}

					//bonuses from officials
					official = getOfficial('Commercial Director',officials);
					if(official!=null){
						earnings.push({name:'commercial_director_sponsor_bonus',value:sponsor_bonus*official.sponsor_bonus,total:1});
					}

					
					official = getOfficial('Marketing Manager',officials);
					if(official!=null){
						earnings.push({name:'marketing_manager_sponsor_bonus',value:sponsor_bonus*official.sponsor_bonus,total:1});
					}
					
					official = getOfficial('Public Relation Officer',officials);
					if(official!=null){
						
						earnings.push({name:'public_relation_officer_sponsor_bonus',value:sponsor_bonus*official.sponsor_bonus,total:1});
					}
					callback(null,{earnings:earnings,costs:costs});
				},
				function(match_money,callback){
					//save into database
					console.log('match money',match_money);
					var sql = "INSERT INTO "+config.database.database+".game_team_expenditures\
								(game_team_id,item_name,item_type,amount,game_id,match_day,item_total)\
								VALUES ";

					var vals = [];
					for(var i in match_money.earnings){
						if(i>0){
							sql+=',';
						}
						sql+="(?,?,?,?,?,?,?)";
						vals.push(team.id);
						vals.push(match_money.earnings[i].name);
						vals.push(1);
						vals.push(match_money.earnings[i].value);
						vals.push(game[0].game_id);
						vals.push(game[0].matchday);
						vals.push(match_money.earnings[i].total);
					}
					for(var j in match_money.costs){
						if(i>0||j>0){
							sql+=',';
						}
						sql+="(?,?,?,?,?,?,?)";
						vals.push(team.id);
						vals.push(match_money.costs[j].name);
						vals.push(2);
						vals.push(match_money.costs[j].value*-1);
						vals.push(game[0].game_id);
						vals.push(game[0].matchday);
						vals.push(match_money.costs[j].total);
					}
					sql+=" ON DUPLICATE KEY UPDATE\
						   amount = VALUES(amount),\
						   item_total = VALUES(item_total);";

					conn.query(sql,vals,function(err,rs){
						console.log(S(this.sql).collapseWhitespace().s);
						callback(null,'ok');
					});
					
				}
			],
			function(err,result){
				console.log('home calculation finished',result);
				conn.release();
				console.log('finished calculation');
				done(err,null);
				
			}
		);
		
		
	});
}
/**
* calculate away revenues.
* 1. revenues come from sponsor and winning bonus
* 2. expenses include player salaries and staff salaries
*/
function calculate_away_revenue_stats(team,game_id,game,rank,away_rank,done){
	console.log('calculating away revenue');
	console.log(team,rank);
	console.log('game:');
	console.log(game);
	console.log('-----');


	

	
	var earnings = [];
	var costs = [];
	pool.getConnection(function(err,conn){
		async.waterfall(
			[
				function(callback){
					//get team's officials
					conn.query(
						"SELECT b.* FROM "+config.database.database+".game_team_officials a\
						INNER JOIN "+config.database.database+".game_officials b\
						ON a.official_id = b.id WHERE game_team_id=?\
						LIMIT 20;",
						[team.id],
						function(err,officials){
							callback(null,officials);
					});
				},
				function(officials,callback){
					conn.query("SELECT SUM(salary) AS salaries \
								FROM "+config.database.database+".game_team_players a\
								INNER JOIN "+config.database.database+".master_player b\
								ON a.player_id = b.uid\
								WHERE a.game_team_id = ?;",
								[team.id],
								function(err,rs){
									if(!err){
										callback(err,rs[0].salaries,officials);
									}else{
										callback(err,null,null);
									}
					});
				},
				function(player_salaries,officials,callback){
					console.log(officials);

					//7. Player Salaries
					console.log('player salaries : ',player_salaries);
					costs.push({name:'player_salaries',value:player_salaries,total:1});
					//8. Official Salaries
					for(var i in officials){
						console.log(officials[i].salary);
						costs.push({name:reformatName(officials[i].name),value:officials[i].salary,total:1});
					}
					console.log('costs',costs);
					callback(null,officials,earnings,costs);
				},
				function(officials,earnings,costs,callback){
					//sponsorship bonuses
					conn.query(
						"SELECT b.*,a.valid_for FROM "+config.database.database+".game_team_sponsors a\
							INNER JOIN "+config.database.database+".game_sponsorships b\
							ON a.sponsor_id = b.id\
							WHERE a.game_team_id = ? LIMIT 30",
						[team.id],
						function(err,sponsors){
							
							if(err){
								console.log(err.message);
							}
							callback(null,officials,earnings,costs,sponsors);
					});

				},
				function(officials,earnings,costs,sponsors,callback){
					//calculate the sponsorship bonus
					var sponsor_bonus = 0;
					for(var i in sponsors){
						sponsor_bonus += sponsors[i].value;
					}

					earnings.push({name:'sponsorship',value:sponsor_bonus,total:1});

					//winning bonuses
					if(game.home_score < game.away_score){
						console.log('away team is winning');
						earnings.push({name:'win_bonus',value:sponsor_bonus*0.01,total:1});
					}

					//bonuses from officials
					official = getOfficial('Commercial Director',officials);
					if(official!=null){
						earnings.push({name:'commercial_director_sponsor_bonus',value:sponsor_bonus*official.sponsor_bonus,total:1});
					}

					
					official = getOfficial('Marketing Manager',officials);
					if(official!=null){
						earnings.push({name:'marketing_manager_sponsor_bonus',value:sponsor_bonus*official.sponsor_bonus,total:1});
					}
					
					official = getOfficial('Public Relation Officer',officials);
					if(official!=null){
						
						earnings.push({name:'public_relation_officer_sponsor_bonus',value:sponsor_bonus*official.sponsor_bonus,total:1});
					}
					callback(null,{earnings:earnings,costs:costs});
				},
				function(match_money,callback){
					//save into database
					console.log(match_money);
					var sql = "INSERT INTO "+config.database.database+".game_team_expenditures\
								(game_team_id,item_name,item_type,amount,game_id,match_day,item_total)\
								VALUES ";

					var vals = [];
					for(var i in match_money.earnings){
						if(i>0){
							sql+=',';
						}
						sql+="(?,?,?,?,?,?,?)";
						vals.push(team.id);
						vals.push(match_money.earnings[i].name);
						vals.push(1);
						vals.push(match_money.earnings[i].value);
						vals.push(game[0].game_id);
						vals.push(game[0].matchday);
						vals.push(match_money.earnings[i].total);
					}
					for(var j in match_money.costs){
						if(i>0||j>0){
							sql+=',';
						}
						sql+="(?,?,?,?,?,?,?)";
						vals.push(team.id);
						vals.push(match_money.costs[j].name);
						vals.push(2);
						vals.push(match_money.costs[j].value*-1);
						vals.push(game[0].game_id);
						vals.push(game[0].matchday);
						vals.push(match_money.costs[j].total);
					}
					sql+=" ON DUPLICATE KEY UPDATE\
						   amount = VALUES(amount),\
						   item_total = VALUES(item_total);";

					conn.query(sql,vals,function(err,rs){
						console.log(this.sql);
						callback(null,'ok');
					});
					
				}
			],
			function(err,result){
				console.log(result);
				conn.release();
				console.log('finished calculation');
				done(err,null);
					
			}
		);
		
		
	});
}
function reformatName(str){
	str = str.toLowerCase().split(' ').join('_');
	return str;
}
function isOfficialExists(official_name,officials){
	for(var i in officials){
		if(officials[i].name==official_name){
			return true;
		}
	}
}
function getOfficial(official_name,officials){
	for(var i in officials){
		if(officials[i].name==official_name){
			return officials[i];
		}
	}
	return {
		sponsor_bonus:0,
		attendance_bonus:0,
		op_cost_bonus:0,
		transfer_bonus:0
	};
}
/* Stadium Income
*  High = Against Top 3 Teams in EPL
*  Standard = Against 4 - 10 Teams in EPL
*  Low = Against 11 - Bottom Teams in EPL
*/
function getStadiumIncome(away_rank){
	if(away_rank<=3){
		return "high";
	}if(away_rank>3 && away_rank<=10){
		return "standard"
	}else{
		return "low";
	}
}
function getQuadrant(rank){
	if(rank==0){
		return 'q4';
	}
	var ratio = rank/total_teams;
	if(ratio<=0.25){
		return 'q1';
	}else if(ratio>0.25 && ratio<=0.5){
		return 'q2';
	}else if(ratio>0.5 && ratio<=0.75){
		return 'q3';
	}else{
		return 'q4';
	}
}
function getHomeTeams(team_id,start,limit,done){
	pool.getConnection(function(err,conn){
		console.log('open connection');
		conn.query("SELECT * FROM "+config.database.database+".game_fixtures WHERE game_id=?",
					[game_id],
					function(err,rs){
							conn.release();
							console.log('disconnected')
							done(err,rs);
							
					});
		
	});
}
function getGameFixture(game_id,done){
	console.log('get info for game_id #'+game_id);
	pool.getConnection(function(err,conn){
		console.log('open connection');
		conn.query("SELECT * FROM "+config.database.database+".game_fixtures WHERE game_id=?",
					[game_id],
					function(err,rs){
							console.log(S(this.sql).collapseWhitespace().s);
							conn.release();
							console.log('disconnected')
							done(err,rs);
						
					});
		
	});
}
function getTeamProfile(game,done){
	console.log(game);
	console.log('get team profile');
	var home,away;
	pool.getConnection(function(err,conn){
		async.parallel([
				function(callback){
					conn.query("SELECT b.*,a.rank FROM "+config.database.database+".master_rank a\
						INNER JOIN "+config.database.database+".master_team b\
						ON a.team_id = b.uid\
						WHERE a.team_id = ?\
						ORDER BY rank",
						[game.home_id],
						function(err,team){
							callback(err,team);	
						});
				},
				function(callback){
					conn.query("SELECT b.*,a.rank FROM "+config.database.database+".master_rank a\
						INNER JOIN "+config.database.database+".master_team b\
						ON a.team_id = b.uid\
						WHERE a.team_id = ?\
						ORDER BY rank",
						[game.away_id],
						function(err,team){
							callback(err,team);	
						});
				},
			],
			function(err,result){
				//console.log(result);
				conn.release();
				done(err,{home:result[0],away:result[1]});	
		});
		
			
	});
}