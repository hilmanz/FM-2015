/**
event_distribution.js
these is the in-game Event Distribution across all players.
it main jobs is to check the event which need to be distributed when the schedule date is met.
there's 2 kind of events here.  1 that giveaway a money to selected individuals or teams, and one that
give a point bonus/penalty if the user's team owned the several selected players on matchday.
**/
/////THE MODULES/////////
var fs = require('fs');
var path = require('path');
var config = require('./config').config;
var xmlparser = require('xml2json');
var master = require('./libs/master');
var async = require('async');
var mysql = require('mysql');
var S = require('string');

var dbschema = config.database.frontend_schema;
/////DECLARATIONS/////////


/////THE LOGICS///////////////
//mysql pool
var pool  = mysql.createPool({
   host     : config.database.host,
   user     : config.database.username,
   password : config.database.password,
});


pool.getConnection(function(err,conn){
	async.waterfall([
		//process triggered events first
		function(callback){
			processTriggeredEvents(conn, function(err,rs){
				callback(err);
			});
		},
		//get all standard events which will happen today and still not been processed.
		function(callback){
			getAllEventsWhichWillHappenToday(conn, function(err,rs){
				callback(err,rs);
			});
		},
		function(schedules,callback){
			processSchedule(conn,schedules,function(err){
				callback(err);
			});
		},
		function(callback){
			//process immediate events
			processImmediateEvents(conn, function(err){
				callback(err);
			});
		},
		function(callback){
			//process money perks.
			//as per 12/12/2013, every money event will be processed immediately.
			processMoneyPerks(conn, function(err){
				callback(err);
			});
		}
	],
	function(err){
		conn.release();
		pool.end(function(err){
			console.log('all done');
		});
	});
});

//TRIGGERED EVENTS
function processTriggeredEvents(conn, done){
		console.log('process triggered events');
		async.waterfall([
			function(cb){
				//get triggered events that happen today
				getAllTriggeredEventsThatHappenToday(conn,function(err,rs){
					cb(err,rs);
				});
			},
			function(events,cb){
				processTriggeredEventsSchedule(conn,events,function(err,rs){
					cb(err,rs);
				});
			}
		],
		function(err,rs){
			done(err);
		});
}

function getAllTriggeredEventsThatHappenToday(conn,done){
	conn.query("SELECT * \
				FROM ffgame.master_triggered_events \
				WHERE n_status=0 AND DATE(schedule_dt) <= DATE(NOW()) \
				LIMIT 20;",
				[],
				function(err,rs){
					console.log(S(this.sql).collapseWhitespace().s);
					done(err,rs);
				});
}
function processTriggeredEventsSchedule(conn,schedules,done){
	async.eachSeries(schedules,
		function(schedule,next){
			if(schedule.event_type == 1 || schedule.event_type == 2){
				if(schedule.recipient_type == 0){
					if(schedule.offered_player_id==0){
						sendTriggeredEventsToAllTeams(conn,schedule,function(err){
							flagTriggeredEvent(conn,schedule.id,1,function(err){
								next();
							});
							
						});
					}else{
						sendTriggeredEventsToAllTeamsWithPlayerIdExist(conn,schedule,function(err){
							flagTriggeredEvent(conn,schedule.id,1,function(err){
								next();
							});
							
						});
					}
					
				}else if(schedule.recipient_type == 5){
					sendTriggeredEventsByOriginalTeam(conn,schedule,function(err){
						flagTriggeredEvent(conn,schedule.id,1,function(err){
							next();
						});
					});
				}else{
					if(schedule.offered_player_id == 0){
						getTeamsRangeInTier(conn,schedule.recipient_type,
							function(err,start_rank,end_rank){
								sendTriggeredEventsToTeamInRank(conn,schedule,start_rank,end_rank,function(err){
									flagTriggeredEvent(conn,schedule.id,1,function(err){
										next();
									});
								});
						});
					}else{
						getTeamsRangeInTier(conn,schedule.recipient_type,
							function(err,start_rank,end_rank){
								sendTriggeredEventsToTeamInRank_WithPlayerIdExists(conn,schedule,start_rank,end_rank,function(err){
									flagTriggeredEvent(conn,schedule.id,1,function(err){
										next();
									});
								});
						});
					}
					
				}
			}
	},function(err){
		done(err);
	});
}
function flagTriggeredEvent(conn,event_id,flag,done){
	conn.query("UPDATE ffgame.master_triggered_events SET n_status = 1 \
				WHERE id = ?",[event_id],function(err,rs){
					done(err);
				});
}
function sendTriggeredEventsByOriginalTeam(conn,schedule,done){
	var has_data = true;
	var since_id = 0;
	async.whilst(
		function(){
			return has_data;
		},
		function(next){
			conn.query("SELECT \
						a.id,\
						a.fb_id,\
						a.email,\
						a.name,\
						c.id AS game_team_id,\
						c.team_id AS original_team_id\
						FROM "+dbschema+".users a \
						INNER JOIN \
						ffgame.game_users b\
						ON a.fb_id = b.fb_id\
						INNER JOIN \
						ffgame.game_teams c\
						ON c.user_id = b.id \
						WHERE a.id > ?\
						AND c.team_id=?\
						LIMIT 100;",
				[since_id,schedule.target_team],
				function(err,rs){
					console.log('sendTriggeredEventsByOriginalTeam',S(this.sql).collapseWhitespace().s);
					if(rs!=null && rs.length>0){
						since_id = rs[ (rs.length-1) ].id;
						sendTriggeredNotificationToUsers(conn,schedule,rs,function(err){
							next();
						});
					}else{
						has_data = false;
						next();
					}
				});
		},
		function(err){
			done(err);
		}
	);
}
function sendTriggeredEventsToAllTeamsWithPlayerIdExist(conn,schedule,done){
	var has_data = true;
	var since_id = 0;
	async.whilst(
		function(){
			return has_data;
		},
		function(next){
			conn.query("SELECT \
						a.id,\
						a.fb_id,\
						a.email,\
						a.name,\
						c.id AS game_team_id,\
						c.team_id AS original_team_id\
						FROM "+dbschema+".users a \
						INNER JOIN \
						ffgame.game_users b\
						ON a.fb_id = b.fb_id\
						INNER JOIN \
						ffgame.game_teams c\
						ON c.user_id = b.id \
						WHERE a.id > ? \
						AND EXISTS\
						(\
							SELECT 1 FROM ffgame.game_team_players d \
							WHERE d.game_team_id = c.id AND d.player_id=?\
							LIMIT 1\
						)\
						LIMIT 100;",
				[since_id,schedule.offered_player_id],
				function(err,rs){
					console.log(S(this.sql).collapseWhitespace().s);
					if(rs!=null && rs.length>0){
						since_id = rs[ (rs.length-1) ].id;
						sendTriggeredNotificationToUsers(conn,schedule,rs,function(err){
							next();
						});
					}else{
						has_data = false;
						next();
					}
				});
		},
		function(err){
			done(err);
		}
	);
}
function sendTriggeredEventsToAllTeams(conn,schedule,done){
	var has_data = true;
	var since_id = 0;
	async.whilst(
		function(){
			return has_data;
		},
		function(next){
			conn.query("SELECT \
				a.id,\
				a.fb_id,\
				a.email,\
				a.name,\
				c.id AS game_team_id,\
				c.team_id AS original_team_id\
				FROM "+dbschema+".users a \
				INNER JOIN \
				ffgame.game_users b\
				ON a.fb_id = b.fb_id\
				INNER JOIN \
				ffgame.game_teams c\
				ON c.user_id = b.id \
				WHERE a.id > ? LIMIT 100;",
				[since_id],
				function(err,rs){
					console.log(S(this.sql).collapseWhitespace().s);
					if(rs!=null && rs.length>0){
						since_id = rs[ (rs.length-1) ].id;
						sendTriggeredNotificationToUsers(conn,schedule,rs,function(err){
							next();
						});
					}else{
						has_data = false;
						next();
					}
				});
		},
		function(err){
			done(err);
		}
	);
}
function sendTriggeredEventsToTeamInRank_WithPlayerIdExists(conn,schedule,start_rank,end_rank,done){
	var has_data = true;
	var since_id = 0;
	async.whilst(
		function(){
			return has_data;
		},
		function(next){
			conn.query("SELECT e.id,c.email,e.id AS game_team_id \
							FROM "+dbschema+".points a\
							INNER JOIN "+dbschema+".teams b\
							ON a.team_id = b.id\
							INNER JOIN "+dbschema+".users c\
							ON c.id = b.user_id\
							INNER JOIN ffgame.game_users d\
							ON d.fb_id = c.fb_id\
							INNER JOIN ffgame.game_teams e\
							ON e.user_id = d.id\
							WHERE a.id > ? AND rank > ? AND rank <= ? \
							AND EXISTS\
							(\
								SELECT 1 FROM ffgame.game_team_players f\
								WHERE f.game_team_id = e.id AND f.player_id = ?\
								LIMIT 1\
							)\
							ORDER BY a.id ASC \
							LIMIT 100;",
				[since_id,start_rank,end_rank,schedule.offered_player_id],
				function(err,rs){
					console.log(S(this.sql).collapseWhitespace().s);
					if(rs!=null && rs.length>0){
						since_id = rs[ (rs.length-1) ].id;
						sendTriggeredNotificationToUsers(conn,schedule,rs,function(err){
							next();
						});
					}else{
						has_data = false;
						next();
					}
				});
		},
		function(err){
			done(err);
		}
	);
}
function sendTriggeredEventsToTeamInRank(conn,schedule,start_rank,end_rank,done){
	var has_data = true;
	var since_id = 0;
	async.whilst(
		function(){
			return has_data;
		},
		function(next){
			conn.query("SELECT e.id,c.email,e.id AS game_team_id \
							FROM "+dbschema+".points a\
							INNER JOIN "+dbschema+".teams b\
							ON a.team_id = b.id\
							INNER JOIN "+dbschema+".users c\
							ON c.id = b.user_id\
							INNER JOIN ffgame.game_users d\
							ON d.fb_id = c.fb_id\
							INNER JOIN ffgame.game_teams e\
							ON e.user_id = d.id\
							WHERE a.id > ? AND rank > ? AND rank <= ? \
							ORDER BY a.id ASC \
							LIMIT 100;",
				[since_id,start_rank,end_rank],
				function(err,rs){
					console.log(S(this.sql).collapseWhitespace().s);
					if(rs!=null && rs.length>0){
						since_id = rs[ (rs.length-1) ].id;
						sendTriggeredNotificationToUsers(conn,schedule,rs,function(err){
							next();
						});
					}else{
						has_data = false;
						next();
					}
				});
		},
		function(err){
			done(err);
		}
	);
}

/*
* 20/01/2014 - notifikasi cukup via inbox in-game saja.
* email akan difokuskan untuk newsletter.
*/
function sendTriggeredNotificationToUsers(conn,schedule,users,cb){
	async.eachSeries(users,function(user,next){
		async.waterfall([
			function(done){
				/*conn.query("INSERT INTO ffgame.email_queue\
					(subject,email,plain_txt,html_text,queue_dt,n_status)\
					VALUES\
					(?,?,?,?,NOW(),0);",
					[schedule.email_subject,user.email,schedule.email_body_txt,schedule.email_body_txt],
					function(err,rs){
						console.log(S(this.sql).collapseWhitespace().s);
						done(err);
					});
				*/
				done(null);
			},
			function(done){
				conn.query("INSERT INTO "+dbschema+".notifications\
					(content,url,dt,game_team_id)\
					VALUES\
					(?,'#',NOW(),?)",
					[ '<a href="'+schedule.offer_url+'">'+nl2br(schedule.email_body_plain)+'</a>',
					 user.game_team_id ],
					function(err,rs){
						console.log(S(this.sql).collapseWhitespace().s);
						done(err);
					});
			}
		],
		function(err,r){
			next();
		});
	},function(err){
		cb(err);
	});		
}
function getTeamsRangeInTier(conn,tier,done){
	async.waterfall([
		function(callback){
			conn.query("SELECT MAX(rank) AS max_rank FROM "+dbschema+".points;",
						[],
						function(err,rs){
				callback(err,rs[0].max_rank);
			});
		},
		function(max_rank,callback){
			console.log('max_rank',max_rank);
			tier = parseInt(tier);
			switch(tier){
				case 1:
					start_rank = 0;
					end_rank 	= Math.floor(0.25 * max_rank);
				break;
				case 2:
					start_rank = Math.ceil(0.25 * max_rank);
					end_rank 	= Math.floor(0.5 * max_rank);
				break;
				case 3:
					start_rank = Math.ceil(0.5 * max_rank);
					end_rank 	= Math.floor(0.75 * max_rank);
				break;
				case 4:
					start_rank = Math.ceil(0.75 * max_rank);
					end_rank 	= Math.floor(1.0 * max_rank);
				break;
				default:
					start_rank = 0;
					end_rank 	= 0;
				break;
			}
			callback(null,start_rank,end_rank);
		},
	],
	function(err,start_rank,end_rank){
		done(err,start_rank,end_rank);
	});
}









//STANDARD EVENTS
function getAllEventsWhichWillHappenToday(conn, cb){
	//the yesterday's unexecuted events should be able to processed also.
	conn.query("SELECT * FROM ffgame.master_events \
				WHERE n_status=0 \
				AND DATE(schedule_dt) <= DATE(NOW()) \
				LIMIT 20;",
				[],function(err,rs){
						try{
							if(rs.length>0){
								cb(err,rs);
							}else{
								cb(err,[]);
							}
						}catch(e){
							cb(err,null);
						}
				});
}

//for each events, check its event_type
//event_type:1 -> affects only to player data (gives or reduces money)
//event_type:2 -> affects only to master data (gives or reduces points)
//the player data events will in effect immediately, 
//while the master data only executed on matchday
//after the updater_worker process ended, and before the rank_and_points.js executed.
function processSchedule(conn,schedules,cb){
	console.log(schedules);
	async.eachSeries(schedules,function(schedule,next){
		if(schedule.event_type==1){
			//if event_type 1
			processPlayerEvent(conn,schedule,function(err){
				next();
			});
		}else{
			//if event type 2
			processMasterEvent(conn,schedule,function(err){
				next();
			});
		}
	},function(err){
		cb(err);
	});
}

function processPlayerEvent(conn,schedule,cb){
	console.log('process player event');
	//check the target recipients
	switch(schedule.target_type){
		case 1:
			//if selected individual teams, then queue those selected individual team to event_immediate table
			//and then queue the email notifications
			distributeEventToIndividualTeam(conn,schedule,function(err){
				flagSchedule(schedule,1,function(err,rs){
					cb(err);
				});
				
			});
		break;
		case 2:
			//if all teams, then simply queue all teams and insert into event_immediate table.
			//and then queue the email notifications
			distributeEventToAllTeams(conn,schedule,function(err){
				flagSchedule(schedule,1,function(err,rs){
					cb(err);
				});
			});
		break;
		case 3:
			//if by tier, then query the n-tier teams, then put those n-tier tam to event_immediate table
			//and then queue the email notifications
			distributeEventByTier(conn,schedule,function(err){
				flagSchedule(conn,schedule,1,function(err,rs){
					cb(err);
				});
			});
		break;
		case 4:
			//if by original player, then we query all teams that has that player in their team,
			//then insert them into event_immediate table. 
			//and then queue the email notifications
			distributeEventByOriginalPlayer(conn,schedule,function(err){
				flagSchedule(conn,schedule,1,function(err,rs){
					cb(err);
				});
			});
		break;
		case 5:
			//if by original team, we query all team that play as the original team,
			//then insert them into event_immediate table, and then queue the email.
			if(schedule.prequisite_event_id==0){
				distributeEventByOriginalTeam(conn,schedule,function(err){
					flagSchedule(conn,schedule,1,function(err,rs){
						cb(err);
					});
				});	
			}else{
				distributeEventByOriginalTeamPrequisite(conn,schedule,function(err){
					flagSchedule(conn,schedule,1,function(err,rs){
						cb(err);
					});
				});	
			}
			
		break;
		default:
			//do nothing, something is not right here.
			cb(null);
		break;
	}
}
function flagSchedule(conn,schedule,flag,cb){
	
		conn.query("UPDATE ffgame.master_events SET n_status=? WHERE id=?",
					[flag,schedule.id],
					function(err,rs){
						
						cb(err,rs);	
					});
	
}
function distributeEventToIndividualTeam(conn,schedule,cb){
	console.log('distributeEventToIndividualTeam');
	var targets = JSON.parse(schedule.target_value);
	
		//queue those selected individual teams to event_immediate table
		distributeEachTeam(conn,schedule,targets,function(err){

			cb(err);
		});
	
}
function distributeEachTeam(conn,schedule,targets,cb){
	async.eachSeries(targets,function(target,next){
		async.waterfall([
			function(callback){
				//queueing
				conn.query("INSERT IGNORE INTO ffgame.job_event_immediate\
							(master_event_id,game_team_id,apply_date,n_status)\
							VALUES\
							(?,?,NOW(),0)",[schedule.id,target],
							function(err,rs){
								callback(err,rs);
							});
			},function(rs,callback){
				if(rs!=null){
					//queue email notification
					async.waterfall([
						function(c){
							conn.query("SELECT a.id,c.id as game_team_id,a.name,a.email FROM "+dbschema+".users a\
										INNER JOIN ffgame.game_users b\
										ON a.fb_id = b.fb_id\
										INNER JOIN ffgame.game_teams c\
										ON c.user_id = b.id\
										WHERE c.id = ? LIMIT 1",[target],function(err,r){
											console.log(S(this.sql).collapseWhitespace().s);
											try{
												c(err,r[0]);
											}catch(e){
												c(null,null);
											}
										});
						},
						function(user,c){
							if(typeof user !== 'undefined'){
								//2014-01-20 - event tidak perlu ada notifikasi via email
								/*
								conn.query("INSERT INTO ffgame.email_queue\
										(subject,email,plain_txt,html_text,queue_dt,n_status)\
										VALUES\
										(?,?,?,?,NOW(),0);",[
											schedule.email_subject,
											user.email,
											schedule.email_body_plain,
											schedule.email_body_txt
										],
										function(err,ins){
											console.log(S(this.sql).collapseWhitespace().s);
											c(err,user);
										});
								*/
								c(null,user);
							}else{
								c(null,null);
							}
							
						},
						function(user,c){
							console.log(user);
							if(user!=null){
								conn.query("INSERT INTO "+dbschema+".notifications\
											(content,url,dt,game_team_id)\
											VALUES\
											(?,'#',NOW(),?)"
											,[
												schedule.email_body_plain,
												user.game_team_id
											]
											,function(err,ins){
												console.log(S(this.sql).collapseWhitespace().s);
												c(err,ins);
											});
							}else{
								c(null,null);
							}
						}
					],
					function(err,r){
						callback(err);
					});
				}
			}
		],
		function(err){
			next();
		});
	},function(err){
		cb(err);	
	});
}
function distributeEventToAllTeams(conn,schedule,cb){
	console.log('distributeEventToAllTeams');
	
		var has_data = true;
		var since_id = 0;
		async.whilst(function(){
			return has_data;
		},function(next){
			console.log(since_id);
			conn.query("SELECT id FROM ffgame.game_teams WHERE id > ? ORDER BY id ASC LIMIT 100",[since_id],
			function(err,teams){
				if(teams.length>0){
					var targets = [];
					for(var i in teams){
						targets.push(teams[i].id);
					}
					since_id = teams[teams.length-1].id;

					distributeEachTeam(conn,schedule,targets,function(err){
						next();
					});
				}else{
					has_data = false;
					next();
				}
			});
		},function(err){
			
				cb(err);
			
			
		});

}
function distributeEventByOriginalTeam(conn,schedule,cb){
	console.log('distirbuteEventByOriginalTeam');
	var the_targets = JSON.parse(schedule.target_value);
	
		var has_data = true;
		var since_id = 0;
		async.whilst(function(){
			return has_data;
		},function(next){
			console.log(since_id);
			conn.query("SELECT id as game_team_id FROM ffgame.game_teams \
						WHERE id > ? AND team_id IN (?)\
						ORDER BY id ASC\
						LIMIT 100;",[since_id,the_targets],

			function(err,teams){
				console.log('distributeEventByOriginalTeam',S(this.sql).collapseWhitespace().s);
				if(teams.length>0){
					var targets = [];
					for(var i in teams){
						targets.push(teams[i].game_team_id);
					}
					since_id = teams[teams.length-1].game_team_id;

					distributeEachTeam(conn,schedule,targets,function(err){
						next();
					});
				}else{
					has_data = false;
					next();
				}
			});
		},function(err){
			
			
				cb(err);
			
		});
	
}
function distributeEventByOriginalTeamPrequisite(schedule,cb){
	console.log('distributeEventByOriginalTeamPrequisite');
	var the_targets = JSON.parse(schedule.target_value);
	
		var has_data = true;
		var since_id = 0;
		async.whilst(function(){
			return has_data;
		},function(next){
			console.log(since_id);
			var sql = "";
			if(schedule.prequisite_trigger_type==0){
				sql = "SELECT id AS game_team_id \
						FROM ffgame.game_teams a\
						WHERE id > ? AND team_id IN (?)\
						AND EXISTS\
						(SELECT 1 FROM ffgame.game_perks e \
						 WHERE e.event_id = ? AND e.game_team_id =  a.id \
						 AND e.n_status <> 2 LIMIT 1\
						 ) \
						ORDER BY id ASC\
						LIMIT 100;"
			}else{
				sql = "SELECT id AS game_team_id \
						FROM ffgame.game_teams a\
						WHERE id > ? AND team_id IN (?)\
						AND EXISTS\
						(SELECT 1 FROM ffgame.game_perks e \
						 WHERE e.event_id = ? AND e.game_team_id =  a.id \
						 AND e.n_status = 2 LIMIT 1\
						 ) \
						ORDER BY id ASC\
						LIMIT 100;"
			}
			conn.query(sql,[since_id,the_targets,schedule.prequisite_event_id],

			function(err,teams){
				console.log('distributeEventByOriginalTeam by prequisite',S(this.sql).collapseWhitespace().s);
				if(teams.length>0){
					var targets = [];
					for(var i in teams){
						targets.push(teams[i].game_team_id);
					}
					since_id = teams[teams.length-1].game_team_id;

					distributeEachTeam(conn,schedule,targets,function(err){
						next();
					});
				}else{
					has_data = false;
					next();
				}
			});
		},function(err){
			
				cb(err);
			
		});
	
}
function distributeEventByOriginalPlayer(conn,schedule,cb){
	console.log('distirbuteEventByOriginalPlayer');
	var players = JSON.parse(schedule.target_value);
	
		var has_data = true;
		var since_id = 0;
		async.whilst(function(){
			return has_data;
		},function(next){
			console.log(since_id);
			conn.query("SELECT game_team_id FROM ffgame.game_team_players \
						WHERE game_team_id > ? AND player_id IN (?)\
						GROUP BY game_team_id\
						ORDER BY game_team_id ASC\
						LIMIT 100;",[since_id,players],
			function(err,teams){
				console.log('distributeEventByOriginalPlayer',S(this.sql).collapseWhitespace().s);
				if(teams.length>0){
					var targets = [];
					for(var i in teams){
						targets.push(teams[i].game_team_id);
					}
					since_id = teams[teams.length-1].game_team_id;

					distributeEachTeam(conn,schedule,targets,function(err){
						next();
					});
				}else{
					has_data = false;
					next();
				}
			});
		},function(err){
			
				cb(err);
			
		});
	
}
function distributeEventByTier(schedule,cb){
	console.log('distributeEventByTier');
	var targets = JSON.parse(schedule.target_value);
	var tier = targets[0];
	console.log('Tier : ',tier);

	var start_rank = 0;
	var end_rank = 0;
	
		async.waterfall([
				function(callback){
					conn.query("SELECT MAX(rank) AS max_rank FROM "+dbschema+".points;",
								[],
								function(err,rs){
						callback(err,rs[0].max_rank);
					});
				},
				function(max_rank,callback){
					console.log('max_rank',max_rank);
					switch(tier){
						case '1':
							start_rank = 0;
							end_rank 	= Math.floor(0.25 * max_rank);
						break;
						case '2':
							
							start_rank = Math.ceil(0.25 * max_rank);
							end_rank 	= Math.floor(0.5 * max_rank);
						break;
						case '3':
							start_rank = Math.ceil(0.5 * max_rank);
							end_rank 	= Math.floor(0.75 * max_rank);
						break;
						case '4':
							start_rank = Math.ceil(0.75 * max_rank);
							end_rank 	= Math.floor(1.0 * max_rank);
						break;
						default:
							start_rank = 0;
							end_rank 	= 0;
						break;
					}
					callback(null);
				},
				function(callback){
					//now get the teams in these tiers
					var has_data = true;
					var since_id = 0;
					async.whilst(function(){
						return has_data;
					},function(next){
						console.log(since_id);
						conn.query("SELECT e.id AS game_team_id \
							FROM "+dbschema+".points a\
							INNER JOIN "+dbschema+".teams b\
							ON a.team_id = b.id\
							INNER JOIN "+dbschema+".users c\
							ON c.id = b.user_id\
							INNER JOIN ffgame.game_users d\
							ON d.fb_id = c.fb_id\
							INNER JOIN ffgame.game_teams e\
							ON e.user_id = d.id\
							WHERE a.id > ? AND rank > ? AND rank <= ? \
							ORDER BY a.id ASC\
							LIMIT 100;",
						[since_id,start_rank,end_rank],
						function(err,teams){
							console.log(S(this.sql).collapseWhitespace().s);
							if(teams.length>0){
								var targets = [];
								for(var i in teams){
									targets.push(teams[i].game_team_id);
								}
								since_id = teams[teams.length-1].game_team_id;

								distributeEachTeam(conn,schedule,targets,function(err){
									next();
								});
							}else{
								has_data = false;
								next();
							}
						});
					},function(err){
						callback(err);
					});
				}
			],
			function(err){
				
					cb(err);
				
		});
	
	
	
}

function processMasterEvent(conn,schedule,cb){
	console.log('process master event');
	//check the target recipients
	switch(schedule.target_type){
		case 1:
			//if individual teams, then queue all teams which its original team is matched the selected teams,
			//and scheduled it for the next match_day (can we distribute the point immediately ? )
			//and then queue the email notifications
			distributeMasterEventToTeam(conn,schedule,function(err){
				flagSchedule(conn,schedule,1,function(err,rs){
					cb(err);
				});
			});
		break;
		case 4:
			//if individual players, then queue all teams who has the selected players on their team rooster and
			//scheduled it for the next match_day
			//and then queue the email notifications
			//if(schedule.prequisite_event_id > 0){
			//	distributeMasterEventToPlayerPrequisite(schedule,function(err){
			//		flagSchedule(schedule,1,function(err,rs){
			//			cb(err);
			//		});
			//	});
			//}else{
				distributeMasterEventToPlayer(conn,schedule,function(err){
					flagSchedule(conn,schedule,1,function(err,rs){
						cb(err);
					});
				});
			//}
			
		break;		
		default:
			//do nothing, something is not right here.
			cb(null);
		break;
	}
}
function distributeMasterEventToPlayer(conn,schedule,cb){
	console.log('distributeMasterEventToPlayer');
	var targets = JSON.parse(schedule.target_value);
	distributeMasterEvents(conn,targets,schedule,function(err){
		
		//sendNotificationEmails(schedule,function(err){
			console.log('finished');
			cb(err);
		//});
	});
}
function distributeMasterEventToTeam(conn,schedule,cb){
	console.log('distributeMasterEventToTeam');
	var targets = JSON.parse(schedule.target_value);
	var players = [];
	//for each team, we take the list of its players
	async.eachSeries(targets,function(target,next){
		
			conn.query("SELECT uid FROM ffgame.master_player WHERE team_id=? LIMIT 100;",
					[target],function(err,rs){
						for(var i in rs){
							players.push(rs[i].uid);
						}
						
						
							next();
						
					});
		
		
	},function(err){
		console.log(players);
		distributeMasterEvents(conn,players,schedule,function(err){
			//sendNotificationEmails(schedule,function(err){
				cb(err);
			//});
			
		});
		
	});
	
}
function distributeMasterEvents(conn,targets,schedule,cb){
	
		async.eachSeries(targets,function(target,next){
			async.waterfall([
				function(callback){
					//first we try to check the available matchday closer to schedule_dt.
					conn.query("SELECT matchday \
								FROM ffgame.game_fixtures \
								WHERE match_date > ? \
								ORDER BY matchday ASC LIMIT 1;",[schedule.schedule_dt],
								function(err,fixture){
									console.log(S(this.sql).collapseWhitespace().s);
									callback(err,fixture[0].matchday);
								});
				},
				function(matchday,callback){
					//get all the players and queue one by one.
					//since the player is a massive 50k++ 
					//we need to do it in batches
					if(schedule.prequisite_event_id == 0){
						processAllUsersForMaster(conn,target,schedule,matchday,
											function(err){
												callback(err,matchday);	
											});
					}else{
						processAllUsersForMasterByPrequisite(conn,target,schedule,matchday,
											function(err){
												callback(err,matchday);	
											});
					}
					
				}
			],
			function(err,rs){
				next();
			});
		},function(err){
			
				cb(err);
			
			
		});
	
}
function processAllUsersForMaster(conn,target,schedule,matchday,done){
	var has_data = true;
	var since_id = 0;
	async.whilst(
		function(){
			return has_data;
		},
		function(next){
			conn.query("SELECT \
						a.id,\
						a.fb_id,\
						a.email,\
						a.name,\
						c.id AS game_team_id,\
						c.team_id AS original_team_id\
						FROM "+dbschema+".users a \
						INNER JOIN \
						ffgame.game_users b\
						ON a.fb_id = b.fb_id\
						INNER JOIN \
						ffgame.game_teams c\
						ON c.user_id = b.id \
						WHERE a.id > ? AND EXISTS\
						(SELECT 1 FROM ffgame.game_team_players d \
						 WHERE d.game_team_id = c.id AND d.player_id = ? LIMIT 1)\
						ORDER BY id\
						LIMIT 100;",[since_id,target],function(err,rs){
							console.log(S(this.sql).collapseWhitespace().s);
							try{
								if(rs!=null && rs.length > 0){
									since_id = rs[ (rs.length - 1) ].id;
									populate_job_event_master_player(conn,target,schedule,matchday,rs,function(err){
										next();
									});
								}else{
									has_data = false;
									next();
								}
							}catch(e){
								has_data = false;
								next();
							}
						});
		},
		function(err){
			done(err);
		}
	);
}
function processAllUsersForMasterByPrequisite(conn,target,schedule,matchday,done){
	var has_data = true;
	var since_id = 0;
	async.whilst(
		function(){
			return has_data;
		},
		function(next){
			if(schedule.prequisite_trigger_type==0){
				sql = "SELECT \
						a.id,\
						a.fb_id,\
						a.email,\
						a.name,\
						c.id AS game_team_id,\
						c.team_id AS original_team_id\
						FROM "+dbschema+".users a \
						INNER JOIN \
						ffgame.game_users b\
						ON a.fb_id = b.fb_id\
						INNER JOIN \
						ffgame.game_teams c\
						ON c.user_id = b.id \
						WHERE a.id > ? \
						AND EXISTS\
						(SELECT 1 FROM ffgame.game_team_players d \
						 WHERE d.game_team_id = c.id AND d.player_id = ? LIMIT 1)\
						AND EXISTS\
						(SELECT 1 FROM ffgame.game_perks e WHERE e.event_id = ? \
						 AND e.game_team_id =  c.id \
						 AND e.n_status <> 2 LIMIT 1\
						 ) \
						ORDER BY id\
						LIMIT 100;"
			}else{
				sql = "SELECT \
						a.id,\
						a.fb_id,\
						a.email,\
						a.name,\
						c.id AS game_team_id,\
						c.team_id AS original_team_id\
						FROM "+dbschema+".users a \
						INNER JOIN \
						ffgame.game_users b\
						ON a.fb_id = b.fb_id\
						INNER JOIN \
						ffgame.game_teams c\
						ON c.user_id = b.id \
						WHERE a.id > ? \
						AND EXISTS\
						(SELECT 1 FROM ffgame.game_team_players d \
						 WHERE d.game_team_id = c.id AND d.player_id = ? LIMIT 1)\
						AND EXISTS\
						(SELECT 1 FROM ffgame.game_perks e WHERE e.event_id = ? \
						 AND e.game_team_id =  c.id \
						 AND e.n_status = 2 LIMIT 1\
						 ) \
						ORDER BY id\
						LIMIT 100;"
			}

			conn.query(sql,
						[since_id,target,schedule.prequisite_event_id],function(err,rs){
							console.log(S(this.sql).collapseWhitespace().s);
							try{
								if(rs!=null && rs.length > 0){
									since_id = rs[ (rs.length - 1) ].id;
									if(schedule.affected_item == 2){
										//point event
										populate_job_event_master_player(conn,target,schedule,matchday,rs,function(err){
											next();
										});	
									}else{
										//money event
										var team_targets = [];
										for(var i=0;i<rs.length;i++){
											team_targets.push(rs[0].game_team_id);
										}
										distributeEachTeam(conn,schedule,team_targets,function(err){
											next();
										});
									}
									
								}else{
									has_data = false;
									next();
								}
							}catch(e){
								has_data = false;
								next();
							}
						});
		},
		function(err){
			done(err);
		}
	);
}
function populate_job_event_master_player(conn,target,schedule,matchday,teams,done){
	console.log('distributed to ',teams);
	async.eachSeries(teams,function(team,next){
		console.log('send event to #',team.game_team_id);
		async.waterfall([
			function(cb){
				conn.query("INSERT IGNORE INTO ffgame.job_event_master_player\
								(master_event_id,game_team_id,player_id,matchday,apply_date,n_status)\
								VALUES\
								(?,?,?,?,NOW(),0);",
					[schedule.id,team.game_team_id,target,matchday],
					function(err,rs){
						console.log('----------------');
						console.log(S(this.sql).collapseWhitespace().s);
						if(err) console.log(err.message);
						cb(err,rs);
					});
			},
			function(result,cb){
				//2014-01-20 - event tidak perlu dikirimkan email.
				/*
				//send email notifications
				conn.query("INSERT INTO ffgame.email_queue\
					(subject,email,plain_txt,html_text,queue_dt,n_status)\
					VALUES\
					(?,?,?,?,NOW(),0);",
					[schedule.email_subject,team.email,schedule.email_body_txt,schedule.email_body_txt],
					function(err,rs){
						console.log('----------------');
						console.log(S(this.sql).collapseWhitespace().s);
						if(err) console.log(err.message);
						cb(err);
					});
				*/
				cb(null);
			},function(cb){
				//send message to inbox
				conn.query("INSERT INTO "+dbschema+".notifications\
					(content,url,dt,game_team_id)\
					VALUES\
					(?,'#',NOW(),?);",
					[schedule.email_body_plain,team.game_team_id],
					function(err,rs){
						console.log('----------------');
						console.log(S(this.sql).collapseWhitespace().s);
						if(err) console.log(err.message);
						cb(err);
					});
			}
		],
		function(err){
			if(err){console.log(err.message);}
			next();
		});
	},
	function(err){
		done(err);
	});
}
/*
function sendNotificationEmails(schedule,cb){
	pool.getConnection(function(err,conn){
		//send email to all users
		async.waterfall([
			function(done){
				conn.query("INSERT INTO ffgame.email_queue\
					(subject,email,plain_txt,html_text,queue_dt,n_status)\
					SELECT ? AS subject,email,? AS plain_txt,? AS html_text,\
					NOW() AS queue_dt,0 AS n_status \
					FROM "+dbschema+".users;",
					[schedule.email_subject,schedule.email_body_txt,schedule.email_body_txt],
					function(err,rs){
						console.log(S(this.sql).collapseWhitespace().s);
						done(err);
					});
			},
			function(done){
				conn.query("INSERT INTO "+dbschema+".notifications\
					(content,url,dt,game_team_id)\
					SELECT ? AS content,'#',NOW(),id \
					FROM ffgame.game_teams a;",
					[schedule.email_body_plain],
					function(err,rs){
						console.log(S(this.sql).collapseWhitespace().s);
						done(err);
					});
			}
		],

		function(err,r){
			conn.end(function(err){
				cb(err);
			});
		});
	});
	
					
}
*/


/*
* process all game_perks that rewards a money to user immediately
*/
function processMoneyPerks(conn, cb){
		var has_data = true;
		async.whilst(
		function(){
			return has_data;
		},function(next){
			conn.query("SELECT * FROM ffgame.game_perks \
						WHERE n_status=0 AND money_reward <> 0\
						ORDER BY id ASC LIMIT 100;",[],function(err,rs){
				if(rs!=null && rs.length > 0){
					async.eachSeries(rs,function(queue,nextQueue){
						addMoneyPerk(conn,queue,function(err){
							nextQueue();
						});
					},function(err){
						next();
					});
					
				}else{
					has_data = false;
					next();
				}
			});
		},
		function(err){
			cb(err);
		});	
}

//process the immediate events,
//at the moment, we only send or deduct the money.
function processImmediateEvents(conn, cb){
		var has_data = true;
		async.whilst(function(){
			return has_data;
		},
		function(next){
			conn.query(
				"SELECT a.id,a.game_team_id,a.master_event_id,b.event_name,\
				b.affected_item,b.amount,b.event_type,\
				b.name_appear_on_report,b.target_type,\
				b.target_value \
				FROM ffgame.job_event_immediate a \
				INNER JOIN ffgame.master_events b\
				ON a.master_event_id = b.id \
				WHERE a.n_status=0 LIMIT 10;",
				[],
				function(err,rs){
				if(rs!=null && rs.length>0){
					apply_event_modifier(conn,rs,function(err){
						next();
					});
				}else{
					has_data = false;
					next();
				}
				
			});
		},function(err){
			cb(err);
		});
}

function apply_event_modifier(conn,queue,cb){
	async.eachSeries(queue,function(q,next){
		if(q.affected_item==1){
			//apply money
			addMoney(conn,q,function(err){
				next();
			});
		}else{
			//skip dulu, yg apply per points nanti.
			next();
		}
	},function(err){
		cb(err);
	});
}
function addMoney(conn,queue,cb){
	console.log(queue);
	async.waterfall([
		function(callback){
			//get the next match's game_id
			next_match(conn,queue.game_team_id,function(err,next_match){
				if(next_match!=null && next_match.length > 0){
					
					callback(err,next_match[0]);	
				}else{
					callback(err,'');
				}
			});
		},
		function(next_match,callback){
			if(next_match!=''){
				if(queue.amount>0){
					transaction_type = 1;
				}else{
					//soalnya gak mungkin ss$ 0 kan, jadi selain diatas 0 kita anggap negatif
					transaction_type = 2;
				}
				conn.query("INSERT IGNORE INTO ffgame.game_team_expenditures\
				(game_team_id,item_name,item_type,amount,game_id,match_day,item_total,base_price)\
				VALUES\
				(?,?,?,?,?,?,?,?);",
				[queue.game_team_id,'other_'+queue.name_appear_on_report,transaction_type,
				  queue.amount,next_match.game_id,next_match.matchday,1,1],
				function(err,rs){
					console.log(S(this.sql).collapseWhitespace().s);
					callback(err,rs);
				});
			}else{
				callback(null,null);
			}
		},
		function(isDone,callback){
			conn.query("UPDATE ffgame.job_event_immediate SET n_status=1 WHERE id=?",
						[queue.id],
						function(err,rs){
							callback(err,rs);
						});
		}
	],
	function(err,rs){
		cb(err);
	});
}
function addMoneyPerk(conn,queue,cb){
	console.log('addMoneyPerk',queue);

	async.waterfall([
		function(callback){
			//get the next match's game_id
			next_match(conn,queue.game_team_id,function(err,next_match){
				if(next_match!=null && next_match.length > 0){
					
					callback(err,next_match[0]);	
				}else{
					callback(err,'');
				}
			});
		},
		function(next_match,callback){
			if(next_match!=''){
				if(queue.money_reward>0){
					transaction_type = 1;
				}else{
					//soalnya gak mungkin ss$ 0 kan, jadi selain diatas 0 kita anggap negatif
					transaction_type = 2;
				}
				conn.query("INSERT IGNORE INTO ffgame.game_team_expenditures\
				(game_team_id,item_name,item_type,amount,game_id,match_day,item_total,base_price)\
				VALUES\
				(?,?,?,?,?,?,?,?);",
				[queue.game_team_id,
				 'perk-'+queue.event_id+' '+queue.name,
				  transaction_type,
				  queue.money_reward,
				  next_match.game_id,
				  next_match.matchday,
				  1,
				  1],
				function(err,rs){
					console.log('addMoneyPerk',S(this.sql).collapseWhitespace().s);
					callback(err,rs);
				});
			}else{
				callback(null,null);
			}
		},
		function(isDone,callback){
			conn.query("UPDATE ffgame.game_perks \
						SET n_status=1,apply_dt=NOW() WHERE id=?",
						[queue.id],
						function(err,rs){
							console.log('addMoneyPerk',S(this.sql).collapseWhitespace().s);
							callback(err,rs);
						});
		}
	],
	function(err,rs){
		cb(err);
	});
}

function next_match(conn,game_team_id,done){
	
	
	async.waterfall(
		[
			function(callback){
				conn.query("SELECT team_id FROM ffgame.game_teams WHERE id = ? LIMIT 1",
							[game_team_id],function(err,rs){
								console.log('next_match',S(this.sql).collapseWhitespace().s);
								if(rs.length>0){
									callback(err,rs[0].team_id);	
								}else{
									callback(err,0);	
								}
								
							});
			},
			function(team_id,callback){
				conn.query("SELECT a.id,\
						a.game_id,a.home_id,b.name AS home_name,a.away_id,\
						c.name AS away_name,a.home_score,a.away_score,\
						a.matchday,a.period,a.session_id,a.attendance,match_date\
						FROM ffgame.game_fixtures a\
						INNER JOIN ffgame.master_team b\
						ON a.home_id = b.uid\
						INNER JOIN ffgame.master_team c\
						ON a.away_id = c.uid\
						WHERE (home_id = ? OR away_id=?) AND period <> 'FullTime'\
						ORDER BY a.matchday\
						LIMIT 1;\
						",[team_id,team_id],function(err,rs){
							callback(err,rs);
						});
			},
			function(rs,callback){
				var matchday = rs[0].matchday - 1;
				if(matchday == 0){
					matchday = 1;
				}
				try{
					conn.query("SELECT match_date \
								FROM \
								ffgame.game_fixtures \
								WHERE matchday = ? \
								ORDER BY match_date DESC \
								LIMIT 1;",
								[matchday],
								function(err,last_match){
									rs[0].last_match = last_match[0].match_date;
									callback(err,rs);
								});
				}catch(e){
					callback(null,rs);
				}
			}
		],
		function(err,result){
			done(err,result);
		}
	);
	
}

function nl2br (str, is_xhtml) {
  // http://kevin.vanzonneveld.net
  // +   original by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
  // +   improved by: Philip Peterson
  // +   improved by: Onno Marsman
  // +   improved by: Atli Þór
  // +   bugfixed by: Onno Marsman
  // +      input by: Brett Zamir (http://brett-zamir.me)
  // +   bugfixed by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
  // +   improved by: Brett Zamir (http://brett-zamir.me)
  // +   improved by: Maximusya
  // *     example 1: nl2br('Kevin\nvan\nZonneveld');
  // *     returns 1: 'Kevin<br />\nvan<br />\nZonneveld'
  // *     example 2: nl2br("\nOne\nTwo\n\nThree\n", false);
  // *     returns 2: '<br>\nOne<br>\nTwo<br>\n<br>\nThree<br>\n'
  // *     example 3: nl2br("\nOne\nTwo\n\nThree\n", true);
  // *     returns 3: '<br />\nOne<br />\nTwo<br />\n<br />\nThree<br />\n'
  var breakTag = (is_xhtml || typeof is_xhtml === 'undefined') ? '<br ' + '/>' : '<br>'; // Adjust comment to avoid issue on phpjs.org display

  return (str + '').replace(/([^>\r\n]?)(\r\n|\n\r|\r|\n)/g, '$1' + breakTag + '$2');
}