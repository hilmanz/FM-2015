/**
* API for salary negotiation
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
var player_purchase = require(path.resolve('./libs/api/player_purchase'));
var pool = {};
function prepareDb(callback){

	pool.getConnection(function(err,conn){
		callback(conn);
	});
	
}


/** negotiate transfer**/
exports.negotiate_salary_window = function(game_team_id,
										nego_id,
										offer_price,
										league,
										callback){
	prepareDb(function(conn){
		negotiate_salary_window_proc(conn,
									game_team_id,
									nego_id,
									offer_price,
									0,
									0,
									league,
		function(err,result){
			conn.release();
			callback(err,result);
		});
	});
}

exports.negotiate_salary = function(redisClient,
									game_team_id,
									nego_id,
									offer_price,
									goal,
									cleansheet,
									league,
									callback){

	prepareDb(function(conn){
		negotiate_salary_process(conn,redisClient,game_team_id,nego_id,offer_price,goal,cleansheet,league,
		function(err,result){
			conn.release();
			callback(err,result);
		});
	});
}

//gather all informations needed for single player
var negotiate_salary_window_proc = function(conn,
												game_team_id,
												nego_id,
												offer_price,
												goal_bonus,
												cleansheet_bonus,
												league,
												callback){
	var data = {};
	async.waterfall([
		function(cb){
			conn.query("SELECT * FROM "+config.database.database+".game_transfer_nego\
						 WHERE id = ? AND game_team_id = ? LIMIT 1;",[nego_id,game_team_id],
			function(err,rs){
				if(rs!=null){
					
					data.transfer = rs[0];
					cb(err);
				}else{
					cb(new Error('no data'));
				}
			});
		},
		function(cb){
			//get player info
			conn.query("SELECT a.id,a.uid,a.name,a.team_id,a.transfer_value,a.salary,a.position,b.name AS club_name\
							FROM "+config.database.database+".master_player a INNER JOIN ffgame.master_team b\
							ON a.team_id = b.uid WHERE a.uid=?;",[data.transfer.player_id],
							function(err,rs){
								console.log(S(this.sql).collapseWhitespace().s);
								data.player = rs[0];
								cb(err);
							});
			
		},
		function(cb){
			//check for transfer window id
			
			conn.query("SELECT id FROM "+config.database.database+".master_transfer_window \
						WHERE NOW() BETWEEN tw_open AND tw_close LIMIT 1;",[],function(err,rs){
							var tw_id = 0;
							if(rs!=null && rs.length > 0){
								tw_id = rs[0].id;
							}
							data.tw_id = tw_id;
							cb(err);
						});
		},
		function(cb){
			//get club current players in the same positions
			conn.query("SELECT COUNT(*) AS total \
						FROM "+config.database.database+".game_team_players a\
						INNER JOIN ffgame.master_player b ON a.player_id = b.uid \
						WHERE game_team_id = ? AND b.position=?;",
						[game_team_id,data.player.position],function(err,rs){
								data.in_rooster = rs[0].total;
								cb(err);
							});	

		},
		function(cb){
			conn.query("SELECT rank \
						FROM "+config.database.database+".game_team_staffs \
						WHERE game_team_id=? AND staff_type='dof' LIMIT 1;",
						[game_team_id],function(err,rs){
							var dof = 0;
							if(rs!=null && rs.length==1){
								dof = game_config.staff_bonus.dof[rs[0].rank];
							}
							data.dof = dof;
							cb(err);
						});
		},
		function(cb){
			
			conn.query("SELECT rank FROM "+config.database.database+".master_rank WHERE team_id=?",
						[data.player.team_id],function(err,rs){
							console.log(S(this.sql).collapseWhitespace().s);
							var rank = 4;
							if(rs!=null){
								rank = rs[0].rank;
							}
							if(rank > 20){
								rank = 20;
							}
							data.player_rank = Math.ceil(rank/5);
							cb(err);
						});
			
		},
		function(cb){
			//get team info
			team_info.getTeamInfo(conn,game_team_id,league,function(err,team_info_data){
				data.team_info = team_info_data;
				data.quadrant_bonus = compareQuadrant(data.player_rank,
														data.team_info.team_points.quadrant);
				data.rooster_bonus = inRoosterCompare(data.player.position,data.in_rooster);
				
				data.goal_bonus = 0;
				if(goal_bonus > 0){
					data.goal_bonus = goal_bonus;
				}
				data.cleansheet_bonus = 0;
				if(cleansheet_bonus > 0){
					data.cleansheet_bonus = cleansheet_bonus;
				}
				if(offer_price == 0){
					data.offer_price = data.player.salary;
				}

				data.offer_price = offer_price;
				data = calculate_score(data);

				if(data.base_agreement_score < 2.5){
					data.player_decision = 0;	
				}else{
					data.player_decision = 1;
				}

				data.statuses = generateStatuses(data);
				cb(err);
			});
		},


	],
	function(err,rs){
		console.log(data);
		callback(err,data);
	});
}
var generateStatuses = function(data){
	var statuses = [];
	
	if(data.quadrant_bonus >= 0){
		statuses.push("Peringkat klub lo menarik (+"+data.quadrant_bonus+")");
	}else{
		statuses.push("Peringkat klub lo ga menarik ("+data.quadrant_bonus+")");
	}
	



	if(data.rooster_bonus >= 0){
		statuses.push("Klub lo belum punya banyak "+data.player.position+" (+"+data.rooster_bonus+")");
	}else{
		statuses.push("Klub loe sudah punya banyak "+data.player.position+" ("+data.rooster_bonus+")");
	}


	if(data.goal_mod > 0){
		statuses.push("Dapet bonus kalo cetak gol (+"+parseFloat(data.goal_mod).toFixed(2)+")");
	}else{
		statuses.push("Nggak dapet bonus kalau mencetak gol ("+parseFloat(data.goal_mod).toFixed(2)+")");
	}
	
	if(data.cleansheet_mod > 0){
		statuses.push("Dapet bonus kalo cleansheet (+"+parseFloat(data.cleansheet_mod).toFixed(2)+")");
	}else{
		statuses.push("Nggak dapet bonus kalau cleansheet ("+parseFloat(data.cleansheet_mod).toFixed(2)+")");
	}
	console.log('offer gaji : ',data.offer_price);
	if(parseFloat(data.offer_price) > parseFloat(data.player.salary)){
		statuses.push("Gaji Menarik (+"+parseFloat(data.salary_bonus).toFixed(2)+")");
	}else{
		statuses.push("Gaji terlalu kecil ("+parseFloat(data.salary_bonus).toFixed(2)+")");
	}

	statuses.push("Kemampuan Negosiasi Director of Football (+"+parseFloat(data.dof).toFixed(2)+")");
	
	return statuses;
}
var negotiate_salary_process = function(conn,
										redisClient,
										game_team_id,
										nego_id,
										offer_price,
										goal,
										cleansheet,
										league,
										callback){
	var data = {};
	async.waterfall([
		function(cb){
			negotiate_salary_window_proc(conn,
										game_team_id,
										nego_id,
										offer_price,
										goal,
										cleansheet,
										league,
			function(err,rs){
				data = rs;
				cb(err);
			});
		},
		function(cb){
			var message = "";
			var salary_nego_id = 0;
			var msg_id = (new Date()).getTime();
			var msg_type = "salary_nego";
			var meta = {};
			if(data.player_decision==1){
				message = messages.message.salary_nego_success(data.player.name,offer_price);
				meta = {'status':'success','nego_id':nego_id,'salary_nego_id':0};
				//purchase the player and register to player_salary_nego log
				player_purchase.save(conn,redisClient,game_team_id,data.player.uid,data,league,function(err,rs){
					console.log('player_purchase',rs);
					if(rs!=null){
						notifications.send(conn,game_team_id,league,'',message,msg_id,msg_type,meta,function(err,rs){
							
							cb(err,data);
						});
					}else{
						message = messages.message.salary_nego_failed2(data.player.name,offer_price);
						meta = {'status':'failed','nego_id':nego_id,'salary_nego_id':0};
						notifications.send(conn,game_team_id,league,'',message,msg_id,msg_type,meta,function(err,rs){
							cb(err,data);
						});
					}
				});

				
			}else{
				message = messages.message.salary_nego_failed(data.player.name,offer_price);
				meta = {'status':'failed','nego_id':nego_id,'salary_nego_id':0};
				notifications.send(conn,game_team_id,league,'',message,msg_id,msg_type,meta,function(err,rs){
					cb(err,data);
				});
			}

		},
		


	],
	function(err,rs){
		//console.log(data);
		callback(err,rs);
	});
}

exports.setPool = function(p){
	pool = p;
	team_info.setPool(p);
}
exports.setConfig = function(c){
	config = c;
	team_info.setConfig(c);
	notifications.setConfig(c);
	player_purchase.setConfig(c);
}

function compareQuadrant(player_rank,club_rank){
	//rank 1 tertinggi, 4 terendah

	//player 2, club 1 -> berarti +1
	//player 1, club 1 -> berarti +0
	//player 3, club 4 -> berarti -1
	if(typeof club_rank == undefined){
		club_rank = 4;
	}
	var diff = player_rank - club_rank;
	return game_config.quadrant_comparison[diff];
}
function inRoosterCompare(player_position,players_in_position){

	var bonus = 0;

	if(players_in_position >= 9){
		return game_config.in_rooster[player_position][10];
	}else{
		return game_config.in_rooster[player_position][players_in_position];
	}
}

function calculate_score(data){
	// salary bonus = (offer/base) + ((offer/base) * dof_bonus)
	// goal bonus = (bonus/base) + ((bonus/base) * dof_bonus)
	// cleansheet bonus = (bonus/base) + ((bonus/base) * dof_bonus)
	// DEAL BONUS = Salary Bonus + Quadrant Bonus + DOF Bonus + ROOSTER Bonus
	var offer = data.offer_price;
	var base = data.player.salary;
	var dof_bonus = data.dof;
	var salary_bonus = (offer/base) + ((offer/base)*dof_bonus);
	var goal_bonus = (data.goal_bonus/base) + ((data.goal_bonus/base) * dof_bonus);
	var cleansheet_bonus = (data.cleansheet_bonus/base) + ((data.cleansheet_bonus/base) * dof_bonus);
	var total = salary_bonus + goal_bonus + cleansheet_bonus + data.quadrant_bonus + dof_bonus + data.rooster_bonus;
	data.goal_mod = goal_bonus;
	data.cleansheet_mod = cleansheet_bonus;
	data.base_agreement_score = total;
	data.salary_bonus = salary_bonus;
	return data;
}