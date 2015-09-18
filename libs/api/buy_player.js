/**
* API for buying a player
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
var team_info = require(path.resolve('./libs/api/team_info'));
var game_config = require(path.resolve('./libs/game_config'));
var messages = require(path.resolve('./libs/messages'));
var notifications = require(path.resolve('./libs/api/notifications'));
var pool = {};
var redisClient = {};
function prepareDb(callback){

	pool.getConnection(function(err,conn){
		callback(conn);
	});
	
}


/** negotiate transfer**/
exports.negotiate_transfer = function(game_team_id,player_id,offer_price,league,callback){
	prepareDb(function(conn){
		negotiate_transfer_process(conn,game_team_id,player_id,offer_price,league,
		function(err,result){
			conn.release();
			callback(err,result);
		});
	});
}
exports.is_player_in_club = function(game_team_id,player_id,league,callback){
	prepareDb(function(conn){
		conn.query("SELECT id FROM "+config.database.database+".game_team_players \
					WHERE game_team_id = ? and player_id = ? LIMIT 1",
					[game_team_id,player_id],
		function(err,rs){
			console.log(S(this.sql).collapseWhitespace().s);
			conn.release();
			try{
				if(rs!=null && rs[0].id > 0){
					callback(err,true);
				}else{
					callback(err,false);
				}
			}catch(e){
				callback(err,false);
			}
			
		});
	});
}
var negotiate_transfer_process = function(conn,
												game_team_id,
												player_id,
												offer_price,
												league,
												callback){

	async.waterfall([
		function(cb){
			console.log('negotiate_transfer_process','['+game_team_id+'] -',player_id,offer_price);
			//get player info
			conn.query("SELECT a.id,a.uid,a.name,a.team_id,a.transfer_value,a.salary,a.position,b.name AS club_name\
							FROM "+config.database.database+".master_player a INNER JOIN ffgame.master_team b\
							ON a.team_id = b.uid WHERE a.uid=?;",[player_id],function(err,rs){
								cb(err,rs[0]);
							});
		},
		function(player,cb){
			//check for transfer window id
			console.log('player :  ',player);
			conn.query("SELECT id FROM "+config.database.database+".master_transfer_window \
						WHERE NOW() BETWEEN tw_open AND tw_close LIMIT 1;",[],function(err,rs){
							var tw_id = 0;
							if(rs!=null && rs.length > 0){
								tw_id = rs[0].id;
							}
							cb(err,player,tw_id);
						});
		},
		function(player,tw_id,cb){
			conn.query(
				"SELECT points,performance FROM "+config.database.statsdb+".master_player_performance \
				 WHERE player_id=? ORDER BY id DESC;",
				[player_id],
				function(err,rs){
					if(!err){
						var transfer_value = player.transfer_value;
						//console.log(this.sql);
						//@TODO we need to calculate the player's performance value to affect
						//the latest transfer value
						if(rs.length>0){
							rs[0].performance = rs[0].performance || 0;
							if(rs[0].performance!=0){
								transfer_value = transfer_value + ((((rs[0].performance / 10) * 1)/100)*transfer_value);
							}
						}
						console.log(rs[0].performance);
						console.log('new transfer_value : ',transfer_value);
						player.transfer_value = transfer_value;
						cb(err,player,tw_id);

					}else{
						cb(new Error('player got no performance'),player,tw_id);
					}
				});
		},
		function(player,tw_id,cb){
			console.log('tw_id : ',tw_id);
			//total purchase so far in current transfer window
			conn.query("SELECT COUNT(id) AS total \
						FROM "+config.database.database+".game_transfer_history\
						WHERE tw_id=? AND game_team_id=? AND transfer_type=1",[tw_id,game_team_id],
						function(err,rs){
							cb(err,player,tw_id,rs[0].total);
						});
			
		},
		function(player,tw_id,total_purchase,cb){

			console.log('total purchase : ',total_purchase);
			//current budget
			conn.query("SELECT SUM(budget+balance) AS money FROM (\
												SELECT budget, 0 AS balance \
												FROM "+config.database.database+".game_team_purse \
												WHERE game_team_id=?\
													UNION\
												SELECT 0 AS budget,SUM(amount) AS balance \
												FROM "+config.database.database+".game_team_expenditures \
												WHERE game_team_id = ?) a;",
								[game_team_id,game_team_id],function(err,rs){
									if(rs!=null){
										cb(err,player,tw_id,total_purchase,rs[0].money);
									}else{
										cb(new Error('no money'),0);
									}
								});
			
		},
		function(player,tw_id,total_purchase,budget,cb){
			console.log("current budget : "+budget);
			//get player quadrant
			console.log("SELECT rank FROM "+config.database.database+".master_rank WHERE team_id=?");
			conn.query("SELECT rank FROM "+config.database.database+".master_rank WHERE team_id=?",
						[player.team_id],function(err,rs){
							console.log(S(this.sql).collapseWhitespace().s);
							var rank = 4;
							if(rs!=null){
								rank = Math.ceil(rs[0].rank/5);
							}
							cb(err,player,tw_id,total_purchase,budget,rank);
						});
			
		},
		function(player,tw_id,total_purchase,budget,player_quadrant,cb){
			conn.query("SELECT rank \
						FROM "+config.database.database+".game_team_staffs \
						WHERE game_team_id=? AND staff_type='dof' LIMIT 1;",
						[game_team_id],function(err,rs){
							var dof = 0;
							if(rs!=null && rs.length==1){
								dof = game_config.staff_bonus.dof[rs[0].rank];
							}
							console.log('dof',dof);
							cb(err,player,tw_id,total_purchase,budget,player_quadrant,dof);
						});
		},
		function(player,tw_id,total_purchase,budget,player_quadrant,dof,cb){
			//get team info
			team_info.getTeamInfo(conn,game_team_id,league,function(err,team_info_data){
				console.log(team_info_data);

				cb(err,player,tw_id,total_purchase,budget,player_quadrant,dof,team_info_data);
			});
		},
		function(player,tw_id,total_purchase,budget,player_quadrant,dof,team_info_data,cb){
			createNegotiationResult(offer_price,player,tw_id,total_purchase,budget,
									player_quadrant,dof,team_info_data,function(err,result){
										console.log(result);
				cb(err,player,game_team_id,result);
				
			});
		},
		function(player,game_team_id,nego_result,cb){

			var message = "";
			var nego_id = 0;
			var salary_nego_id = 0;
			var msg_id = (new Date()).getTime();
			var msg_type = "transfer_nego";
			if(nego_result.status==1){
				message = messages.message.transfer_negotiation_success(player.club_name,player.name,offer_price);
				saveTransaction(conn,player,
								game_team_id,
								nego_result.offer_price,
								nego_result.acc_price,
								function(err,rs){
					meta = {'transfer_status':'success','nego_id':rs.nego_id,'salary_nego_id':0};
					notifications.send(conn,game_team_id,league,'',message,msg_id,msg_type,meta,function(err,rs){
						cb(err,nego_result);
					});
				});
				
			}else if(nego_result.status==2){
				message = messages.message.insufficient_budget(player.club_name,player.name,offer_price);
				
				meta = {'transfer_status':'failed'};
				notifications.send(conn,game_team_id,league,'',message,msg_id,msg_type,meta,function(err,rs){
					cb(err,nego_result);
				});
				
				
			}else{
				message = messages.message.transfer_negotiation_failed(player.club_name,player.name,offer_price);
				meta = {'transfer_status':'failed'};
				notifications.send(conn,game_team_id,league,'',message,msg_id,msg_type,meta,function(err,rs){
					cb(err,nego_result);
				});
			}
			
		}


	],
	function(err,rs){
		callback(err,rs);
	});
}

var saveTransaction = function(conn,player,game_team_id,offer_price,acceptable_price,callback){
	async.waterfall([
		function(cb){
			conn.query("INSERT INTO "+config.database.database+".game_transfer_nego\
						(game_team_id,master_team_id,player_id,transfer_dt,offer_price,acceptable_price,n_status)\
						VALUES\
						(?,?,?,NOW(),?,?,1);",
						[game_team_id,player.team_id,player.uid,
							offer_price,acceptable_price],
			function(err,rs){
				cb(err,rs.insertId);
			});
		},
		function(nego_id,cb){
			cb(null,{nego_id:nego_id});
		}
	],
	function(err,rs){
		callback(err,rs);
	});
}

var createNegotiationResult = function(offer_price,player,tw_id,total_purchase,budget,player_quadrant,dof,team_info_data,callback){

	//hitung acceptable price
	//acceptable price = (base_price - (base_price *  director of football bonus)) + (base_price * markup_modifier)
	var base_price = player.transfer_value;
	var markup_mod = getMarkupModifier(total_purchase);
	var acc_price = (base_price - (base_price * dof)) + (base_price * markup_mod);
	console.log('acceptable_price',acc_price);

	if(budget < offer_price){
		//no budget
		callback(null,{total_purchase:total_purchase,dof:dof,base_price:base_price,markup_mod:markup_mod,acc_price:acc_price,offer_price:offer_price,budget:budget,status:2});
	}else if(budget >= offer_price && offer_price >= acc_price && total_purchase < 5){

		callback(null,{total_purchase:total_purchase,dof:dof,base_price:base_price,markup_mod:markup_mod,acc_price:acc_price,offer_price:offer_price,budget:budget,status:1});
	}else{
		callback(null,{total_purchase:total_purchase,dof:dof,base_price:base_price,markup_mod:markup_mod,acc_price:acc_price,offer_price:offer_price,budget:budget,status:0});
	}
}

var getMarkupModifier = function(total_purchases){
	if(total_purchases > 4){
		return game_config.markup_modifiers[5];
	}else{
		return game_config.markup_modifiers[total_purchases+1];
	}
}

exports.setPool = function(p){
	pool = p;
	team_info.setPool(p);
}
exports.setConfig = function(c){
	config = c;
	team_info.setConfig(c);
	notifications.setConfig(c);
}