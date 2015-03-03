/**
 * Module dependencies.
 */

var express = require('express')
  , routes = require('./routes')
  , user = require('./routes/user')
  , http = require('http')
  , path = require('path');
var mysql = require('mysql');
var redis = require('redis');
var dummy_api_key = '1234567890';
var auth = require('./libs/api/auth');
var config = require('./config').config;
var async = require('async');
var request = require('request');
var argv = require('optimist').argv;
var report_team = require(path.resolve('./libs/stats_report_team'));
var report_player = require(path.resolve('./libs/stats_report_player'));
//mysql pool
var pool  = mysql.createPool({
   host     : config.database.host,
   user     : config.database.username,
   password : config.database.password,
});


var app = express();
var RedisStore = require('connect-redis')(express);

// all environments
var app_port =  config.port;

if(typeof argv.port !== 'undefined'){
	app_port = argv.port;
}

app.set('port', app_port);
app.set('views', __dirname + '/views');
app.set('view engine', 'jade');
app.use(express.favicon());
app.use(express.logger('dev'));
app.use(express.bodyParser());
app.use(express.methodOverride());
app.use(express.cookieParser('i die, you die, everybody die'));
app.use(express.session({ store: new RedisStore(config.redis) }));

var client = redis.createClient(config.redis.port,config.redis.host);
client.on("error", function (err) {
    console.log("Error " + err);
});
app.use(function(req,res,next){
	//bind everything we need
	req.redisClient = client;
	next();
});
app.use(app.router);

app.use(express.static(path.join(__dirname, 'public')));

// development only
if ('development' == app.get('env')) {
  app.use(express.errorHandler());
}

app.get('/', function(req,res){
	res.send(200,{status:1});
});
app.get('/fixtures',[],function(req,res){
	if(typeof req.query['league'] === 'undefined'){
		req.query['league'] = 'epl';
	}
	getFixtures(req.query['league'],function(err,rs){
		res.send(200,{status:1,data:rs});
	});
});
app.get('/simulator/reset', [], function(req,res){
	resetData(function(err,rs){
		res.send(200,{status:1});	
	});
});
app.get('/simulator/update', [], function(req,res){
	simulate(function(err,rs){
		res.send(200,{status:1,data:rs});	
	});
	
});

app.get('/playerstats/:game_id',[],function(req,res){
	getPlayerStats(req.params.game_id, function(err,rs){
		res.send(200,{status:1,data:rs});
	});
});

app.get('/matchstats/:game_id',[],function(req,res){
	client.get('teamstats_'+req.params.game_id,function(err,resp){
		var data = JSON.parse(resp);
		report_team.process_team_stats(data,function(err,rs){
			res.send(200,{status:1,data:rs});
		});
	});
});
app.get('/get/:game_id',[],function(req,res){
	
	client.get('lvrt_'+req.params.game_id,function(err,resp){
		
		var data = JSON.parse(resp);
		res.send(200,{status:1,data:data});
	});
});
app.get('/fm_playerstats/:game_id',[],function(req,res){
	client.get('match_'+req.params.game_id,function(err,resp){
		var data = JSON.parse(resp);
		res.send(200,{status:1,data:data});
	});
});
app.get('/livestats/:game_id',[],function(req,res){
	
	var game_id = req.params.game_id;
	async.waterfall([
		function(cb){
			client.get('teamstats_'+game_id,function(err,rs){
				cb(err,JSON.parse(rs));
			});
		},
		function(teamstats,cb){
			client.get('goals_'+game_id,function(err,rs){
				cb(err,teamstats,JSON.parse(rs));
			});
		},
		function(teamstats,goals,cb){
			client.get('playerrefs_'+game_id,function(err,rs){
				cb(err,teamstats,goals,JSON.parse(rs));
			});
		},
		function(teamstats,goals,playerrefs,cb){
			client.get('fixture_'+game_id,function(err,rs){
				cb(err,teamstats,goals,playerrefs,JSON.parse(rs));
			});
		},
		function(teamstats,goals,playerrefs,fixture,cb){
			client.get('accstats_'+game_id,function(err,rs){
				cb(err,teamstats,goals,playerrefs,fixture,JSON.parse(rs));
			});
		},
		function(teamstats,goals,playerrefs,fixture,player_stats,cb){
			reformatResults(teamstats,goals,playerrefs,fixture,player_stats,function(err,rs){
				cb(err,rs);
			});
		}
	],
	function(err,rs){
		res.send(200,{status:1,data:rs});
	});
});

http.createServer(app).listen(3999, function(){
  console.log('ready');
});



function reformatResults(teamstats,goals,playerrefs,fixture,player_stats,done){
	
	var team_stats = formatTeamStats(teamstats);
	var goals = formatGoalStats(goals);
	var players = formatPlayerRefs(playerrefs);
	//var player_stats = formatPlayerStats(player_stats);


	done(null,{
		team_stats:team_stats,
		goals:goals,
		lineup:players,
		matchinfo:fixture
	});
}

function formatPlayerRefs(data){
	var stats = {};
	//console.log(goals);
	
	for(var i in data){
		if(typeof stats[data[i].team_id] === 'undefined'){
			stats[data[i].team_id] = [];

		}
		
		stats[data[i].team_id].push(data[i]);
	}
	
	return stats;
}
function formatGoalStats(goals){
	var stats = {};
	//console.log(goals);
	
	for(var i in goals){
		if(typeof stats[goals[i].team_id] === 'undefined'){
			stats[goals[i].team_id] = [];

		}
		
		stats[goals[i].team_id].push(goals[i]);
	}
	
	return stats;
}
function formatTeamStats(teamstats){
	var stats = {};

	for(var i in teamstats){
		if(typeof stats[teamstats[i].team_id] === 'undefined'){
			stats[teamstats[i].team_id] = {};

		}
		if(typeof stats[teamstats[i].team_id][teamstats[i].stats_name] === 'undefined'){
			stats[teamstats[i].team_id][teamstats[i].stats_name] = 0;
		}
		stats[teamstats[i].team_id][teamstats[i].stats_name] += parseInt(teamstats[i].stats_value);
	}
	return stats;
}

function simulate(done){
	var game_id = 'f750260';
	var str_game_id = '750260';

	pool.getConnection(function(err,conn){
		async.waterfall([
			function(cb){
				//get all pushlogs 
				conn.query("SELECT * FROM optadb_wc.pushlogs WHERE gameId=? AND feedType = 'F9' ORDER BY id ASC;",
							[str_game_id],function(err,rs){
								cb(err,rs);
							});
			},
			function(rs,cb){
				client.get('simulator_'+game_id,function(err,data){
					
					var d = rs[parseInt(data)];
					var fs = require('fs');
					fs.writeFile("./data/"+d.saved_file,d.content, 
					function(err) {
					    if(err) {
					        console.log(err.message);
					    } else {
					        console.log("The file was saved!");
					    }
					    cb(err,d.saved_file);
					});
				});
			},
			function(filename,cb){
				console.log('http://localhost:3080/?file='+filename);
				request('http://localhost:3080/?file='+filename,
				function(err,response,body){
					cb(err,response);
				});
			},
			function(response,cb){
				console.log('simulator_'+game_id);
				client.incr('simulator_'+game_id,function(err,rs){
					console.log('simulator_'+game_id);
					if(err) console.log(err.message);
					cb(err,response);
				});
			}
		],
		function(err,rs){
			console.log('foo');
			conn.release();
			done(err,rs);
			
		});
	});



	//done(null);
}
function resetData(done){
	var game_id = 'f750260';
	pool.getConnection(function(err,conn){
		async.waterfall([
			function(cb){
				conn.query("UPDATE optadb_wc.matchinfo \
							SET period = 'PreMatch', home_score = 0, away_score=0, matchtime = 0 WHERE game_id=?;",
							[game_id],function(err,rs){
								cb(err);
							});
			},
			function(cb){
				conn.query("DELETE FROM optadb_wc.player_stats WHERE game_id=?;",
							[game_id],function(err,rs){
								cb(err);
							});
			},
			function(cb){
				conn.query("DELETE FROM optadb_wc.goals WHERE game_id=?;",
							[game_id],function(err,rs){
								cb(err);
							});
			},
			function(cb){
				conn.query("DELETE FROM optadb_wc.substitutions WHERE game_id=?;",
							[game_id],function(err,rs){
								cb(err);
							});
			},
			function(cb){
				client.set('simulator_'+game_id,0,function(err,rs){

					cb(err);
				});
			},
			function(cb){
				client.del('lvrt_'+game_id,function(err,rs){
					cb(err);
				});
			}
		],
		function(err,rs){
			conn.release();
				done(err,rs);
			
		});
	});
}

function getPlayerStats(game_id, done){
	pool.getConnection(function(err,conn){
		async.waterfall([
			function(cb){
				conn.query("SELECT * FROM ffgame.game_matchstats_modifier LIMIT 100000;",
							[],function(err,rs){
								cb(err,rs);
							});
			},
			function(stats_modifier, cb){
				client.get('player_stats'+game_id,function(err,resp){
					var data = JSON.parse(resp);
					report_player.process_team_stats(data,stats_modifier,function(err,rs){
						cb(err, rs);
					});
				});
			}
		],
		function(err,rs){
			conn.release();
			done(err,rs);
		});
	});
}

function getFixtures(league,done){
	var dbname = '';
	if(league=='ita'){
		dbname = 'ffgame_ita';
	}else{
		dbname = 'ffgame';
	}
	pool.getConnection(function(err,conn){
		conn.query("SELECT game_id,home_id,away_id,home_score,away_score,period,matchday,match_date \
					FROM "+dbname+".game_fixtures\
					LIMIT 1000;",[],function(err,rs){
						conn.release();
						done(err,rs);
					});
	});
}
function accessDenied(req,res){
	res.send(401,'Access Denied');
}



