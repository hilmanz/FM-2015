/**
* master.js
* this is a model which handles all master data.
*
*/
var mysql = require('mysql');
var async = require('async');
var config = require('../config').config;
var pool  = mysql.createPool({
   host     : config.database.host,
			  user     : config.database.username,
			  password : config.database.password,
});
exports.update_team_data = function(data,callback){
		pool.getConnection(function(err,conn){
			async.waterfall([
				function(cb){
					//insert master_team
					master_team(conn,data,function(err,rs){
						cb(err,rs);
					});
				}
			],
			function(err,rs){
				conn.release();
				pool.end(function(err){
					console.log('done');
				});
			});

		});	
}

function master_team(conn,data,done){
	var team_data = [];
	var teams = data.SoccerFeed.SoccerDocument.Team;
	async.eachSeries(teams,function(team,next){
		async.waterfall([
			function(cb){

				var sql = "INSERT INTO "+config.database.optadb+".master_team\
							   (uid,name,founded,symid,stadium_id,stadium_name,stadium_capacity)\
							    VALUES\
							   (?,?,?,?,?,?,?) \
							   ON DUPLICATE KEY UPDATE \
							   name = VALUES(name),\
							   founded = VALUES(founded),\
							   symid = VALUES(symid),\
							   stadium_id = VALUES(stadium_id),\
							   stadium_name = VALUES(stadium_name),\
							   stadium_capacity = VALUES(stadium_capacity);";
				try{
					if(typeof team.Stadium === 'undefined'){
						team.Stadium = {Name:'',Capacity:0};
					}
					conn.query(sql,[team.uID,team.Name,team.Founded,
								team.SYMID,'',team.Stadium.Name,
								team.Stadium.Capacity],
								function(err,result){
									if(err) console.log(err.message);
									cb(err,result);
								});
				}catch(e){
					console.log(e.message,team);
					cb(null,null);
				}
				
			},
			function(result,cb){
				try{
					master_players(conn,team.uID,team.Player,function(err){
						cb(err,null);
					});
				}catch(e){
					cb(null,null);
				}
				
			}
		],
		function(err,rs){
			next();
		});
	},function(err){
		done(err);
	});
}
function master_players(conn,team_id,players,done){

	async.eachSeries(players,
					function(player,next){
						var stat = {};
						for(var s in player.Stat){
							stat[player.Stat[s].Type] = player.Stat[s]['$t'];
						}
						var sql = "INSERT INTO "+config.database.optadb+".master_player\
								 (uid,name,position,first_name,last_name,known_name,birth_date,\
								 	weight,height,jersey_num,real_position,real_position_side,\
								 	join_date,country,team_id)\
								 VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)\
								 ON DUPLICATE KEY UPDATE\
								 name= VALUES(name),\
								 position= VALUES(position),\
								 first_name= VALUES(first_name),\
								 last_name= VALUES(last_name),\
								 known_name= VALUES(known_name),\
								 birth_date= VALUES(birth_date),\
								 weight= VALUES(weight),\
								 height= VALUES(height),\
								 jersey_num= VALUES(jersey_num),\
								 real_position= VALUES(real_position),\
								 real_position_side= VALUES(real_position_side),\
								 join_date= VALUES(join_date),\
								 country= VALUES(country),\
								 team_id= VALUES(team_id)\
								 ;";
						
						var params = [
							player.uID,
							player.Name,
							player.Position,
							stat.first_name,
							stat.last_name,
							stat.known_name,
							stat.birth_date,
							stat.weight,
							stat.height,
							stat.jersey_num,
							stat.real_position,
							stat.real_position_side,
							stat.join_date,
							stat.country,
							team_id
						];
						conn.query(sql,params,function(err,rs){
							console.log('players ',team_id,player.uID);
							next();
						});
					},
					function(err){
						done(err);
					});
}
exports.update_team_player_data = function(data,callback){
	for(var i in data.SoccerFeed.SoccerDocument.Team){
		var team = data.SoccerFeed.SoccerDocument.Team[i];
		for(var j in team.Player){
			var player = team.Player[j];
			console.log(player);
		}
	}
}
