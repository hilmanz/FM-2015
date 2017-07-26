/**
ranks and points updater
Multi-Worker.
**/
/////THE MODULES/////////
var fs = require('fs');
var path = require('path');
var config = require('./config').config;
var xmlparser = require('xml2json');
var master = require('./libs/master');
var async = require('async');
var mysql = require('mysql');
var util = require('util');
var argv = require('optimist').argv;
var S = require('string');
/////DECLARATIONS/////////

var stat_maps = require('./libs/stats_map').getStats();

var http = require('http');


var ranks = require(path.resolve('./libs/gamestats/ranks.worker'));

var league = 'epl';
if(typeof argv.league !== 'undefined'){
	switch(argv.league){
		case 'ita':
			console.log('Serie A Activated');
			config = require('./config.ita').config;
		break;
		default:
			console.log('EPL Activated');
			config = require('./config').config;
		break;
	}
	league = argv.league;
}


var FILE_PREFIX = config.updater_file_prefix+config.competition.id+'-'+config.competition.year;

/////THE LOGICS///////////////

var pool  = mysql.createPool({
   host     : config.database.host,
   user     : config.database.username,
   password : config.database.password,
});
ranks.setPool(pool);
ranks.setConfig(config);
ranks.setLeague(league);


var bot_id = (typeof argv.bot_id !=='undefined') ? argv.bot_id : Math.round(1000+(Math.random()*999999));

var options = {
  host: config.job_server_rank.host,
  port: config.job_server_rank.port,
  path: '/job/?bot_id='+ bot_id
};
var limit = 100;
var dt = new Date();
console.log(options);


http.request(options, function(response){
	var str = '';
	response.on('data', function (chunk) {
	    str += chunk;
	});
	response.on('end',function(){
		var resp = JSON.parse(str);
		console.log(resp);
		
		//resp.status=1;//DEBUG ONLY
		//resp.data.since_id=0;
		//resp.data.until_id=1;
		//resp.last_queue = 1;
		//resp.data.game_id = 'f694992';
		if(resp.status==1){
			
			console.log('RANK-WORKER-'+bot_id,'processing #queue',resp.data.id,' of game #',
						resp.data.game_id,
						' starting from',resp.data.since_id,' until ',resp.data.until_id);
			resp.data.update_rank = false;
			if(resp.last_queue == 1){
				resp.data.update_rank = true;
			}

			var open_time = dt.getTime();
			console.log('TIME','OPEN : ',open_time);
			pool.getConnection(function(err,conn){
				process_report(
					conn,
					resp.data,
					function(err,rs){
					
					console.log('FINISHED');
					conn.query("UPDATE "+config.database.statsdb+".job_queue_rank \
								SET finished_dt = NOW(),\
									n_status = 2 \
								WHERE id = ?",
								[resp.data.id],function(err,rs){
									console.log('RANK-WORKER-'+bot_id,'flag queue as done');
									conn.release();
										dt = new Date();
										var close_time = dt.getTime();
										console.log('TIME','CLOSE : ',close_time);
										console.log('TIME','SPENT : ',(close_time-open_time),'ms');
										console.log('RANK-WORKER-'+bot_id,'database connection closed');
										pool.end(function(err){
											console.log('RANK-WORKER-'+bot_id,'database pool closed');
										});
										
									
									
								});
					
				});
			});
			
		}else{
			pool.end(function(err){
				console.log('RANK-WORKER-'+bot_id,'no need to update points and ranks close db anyway');
			});
		}
	});
}).end();

function process_report(conn,job,done){
	async.waterfall([
		function(callback){
			update_points_and_ranks(job,conn,function(err){
				callback(err,null);	
			});
		}
	],
	function(err,rs){
		done(err,rs);
	});
}
function update_points_and_ranks(job,conn,done){
	
	conn.query("SELECT id as game_team_id,team_id as original_team_id \
				FROM "+config.database.database+".game_teams \
				WHERE id BETWEEN ? AND ?\
				ORDER BY id ASC LIMIT ?;",
				[
					job.since_id,
					job.until_id,
					limit
				],
				function(err,rs){
					if(rs!=null && rs.length > 0){
						console.log('RANK-WORKER-'+bot_id,S(this.sql).collapseWhitespace().s);
						since_id = rs[rs.length-1].game_team_id;
						async.waterfall([
							function(c){
								update_team_points(conn,rs,function(err){
									c(err);
								});
							},
							function(c){
								apply_perks(conn,rs,function(err){
									c(err);
								});
							}
						],
						function(err,r){
							ranks.update(
								conn,
								job.since_id,
								job.until_id,
								job.update_rank,
								job.game_id,
								function(err,rs){
									console.log('done');
									done(err);
								}
							);
							
						});
					}else{
						done(err);
					}
				});
				
			
	
}
function apply_perks(conn,teams,done){
	async.eachSeries(teams,function(team,next){
		async.waterfall([
			function(cb){
				conn.query("SELECT matchday \
							FROM \
							"+config.database.statsdb+".game_team_player_weekly \
							WHERE game_team_id=? ORDER BY matchday DESC LIMIT 1;",
							[team.game_team_id],function(err,rs){
								console.log('PERK',console.log(S(this.sql).collapseWhitespace().s));
								try{
									if(rs!=null){
										cb(err,rs[0].matchday);
									}else{
										cb(null,0);
									}
								}catch(err){
									cb(null,0);
								}
								
							});
			},
			function(matchday,cb){
				//get the game id
				conn.query("SELECT game_id FROM "+config.database.database+".game_fixtures \
							WHERE matchday=? \
							AND (home_id = ? OR away_id = ?) \
							LIMIT 1;",
							[matchday,
							 team.original_team_id,
							 team.original_team_id],
							 function(err,fixture){
							 	if(fixture!=null && fixture.length > 0){
							 		cb(err,fixture[0].game_id,matchday);	
							 	}else{
							 		cb(err,'',matchday);
							 	}
								
							});
			},
			function(game_id,matchday,cb){
				conn.query("SELECT * FROM "+config.database.database+".game_perks \
					WHERE game_team_id=? \
					AND matchday=? \
					AND n_status=0 \
					LIMIT 100;",
					[team.game_team_id,matchday],
					function(err,perks){
						console.log('PERK',console.log(S(this.sql).collapseWhitespace().s));
						process_perks(conn,team,perks,game_id,function(err){
							cb(err);
						});
					});
			}

		],
		function(err){
			next();
		});
		
	},function(err){
		done(err);
	});
}
function process_perks(conn,team,perks,game_id,done){
	console.log('PERK','processing perk for #',team.game_team_id);
	if(perks!=null && perks.length>0){
		console.log('PERK',perks);
		async.eachSeries(perks,function(perk,next){
			async.waterfall([
				function(cb){
					if(perk.money_reward > 0){
						//perk_money_reward(conn,team.game_team_id,perk,game_id,
						//function(err){
						//	cb(err);
						//});
						cb(null);
					}else{
						cb(null);
					}
				},
				function(cb){
					//flag as done
					flagPerkAsDone(conn,perk.id,function(err){
						cb(err);
					});
				}
			],
			function(err){
				next();
			});
		},function(err){
			console.log('PERK','done');
			done(err);
		});
	}else{
		console.log('PERK','no perk to process');
		done(null);
	}
}
function flagPerkAsDone(conn,perk_id,done){
	conn.query("UPDATE "+config.database.database+".game_perks SET n_status=1 WHERE id = ?",
				[perk_id],function(err,rs){
					done(err);
				});
}
function perk_money_reward(conn,game_team_id,perk,game_id,done){
	conn.query("INSERT IGNORE INTO "+config.database.database+".game_team_expenditures\
				(game_team_id,item_name,item_type,\
				 amount,game_id,match_day,item_total,base_price)\
				VALUES\
				(?,?,?,?,?,?,?,?);",
				[game_team_id,'perk-'+perk.event_id+' '+perk.name,
				 1,perk.money_reward,game_id,perk.matchday,1,1],
				function(err,rs){
					done(err);
				});
}
function update_team_points(conn,teams,done){
	var game_team_id = [];
	for(var i=0; i < teams.length;i++){
		game_team_id.push(teams[i].game_team_id);
	}
	conn.query("INSERT INTO "+config.database.statsdb+".game_team_points\
					(game_team_id,points)\
					(SELECT game_team_id,SUM(points) AS total_points\
					FROM "+config.database.statsdb+".game_team_player_weekly\
					WHERE game_team_id IN (?)\
					GROUP BY game_team_id)\
					ON DUPLICATE KEY UPDATE\
					points = VALUES(points);",
					[game_team_id],
					function(err,rs){
						console.log('GAME_TEAM_POINTS',S(this.sql).collapseWhitespace().s);
						done(err);
					});
	
}