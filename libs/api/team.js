/**
* API for team
*/
var config = {};
exports.setConfig = function(c){
	config = c;
}

var fs = require('fs');
var path = require('path');
var xmlparser = require('xml2json');
var async = require('async');
var mysql = require('mysql');
var S = require('string');
var initial_money = require(path.resolve('./libs/game_config')).initial_money;
var team_stars	= require(path.resolve('./libs/game_config')).team_stars;
var pool = {};
function prepareDb(callback){

	pool.getConnection(function(err,conn){
		callback(conn);
	});
	/*
	var connection = mysql.createConnection({
  		host     : config.database.host,
	   	user     : config.database.username,
	   	password : config.database.password,
	});
	
	return connection;
	*/
}

/** get master team data**/
function getTeams(callback){
	
	prepareDb(function(conn){
		conn.query("SELECT uid,name FROM "+config.database.database+".master_team ORDER BY name LIMIT 100;",
			[],function(err,team){
				conn.release();
				callback(err,team);
				
			});
	});
	
	

}
/** get master player data **/
function getPlayers(team_uid,callback){
	prepareDb(function(conn){
		async.waterfall([
				function(callback){
					conn.query("SELECT uid,name,birth_date,real_position,known_name,join_date,\
					position,country,salary,transfer_value \
					FROM "+config.database.database+".master_player \
					WHERE team_id=? ORDER BY last_name ASC,position ASC LIMIT 100",
					[team_uid],
					function(err,players){
						callback(err,players);
					});
				},
				function(players,callback){
					var player_with_stats = [];
					async.eachSeries(players,function(player,next){
						
						conn.query("SELECT SUM(total_points) AS points,\
									SUM(performance) AS performance,\
									SUM(last_points) AS last_point\
									FROM (\
									(SELECT SUM(points) AS total_points ,\
									0 AS performance, 0 AS last_points\
									FROM "+config.database.statsdb+".master_player_performance \
									WHERE player_id = ?)\
									UNION ALL\
									(SELECT 0 ,performance,points \
										FROM "+config.database.statsdb+".master_player_performance\
										WHERE player_id= ?\
										ORDER BY id DESC LIMIT 1)\
									)a;\
									",
									[player.uid,player.uid],
									function(err,rs){
										//console.log('----->',this.sql);
										if(rs!=null){
											player.stats = rs[0];	
										}else{
											player.stats = {};
										}
										player_with_stats.push(player);
										next();
									}
								);
					},function(err){

						callback(err,player_with_stats);
					});
				}
			],
			function(err,result){
				conn.release();
				callback(err,result);
				
			}
		);
	});
	
	
}

//get top players (players with the highest points)
//@params total -> how many players you want to retrieved
function getMasterTopPlayers(total,callback){
	prepareDb(function(conn){
		async.waterfall([
				function(callback){
					conn.query("SELECT a.uid AS player_id,a.team_id,\
								NAME,SUM(points) AS total\
								FROM "+config.database.database+".master_player a\
								INNER JOIN "+config.database.statsdb+".master_match_player_points b\
								ON a.uid = b.player_id\
								WHERE EXISTS (SELECT 1 FROM "+config.database.database+".game_fixtures c \
								WHERE c.game_id = b.game_id AND session_id="+config.competition.year+" LIMIT 1)\
								GROUP BY b.player_id ORDER BY total DESC LIMIT ?;",
					[parseInt(total)],
					function(err,players){
						console.log(S(this.sql).collapseWhitespace().s);
						console.log(players);
						callback(err,players);
					});
				},
				
			],
			function(err,result){
				conn.release();
				callback(err,result);
				
			}
		);
	});
}
/** get team detail from master **/
function getTeamById(team_uid,callback){
	prepareDb(function(conn){
		conn.query("SELECT uid,name FROM "+config.database.database+".master_team WHERE uid = ? LIMIT 1;",
		[team_uid],function(err,team){
			conn.release();
			callback(err,team[0]);
			
		});
	});
	
}

/** create user team **/
function create(data,callback){
	prepareDb(function(conn){
			async.waterfall(
			[
				function(callback){
					conn.query("SELECT id FROM "+config.database.database+".game_users WHERE fb_id=? LIMIT 1",
								[data.fb_id],
								function(err,rs){
									//console.log(this.sql);
									console.log(S(this.sql).collapseWhitespace().s);
									callback(null,rs[0]);
								});
					
				},
				function(user,callback){
					
					if(user==null || (typeof user === 'undefined')){
						console.log(user);
						console.log('CREATE_TEAM','GAME_USER NOT FOUND, WE RECREATE IT');
						recreate_game_user(conn,data.fb_id,function(err,rs){
							callback(err,rs);
						});
					}else{
						callback(null,user);
					}
				},
				function(user,callback){
					if(user==null){
						console.log('CREATE_TEAM','GAME_USER STILL NOT FOUND');
						callback(new Error('user not found'),null);
					}else{
						console.log(user);
						conn.query("INSERT INTO "+config.database.database+".game_teams\
								(user_id,team_id,created_date,n_status)\
								VALUES\
								(?,?,NOW(),1);",[user.id,data.team_id],function(err,rs){
									console.log('CREATE_TEAM',S(this.sql).collapseWhitespace().s);
									if(err){
										console.log('CREATE_TEAM',err.message);
									}
									callback(err,rs);
								});
					}
				},
				function(result,callback){
					console.log(result);
					if(result!=null){

						var sql = "INSERT IGNORE INTO "+config.database.database+".game_team_players\
									(game_team_id,player_id) VALUES\
									";
						var d = [];
						for(var i in data.players){
							if(i>0){
								sql+=",";
							}
							sql+="(?,?)";
							d.push(result.insertId);
							d.push(data.players[i]);
						}
						conn.query(sql,d,function(err,rs){
							console.log('CREATE_TEAM',S(this.sql).collapseWhitespace().s);
							console.log('CREATE_TEAM',rs);
							callback(err,result.insertId);
						});
					}else{
						callback(new Error('no result'),'');
					}
				},
				function(game_team_id,callback){

					if(game_team_id!=null){
						conn.query(
							"INSERT IGNORE INTO "+config.database.database+".game_team_purse(game_team_id,budget)\
							 VALUES(?,?)"
						,[game_team_id,initial_money],
						function(err,rs){
							console.log('CREATE_TEAM',S(this.sql).collapseWhitespace().s);
							callback(err,game_team_id);
						});
					}else{
						callback(err,0);
					}
				}
			],
			function(err,result){
				
				conn.release();
				callback(err,result);	
				
			}
		);
	});
	
	
}
function recreate_game_user(conn,fb_id,done){
	async.waterfall([
		function(cb){
			conn.query("INSERT IGNORE INTO "+config.database.database+".game_users \
						(name,email,phone,fb_id,n_status,access_key,register_date) \
						SELECT name,email,'',fb_id,1,'',NOW() \
						FROM "+config.database.frontend_schema+".users \
						WHERE fb_id=? LIMIT 1",
						[fb_id],
						function(err,rs){
							console.log(S(this.sql).collapseWhitespace().s);
							cb(err,rs);
						});
		},
		function(rs,cb){
			conn.query("SELECT id FROM "+config.database.database+".game_users WHERE fb_id=? LIMIT 1",
			[fb_id],
			function(err,rs){
				
				console.log('CREATE_TEAM','RECREATE',S(this.sql).collapseWhitespace().s);
				cb(err,rs[0]);
			});
		}
	],
	function(err,rs){
		done(err,rs);
	});
}
/**
remove user team
**/
function remove_team(game_team_id,callback){
	prepareDb(function(conn){
		async.waterfall(
			[
				function(callback){
					conn.query("DELETE FROM "+config.database.database+".game_teams WHERE id=? LIMIT 1",
								[game_team_id],
								function(err,rs){
									callback(err);
								});
					
				},
				function(callback){
					conn.query("DELETE FROM "+config.database.database+".game_team_players WHERE game_team_id=?",
								[game_team_id],
								function(err,rs){
									callback(err,'');
								});
				}
				
			],
			function(err,result){
				conn.release();
				callback(err,result);	
				
			}
		);
	});
	
}

function getUserTeam(fb_id,done){
	prepareDb(function(conn){
		async.waterfall(
			[
				function(callback){
					conn.query("SELECT id FROM "+config.database.database+".game_users WHERE fb_id=? LIMIT 1",
								[fb_id],
								function(err,rs){
									console.log("TEAM : ",S(this.sql).collapseWhitespace().s);
									callback(null,rs[0]);
								});
					
				},
				function(user,callback){
					if(typeof user !=='undefined'){
						conn.query("SELECT * FROM "+config.database.database+".game_teams WHERE user_id = ? LIMIT 1",[
							user.id
						],
							function(err,team){
								//console.log(team);
								console.log("TEAM : ",S(this.sql).collapseWhitespace().s);
								try{
									callback(err,team[0]);
								}catch(e){
									callback(new Error('team not found'),[]);
								}
						});
					}else{
						callback(new Error('user not found'),[]);
					}
				},
			],
			function(err,result){
				conn.release();
				done(err,result);	
				
			}
		);
	});
	
}
function getUserTeamPoints(fb_id,done){
	prepareDb(function(conn){
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
									if(rs!=null){
										callback(null,rs[0]);
									}else{
										callback(null,null);
									}
								});
					
				},
				function(rs,callback){
					if(rs!=null&&rs.id!=null){
						//extra points
						conn.query("SELECT SUM(extra_points) AS extra_point \
									FROM "+config.database.statsdb+".game_team_extra_points \
									WHERE game_team_id=?;",[rs.id],function(err,r){
										if(!err){
											if(r!=null){
												//rs.points += r[0].extra_point;

												rs.extra_points = r[0].extra_point;
											}
										}
										callback(err,rs);
						});
					}else{
						callback(null,rs);
					}
				},
				function(rs,callback){
					if(rs!=null){
						//get per game stats
						if(rs.id!=null){
							conn.query("SELECT a.game_id,SUM(a.points) AS total_points,\
										0 AS extra_points,\
										a.matchday\
										FROM \
										"+config.database.statsdb+".game_team_player_weekly a\
										WHERE a.game_team_id = ?\
										GROUP BY a.game_id LIMIT 400;",
										[rs.id],
										function(err,result){
											console.log(S(this.sql).collapseWhitespace().s);
											rs.game_points = result;
											callback(null,rs);
										});
						}
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
										if(!err){
											if(r!=null){
												for(var i in rs.game_points){
													for(var j in r){
														if(rs.game_points[i].game_id==r[j].game_id){
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
				console.log(result);
				conn.release();
				done(err,result);	
				
			}
		);
	});
	

}

//make it accessable from anywhere
exports.getUserTeamPoints = getUserTeamPoints;
exports.getTeams = getTeams;
exports.getPlayers = getPlayers;
exports.getTeamById = getTeamById;
exports.create = create;
exports.remove_team = remove_team;
exports.getUserTeam = getUserTeam;
exports.getMasterTopPlayers = getMasterTopPlayers;
exports.setPool = function(p){
	pool = p;
}