/**
this is a leaderboard generator

**/
/////THE MODULES/////////
var fs = require('fs');
var path = require('path');
var config = require('./config').config;
var game_config = require('./libs/game_config');
var xmlparser = require('xml2json');
var master = require('./libs/master');
var async = require('async');
var mysql = require('mysql');
var util = require('util');
var argv = require('optimist').argv;
var S = require('string');
var redis = require('redis');
/////DECLARATIONS/////////



var http = require('http');

var redisClient = redis.createClient(config.redis.port,config.redis.host);
redisClient.on("error", function (err) {
    console.log("Error " + err);
});


//var ranks = require(path.resolve('./libs/gamestats/ranks.worker'));

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




var limit = 100;
var dt = new Date();
var matchday = 0;
var t_month = 0;
var t_year = 0;
var total_time = 0;
//1. 	regularly update the player moods. player moods are not regenerative. so its based on several conditions.
//2. 	make a random offers by rolling D12, those who have 4+ will get a transfer offers

pool.getConnection(function(err,conn){
	//get the queue, and do the job !
	conn.query("SELECT * FROM "+config.database.statsdb+".job_update_ranks WHERE league=? AND n_status=0;",[league],
		function(err,rs){
			console.log(rs);
			if(rs!=null && rs.length > 0){
				matchday = rs[0].matchday;
				t_month = rs[0].t_month;
				t_year = rs[0].t_year;
				console.log('got job, here we go again...');
				run(function(err){
					conn.query("UPDATE "+config.database.statsdb+".job_update_ranks SET \
								n_status = 1,finished_dt = NOW(), elapsed_time = ? \
								WHERE id = ?",[total_time,rs[0].id],function(err,rs){
									console.log('flag',S(this.sql).collapseWhitespace().s);
										pool.end(function(err){
											console.log('pool closed');
											console.log('quitting redis');
											//delete the cache
											
											cleaningUp(function(){
												redisClient.quit(function(err){
													console.log('done');
												});
											});
											//redisClient.quit(function(err){});
											
										});
								});
				});
			}else{
				console.log('no job, bye !');
				conn.release();
				pool.end(function(err){
					console.log('pool closed');
					console.log('quitting redis');
					redisClient.quit(function(err){
						console.log('done');
					});
					
				});
			}
		});
});
function run(done){
	async.waterfall([
		//overall points
		function(cb){
			console.log("OVERALL POINTS",league);
			calculate_rankings(function(err){
				cb(err);
			});
		},
		function(cb){
			console.log('UPDATING DATABASE');
			redisClient.llen('team_sorted_'+league,function(err,counts){
				console.log('total sorted :',counts);
				cb(err,counts);
			});
		},
		function(counts,cb){
			redisClient.lrange('team_sorted_'+league,0,counts,function(err,rs){
				cb(err,rs);
			});
		},
		function(data,cb){
			var rank = 1;
			var ts = (new Date().getTime());
			var ets = 0;
			var bulk = [];
			var n = 0;
			console.log('updating ranks');
			pool.getConnection(function(err,conn){
				async.whilst(function(){
					if(data.length!=0){
						//console.log('current lenght',data.length);
						return true;
					}else{
						return false;
					}
				},
				function(done){
					var o = data.pop();
					bulk.push([o,rank,league]);
					rank++;
					if((n%100 == 0 && n>0) || data.length==0){
						//console.log(n,data.length);
						conn.query("INSERT INTO fantasy.points(team_id,rank,league) VALUES ? \
								ON DUPLICATE KEY UPDATE \
								rank = VALUES(rank)",
						[bulk],
						function(err,rs){
							if(err){
								console.log(err.message);
							}
							//console.log(S(this.sql).collapseWhitespace().s);
							bulk = [];
							n++;
							done();
						});
					}else{
						n++;
						done();
					}
					
				},function(err){
					total_time+=(((new Date()).getTime() - ts) / 60000);
					console.log('Ended in ',(((new Date()).getTime() - ts) / 60000),'minutes');
					conn.release();
					cb(err);
				});
			});
			
		},
		function(cb){
			console.log("Weekly Points",league,"matchday : "+matchday);
			processWeeklyPoints(function(err){
				cb(err);
			});
		},
		function(cb){
			console.log('UPDATING DATABASE');
			redisClient.llen('weekly_points_sorted_'+league,function(err,counts){
				console.log('total sorted :',counts);
				cb(err,counts);
			});
		},
		function(counts,cb){
			redisClient.lrange('weekly_points_sorted_'+league,0,counts,function(err,rs){
				cb(err,rs);
			});
		},
		function(data,cb){

			//console.log(data);
			var rank = 1;
			var ts = (new Date().getTime());
			var ets = 0;
			var bulk = [];
			var n = 0;
			console.log('updating weekly ranks');
			pool.getConnection(function(err,conn){
				async.whilst(function(){
					if(data.length!=0){
						//console.log('current lenght',data.length);
						return true;
					}else{
						return false;
					}
				},
				function(done){
					var o = data.pop();
					bulk.push([o,matchday,rank,league]);
					rank++;
					if((n%100 == 0 && n>0) || data.length==0){
						//console.log(n,data.length);
						conn.query("INSERT INTO fantasy.weekly_ranks(team_id,matchday,rank,league) \
								VALUES ? \
								ON DUPLICATE KEY UPDATE \
								rank = VALUES(rank)",
						[bulk],
						function(err,rs){
							if(err){
								console.log(err.message);
							}
							console.log(S(this.sql).collapseWhitespace().s);
							bulk = [];
							n++;
							done();
						});
					}else{
						n++;
						done();
					}
					
				},function(err){
					total_time+=(((new Date()).getTime() - ts) / 60000);
					console.log('Ended in ',(((new Date()).getTime() - ts) / 60000),'minutes');
					conn.release();
					cb(err);
				});
			});
			
		},

		function(cb){
			console.log("Monthly Points",league,"matchday : "+matchday);
			processMonthlyPoints(function(err){
				cb(err);
			});
		},
		function(cb){
			console.log('UPDATING DATABASE');
			redisClient.llen('monthly_points_sorted_'+league,function(err,counts){
				console.log('total sorted :',counts);
				cb(err,counts);
			});
		},
		function(counts,cb){
			redisClient.lrange('monthly_points_sorted_'+league,0,counts,function(err,rs){
				cb(err,rs);
			});
		},
		function(data,cb){

			//console.log(data);
			var rank = 1;
			var ts = (new Date().getTime());
			var ets = 0;
			var bulk = [];
			var n = 0;
			console.log('updating monthly ranks');
			pool.getConnection(function(err,conn){
				async.whilst(function(){
					if(data.length!=0){
						//console.log('current lenght',data.length);
						return true;
					}else{
						return false;
					}
				},
				function(done){
					var o = data.pop();
					bulk.push([o,t_month,t_year,rank,league]);
					rank++;
					if((n%100 == 0 && n>0) || data.length==0){
						//console.log(n,data.length);
						conn.query("INSERT INTO fantasy.monthly_points(team_id,bln,thn,rank,league) \
								VALUES ? \
								ON DUPLICATE KEY UPDATE \
								rank = VALUES(rank)",
						[bulk],
						function(err,rs){
							if(err){
								console.log(err.message);
							}
							console.log(S(this.sql).collapseWhitespace().s);
							bulk = [];
							n++;
							done();
						});
					}else{
						n++;
						done();
					}
					
				},function(err){
					total_time+=(((new Date()).getTime() - ts) / 60000);
					console.log('Ended in ',(((new Date()).getTime() - ts) / 60000),'minutes');
					conn.release();
					cb(err);
				});
			});
			
		}

	],

	function(err){
		done(err);
	});
}


function cleaningUp(callback){
	async.waterfall([
		function(cb){
			redisClient.llen('teams_'+league,function(err,counts){
				redisClient.lrange('teams_'+league,0,counts,function(err,rs){

					async.eachSeries(rs,function(item,next){
						redisClient.del("tp_"+league+"_"+item,function(err){
							//console.log("removing : "+"tp_"+league+"_"+item);
							next();
						});
					},function(err){
						redisClient.del("teams_"+league,function(err){
							console.log("teams_"+league,"removed");
							redisClient.del("team_sorted_"+league,function(err){
								console.log("team_sorted_"+league,"removed");
								cb();
							});
						});
					});
				});
			});
		},
		function(cb){
			redisClient.llen('weekly_points_'+league,function(err,counts){
				redisClient.lrange('weekly_points_'+league,0,counts,function(err,rs){

					async.eachSeries(rs,function(item,next){
						redisClient.del("twp_"+league+"_"+item,function(err){
							//console.log("removing : "+"tp_"+league+"_"+item);
							next();
						});
					},function(err){
						redisClient.del("weekly_points_"+league,function(err){
							console.log("weekly_points_"+league,"removed");
							redisClient.del("weekly_points_sorted_"+league,function(err){
								console.log("weekly_points_sorted_"+league,"removed");
								cb();
							});
						});
					});
				});
			});
		},
		function(cb){
			redisClient.llen('monthly_points_'+league,function(err,counts){
				redisClient.lrange('monthly_points_'+league,0,counts,function(err,rs){

					async.eachSeries(rs,function(item,next){
						redisClient.del("tmp_"+league+"_"+item,function(err){
							//console.log("removing : "+"tp_"+league+"_"+item);
							next();
						});
					},function(err){
						redisClient.del("monthly_points_"+league,function(err){
							console.log("monthly_points_"+league,"removed");
							redisClient.del("monthly_points_sorted_"+league,function(err){
								console.log("monthly_points_sorted_"+league,"removed");
								cb();
							});
						});
					});
				});
			});
		}
	],

	function(err){
		callback();
	});
	
}

function calculate_rankings(callback){
	var is_done = false;
	var since_id = 0;
	var limit = 1000;
	console.log(since_id);
	console.log('reset the cache');
	redisClient.del('teams_'+league,function(err){

		async.whilst(
		    function () { 
		    	if(!is_done){return true;}
		    	return false;
		    },
		    function (done) {
		    	async.waterfall([
		    		
		    		function(cb){
		    			 pool.getConnection(function(err,conn){
		    			 	console.log('since_id : ',since_id);
				        	conn.query("SELECT * FROM fantasy.points WHERE id > ? AND league=? \
				        					ORDER BY id ASC LIMIT "+limit,
				        				[
				        					since_id,
				        					league
				        				],function(err,rs){
				        					
				        					if(rs!=null && rs.length > 0){
				        						
				        						since_id = rs[rs.length-1].id;
				        					}else{
				        						is_done = true;
				        						rs = [];
				        					}
				        					conn.release();
				        					cb(err,rs);
				        				});
				        });
		    		},
		    		function(rs,cb){
		    			var multi = redisClient.multi();

		    			for(var i in rs){
		    				//console.log('teams_'+league,'->',rs[i].team_id);
		    				multi.rpush('teams_'+league,
		    									rs[i].team_id);
		    			}
		    			multi.exec(function(err,result){
		    				if(err){
		    					console.log(err.message);
		    				}
		    				
		    				async.eachSeries(rs,function(item,next){
	    						//console.log('tp_'+league+'_'+item.team_id);
	    						redisClient.set('tp_'+league+'_'+item.team_id,
	    											Math.floor(item.points + item.extra_points),
	    						function(err,rs){
	    							next();
	    						});
			    				
			    			},function(err){
			    				cb(null,rs);
			    			});
		    			});
		    		
		    			
		    		},
		    		
		    	],
		    	function(err,rs){
		    		done(err);
		    	});
		       
		    },
		    function (err) {
		    	//lets sort it out
    			redisClient.sort('teams_'+league,'BY','tp_'+league+'_*',
    							'STORE','team_sorted_'+league,
    			function(err,rs){
    				
    				 callback(err);
    			});
		       
		    }
		);
	});
	
}


function processWeeklyPoints(callback){
	var is_done = false;
	var since_id = 0;
	var limit = 1000;
	redisClient.del('weekly_points_'+league,function(err){
		async.whilst(
		    function () { 
		    	if(!is_done){return true;}
		    	return false;
		    },
		    function (done) {
		    	async.waterfall([
		    		function(cb){
		    			pool.getConnection(function(err,conn){
		    				conn.query("SELECT id,team_id, \
		    							SUM(points+extra_points) as total_points \
		    							FROM fantasy.weekly_points \
		    							WHERE team_id > ? AND league=? AND matchday = ?\
		    							GROUP BY team_id \
		    							ORDER BY team_id ASC LIMIT "+limit,
		    							[
		    								since_id,
		    								league,
		    								matchday
		    							],function(err,rs){
		    								//console.log(S(this.sql).collapseWhitespace().s);
		    								if(rs!=null && rs.length >0){
		    									since_id = rs[rs.length-1].team_id;
		    								}else{
		    									is_done = true;
		    									rs = [];
		    								}
		    								conn.release();
		    								cb(err,rs);						
		    				});
		    			});
		    		},
		    		function(rs,cb){
		    			var multi = redisClient.multi();

		    			for(var i in rs){
		    				//console.log('->',rs[i].team_id);
		    				multi.rpush('weekly_points_'+league,
		    									rs[i].team_id);
		    			}
		    			multi.exec(function(err,result){
		    				if(err){
		    					console.log(err.message);
		    				}
		    				
		    				async.eachSeries(rs,function(item,next){
	    						//console.log('twp_'+league+'_'+item.team_id);
	    						redisClient.set('twp_'+league+'_'+item.team_id,
	    											Math.floor(item.total_points),
	    						function(err,rs){
	    							next();
	    						});
			    				
			    			},function(err){
			    				cb(null,rs);
			    			});
		    			});
		    		}
		    	],
		    	function(err,rs){
		    		done();
		    	});
		    },
		    function(err){
		    	//lets sort it out
    			console.log('weekly_points_'+league,'BY','twp_'+league+'_*',
    							'STORE','weekly_points_sorted_'+league);
    			redisClient.sort('weekly_points_'+league,'BY','twp_'+league+'_*',
    				'STORE','weekly_points_sorted_'+league,
    			function(err,rs){
    				
    				callback(err);
    				
    			});
		    	
			}
		);
	});
}

function processMonthlyPoints(callback){
	var is_done = false;
	var since_id = 0;
	var limit = 1000;
	redisClient.del('monthly_points_'+league,function(err){
		async.whilst(
		    function () { 
		    	if(!is_done){return true;}
		    	return false;
		    },
		    function (done) {
		    	async.waterfall([
		    		function(cb){
		    			pool.getConnection(function(err,conn){
		    				conn.query("SELECT id,team_id, \
		    							SUM(points+extra_points) as total_points \
		    							FROM fantasy.weekly_points \
		    							WHERE team_id > ? AND league=? AND t_month = ? AND t_year = ?\
		    							GROUP BY team_id \
		    							ORDER BY team_id ASC LIMIT "+limit,
		    							[
		    								since_id,
		    								league,
		    								t_month,
		    								t_year
		    							],function(err,rs){
		    								//console.log(S(this.sql).collapseWhitespace().s);
		    								if(rs!=null && rs.length >0){
		    									since_id = rs[rs.length-1].team_id;
		    								}else{
		    									is_done = true;
		    									rs = [];
		    								}
		    								conn.release();
		    								cb(err,rs);						
		    				});
		    			});
		    		},
		    		function(rs,cb){
		    			var multi = redisClient.multi();

		    			for(var i in rs){
		    				//console.log('->',rs[i].team_id);
		    				multi.rpush('monthly_points_'+league,
		    									rs[i].team_id);
		    			}
		    			multi.exec(function(err,result){
		    				if(err){
		    					console.log(err.message);
		    				}
		    				
		    				async.eachSeries(rs,function(item,next){
	    						//console.log('twp_'+league+'_'+item.team_id);
	    						redisClient.set('tmp_'+league+'_'+item.team_id,
	    											Math.floor(item.total_points),
	    						function(err,rs){
	    							next();
	    						});
			    				
			    			},function(err){
			    				cb(null,rs);
			    			});
		    			});
		    		}
		    	],
		    	function(err,rs){
		    		done();
		    	});
		    },
		    function(err){
		    	pool.getConnection(function(err,conn){
    				conn.query("INSERT INTO fantasy.monthly_points\
    							(team_id,points,rank,league,bln,thn)\
    							SELECT team_id, \
    							SUM(points+extra_points) as total_points,0 as ranks,league,t_month,t_year \
    							FROM fantasy.weekly_points \
    							WHERE league=? AND t_month = ? AND t_year = ?\
    							GROUP BY team_id;",
    							[
    								league,
    								t_month,
    								t_year
    							],function(err,insertRs){
    								console.log('monthly_points',S(this.sql).collapseWhitespace().s);
    								conn.release();
    								//lets sort it out
					    			console.log('monthly_points_'+league,'BY','twp_'+league+'_*',
					    							'STORE','weekly_points_sorted_'+league);
					    			redisClient.sort('monthly_points_'+league,'BY','tmp_'+league+'_*',
					    				'STORE','monthly_points_sorted_'+league,
					    			function(err,rs){
					    				
					    				callback(err);
					    				
					    			});			
    							});
    			});
		    	
		    	
			}
		);
	});
}