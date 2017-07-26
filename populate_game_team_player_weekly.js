/**
* module for updating the match results across all users's lineups
*
* we only calculate unprocessed team.
* the processed team will have an entry in game_team_lineups_history.
* if there's no existing game_id on the history, we will do the calculation.
* 
* here's the rule
* 1. we only update the lineup history for team which playes in the game (game_id)
* 2. for those team which not played in the game, we only track for the player who played in the real-world.
*    and add the stats to the team.
*/

/**
* the module to read match_results file.
*/
var fs = require('fs');
var path = require('path');
var async = require('async');
var xmlparser = require('xml2json');
var config = require(path.resolve('./config')).config;
var S = require('string');
var mysql = require('mysql');
var pool  = mysql.createPool({
   host     : config.database.host,
   user     : config.database.username,
   password : config.database.password,
});
var punishment = require(path.resolve('./libs/gamestats/punishment_rules'));
var player_stats_category = require(path.resolve('./libs/game_config')).player_stats_category;

var conn = mysql.createConnection({
 	host     : config.database.host,
   user     : config.database.username,
   password : config.database.password,
});

var start = 0;
var limit = 1000;
var doLoop = true;
async.doWhilst(
	function(callback){
		conn.query("SELECT a.game_id,b.matchday,a.player_id,a.game_team_id \
						FROM ffgame_stats.game_match_player_points a\
						INNER JOIN ffgame.game_fixtures b\
						ON a.game_id=b.game_id LIMIT ?,?",
			[start,limit],
			function(err,rs){
				if(rs.length>0){
					populate(conn,rs,function(err){
						start+=limit;
						callback();
					});
				}else{
					doLoop=false;
					callback();
				}
		});
	}, function(){
		return doLoop;
	}, function(err){
	conn.end(function(err){
		console.log('done');
	});
});

function populate(conn,rs,cb){
	async.eachSeries(rs,function(item,next){
		async.waterfall(
			[
				function(callback){
					conn.query("SELECT a.position_no,c.position \
								FROM ffgame.game_team_lineups_history a\
								INNER JOIN ffgame.master_player c\
								ON a.player_id = c.uid\
								WHERE a.game_team_id = ? AND a.player_id = ?\
								AND EXISTS (SELECT 1 FROM ffgame.game_fixtures b\
								WHERE b.matchday=? AND a.game_id = b.game_id LIMIT 1);",
					[
						item.game_team_id,
						item.player_id,
						item.matchday
					 ],
					function(err,rs){
						//console.log(S(this.sql).collapseWhitespace().s);
						try{
							callback(err,rs[0].position_no,rs[0].position);
						}catch(e){
							callback(err,0,'');	
						}
						
					});
				},
				function(position_no,position,callback){
					if(position_no!=0){
						getPlayerDailyTeamStats(conn,item.game_team_id,item.player_id,
												position,item.matchday,position_no,
												function(err,rs){
													callback(err,rs);
												});
					}else{
						callback(null,null);
					}
					
				}
			],
			function(err,rs){
				next();
			}
		);
	},function(err){
		cb(err);
	});
}
function getPlayerDailyTeamStats(conn,game_team_id,player_id,player_pos,matchday,position_no,done){
	console.log('ISSUE2','updating matchday#',matchday,'stats for player #',player_id,'in team #',game_team_id);
	var pos = 'g';
	switch(player_pos){
		case 'Forward':
			pos = 'f';
		break;
		case 'Midfielder':
			pos = 'm';
		break;
		case 'Defender':
			pos = 'd';
		break;
		default:
			pos = 'g';
		break;
	}
	
	sql = "SELECT a.game_id,stats_name,stats_value\
			FROM ffgame_stats.master_player_stats a \
			INNER JOIN ffgame.game_fixtures b\
			ON a.game_id = b.game_id\
			WHERE a.player_id=? AND b.matchday=?\
			LIMIT 500;";
	
	
	async.waterfall([
		function(callback){
			conn.query(sql,
				[player_id,matchday,game_team_id,game_team_id],
				function(err,rs){
					//console.log(S(this.sql).collapseWhitespace().s);		
					callback(err,rs);	
					
				});
		},
		function(result,callback){
			conn.query("SELECT * FROM ffgame.game_matchstats_modifier;",
			[],
			function(err,rs){
				callback(err,rs,result);	
				
			});
		},
		function(modifiers,result,callback){
			//mapping
			var weekly = [];
			var point_modifier = 1.0;
			
			if(result.length>0){
				for(var i in result){
					
					//distributed the counts for each categories
					for(var category in player_stats_category){
						for(var j in player_stats_category[category]){
							if(player_stats_category[category][j] == result[i].stats_name){
								var points =  (parseInt(result[i].stats_value) * getModifierValue(modifiers,
																	  					result[i].stats_name,
																	  					pos));
								weekly.push({
									game_id:result[i].game_id,
									category:category,
									game_team_id:game_team_id,
									player_id:player_id,
									matchday:matchday,
									stats_name:result[i].stats_name,
									stats_value:result[i].stats_value,
									points: (points * point_modifier),
									position_no: position_no

								});
							}
						}
					}
				}
			}
			//console.log(weekly);
			callback(null,weekly);
		},
		function(weekly,callback){

			async.eachSeries(weekly,function(w,next){
				conn.query("INSERT INTO ffgame_stats.game_team_player_weekly\
						(game_id,game_team_id,matchday,player_id,stats_category,stats_name,stats_value,points,position_no)\
						VALUES\
						(?,?,?,?,?,?,?,?,?)\
						ON DUPLICATE KEY UPDATE\
						stats_value = VALUES(stats_value),\
						points = VALUES(points),\
						position_no = VALUES(position_no)",
						[w.game_id,
						w.game_team_id,
						 w.matchday,
						 w.player_id,
						 w.category,
						 w.stats_name,
						 w.stats_value,
						 w.points,
						 w.position_no
						 ],function(err,rs){
						 	//console.log(S(this.sql).collapseWhitespace().s);
						 	next();
						});
			},
			function(err){callback(err,weekly)});
			
		}
	],
	function(err,result){
		done(err,result);
	});
}
/**
* get modifier value based on player's position
*/
function getModifierValue(modifiers,stats_name,position){
	
	for(var i in modifiers){
		if(modifiers[i].name==stats_name){
			return (parseInt(modifiers[i][position]));
		}
	}
	return 0;
}