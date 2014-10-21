/**
cache reset
bot for resetting the master player stats's cache
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
var redis = require('redis');
/////DECLARATIONS/////////

var stat_maps = require('./libs/stats_map').getStats();

var http = require('http');



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

var client = redis.createClient(config.redis.port,config.redis.host);
client.on("error", function (err) {
    console.log("Error " + err);
});

var FILE_PREFIX = config.updater_file_prefix+config.competition.id+'-'+config.competition.year;

/////THE LOGICS///////////////

var pool  = mysql.createPool({
   host     : config.database.host,
   user     : config.database.username,
   password : config.database.password,
});


var bot_id = (typeof argv.bot_id !=='undefined') ? argv.bot_id : Math.round(1000+(Math.random()*999999));

var options = {
  host: config.job_server_rank.host,
  port: config.job_server_rank.port,
  path: '/job/?bot_id='+ bot_id
};
var limit = 100;
var dt = new Date();
console.log(options);

pool.getConnection(function(err,conn){
	async.waterfall([
		function(cb){
			conn.query("SELECT * FROM "+config.database.database+".master_player LIMIT 1000;",[],function(err,rs){
				cb(err,rs);
			});
		},
		function(players,cb){
			async.eachSeries(players,function(player,next){
				async.waterfall([
					function(callback){
						
						client.del(
								'getPlayerTeamStats_'+league+'_0_'+player.uid
								,function(err,lineup){
									
									console.log('RESET CACHE',
												'remove getPlayerTeamStats_0_'+player.uid,
												lineup);
									callback(err);
								});
						//console.log('getPlayerTeamStats_'+league+'_0_'+player.uid);
					},
					function(callback){
						client.del(
								'getPlayerDailyTeamStats_'+league+'_0_'+player.uid
								,function(err,lineup){
									console.log('RESET CACHE',
												'remove getPlayerDailyTeamStats_0_'+player.uid,
												lineup);
									callback(err);
								});
					}
				],function(err,r){
					next();
				});
				
			},function(err){
				console.log('completed');
				cb(null,null);
			});
		}
	],function(err,rs){
		conn.release();
		pool.end(function(err){
			console.log('closing redis');
			client.end();
			
		});
	});
});