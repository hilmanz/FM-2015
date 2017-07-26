/**
populating the ffgame.game_fixtures
**/
/////THE MODULES/////////
var fs = require('fs');
var path = require('path');
var config = require('./config').config;
var xmlparser = require('xml2json');
var master = require('./libs/master');
var async = require('async');
var mysql = require('mysql');
/////DECLARATIONS/////////
var FILE_PREFIX = config.updater_file_prefix+config.competition.id+'-'+config.competition.year;



/////THE LOGICS///////////////
var conn = mysql.createConnection({
 	host     : config.database.host,
   user     : config.database.username,
   password : config.database.password,
});

var n_teams = 0;
var start = 0;
var limit = 100;
async.doWhilst(
	function(callback){
		conn.query("SELECT * FROM ffgame.game_teams LIMIT ?,?;",
					[start,limit],
					function(err,rs){
						console.log(this.sql);
			try{
				n_teams = rs.length;

				start+=limit;
			}catch(e){
				console.log(e.message);
				n_teams = 0;
			}
			if(n_teams>0){
				populate_data(conn,rs,function(err){
					callback();	
				});
			}else{
				callback();
			}
			
		});
		
	},
	function(){
		console.log('n_teams',n_teams);
		if(n_teams>0){
			return true;
		}
	}, 
	function(err){
	conn.end(function(err){
		console.log('finished');
	});
});

function populate_data(conn,teams,callback){
	async.eachSeries(teams,function(team,next){
		console.log(team.id,team.user_id,team.team_id);
		async.waterfall([
			function(cb){
				//kita urus pemain2 yang dibeli dulu.
				conn.query("SELECT 1 AS tw_id,game_team_id,player_id,transfer_value,\
							NOW() AS transfer_date,1 AS transfer_type \
							FROM ffgame.game_team_players a\
							INNER JOIN ffgame.master_player b\
							ON a.player_id = b.uid\
							WHERE game_team_id=? AND team_id <> ?;",
							[team.id,team.team_id],
							function(err,rs){
								cb(err,rs);
							});
			},
			function(players,cb){
				var p = [];
				//sekarang cari harga transfer value aslinya.
				async.eachSeries(players,function(player,next){
					var transfer_value = player.transfer_value;
					console.log(player.player_id,'base transfer : ',transfer_value);
					conn.query(
						"SELECT points,performance FROM ffgame_stats.master_player_performance \
						 WHERE player_id=? ORDER BY id DESC;",
						[player.player_id],
						function(err,rs){
							if(!err){
								//console.log(this.sql);
								//@TODO we need to calculate the player's performance value to affect
								//the latest transfer value
								if(rs.length>0){
									rs[0].performance = rs[0].performance || 0;
									if(rs[0].performance!=0){
										transfer_value = transfer_value + ((((rs[0].performance / 10) * 1)/100)*transfer_value);
									}
								}
								
							}

							player.transfer_value = transfer_value;
							p.push(player);		
							next();
						});
				},
				function(err){
					cb(err,p);
				});
			},
			function(players,cb){
				//insert the transaction
				async.eachSeries(players,
								function(player,next){
									conn.query("INSERT IGNORE INTO ffgame.game_transfer_history\
												(tw_id,game_team_id,player_id,transfer_value,\
												 transfer_date,transfer_type)\
												VALUES(?,?,?,?,NOW(),1)",
											  [
											  	1,
											   team.id,
											   player.player_id,
											   player.transfer_value
											   ],
											  function(err,rs){
											  	next();
											  });
								},
								function(err){
									cb(err,players);
								}
							);

			},
			function(players,cb){
				//sekarang urus pemain2 yang dijual.
				conn.query(
					"SELECT 1 AS tw_id,? AS game_team_id,uid AS player_id,\
					transfer_value,NOW() AS transfer_date,2 AS transfer_type \
					FROM ffgame.master_player a \
					WHERE a.uid NOT IN (SELECT player_id FROM ffgame.game_team_players b \
										WHERE b.game_team_id = ?)\
					AND a.team_id = ?;",
					[team.id,team.id,team.team_id],
					function(err,rs){
						console.log(this.sql);
						console.log(rs);
						cb(err,rs);
					});
			},
			function(players,cb){
				//penjualan pemain pakai base transfer value saja. karena pertandingan belum berjalan.
				//insert the transaction
				async.eachSeries(players,
								function(player,next){
									conn.query("INSERT IGNORE INTO ffgame.game_transfer_history\
												(tw_id,game_team_id,player_id,transfer_value,\
												 transfer_date,transfer_type)\
												VALUES(?,?,?,?,NOW(),2)",
											  [
											  	1,
											   team.id,
											   player.player_id,
											   player.transfer_value
											   ],
											  function(err,rs){
											  	next();
											  });
								},
								function(err){
									cb(err,players);
								}
							);
			}
		],
		function(err,results){
			console.log(results);
			next();
		});
		
	},
	function(err){
		callback();
	});
}