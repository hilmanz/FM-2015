var path = require('path');
var gameplay = require(path.resolve('./libs/api/gameplay'));
var ecash = require(path.resolve('./libs/api/ecash'));

var config = {};
exports.setConfig = function(c){
	config = c;
	ecash.setConfig(config);
	gameplay.setConfig(config);

}
exports.setLeague = function(l){
	gameplay.setLeague(l);
}
exports.setPool = function(pool){
	gameplay.setPool(pool);
}

exports.getLineup = function(req,res){
	gameplay.getLineup(req.redisClient,req.params.id,
		function(err,rs){
			if(rs!=null){
				res.send(200,rs);
			}else{
				//send the default configurations
				res.send(200,{lineup:[],formation:'4-4-2'});
			}
	});
}
exports.getPlayers = function(req,res){
	gameplay.setRedisClient(req.redisClient);
	gameplay.getPlayers(req.params.id,function(err,rs){
		if(rs!=null){
			res.json(200,rs);
		}else{
			res.send(200,[]);
		}
	});
}
exports.getTransferWindow = function(req,res){
	gameplay.getTransferWindow(function(err,rs){
		if(rs!=null){
			
			res.json(200,rs);
		}else{
			res.send(200,[]);
		}
	});
}
exports.getCash = function(req,res){
	gameplay.getCash(req.params.fb_id,function(err,rs){
		if(rs!=null){
			
			res.json(200,rs);
		}else{
			res.send(200,[]);
		}
	});
}
exports.setLineup = function(req,res){
	
	gameplay.setLineup(req.redisClient,req.body.team_id,
						JSON.parse(req.body.players),
						req.body.formation,
		function(err,rs,upcoming_matchday){

			if(err){
				console.log(err.message);
				handleError(res);
			}else{
				if(rs!=null){
					res.json(200,{status:1,lineup:rs,matchday:upcoming_matchday});
				}else{
					res.send(200,{status:0,err:err.message});
				}
			}
	});
	
}
exports.fixtures = function(req,res){
	gameplay.match.fixtures(function(err,rs){
		if(err){
			console.log(err.message);
			handleError(res);
		}else{
			if(rs!=null){
				res.json(200,{status:1,matches:rs});
			}else{
				res.send(200,{status:0});
			}
		}
	});
}
exports.official_list = function(req,res){
	gameplay.officials.official_list(req.params.game_team_id,
		function(err,rs){
			if(err){
				handleError(res);
			}else{
				if(rs!=null){
					res.json(200,{status:1,officials:rs});
				}else{
					res.send(200,{status:0});
				}
			}
	});
}
exports.hire_staff = function(req,res){
	gameplay.officials.hire_official(req.body.team_id,
									 req.body.official_id,
		function(err,rs){
			console.log(rs);
			if(err){
				handleError(res);
			}else{
				if(typeof rs !== 'undefined'){
					res.json(200,{status:1,officials:rs});
				}else{
					res.json(200,{status:0,officials:rs});
				}
			}
	});
}
exports.fire_staff = function(req,res){
	gameplay.officials.remove_official(req.body.team_id,
									 req.body.official_id,
		function(err,rs){
			if(err){
				handleError(res);
			}else{
				res.json(200,{status:1,message:'the staff is been removed successfully !'});
			}
	});
}


exports.getBudget = function(req,res){
	gameplay.getBudget(req.params.game_team_id,
		function(err,rs){
			if(err){
				handleError(res);
			}else{
				if(rs!=null){
					try{
						res.json(200,{status:1,budget:rs[0].budget});
					}catch(e){
						console.log('failed to get budget',rs,'at service/gameplay line 114');
						res.send(200,{status:0});
					}
				}else{
					res.send(200,{status:0});
				}
			}
	});
}
exports.getSponsors = function(req,res){
	gameplay.sponsorship.getAvailableSponsorship(
		req.params.team_id,
			function(err,rs){
				if(err){
					handleError(res);
				}else{
					if(rs!=null){
						res.json(200,{status:1,sponsorships:rs});
					}else{
						res.send(200,{status:0});
					}
				}
		});
}
exports.getActiveSponsors = function(req,res){
	gameplay.sponsorship.getActiveSponsors(
		req.params.game_team_id,
			function(err,rs){
				if(err){
					handleError(res);
				}else{
					if(rs!=null){
						res.json(200,{status:1,sponsors:rs});
					}else{
						res.send(200,{status:0});
					}
				}
		});
}
exports.applySponsorship = function(req,res){
	gameplay.sponsorship.applySponsorship(
		req.body.game_id,
		req.body.matchday,
		req.body.game_team_id,
		req.body.sponsor_id,
			function(err,rs){
				if(err){
					console.log('error:',err.message);
					if(err.message=='ALREADY_HAVE_SPONSOR'){
						res.json(200,{status:2,message:'Already have a sponsor'});
					}else if(err.message=='ALREADY_APPLIED'){
						res.send(200,{status:0,message:'Sponsorship Denied !'});
					}else{
						handleError(res);	
					}
				}else{
					if(rs){
						res.json(200,{status:1,message:'Sponsorship Accepted'});
					}else{
						res.send(200,{status:0,message:'Sponsorship Denied !'});
					}
				}
		});
};

exports.match_results = function(req,res){
	gameplay.match.results(
		req.params.game_id,
		
			function(err,rs){
				if(err){
					handleError(res);
				}else{
					if(rs){
						res.json(200,{status:1,data:rs});
					}else{
						res.send(200,{status:0,data:[]});
					}
				}
		});
}
exports.match_results_for_user_team = function(req,res){
	gameplay.match.getMatchResultForUserTeam(
		req.params.game_team_id,
		req.params.game_id,
		
			function(err,rs){
				if(err){
					handleError(res);
				}else{
					if(rs){
						res.json(200,{status:1,data:rs});
					}else{
						res.send(200,{status:0,data:[]});
					}
				}
		});
}
exports.player_data = function(req,res){
	var async = require('async');
	gameplay.setRedisClient(req.redisClient);
	async.waterfall(
		[
			function(callback){
				console.log('getPlayers',req.params.id,'retrieving player data');
				gameplay.getPlayerDetail(req.params.id,function(err,player){
					console.log('getPlayers',req.params.id,player);
					callback(err,player);
				});
			},
			function(player,callback){
				console.log('getPlayers',req.params.id,'retrieving player stats');
				gameplay.getPlayerStats(
							req.params.id,
							function(err,rs){
								console.log('getPlayers',req.params.id,rs);
								callback(err,{player:player,stats:rs});	
							});
			},
			function(result,callback){
				if(result.player != null){
					console.log('getPlayers',req.params.id,'retrieving player overall');
					gameplay.getPlayerOverallStats(0,
												   result.player.player_id,
												   function(err,rs){
						console.log('getPlayers',req.params.id,rs);
						result.overall_stats = rs;
						callback(err,result);
					});
				}else{
					//we don't need to throw an error, 
					//so just return empty result.
					callback(null,result);
				}
			},
			function(result,callback){
				
				if(result.player != null){
					gameplay.getPlayerDailyTeamStats(0,
												   result.player.player_id,
												   result.player.position,
												   function(err,rs){
						result.daily_stats = rs;
						callback(err,result);
					});
				}else{
					//we don't need to throw an error, 
					//so just return empty result.
					callback(null,result);
				}
			}
		],
		function(err,result){
			if(err){
				console.log('getPlayers',req.params.id,'ERROR',result);
				handleError(res);
			}else{
				if(result){
					console.log('getPlayers',req.params.id,'OK',result);
					res.json(200,{status:1,data:result});
				}else{
					console.log('getPlayers',req.params.id,'ERROR',result);
					res.send(200,{status:0,data:{player:{},stats:[]}});
				}
			}
		}
	);
	
}

exports.player_team_data = function(req,res){
	var async = require('async');
	gameplay.setRedisClient(req.redisClient);
	async.waterfall(
		[
			function(callback){
				gameplay.getTeamPlayerDetail(req.params.game_team_id,
											 req.params.id,
											 function(err,player){
					callback(err,player);
				});
			},
			function(player,callback){
				gameplay.getPlayerTeamStats(
							req.params.game_team_id,
							req.params.id,
							function(err,rs){

								callback(err,{player:player,stats:rs});	
							});
			},
			function(result,callback){
				if(result.player != null){
					gameplay.getPlayerOverallStats(req.params.game_team_id,
												   result.player.player_id,
												   function(err,rs){
						result.overall_stats = rs;
						callback(err,result);
					});
				}else{
					//we don't need to throw an error, 
					//so just return empty result.
					callback(null,result);
				}
			},
			function(result,callback){
				
				if(result.player != null){
					gameplay.getPlayerDailyTeamStats(req.params.game_team_id,
												   result.player.player_id,
												   result.player.position,
												   function(err,rs){
						result.daily_stats = rs;
						callback(err,result);
					});
				}else{
					//we don't need to throw an error, 
					//so just return empty result.
					callback(null,result);
				}
			}
		],
		function(err,result){
			console.log(result);
			if(err){
				handleError(res);
			}else{
				if(result){
					res.json(200,{status:1,data:result});
				}else{
					res.send(200,{status:0,data:{player:{},stats:[]}});
				}
			}
		}
	);
	
}
exports.financial_statements = function(req,res){
	var async = require('async');
	async.waterfall([
		function(callback){
			gameplay.getBudget(req.params.game_team_id,function(err,result){
				if(result==null){
					callback(new Error('budget not found'),null);
				}else{
					callback(err,result[0]);	
				}
				
			});
		},
		function(budget,callback){
			gameplay.getFinancialStatement(req.params.game_team_id,function(err,result){
				try{
					result.budget = budget.budget;	
					callback(err,result);
				}catch(e){
					callback(err,null);
				}
				
				
			});
		}
	],
	function(err,result){
		if(err){
			handleError(res);
		}else{
			if(result){
				res.json(200,{status:1,data:result});
			}else{
				res.send(200,{status:0});
			}
		}
	});
	
}
exports.weekly_finance = function(req,res){
	var async = require('async');
	async.waterfall([
		function(callback){
			gameplay.getBudget(req.params.game_team_id,function(err,result){
				callback(err,result[0]);
			});
		},
		function(budget,callback){
			gameplay.getWeeklyFinance(req.params.game_team_id,req.params.week,
				function(err,result){
					callback(err,{budget:budget.budget,
								  transactions:result});
			});
		}
	],
	function(err,result){
		if(err){
			handleError(res);
		}else{
			if(result){
				res.json(200,{status:1,data:result});
			}else{
				res.send(200,{status:0});
			}
		}
	});
	
}
exports.next_match = function(req,res){
	gameplay.next_match(req.params.team_id,function(err,match){
		if(!err){
			res.json(200,{status:1,match:match[0]});
		}else{
			res.send(200,{status:0});
		}
	});
}
exports.best_match = function(req,res){
	gameplay.best_match(req.params.game_team_id,function(err,result){
		if(!err){
			res.json(200,{status:1,data:result});
		}else{
			res.send(200,{status:0});
		}
	});
}
exports.best_player = function(req,res){
	gameplay.best_player(req.params.game_team_id,function(err,result){
		if(!err){
			res.json(200,{status:1,data:result});
		}else{
			res.send(200,{status:0});
		}
	});
}
exports.last_earning = function(req,res){
	gameplay.last_earning(req.params.game_team_id,function(err,result){
		if(!err){
			res.json(200,{status:1,data:result});
		}else{
			res.send(200,{status:0});
		}
	});
}
exports.last_expenses = function(req,res){
	gameplay.last_expenses(req.params.game_team_id,function(err,result){
		if(!err){
			res.json(200,{status:1,data:result});
		}else{
			res.send(200,{status:0});
		}
	});
}
exports.get_venue = function(req,res){
	gameplay.getVenue(req.params.team_id,function(err,venue){
		if(!err){
			res.json(200,{status:1,venue:venue});
		}else{
			res.send(200,{status:0});
		}
	});
}

exports.livestats = function(req,res){
	var client = req.redisClient;
	client.get('match_'+req.params.game_id,function(err,rs){
		if(!err){
			var player_stats = JSON.parse(rs);
			client.get('playerrefs_'+req.params.game_id,function(err,lineup){
				if(!err){
					client.get('teamstats_'+req.params.game_id,function(err,matchstats){
						if(!err){
							client.get('fixture_'+req.params.game_id,function(err,fixture){
								if(!err){
									client.get('accstats_'+req.params.game_id,function(err,accstats){
										if(!err){
											res.json(200,{status:1,data:{
												lineup:JSON.parse(lineup),
												matchinfo:JSON.parse(fixture),
												accumulative:filterAccumulativeStats(JSON.parse(accstats)),
												stats:player_stats}
											});
										}else{
											res.send(200,{status:0});
										}
									});
								}else{
									res.send(200,{status:0});
								}	
							});	
						}else{
							res.send(200,{status:0});
						}
					});
					
				}else{
					res.send(200,{status:0});
				}
			});
			
		}else{
			res.send(200,{status:0});
		}
	});
}
exports.livegoals = function(req,res){
	var client = req.redisClient;
	client.get('goals_'+req.params.game_id,function(err,rs){
		if(!err){
			res.json(200,{status:1,data:JSON.parse(rs)});
		}else{
			res.send(200,{status:0});
		}
	});
}
exports.livematches = function(req,res){
	var client = req.redisClient;
	var league = req.params.league;
	client.get('matchinfo_'+req.params.matchday+'_'+league,function(err,rs){

		if(!err){
			console.log('livematches',JSON.parse(rs));
			res.json(200,{status:1,data:JSON.parse(rs)});
		}else{
			console.log('livematches',err.message);
			res.send(200,{status:0});
		}
	});
}
exports.standings = function(req,res){
	var key = 'standings_epl';
	if(req.params.league == 'ita'){
		key = 'standings_ita';
	}
	
	var client = req.redisClient;
	client.get(key,function(err,rs){

		if(!err){
			console.log('standings',JSON.parse(rs));
			res.json(200,{status:1,data:JSON.parse(rs)});
		}else{
			console.log('standings',err.message);
			res.send(200,{status:0});
		}
	});
}
exports.matchstatus = function(req,res){
	gameplay.matchstatus(req.params.matchday,function(err,rs){
		if(!err){
			res.json(200,{status:1,is_finished:rs});
		}else{
			res.send(200,{status:0});
		}
	});
}
function handleError(res){
	res.send(501,{error:'no data available'});
}
//sale a player
exports.sale = function(req,res){
	gameplay.sale(
		req.redisClient,
		req.body.window_id,
		req.body.game_team_id,
		req.body.player_id,
		function(err,result){
			if(err){
				
				if(err.message=='INVALID_TRANSFER_WINDOW'){

					res.send(200,{status:-1,
									message:'you cannot sale a player who already bought from the same transfer window'});
				}else{
					handleError(res);
				}
				
			}else{
				if(result!=null){
					res.send(200,{status:1,data:result,message:'the player has been successfully sold.'});
				}else{
					res.send(200,{status:0,message:'Oops, cannot sale the player.'});
				}	
			}
		}
	);
}

//buy a player
exports.buy = function(req,res){
	gameplay.buy(
		req.redisClient,
		req.body.window_id,
		req.body.game_team_id,
		req.body.player_id,
		function(err,result){
			if(err){
				
				if(err.message=='no money'){
					res.send(200,{status:2,data:{player:{},stats:[]}});
				}else if(err.message == 'INVALID_TRANSFER_WINDOW'){
					res.send(200,{status:-1,
									message:'you cannot buy a player who already sold from the same transfer window'});
				}else{
					handleError(res);	
				}
			}else{
				if(result!=null){
					res.send(200,{status:1,data:result,message:'the player has been successfully bought.'});
				}else{
					res.send(200,{status:0,message:'Oops, cannot buy the player.'});
				}	
			}
		}
	);
}
exports.add_expenditure = function(req,res){
	gameplay.add_expenditure(
		req.body.game_team_id,
		req.body.transaction_name,
		req.body.amount,
		function(err,result){
			if(err){
				res.send(200,{status:0,message:'transaction failed'});
				
			}else{
				if(result!=null){
					res.send(200,{status:1,data:result});
				}else{
					res.send(200,{status:0,message:'transaction didnt return anything'});
				}	
			}
		}
	);
}
//get the list of match result statistics
exports.leaderboard = function(req,res){
	gameplay.leaderboard(function(err,rs){
		if(err){
			handleError(res);
		}else{
			if(rs!=null){
				res.json(200,{status:1,data:rs});
			}else{
				res.send(200,{status:0});
			}
		}
	});
}
exports.setInputAttempt = function(req,res){
	req.redisClient.set(req.query.name,req.query.value,function(err,rs){
		req.redisClient.expire(req.query.name, 60*60*24,function(err,rs){
			res.json(200,{status:1});
		});
		
	});
}
exports.storeToRedisTmp = function(req,res){
	var ttl = 60*60*24;
	if(typeof req.query.ttl !== 'undefined'){
		ttl = req.query.ttl;
	}
	req.redisClient.set(req.query.name,req.query.value,function(err,rs){
		req.redisClient.expire(req.query.name, ttl,function(err,rs){
			res.json(200,{status:1});
		});
	});
}
exports.getFromRedisTmp = function(req,res){
	req.redisClient.get(req.query.name,function(err,rs){
		res.json(200,{status:1,data:rs});
	});
}
exports.getRedisKeys = function(req,res){
	req.redisClient.keys(req.query.pattern,
	function(err,rs){
		res.json(200,{status:1,data:rs});
	});	
}
exports.getInputAttempt = function(req,res){
	req.redisClient.get(req.query.name,function(err,rs){
		res.json(200,{status:1,data:rs});
	});
}
exports.redeemCode = function(req,res){
	gameplay.redeemCode(req.body.game_team_id,
						req.body.coupon_code,
						function(err,rs){
		if(err){
			handleError(res);
		}else{
			if(rs!=null){
				res.json(200,{status:1,data:rs});
			}else{
				res.send(200,{status:0});
			}
		}					
	});
}

/*
* apply digital perk to game_team_id
* these method can only be executed after user buy the perk in online catalog.
*/
exports.apply_perk = function(req,res){
	gameplay.apply_perk(req.params.game_team_id,
						req.params.perk_id,
						function(err,rs){
		if(err){
			handleError(res);
		}else{
			if(rs!=null){
				res.json(200,{status:1,data:rs});
			}else{
				res.send(200,{status:0});
			}
		}		
	});
}
/*
* check if the perk is already in used (or currently active)
* returns status 1 if in use, and 0 if not in use.
*/
exports.check_perk = function(req,res){
	gameplay.check_perk(req.params.game_team_id,
						req.params.perk_id,
						function(err,rs){
		if(err){
			handleError(res);
		}else{
			if(rs!=null){
				if(rs==true){
					res.json(200,{status:1});	
				}else{
					res.json(200,{status:0});	
				}
			}else{
				res.send(200,{status:0});
			}
		}		
	});
}

/*
* getting bet information including 
* live match score, corner, fouls, yellow card,interceptions, shot on target
* also the list of the winners when the match's period is FullTime
*/
exports.bet_info = function(req,res){
	gameplay.bet_info(req.redisClient,
					  req.params.game_id,
						function(err,rs){
		if(err){
			handleError(res);
		}else{
			if(rs!=null){
				if(rs==true){
					res.json(200,{status:1,data:rs});	
				}else{
					res.json(200,{status:0,data:rs});	
				}
			}else{
				res.send(200,{status:0,data:rs});
			}
		}		
	});
}
//other functions
function filterAccumulativeStats(stats){
	var teamstats = {};
	for(var i in stats){
		if(typeof teamstats[stats[i].team_id] === 'undefined'){
			teamstats[stats[i].team_id] = [];
		}
		if(isAccumulativeStatsOk(stats[i].stats_name)){
			teamstats[stats[i].team_id].push({
				name:stats[i].stats_name,
				value:stats[i].total
			});
		}
	}
	return teamstats;
}
//checking if the stats_name is the stats we want to include in output
function isAccumulativeStatsOk(stats_name){
	var valid_stats = [
		'ontarget_scoring_att',
		'total_scoring_att',
		'att_ibox_blocked',
		'att_ibox_goal',
		'att_ibox_miss',
		'att_ibox_target',
		'att_obox_blocked',
		'att_obox_goal',
		'att_obox_miss',
		'att_obox_target',
		'att_ibox_goal',
		'att_obox_goal',
		'att_ibox_post',
		'att_obox_post',
		'big_chance_created',
		'shot_fastbreak',
		'total_scoring_att',
		'accurate_cross_nocorner',
		'total_cross_nocorner',
		'accurate_through_ball',
		'total_through_ball',
		'final_third_entries',
		'pen_area_entries',
		'passes_left',
		'passes_right',
		'fouled_final_third',
		'duel_won',
		'aerial_won',
		'won_contest',
		'won_tackle',
		'total_tackle',
		'effective_head_clearance',
		'blocked_pass',
		'interception',
		'outfielder_block',
		'six_yard_block',
		'blocked_cross',
		'offside_provoked',
		'error_lead_to_shot',
		'error_lead_to_goal',
		'unsuccessful_touch',
		'dispossessed'
	];
	for(var i in valid_stats){
		if(valid_stats[i] == stats_name){
			return true;
		}
	}
}


//get match postponed status
exports.getPostponedStatus = function(req,res){
	req.redisClient.get('postponed-'+req.query.game_id,function(err,postponed){
		if(postponed==null){
			res.json(200,{status:0});
		}else{
			res.json(200,{status:1});
		}
	});
}
exports.setPostponedStatus = function(req,res){
	
	gameplay.setPostponedStatus(req.redisClient,
								req.query.game_id,
								req.query.toggle,
								function(err,rs){
									res.json(200,{status:1});
								});
	
}

exports.getEcashUrl = function(req,res){
	ecash.getEcashUrl(req.query.transaction_id,
						req.query.clientIpAddress,
						req.query.description,
						req.query.amount,
						req.query.source,
						function(err,rs){
							if(!err){
								res.json(200,{status:1,data:rs});	
							}else{
								res.json(200,{status:0,data:rs});
							}
							
						});
}
exports.EcashValidate = function(req,res){
	ecash.validate(req.query.id,function(err,rs){
		res.json(200,rs);
	});
}