/*
player transfer system
it will handle:
1. transfer window
2. buy / sale player
3. transfer negotiation
4. salary negotiation
*/
var path = require('path');
var config = {};
var pool = {};
var buy_player = require(path.resolve('./libs/api/buy_player'));
var salary_nego = require(path.resolve('./libs/api/salary_nego'));
var league = "epl";

exports.init = function(c,p){
	config = c;
	pool = p;
	buy_player.setPool(pool);
	salary_nego.setPool(pool);
}
exports.setConfig = function(c){
	config = c;
	buy_player.setConfig(config);
	salary_nego.setConfig(config);

}
exports.setLeague = function(c){
	league = c;
	

}
exports.setPool = function(pool){
	buy_player.setPool(pool);
	salary_nego.setPool(pool);
}
exports.nego = function(req,res){
	var game_team_id = req.query.game_team_id;
	var nego_id = req.params.nego_id;
	salary_nego.negotiate_salary_window(game_team_id,
										nego_id,
										0,
										league,
										function(err,rs){
											if(!err){
												res.send(200,{status:1,data:rs});
											}else{
												handleError(res);
											}
										});

}
exports.offer = function(req,res){
	var game_team_id = req.body.game_team_id;
	var nego_id = req.params.nego_id;
	console.log(req.body);
	var player_id = req.body.player_id;
	var offer_price = req.body.offer_price;
	var goal_bonus = (typeof req.body.goal_bonus!=='undefined') ? parseInt(req.body.goal_bonus) : 0; 
	var cleansheet_bonus = (typeof req.body.cleansheet_bonus!=='undefined') ? parseInt(req.body.cleansheet_bonus) : 0; 

	if(goal_bonus < offer_price && cleansheet_bonus < offer_price){
		buy_player.is_player_in_club(game_team_id,player_id,league,function(err,is_in_club){
			if(!is_in_club){
				salary_nego.negotiate_salary(req.redisClient,game_team_id,nego_id,
												offer_price,goal_bonus,cleansheet_bonus,league,
				function(err,rs){
					
					res.send(200,{status:1,data:rs});
				});
				
			}else{
				res.send(200,{status:0,message:'Pemain ini sudah bergabung dengan klub anda !'});
			}
		});
		
	}else{
		res.send(200,{status:0,message:'bonus gol dan bonus cleansheet tidak bisa lebih besar dari pada gaji yang ditawarkan !'});
	}

}
exports.buy = function(req,res){
	var game_team_id = req.body.game_team_id;
	var player_id = req.body.player_id;
	var offer_price = parseInt(req.body.offer_price);
	var tw_id = parseInt(req.body.window_id);
	console.log(req.body);
	if(tw_id > 0){
		if(offer_price < 0){
			offer_price = 0;
		}

		if(game_team_id > 0 && player_id !=''){
			buy_player.is_player_in_club(game_team_id,player_id,league,function(err,is_in_club){
				if(!is_in_club){
					buy_player.negotiate_transfer(game_team_id,player_id,offer_price,league,function(err,result){
						if(!err){
							res.send(200,{status:1,data:result});
						}else{
							handleError(res);
						}
					});
				}else{
					res.send(200,{ status:0,
									in_club:true,
									message:'Mohon maaf, pemain ini sudah menjadi pemain di klub loe !'
								 });
				}
			});
		}else{
			handleError(res);
		}
	}else{
		res.send(200,{status:0,message:'Mohon maaf, transfer window sedang ditutup.'});
	}
	
}
exports.negotiate_salary = function(req,res){
	var game_team_id = req.params.game_team_id;
	var nego_id = parseInt(req.params.nego_id);
	var offer_price = parseInt(req.params.offer_price);

	if(offer_price < 0){
		offer_price = 0;
	}

	if(game_team_id > 0 && nego_id > 0 ){
		buy_player.negotiate_salary(
			game_team_id,
			nego_id,
			offer_price,
			function(err,result){
					if(!err){
						res.send(200,{status:1,data:result});
					}else{
						handleError(res);
					}
				}
		);
	}else{
		handleError(res);
	}
}

function handleError(res){
	res.send(200,{status:0});
}