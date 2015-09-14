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
var league = 'epl';
var redisClient = {};
exports.setLeague = function(l){
	league = l;
}
exports.setPool = function(p){
	pool = p;
}
exports.setRedisClient = function(client){
	redisClient = client;
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

function hire_official(game_team_id,staff_id,meta,callback){
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
					(game_team_id,staff_id,name,staff_type,salary,recruit_date,rank,meta)\
					VALUES\
					(?,?,?,?,?,NOW(),?,?);",
					[game_team_id,staff.id,staff.name,staff.staff_type,staff.salary,staff.rank,meta],
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
				},
				function(staff,callback){
					updateCache(conn,game_team_id,function(e){
						if(e!=null){
							console.log('error_staff_cache',e.message());
						}
						callback(null,staff);
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

function updateCache(conn,game_team_id,callback){
	console.log('updateStaffCache',game_team_id);
	conn.query("SELECT * FROM "+config.database.database+".game_team_staffs a\
				WHERE a.game_team_id = ? LIMIT 20;",
				[game_team_id],
				function(err,staffs){
					var name = 'staff_'+league+"_"+game_team_id;
					//reset the cache
					var o = {
						dof:0,
						marketing:0,
						security:0,
						pr:0,
						phy_coach:0,
						gk_coach:0,
						def_coach:0,
						mid_coach:0,
						fwd_coach:0,
						scout:0,
						dice:0,
						gk_tactics:[],
						def_tactics:[],
						mid_tactics:[],
						fwd_tactics:[]
					};
					//-->					
					if(staffs.length > 0){
						for(var i in staffs){
							o[staffs[i].staff_type] = staffs[i].rank;
							if(staffs[i].staff_type=='gk_coach'){
								staffs[i].meta = JSON.parse(staffs[i].meta);
								o.gk_tactics.push(staffs[i].meta.tactics.id);
							}
							if(staffs[i].staff_type=='def_coach'){
								staffs[i].meta = JSON.parse(staffs[i].meta);
								o.def_tactics.push(staffs[i].meta.tactics.id);
							}
							if(staffs[i].staff_type=='mid_coach'){
								staffs[i].meta = JSON.parse(staffs[i].meta);
								o.mid_tactics.push(staffs[i].meta.tactics.id);
							}
							if(staffs[i].staff_type=='fwd_coach'){
								staffs[i].meta = JSON.parse(staffs[i].meta);
								o.fwd_tactics.push(staffs[i].meta.tactics.id);
							}
						}	
					}
					console.log(o);
					redisClient.set(name,JSON.stringify(o),function(err){
						callback(err);
					});
				});
}
function remove_official(game_team_id,official_id,callback){
	prepareDb(function(conn){
		conn.query("DELETE FROM "+config.database.database+".game_team_staffs \
					WHERE game_team_id = ? AND staff_id = ?",
					[game_team_id,official_id],function (err,rs){
						console.log(S(this.sql).collapseWhitespace().s);
						updateCache(conn,game_team_id,function(e){
							if(e!=null){
								console.log('error_staff_cache',e.message());
							}
							
							conn.release();
							callback(err,rs);
						});
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