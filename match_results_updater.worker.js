/**
match_results_updater.js -  multi-workers based.
these app will check if there's a new matchresults updates available.
it will load the data.. parse the xml.. and update the stats for each player's stats.

steps : 
1. check if there's a new file in the folder by crosschecking the file lists with those in database.
2. if there's a new file, we read the xml. and then update our master_report stats.
3. based on the master_report stats, we update individual user's starting players stats
4. flag the file so we dont have to process it anymore.

remember, each files is related to 1 game_id. so every summary must be grouped by game_id.
PS : these only process the master data.

@TODO
let's process unprocessed match.
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
var redis = require('redis');
/////DECLARATIONS/////////

var stat_maps = require('./libs/stats_map').getStats();


var match_results = require('./libs/match_results');
//var match_results = require('./libs/match_results_dummy');
var league = 'epl';
var argv = require('optimist').argv;
var config = require('./config').config;
if(typeof argv.league !== 'undefined'){
	switch(argv.league){
		case 'ita':
			console.log('Serie A Activated');
			config = require('./config.ita').config;
		break;
		case 'copa':
			console.log('COPA America Activated');
			config = require('./config.copa').config;
		break;
		default:
			console.log('EPL Activated');
			config = require('./config').config;
		break;
	}
	league = argv.league;
}


var FILE_PREFIX = config.updater_file_prefix+config.competition.id+'-'+config.competition.year;

//REDIS SETUP
var redisClient = redis.createClient(config.redis.port,config.redis.host);
redisClient.on("error", function (err) {
    console.log("Error " + err);
});

/////THE LOGICS///////////////

var pool = mysql.createPool({
			host: config.database.host,
			user: config.database.username,
			password: config.database.password
		});

pool.getConnection(function(err,conn){
	conn.query("SELECT * FROM "+config.database.database+".game_fixtures \
			WHERE is_processed=0 \
			ORDER BY id ASC LIMIT 10;",[],function(err,games){
				conn.release();
				generateReports(games);
	});
});


function generateReports(games){
	console.log('processing : ');
	for(var i in games){
		console.log(games[i].game_id);
	}
	async.eachSeries(games,function(item,callback){
		//console.log(item.game_id);
		process_report(item.game_id,function(err,result){
			if(err){
				console.log(err.message);
			}
			callback(null);	
		});
	},function(err){
		console.log('Done updating master stats and distributing the job');
		pool.end(function(err){
			match_results.done();
			console.log('closing redis connection');
			redisClient.quit();
		});
		
	});
}


/*
@todo generate master player performance summary ( "+config.database.statsdb+".master_player_performance)
*/
function process_report(game_id,done){
	console.log('process report #',game_id);

	async.waterfall([
		//1st step - get master reports for recent matches.
		function(callback){
			match_results.getReports(game_id,function(err,rs){
				callback(err,rs);
			});
			//callback(null,null);
		},
		function(result,callback){
			console.log('getReports -> ',result);
			if(result.SoccerFeed.SoccerDocument.MatchData.MatchInfo.Period=='FullTime'){
				console.log('already FullTime');
				console.log('distribute the job');
				async.waterfall([
					function(cb){
						distribute_jobs(game_id,function(err){
							cb(err);
						});
					},
					function(cb){
						//we need the matchday
						var competition_stat = result.SoccerFeed.SoccerDocument.Competition.Stat;
						var matchday = 1;
						for(var i in competition_stat){
							if(competition_stat[i].Type=='matchday'){
								matchday = competition_stat[i]['$t'];
								break;
							}
						}

						//reduce the team stats related perks availability
						reduce_perks_usage(matchday,function(err){
							cb(err);
						});

					}
				],
				function(err,rs){
					callback(err,'JOB DISTRIBUTED');
				});
				
			}else{
				callback(null,'ON GOING MATCH');
			}
			
		}
		
	],
	function(err,result){
		done(err,result);
		console.log(result);
	});
}

function distribute_jobs(game_id,done){
	console.log('distributing job for game_id #',game_id);
	pool.getConnection(function(err,conn){
		async.waterfall([
			function(cb){
				conn.query("SELECT COUNT(*) AS total FROM "+config.database.database+".game_teams;",[],
					function(err,rs){
						cb(err,rs[0].total);
				});
			},
			function(total_teams,cb){
				console.log('Total Teams',total_teams);
				var has_data = true;
				var start = 0;
				var limit = 100;
				var queue = [];
				async.doWhilst(
					function(callback){
						conn.query("SELECT id FROM "+config.database.database+".game_teams ORDER BY id ASC LIMIT ?,?",
						[
							start,
							limit
						],
						function(err,rs){
							//console.log(S(this.sql).collapseWhitespace().s);
							if(rs!=null && rs.length > 0){
								queue.push({
									since_id:rs[0].id,
									until_id:rs[rs.length-1].id
								});
								start+=limit;
							}else{
								has_data=false;
							}
							callback();
						});
					},
					function(){
						return has_data;
					},
					function(err){
						cb(err,queue);		
					}
				);
				
			},
			function(queue,cb){
				console.log(queue);
				async.eachSeries(queue,function(q,next){
					conn.query("INSERT IGNORE INTO "+config.database.statsdb+".job_queue\
					(game_id,since_id,until_id,worker_id,queue_dt,finished_dt,current_id,n_done,n_status)\
					VALUES\
					(?,?,?,0,NOW(),NULL,0,0,0)",
					[game_id,
					q.since_id,
					q.until_id
					],
					function(err,rs){
						next();
					});
				},function(err){
					cb(null,queue.length);
				});
			}
		],
		function(err,total_queue){
			conn.release();
			console.log(total_queue,'distributed');
			done(err);
		
		});
	});
}



function reduce_perks_usage(matchday,done){
	console.log('reduce_perks_usage','begins');
	pool.getConnection(function(err,conn){
		async.waterfall([
			function(cb){
				console.log('reduce_perks_usage','check if the perk has reduced for the current matchday');
				redisClient.get('perk_reduced-'+league+'-'+matchday,function(err,rs){
					console.log('reduce_perks_usage','perk_reduced-'+matchday,'->',rs);
					var canReduce = false;
					if(rs==null || JSON.parse(rs) == 0){
						canReduce = true;
					}else{
						canReduce = false;
					}
					cb(err,canReduce);
				});
			},
			function(canReduce,cb){
				if(canReduce){
					conn.query("UPDATE "+config.database.database+".digital_perks SET available = available - 1 \
							WHERE master_perk_id IN (\
							SELECT id FROM "+config.database.database+".master_perks\
							WHERE perk_name \
							IN \
							(\
							'POINTS_MODIFIER_PER_CATEGORY',\
							'EXTRA_POINTS_PERCENTAGE',\
							'EXTRA_POINTS_VALUE'\
							))\
							AND n_status=1;",[],function(err,rs){
								console.log(S(this.sql).collapseWhitespace().s);
								cb(err,canReduce);
							});
				}else{
					cb(null,canReduce);
				}
				
			},
			function(canReduce,cb){
				if(canReduce){
					conn.query("UPDATE "+config.database.database+".digital_perks SET n_status=0,available=0\
							WHERE master_perk_id IN (\
							SELECT id FROM "+config.database.database+".master_perks\
							WHERE perk_name \
							IN \
							(\
							'POINTS_MODIFIER_PER_CATEGORY',\
							'EXTRA_POINTS_PERCENTAGE',\
							'EXTRA_POINTS_VALUE'\
							))\
							AND\
							available < 0\
							AND n_status=1;",[],
							function(err,rs){
								console.log(S(this.sql).collapseWhitespace().s);
								cb(err,canReduce);
							});
				}else{
					cb(null,canReduce);
				}
			},
			function(canReduce,cb){
				//tell redis that these matchday has been reduced. 
				//so we dont have to reduce it again in the future
				if(canReduce){
					console.log('perk_reduced-'+matchday);
					redisClient.set('perk_reduced-'+league+'-'+matchday,1,function(err,rs){
						if(err){
							console.log('reduce_perks_usage',err.message);
						}
						cb(err,rs);
					});
				}else{
					console.log('reduce_perks_usage','no need to reduce');
					cb(null,null);
				}
				
			}
		],
		function(err,rs){
			conn.release();
			console.log('reduce_perks_usage','done');
			done(err,rs);
		
			
		});
	});
}