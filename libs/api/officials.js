/**
api related to officials
*/
var config = {};


var crypto = require('crypto');
var fs = require('fs');
var path = require('path');
var xmlparser = require('xml2json');
var async = require('async');
var mysql = require('mysql');
var dateFormat = require('dateformat');
var redis = require('redis');
var formations = require(path.resolve('./libs/game_config')).formations;
var game_config = require(path.resolve('./libs/game_config'));
var cash = require(path.resolve('./libs/gamestats/game_cash'));
var S = require('string');
var team_info = require(path.resolve('./libs/api/team_info'));
var pool = {};
var league = 'epl';
var redisClient = {};
exports.setConfig = function(c){
	config = c;
	cash.setConfig(config);
	team_info.setConfig(config);
}
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
	console.log('hire_official',game_team_id,staff_id);
	prepareDb(function(conn){
		async.waterfall(
			[
				function(callback){
					//need to know how much coin the team has
					team_info.getTeamInfo(conn,game_team_id,league,function(err,rs){
						console.log('team_info',JSON.stringify(rs));
						callback(null,rs);	
					});
					
				},
				function(team_info,callback){
					var fb_id = team_info.game_user.fb_id;
					cash.get_current_cash(conn,fb_id,function(err,coins){
						callback(err,fb_id,coins);
					});
				},
				function(fb_id,coins,callback){
					conn.query("SELECT * FROM "+config.database.database+".master_staffs\
								 WHERE id IN (?) LIMIT 1;",[staff_id],function(err,rs){
								 	console.log(S(this.sql).collapseWhitespace().s);
								 	callback(err,fb_id,coins,rs[0]);
								 });
				},
				function(fb_id,coins,staff,callback){
					console.log(staff);
					console.log('coins : ',coins,'price : ',
						game_config.staff_price[staff.staff_type][staff.rank]);
					if(coins >= game_config.staff_price[staff.staff_type][staff.rank]){
						conn.query("INSERT IGNORE INTO "+config.database.database+".game_team_staffs\
						(game_team_id,staff_id,name,staff_type,salary,recruit_date,rank,meta)\
						VALUES\
						(?,?,?,?,?,NOW(),?,?);",
						[game_team_id,staff.id,staff.name,staff.staff_type,staff.salary,staff.rank,meta],
						function (err,rs){
							console.log(S(this.sql).collapseWhitespace().s);
							callback(err,rs.insertId,staff.id,fb_id,game_config.staff_price[staff.staff_type][staff.rank]);
						});		
					}else{
						callback(null,0,staff.id,fb_id,game_config.staff_price[staff.staff_type][staff.rank]);
					}
					
				},
				function(insertId,staff_id,fb_id,staff_price,callback){
					if(insertId > 0){
						conn.query("INSERT INTO "+config.database.frontend_schema+".game_transactions\
							(fb_id,transaction_dt,transaction_name,amount,details)\
							VALUES\
							(?,NOW(),?,?,?)\
							ON DUPLICATE KEY UPDATE\
							amount = VALUES(amount);",
							[fb_id,'hire_staff_'+staff_id+'_'+(new Date().getTime()),(staff_price * -1),'hire staff'],
							function(err,rs){
								console.log(S(this.sql).collapseWhitespace().s);
								console.log('Update Coins');
								cash.update_cash_summary(conn,fb_id,function(err,rs){
									callback(err,insertId,staff_id);
								});
								
							});
					}else{
						callback(null,insertId,staff_id);
					}
				},
				function(insertId,staff_id,callback){
					if(insertId > 0){
						conn.query("SELECT * FROM "+config.database.database+".game_team_staffs a\
								WHERE a.game_team_id = ? AND a.staff_id = ?;",
								[game_team_id,staff_id],
								function(err,rs){
									console.log(S(this.sql).collapseWhitespace().s);
									console.log(rs);
									callback(err,rs[0]);
								});
					}else{
						callback(null,null);
					}
					
				},
				function(staff,callback){
					if(staff!=null){
						updateCache(conn,game_team_id,function(e){
						if(e!=null){
							console.log('error_staff_cache',e.message());
						}
						callback(null,staff);
						});
					}else{
						callback(null,null);
					}
					
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
							if(staffs[i].staff_type=='fw_coach'){
								o['fwd_coach'] = staffs[i].rank;
								staffs[i].meta = JSON.parse(staffs[i].meta);
								o.fwd_tactics.push(staffs[i].meta.tactics.id);
							}
						}	
					}
					
					redisClient.set(name,JSON.stringify(o),function(err){
						if(err){
							console.log('updateStaffCache',err.message);
						}
						console.log('updateStaffCache',JSON.stringify(o));
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
						console.log('master-staff',S(this.sql).collapseWhitespace().s);
						for(var i in rs){
							console.log('master-staff',rs[i].rank,game_config.staff_price[rs[i].staff_type][rs[i].rank]);
							rs[i].price = game_config.staff_price[rs[i].staff_type][rs[i].rank];
						}
						conn.release();
						callback(err,rs);
					});
	});
}

function get_user_officials(conn,game_team_id,done){
	async.waterfall(
		[
			function(callback){
				conn.query("SELECT a.*,b.image FROM "+config.database.database+".game_team_staffs a\
							INNER JOIN "+config.database.database+".master_staffs b\
							ON a.staff_id = b.id\
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