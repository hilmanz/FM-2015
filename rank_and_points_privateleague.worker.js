/**
ranks and points updater
Multi-Worker.
**/
/////THE MODULES/////////
var config = require('./config').config;
var async = require('async');
var mysql = require('mysql');
var S = require('string');
var argv = require('optimist').argv;
/////DECLARATIONS/////////


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


var pool  = mysql.createPool({
		host     : config.database.host,
		user     : config.database.username,
		password : config.database.password,
	});

pool.getConnection(function(err, conn){
	async.waterfall([
		function(cb){
			if(typeof argv.matchday === 'undefined'){
				getCurrentMatchday(conn,cb);	
			}else{
				cb(null,argv.matchday);
			}
		},
		function(matchday, cb){
			getGameIdsByMatchday(conn, matchday, cb);
		},
		function(matchday, game_id, cb){
			var where_in = [];
			for(var i=0;i<game_id.length;i++){
				where_in.push(game_id[i].game_id);
			}
			
			cb(null, matchday, where_in, where_in.length);
		},
		function(matchday, where_in, length_game_id, cb){
			checkGameId(conn, matchday, where_in, length_game_id, cb);
			console.log('matchday',matchday);
		},
		function(game_id, matchday, length_game_id, length, cb){
			if(length_game_id == length){
				compareResultJob(conn, game_id, matchday, cb);
			}else{
				cb(new Error('Game_Ids not complete'),null,null,null);
			}
		},
		function(result, matchday, game_id, cb){
			console.log('result[0].total',result[0].total);
			if(result[0].total > 0 && result.length == 1){
				console.log('rs not null');
				var start = 0;
				var limit = 100;
				var loop = true;
				async.whilst(
				    function () { return loop; },
				    function (callback) {
				    	console.log('loop',loop);
				        conn.query("SELECT league_id, team_id FROM \
				        			"+config.database.frontend_schema+".league_member WHERE league='"+league+"' \
				        			LIMIT ?,?", [start,limit],
						function(err, rs){
							console.log(S(this.sql).collapseWhitespace().s);
							console.log("getPlayer", rs.length);
							if(rs.length > 0){
								start += limit;
								async.each(rs, function(player, next){
									console.log('player',player);
									getWeeklyPoint(conn, matchday, game_id, player, function(){
										next();
									});
								},
								function(err){
									callback();
								});
						 		
							}else{
								console.log('kosong');
								loop = false;
								callback();
							}
						});
				    },
				    function (err) {
				        console.log("Selesai");
				        cb(err);
				    });
			}else{
				console.log('rs noll');
				cb(err);
			}
		}
	], function(err){
		conn.release();
		//close the pool as we no longer need it.
		pool.end(function(err){
			console.log('done');
		});
	});
});

function getCurrentMatchday(conn, cb){
	conn.query("SELECT matchday FROM \
				"+config.database.database+".game_fixtures \
				WHERE is_processed = 0 \
				ORDER BY matchday ASC LIMIT 1;",
				[],function(err, rs){
					console.log(S(this.sql).collapseWhitespace().s);
					console.log("getCurrentMatchday",rs);

					if(rs != null && rs.length == 1){
						cb(err,(rs[0].matchday - 1));
					}else{
						cb(new Error('no matchday found'),0);
					}
				});


}

function getGameIdsByMatchday(conn, matchday, cb){
	conn.query("SELECT game_id,period FROM \
				"+config.database.database+".game_fixtures \
				WHERE matchday = ? \
				ORDER BY id ASC LIMIT 40;",
				[matchday],function(err, rs){
					console.log("getGameIdsByMatchday #",matchday,rs);
					if(rs != null && rs.length > 0){
						cb(err, matchday, rs);
					}else{
						cb(new Error('no matchday found'), matchday, []);
					}
				});
}

function checkGameId(conn, matchday, game_id, length_game_id, cb){
	conn.query("SELECT id FROM "+config.database.statsdb+".job_queue WHERE game_id IN (?) GROUP BY game_id",
				[game_id], function(err, rs){
					console.log("checkGameId", rs);
					console.log('GAME_TEAM_POINTS',S(this.sql).collapseWhitespace().s);
					cb(err, game_id, matchday, length_game_id, rs.length);
				});
}

function compareResultJob(conn, game_id, matchday, cb){
	var where_in = [];
	for(var i=0;i<game_id.length;i++){
		where_in.push("'"+game_id[i]+"'");
	}

	var sql = "(SELECT \
				    COUNT(id) as total \
				FROM \
				    "+config.database.statsdb+".job_queue \
				WHERE \
				    game_id IN ("+where_in+") \
				        AND n_status = 2) \
				UNION \
				(SELECT \
				    COUNT(id) as total \
				FROM \
				    "+config.database.statsdb+".job_queue_rank \
				WHERE \
				    game_id IN ("+where_in+") \
				        AND n_status = 2)";
	conn.query(sql,[],
				function(err, rs){
					console.log("compareResultJob", rs);
			 		cb(err, rs, matchday, game_id);
				});
}
function getWeeklyPoint(conn, matchday, game_id, player, cb){
	console.log('getWeeklyPoint', player);
	conn.query("SELECT * FROM "+config.database.frontend_schema+".weekly_points \
				WHERE game_id IN(?) AND team_id=? AND matchday = ? LIMIT 10000", 
				[game_id, player.team_id, matchday],
				function(err, rs){
					console.log(S(this.sql).collapseWhitespace().s);
					console.log('rs.length',rs.length);
					if(rs!=null && rs.length > 0){
						var total_point = 0;
						for(var i=0;i<rs.length;i++)
						{
							total_point += rs[i].points+rs[i].extra_points;
						}

						async.each(rs, function(value, next){
							conn.query("INSERT INTO "+config.database.frontend_schema+".league_table\
								(league_id, team_id, game_id, matchday, \
								matchdate, points, total_points, league) \
								VALUES(?, ?, ?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE points=?,total_points=?",
									[player.league_id, player.team_id, value.game_id,
									 value.matchday, value.matchdate, 
									 Math.ceil(value.points+value.extra_points),
									 Math.ceil(total_point),
									 league,
									 Math.ceil(value.points+value.extra_points),
									 Math.ceil(total_point)],
									function(err, rs){
										console.log(S(this.sql).collapseWhitespace().s);
										next();
									});
						},
						function(err){
							cb(err);
						});
					}
					else
					{
						cb(err);
					}
				});
}