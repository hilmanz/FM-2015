/**
api related to officials
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
var mysql = require('mysql');
var dateFormat = require('dateformat');
var redis = require('redis');
var formations = require(path.resolve('./libs/game_config')).formations;
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

/** get the list of officials **/
function official_list(game_team_id,done){
	prepareDb(function(conn){
		async.waterfall([
				
				function(callback){
					get_user_officials(conn,game_team_id,function(err,result){
						callback(err,result);
					});
				}
			],
			function(err,result){
				conn.release();
				done(err,result);						
				
			});
	});
}

function hire_official(game_team_id,staff_id,callback){
	prepareDb(function(conn){
		async.waterfall(
			[
				function(callback){
					conn.query("SELECT * FROM "+config.database.database+".master_staffs\
								 WHERE id IN (?) LIMIT 1;",[staff_id],function(err,rs){
								 	console.log(S(this.sql).collapseWhitespace().s);
								 	callback(err,rs[0]);
								 });
				},
				function(staff,callback){
					conn.query("INSERT IGNORE INTO "+config.database.database+".game_team_staffs\
					(game_team_id,staff_id,name,staff_type,salary,recruit_date,rank)\
					VALUES\
					(?,?,?,?,?,NOW(),?);",
					[game_team_id,staff.id,staff.name,staff.staff_type,staff.salary,staff.rank],
					function (err,rs){
						console.log(S(this.sql).collapseWhitespace().s);
						callback(err,rs.insertId,staff.id);
					});		
				},
				function(insertId,staff_id,callback){
					conn.query("SELECT * FROM "+config.database.database+".game_team_staffs a\
								WHERE a.game_team_id = ? AND a.staff_id = ?;",
								[game_team_id,staff_id],
								function(err,rs){
									console.log(S(this.sql).collapseWhitespace().s);
									console.log(rs);
									callback(err,rs[0]);
								});
				}
			],
			function(err,result){
				conn.release();
				callback(err,result);
				
			}
		);
	});
	
}
function remove_official(game_team_id,official_id,callback){
	prepareDb(function(conn){
		conn.query("DELETE FROM "+config.database.database+".game_team_staffs \
					WHERE game_team_id = ? AND staff_id = ?",
					[game_team_id,official_id],function (err,rs){
						console.log(S(this.sql).collapseWhitespace().s);
						conn.release();
						callback(err,rs);
						
					});
	});
}



function get_master_staffs(game_team_id,type,callback){
	prepareDb(function(conn){
		conn.query("SELECT * FROM "+config.database.database+".master_staffs \
					WHERE id NOT IN (SELECT staff_id FROM\
							 "+config.database.database+".game_team_staffs\
						WHERE game_team_id = ?) AND staff_type = ? LIMIT 20;",
					[game_team_id,type],function (err,rs){
						console.log(S(this.sql).collapseWhitespace().s);
						conn.release();
						callback(err,rs);
					});
	});
}

function get_user_officials(conn,game_team_id,done){
	async.waterfall(
		[
			function(callback){
				conn.query("SELECT * FROM "+config.database.database+".game_team_staffs\
						    WHERE game_team_id = ? LIMIT 30;",
							[game_team_id],
							function(err,rs){
								callback(err,rs);
							});
			}
		],
		function(err,result){
			done(err,result);
		}
	);
}
exports.official_list = official_list;
exports.hire_official = hire_official;
exports.remove_official = remove_official;

exports.get_master_staffs = get_master_staffs;