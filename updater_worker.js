/**
updater.worker.js
**/
/////THE MODULES/////////
var argv = require('optimist').argv;
var config = require('./config').config;
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



var fs = require('fs');
var path = require('path');
var xmlparser = require('xml2json');
var master = require('./libs/master');
var async = require('async');
var mysql = require('mysql');
var util = require('util');

var redis = require('redis');
/////DECLARATIONS/////////
var FILE_PREFIX = config.updater_file_prefix+config.competition.id+'-'+config.competition.year;
var stat_maps = require('./libs/stats_map').getStats();

var http = require('http');


var match_results = require('./libs/match_results');
var lineup_stats = require('./libs/gamestats/lineup_stats.worker');
var business_stats = require('./libs/gamestats/business_stats.worker');
var ranks = require(path.resolve('./libs/gamestats/ranks.worker'));



//now we need Redis to play with data caches.

//REDIS SETUP
var redisClient = redis.createClient(config.redis.port,config.redis.host);
redisClient.on("error", function (err) {
    console.log("Error " + err);
});

//attach redisClient to the following modules
lineup_stats.setRedisClient(redisClient);
lineup_stats.setConfig(config);
lineup_stats.setLeague(league);
business_stats.setConfig(config);
business_stats.setLeague(league);

/////THE LOGICS///////////////
var conn = mysql.createConnection({
 	host     : config.database.host,
   user     : config.database.username,
   password : config.database.password,
});



var bot_id = (typeof argv.bot_id !=='undefined') ? argv.bot_id : Math.round(1000+(Math.random()*999999));


var options = {
  host: config.job_server.host,
  port: config.job_server.port,
  path: '/job/?bot_id='+bot_id
};
console.log(options);

http.request(options, function(response){
	var str = '';
	response.on('data', function (chunk) {
	    str += chunk;
	});
	response.on('end',function(){
		var resp = JSON.parse(str);
		console.log(resp);
		if(resp.status==1){
			var game_id = resp.data.game_id;
			var since_id = resp.data.since_id;
			var until_id = resp.data.until_id;
			var queue_id = resp.data.id;
			console.log('WORKER-'+bot_id,'processing #queue',queue_id,' of game #',game_id,
						' starting from',since_id,' until ',until_id);

			process_report(queue_id,game_id,since_id,until_id,function(err,rs){
				console.log('DONE');
				async.waterfall([
					function(cb){
						conn.query("UPDATE "+config.database.statsdb+".job_queue SET finished_dt = NOW(),n_status=2 WHERE id = ?",
							[queue_id],function(err,rs){
								console.log('flag queue as done');
								cb(err);
							});
					},
					function(cb){
						conn.query("INSERT IGNORE INTO\
									"+config.database.statsdb+".job_queue_rank\
									(game_id,since_id,until_id,worker_id,queue_dt,current_id,n_done,n_status)\
									VALUES\
									(?,?,?,0,NOW(),0,0,0);",
							[game_id,since_id,until_id],function(err,rs){
								console.log('insert queue into job_queue_rank');
								cb(err);
							});
					}
				],

				function(err){
					conn.end(function(err){
						console.log('database connection closed');
						lineup_stats.done();
						business_stats.done();
						redisClient.quit(function(err){
							console.log('redis closed');
						});
					});
				});
				
				
			});
		}else{
			redisClient.quit(function(err){
				console.log('redis closed');
			});
		}
	});
}).end();


/*
@todo generate master player performance summary ( ffgame_stats.master_player_performance)
*/
function process_report(queue_id,game_id,since_id,until_id,done){
	console.log('process report #',game_id);
	async.waterfall([
		function(callback){
			var is_finished = false;
			console.log('lineup_stats update',game_id,since_id,until_id);
			lineup_stats.update(queue_id,game_id,since_id,until_id,
			function(err,is_done){
				is_finished = is_done;
				callback(err,is_done);
			});
			
			//callback(null,true);
			
		},function(is_done,callback){
			console.log('business stats update ',game_id,'from',since_id,'until',until_id);
			business_stats.update(since_id,until_id,game_id,function(err){
				console.log('business stats update completed');
				console.log('all batches has been processed');
				callback(err,is_done);
			});
		}
	],
	function(err,result){
		done(err,result);
		console.log(result);
	});
}