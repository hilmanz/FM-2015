/**
* API for manager's team information
* 2015/08/28
* authors
* @author hapsoro renaldy <hapsoro.renaldy@msp-entertainment.net>
*/
var config = {};


var fs = require('fs');
var path = require('path');
var xmlparser = require('xml2json');
var async = require('async');
var mysql = require('mysql');
var S = require('string');
var pool = {};
function prepareDb(callback){

	pool.getConnection(function(err,conn){
		callback(conn);
	});
	
}

function sendMessageToInbox(){

}
/** negotiate transfer**/
exports.getTeamInfo = function(conn,game_team_id,league,callback){
	
		getTeamInfoProcess(conn,game_team_id,league,
		function(err,result){
			
			callback(err,result);
		});
	
}
var getTeamInfoProcess = function(conn,
								game_team_id,
								league,
								callback){
	var data = {};
	async.waterfall([
		function(cb){
			conn.query("SELECT * FROM "+config.database.database+".game_teams\
						 WHERE id = ? LIMIT 1;",[game_team_id],
						 function(err,rs){
						 	data.game_team = rs[0];
						 	cb(err);
						});
			
		},
		function(cb){
			
			conn.query("SELECT * FROM "+config.database.database+".game_users WHERE id = ? LIMIT 1;",
						[data.game_team.user_id],
						 function(err,rs){
						 	data.game_user = rs[0];
						 	cb(err);
						});
		},
		function(cb){
			
			conn.query("SELECT id,name,fb_id FROM "+config.database.frontend_schema+".users \
						WHERE fb_id=? LIMIT 1;",
						[data.game_user.fb_id],
						 function(err,rs){
						 	data.team_user = rs[0];
						 	cb(err);
						});
		},
		function(cb){
			console.log(data.team_user.id,league);
			conn.query("SELECT * FROM "+config.database.frontend_schema+".teams \
						WHERE user_id=? AND league = ? LIMIT 1;",
						[data.team_user.id,league],
						 function(err,rs){
						 	console.log(S(this.sql).collapseWhitespace().s);
						 	data.team = rs[0];
						 	cb(err);
						});
		},
		function(cb){
				
			conn.query("SELECT FLOOR(MAX(rank)/4) as n_range FROM "+config.database.frontend_schema+".points;",
						[],
						 function(err,rs){
						 	console.log(rs);
						 	data.n_range = rs[0].n_range;
						 	cb(err);
						});
		},
		function(cb){
			conn.query("SELECT CEIL(rank/"+data.n_range+") as quadrant,(points+extra_points) as total_points,rank \
						FROM fantasy.points \
						WHERE team_id=?;",
						[data.team.id],
						 function(err,rs){
						 	console.log(rs);
						 	if(rs.length>0){
						 		data.team_points = rs[0];
						 	}else{
						 		data.team_points = {
						 			quadrant:4,
						 			total_points:0,
						 			rank:99999999999
						 		};
						 	}
						 	cb(err);
						});
		},
		function(cb){
			//other info like current rooster
			cb(null,data);
		}
		


	],
	function(err,rs){
		callback(err,rs);
	});
}


exports.setPool = function(p){
	pool = p;
}
exports.setConfig = function(c){
	config = c;
}