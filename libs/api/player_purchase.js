/**
* API for salary negotiation
* 2015/08/28
* authors
* @author hapsoro renaldy <hapsoro.renaldy@msp-entertainment.net>
*/
var config = {};


var fs = require('fs');
var path = require('path');
var xmlparser = require('xml2json');
var async = require('async');
var mysql = require('mysql');
var S = require('string');
var team_info = require(path.resolve('./libs/api/team_info'));
var game_config = require(path.resolve('./libs/game_config'));
var messages = require(path.resolve('./libs/messages'));
var notifications = require(path.resolve('./libs/api/notifications'));
var pool = {};
function prepareDb(callback){

	pool.getConnection(function(err,conn){
		callback(conn);
	});
	
}
exports.setConfig = function(c){
	config = c;
	team_info.setConfig(c);
	notifications.setConfig(c);
}
exports.save = function(conn,redisClient,game_team_id,player_id,data,league,done){
	console.log('finalize player purchase',game_team_id,player_id);
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
						conn.query("SELECT COUNT(*) AS total \
									FROM "+config.database.database+".game_transfer_history \
									WHERE tw_id=? AND game_team_id = ? AND player_id = ?;",
									[data.tw_id,game_team_id,player_id],
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
			console.log(data);
			if(can_transfer){
				callback(null,
						name,
						data.transfer.offer_price);
			}else{
				callback(new Error('INVALID_TRANSFER_WINDOW'),null,null);
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
								console.log('check money');
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
						//insert player into team's rooster
						if(has_money){
							conn.query(
								"INSERT INTO "+config.database.database+".game_team_players \
								 (game_team_id,player_id,salary,goal_bonus,cleansheet_bonus,position)\
								 VALUES(?,?,?,?,?,?);",
								 [game_team_id,player_id,data.offer_price,data.goal_bonus,
								 	data.cleansheet_bonus,data.player.position],
								 function(err,rs){
								 	console.log(S(this.sql).collapseWhitespace().s);
								 	console.log('inserted into rooster');
								 	callback(err,rs);
								 });
						}else{
							console.log('error, no money',game_team_id,'-',player_id);
							callback(new Error('no money'),null);
						}
						
					},
					function(rs,callback){
						if(rs!=null){
							//we need to know the next week game_id
							conn.query("SELECT team_id FROM "+config.database.database+".game_teams \
										WHERE id=? LIMIT 1",
								 [game_team_id],
								 function(err,rs){
								 	//console.log(this.sql);
								 	callback(err,name,transfer_value,rs[0]['team_id']);
								 	
								 });
						}else{
							callback(new Error('cannot get the '),name,transfer_value,null);
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
									[data.tw_id,
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
			
		},
		function(result,cb){
			conn.query("INSERT INTO \
						"+config.database.database+".game_player_salary_nego\
						(nego_id,nego_dt,offer_price,base_salary,\
						dof_bonus,quadrant,goal_bonus,cleansheet,\
						rooster,score,n_status)\
						VALUES\
						(?,NOW(),?,?,?,?,?,?,?,?,?)",
						[
							data.transfer.nego_id,
							data.offer_price,
							data.player.salary,
							data.dof,
							data.quadrant_bonus,
							data.goal_bonus,
							data.cleansheet_bonus,
							data.rooster_bonus,
							data.base_agreement_score,
							data.player_decision
						],
			function(err,rs){
				cb(err,result);
			});
		},
	],
	function(err,rs){
		console.log(rs);
		done(err,rs);
	});
}