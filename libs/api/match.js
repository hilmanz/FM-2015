/**
api related to match
*/
var config = {};


exports.setConfig = function(c){
	config = c;
}

var crypto = require('crypto');
var fs = require('fs');
var path = require('path');
var xmlparser = require('xml2json');
var async = require('async');
//var config = require(path.resolve('./config')).config;
var mysql = require('mysql');
var dateFormat = require('dateformat');
var redis = require('redis');
var formations = require(path.resolve('./libs/game_config')).formations;
var stats_map = require(path.resolve('./libs/stats_map')).stats_map;
var S = require('string');
var pool = {};




exports.setPool = function(p){
	pool = p;
}
function prepareDb(callback){
	pool.getConnection(function(err,conn){
		callback(conn);
	});
}

function fixtures(done){
	prepareDb(function(conn){
		conn.query("SELECT a.id,\
				a.game_id,a.home_id,b.name as home_name,a.away_id,c.name as away_name,a.home_score,a.away_score,\
				a.matchday,a.period,a.session_id,a.attendance\
				FROM "+config.database.database+".game_fixtures a\
				INNER JOIN "+config.database.database+".master_team b\
				ON a.home_id = b.uid\
				INNER JOIN "+config.database.database+".master_team c\
				ON a.away_id = c.uid\
				WHERE a.session_id = ? \
				ORDER BY a.matchday\
				LIMIT 1000;",
				[config.competition.year],
				function(err,match){
					console.log(S(this.sql).collapseWhitespace().s);
					conn.release();
					done(err,match);						
					
				});
	});
	
}
function next_match(team_id,done){
	var conn = prepareDb(function(conn){
		conn.query("",
				[],
				function(err,match){
					conn.release();
					done(err,match);						
					
				});
	});
	
}
function getMatchResultForUserTeam(game_team_id,game_id,done){
	prepareDb(function(conn){
		async.waterfall([
			function(callback){
				//get matchday first
				conn.query("SELECT matchday FROM "+config.database.database+".game_fixtures WHERE game_id=?;",[game_id],
							function(err,rs){
								callback(err,rs[0].matchday);
							});
			},
			function(matchday,callback){
				//get all the user's lineups who playes in matchday 1
				conn.query("SELECT a.game_id,a.player_id,a.points,c.team_id as original_team_id,c.name,c.position\
				FROM "+config.database.statsdb+".game_match_player_points a\
				INNER JOIN "+config.database.database+".master_player c\
				ON a.player_id = c.uid\
				WHERE game_team_id = ?\
				AND EXISTS (SELECT 1 FROM "+config.database.database+".game_fixtures b \
					WHERE a.game_id = b.game_id AND b.matchday = ? LIMIT 1)\
				ORDER BY a.points DESC;",
				[game_team_id,matchday],
				function(err,rs){
					callback(err,rs);
				});
			},
			function(players,callback){
				var p = {}
				//get each players stats
				async.eachSeries(players,function(player,next){
					conn.query("SELECT stats_name,stats_value,points FROM "+config.database.statsdb+".game_team_player_weekly \
								WHERE game_team_id = ? AND game_id = ? AND player_id = ?",
								[game_team_id,player.game_id,player.player_id],
								function(err,rs){
									console.log(S(this.sql).collapseWhitespace().s);
									if(!err){
										if(rs.length>0 || (!p[player.player_id])){
											var stats = {};
											var ori_stats = {};
											var total_points = 0;
											for(var i in rs){
												stats[rs[i].stats_name] = rs[i].stats_value;
												ori_stats[rs[i].stats_name] = {total:rs[i].stats_value,points:rs[i].points};
												total_points += rs[i].points;
											}
											p[player.player_id] = {
												name:player.name,
												position:player.position,
												points:total_points,
												original_team_id : player.original_team_id,
												stats:stats,
												ori_stats:ori_stats
											};	
											console.log(p);
										}
									}
									next();
								});
				},function(err){
					callback(err,p);
				});
			}
		],

		function(err,result){
			conn.release();
			done(err,result);
			
		});

	});
}
function results(game_id,done){
	prepareDb(function(conn){
		async.waterfall([
				function(callback){
					//get game details
					conn.query("SELECT a.id,\
				a.game_id,a.home_id,b.name as home_name,a.away_id,c.name as away_name,a.home_score,a.away_score,\
				a.matchday,a.period,a.session_id,a.attendance\
				FROM "+config.database.database+".game_fixtures a\
				INNER JOIN "+config.database.database+".master_team b\
				ON a.home_id = b.uid\
				INNER JOIN "+config.database.database+".master_team c\
				ON a.away_id = c.uid\
				WHERE a.game_id = ?\
				LIMIT 1;",[game_id],
					function(err,game){
						
						callback(err,game[0]);
					});
				},
				function(game,callback){

					//get overall stats
					conn.query("SELECT team_id,stats_name,SUM(stats_value) AS total \
								FROM "+config.database.statsdb+".master_match_result_stats \
								WHERE game_id=? GROUP BY team_id,stats_name;",
					[game_id],
					function(err,stats){
						//console.log(this.sql);
						callback(err,game,stats);
					});
				},
				function(game,stats,callback){

					//get player stats for these match
					conn.query("SELECT a.team_id,a.player_id,b.name,b.position,a.stats_name,SUM(a.stats_value) AS total \
								FROM "+config.database.statsdb+".master_match_result_stats a\
								INNER JOIN\
								"+config.database.database+".master_player b\
								ON a.player_id = b.uid\
								WHERE a.game_id = ?\
								GROUP BY a.team_id,a.player_id,a.stats_name \
								LIMIT 30000;",
						[game_id],
					function(err,player_stats){
					
						callback(err,game,stats,player_stats);
					});
				},
				function(game,stats,player_stats,callback){
					//wrapping all up
					getMatchResultData(game,stats,player_stats,function(err,result){
						callback(err,result);
					});
				}
			],
			function(err,result){
				conn.release();
				console.log(result);
				done(err,result);	
			}
		);
	});
	
}
function getMatchResultData(game,stats,player_stats,callback){
	var result = [];

	var home = {
		team_id:game.home_id,
		name:game.home_name,
		score:game.home_score,
		overall_stats:{},
		player_stats:{}
	};
	var away = {
		team_id:game.away_id,
		name:game.away_name,
		score:game.away_score,
		overall_stats:{},
		player_stats:{}
	};
	
	console.log(stats);
	//console.log(stats_map);
	//var stats_list = require(path.resolve('./libs/stats_map')).getStats();
	var home_overall_stats = {};
	var away_overall_stats = {};
	

	
	//generate overall stats for each team
	for(var i in stats){
		//if(typeof stats_list[stats[i].stats_name]!=='undefined'){

			if(stats[i].team_id == home.team_id){
				if(typeof home_overall_stats[stats[i].stats_name] === 'undefined'){
					home_overall_stats[stats[i].stats_name] = 0;
				}
				home_overall_stats[stats[i].stats_name] += stats[i].total;
			}else{
				if(typeof away_overall_stats[stats[i].stats_name] === 'undefined'){
					away_overall_stats[stats[i].stats_name] = 0;
				}
				away_overall_stats[stats[i].stats_name] += stats[i].total;
			}
		//}
	}
	console.log('home overall stats : ',home_overall_stats);
	home.overall_stats =  home_overall_stats;
	away.overall_stats = away_overall_stats;
	
	



	//process player's stats
	for(var i in player_stats){
		//if(typeof stats_list[player_stats[i].stats_name]!=='undefined'){
			if(player_stats[i].team_id == home.team_id){
				if(typeof home.player_stats[player_stats[i].player_id] === 'undefined'){
					home.player_stats[player_stats[i].player_id] = {
						name:player_stats[i].name,
						position:player_stats[i].position,
						stats:{}
					};
				}
				if(typeof home.player_stats[player_stats[i].player_id]['stats'][player_stats[i].stats_name] === 'undefined'){
					 home.player_stats[player_stats[i].player_id]['stats'][player_stats[i].stats_name] = 0;
				}
				 home.player_stats[player_stats[i].player_id]['stats'][player_stats[i].stats_name] += player_stats[i].total;
			}else{
				if(typeof away.player_stats[player_stats[i].player_id] === 'undefined'){
					away.player_stats[player_stats[i].player_id] = {
						name:player_stats[i].name,
						position:player_stats[i].position,
						stats:{}
					};
				}
				if(typeof away.player_stats[player_stats[i].player_id]['stats'][player_stats[i].stats_name] === 'undefined'){
					 away.player_stats[player_stats[i].player_id]['stats'][player_stats[i].stats_name] = 0;
				}
				 away.player_stats[player_stats[i].player_id]['stats'][player_stats[i].stats_name] += player_stats[i].total;
			}
		//}
	}

	result.push(home);
	result.push(away);
	console.log(away.player_stats);
	callback(null,result);

}
exports.fixtures = fixtures;
exports.results = results;
exports.getMatchResultForUserTeam = getMatchResultForUserTeam;