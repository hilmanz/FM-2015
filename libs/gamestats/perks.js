/*
* these module will helps to calculate the point bonus or penalty gained from perks
*/
var PHPUnserialize = require('php-unserialize');
var async = require('async');
/*
* apply perk for modified the player stats points.
* @params conn 
* @params game_team_id
* @params new_stats , the new generated stats before the points added with perk point bonuses

*/

var config = {};
exports.setConfig = function(c){
	config = c;
}
exports.apply_player_perk = function(conn,game_team_id,player_id,new_stats,matchday,done){

	if(new_stats.length > 0){
		console.log('getExtraPoints',game_team_id,player_id);
		var game_id = new_stats[0].game_id;
		async.waterfall([
			function(cb){
				//get all the perks these team has
				getAllPerks(conn,game_team_id,function(err,perks){
					cb(err,perks);
				});
			},
			function(perks,cb){
				//apply each perks
				process_player_stats_perks(
					conn,
					game_team_id,
					matchday,
					perks,
					new_stats,
					function(err,extra_points,additional_points){
						console.log('process_player_stats_perks',game_team_id,player_id,extra_points,additional_points);
						cb(err,perks,extra_points,additional_points);
					});
			},
			function(perks,extra_points,additional_points,cb){
				//get the game_id played by the game_team_id
				conn.query("SELECT game_id FROM "+config.database.database+".game_fixtures WHERE matchday = ? AND \
							(\
							home_id IN (SELECT team_id FROM "+config.database.database+".game_teams WHERE id = ?)\
							OR\
							away_id IN (SELECT team_id FROM "+config.database.database+".game_teams WHERE id = ?)\
							)\
							LIMIT 1;",
				[
					matchday,game_team_id,game_team_id
				],
				function(err,rs){
					try{
						cb(err,perks,extra_points,additional_points,rs[0].game_id);	
					}catch(e){
						conn.query("SELECT game_id FROM "+config.database.database+".game_fixtures\
								 WHERE matchday=? ORDER BY game_id ASC LIMIT 1",
								 [matchday],function(err,match){
								 	the_game_id = match[0]['game_id'];
								 	console.log('the game id : ',the_game_id,' actually not found');
								 	cb(err,perks,extra_points,additional_points,the_game_id);
								 });
						
					}
					
				});
			},
			function(perks,extra_points,additional_points,the_game_id,cb){
				//calculate the bonus point of overall category points 
				var items = [];
				for(var i in additional_points){
					items.push({
						category: i,
						total: parseFloat(additional_points[i])
					});
				}
				console.log('bonus_per_category',game_team_id,matchday,items);
				async.eachSeries(items,function(item,next){
					if(item.total != 0){
						var modifier_name = item.category + '_Bonus';
						saveExtraPoint(conn,
							           the_game_id,
							           matchday,
							           game_team_id,
							           modifier_name,
							           item.total,
						function(err,rs){
							next();
						});
					}else{
						next();
					}
					
				},function(err,rs){
					cb(err,perks,extra_points)
				});
			},
			function(perks,extra_points,cb){
				if(extra_points.length > 0){
					console.log('getExtraPoints',player_id,extra_points);
					//rangkum extra pointsnya
					var summary = {};
					for(var i in extra_points){
						for(var category in extra_points[i]){
							if(typeof summary[category] === 'undefined'){
								summary[category] = 0;
							}
							summary[category] += parseFloat(extra_points[i][category]);
						}
					}
					console.log('getExtraPoints',player_id,summary);
					var items = [];
					for(var i in summary){
						items.push({category:i,total:summary[i]});
					}

					async.eachSeries(items,function(item,next){
						var modifier_name = item.category + '_' + player_id;
						if(item.total != 0){
							saveExtraPoint(conn,
								           game_id,
								           matchday,
								           game_team_id,
								           modifier_name,
								           item.total,
							function(err,rs){
								next();
							});
						}else{
							next();
						}
					},function(err,rs){
						cb(err,{perks:perks,extra_points:summary,game_id:game_id,matchday:matchday});	
					});
				}else{
					//no additional points
					console.log('getExtraPoints',game_team_id,'no perks bonus');
					cb(null,{perks:perks,extra_points:{},game_id:game_id,matchday:matchday});	
				}
			}
		],
		function(err,rs){
			done(err,rs);
		});
	}else{
		console.log(game_team_id,'no stats, so we ignore it');
		done(null,null);	
	}
	
}


function getAllPerks(conn,game_team_id,callback){
	conn.query("SELECT * FROM "+config.database.database+".digital_perks a\
				INNER JOIN "+config.database.database+".master_perks b\
				ON a.master_perk_id = b.id\
				WHERE \
				a.game_team_id=? \
				AND a.available >= 0 AND a.n_status = 1 LIMIT 100",
				[game_team_id],
				function(err,perks){
					if(perks!=null && perks.length > 0){
						for(var i in perks){
							perks[i].data = PHPUnserialize.unserialize(perks[i].data);
						}
					}
					callback(err,perks);
				});
}

function process_player_stats_perks(conn,game_team_id,matchday,perks,new_stats,callback){
	var extra_points = [];
	var additional_points = {};
	async.waterfall([
		function(cb){
			POINTS_MODIFIER_PER_CATEGORY(
				conn,
				game_team_id,
				perks,
				new_stats,
				function(err,points,add_points){
					additional_points = add_points;
					console.log('additional_points',game_team_id,matchday,additional_points);
					for(var i=0; i < points.length; i++){
						extra_points.push(points[i]);
					}
					points = null;
					cb(err);
				});
		},
		function(cb){
			//@todo  add another perk here
			cb(null);
		}
	],
	function(err){
		callback(err,extra_points,additional_points);
	});
}
/**
* those who has jersey perk, will get additional points every match
* @perk jersey di nerve jadi +10
*/
function apply_jersey_perks(conn,game_id,matchday,game_team_id,callback){
	console.log('apply_jersey_perks','starting');
	async.waterfall([
		function(cb){
			//get all perks available
			getAllPerks(conn,game_team_id,function(err,perks){
				console.log('apply_jersey_perks',game_id,game_team_id,'perks',perks);
				cb(err,perks);
			});
		},
		function(perks,cb){
			var has_perk = false;
			if(perks!=null){
				has_perk = false;
				for(var i in perks){
					if(perks[i].perk_name=='ACCESSORIES' && perks[i].data.type=='jersey'){
						has_perk = true;
						break;
					}
				}
			}
			if(has_perk){
				console.log('apply_jersey_perks',game_team_id,'+100 points');
				saveExtraPoint(conn,
								game_id,
								matchday,
								game_team_id,
								'jersey_perk',
								10,
								function(err,rs){
									cb(err,rs);
								});
			}else{
				console.log('apply_jersey_perks',game_team_id,'no jersey perk');
				cb(null,null);
			}
		}
	],function(err,rs){
		callback(err,rs);
	});
}
exports.apply_jersey_perks = apply_jersey_perks;
/*
* process POINTS_MODIFIER_PER_CATEGORY perks
*/
function POINTS_MODIFIER_PER_CATEGORY(conn,game_team_id,perks,new_stats,callback){
	console.log('new_stats',JSON.stringify(new_stats));
	var extra_points = [];
	

	var extra_points_value = getExtraPointsByValue(perks);
	console.log('extra_points_value',game_team_id,JSON.stringify(extra_points_value));
	console.log('extra_points_value',game_team_id,JSON.stringify(perks));
	if(new_stats.length > 0){
		async.eachSeries(
			new_stats,
			function(stats,next){
				console.log('getExtraPoints',stats.category);
				for(var i in perks){
					if(perks[i].perk_name == 'POINTS_MODIFIER_PER_CATEGORY'
					   && perks[i].data.type == 'booster'){
						switch(perks[i].data.category){
							case 'passing_and_attacking':
								if(stats.category == 'passing_and_attacking'){
									extra_points.push({
										passing_and_attacking:getExtraPoints(perks[i].data,
																			 stats.points)});

								}
								
								
							break;
							case 'defending':
								if(stats.category == 'defending'){
									extra_points.push({
										defending:getExtraPoints(perks[i].data,
																			 stats.points)});
								}
								

							break;
							case 'goalkeeping':
								if(stats.category == 'goalkeeper'){
									extra_points.push({
										goalkeeping:getExtraPoints(perks[i].data,
																			 stats.points)});
								}
								

							break;
							case 'mistakes_and_errors':
								if(stats.category == 'mistakes_and_errors'){
									extra_points.push({
										mistakes_and_errors: getExtraPoints(perks[i].data,
																			 stats.points)});
									console.log('extra_points_value','mistakes_and_errors',game_team_id,
													'getExtraPoints',stats.points,'-->',getExtraPoints(perks[i].data,
																			 stats.points));

								}
								
							break;
							default:
								//do nothing
							break;
						}
					}
				}
				next();
			},function(err,rs){
				console.log('extra_points_value',game_team_id,JSON.stringify(extra_points));
				callback(err,extra_points,extra_points_value);	
			});
	}else{
		callback(null,[]);	
	}
	
}
function getExtraPointsByValue(perks){
	var extra_points_value = {
		passing_and_attacking:0,
		defending:0,
		goalkeeping:0,
		mistakes_and_errors:0

	};
	for(var i=0; i < perks.length; i++){
		switch(perks[i].data.category){
			case 'passing_and_attacking':
				if(perks[i].data.point_value !== 'undefined'){
					extra_points_value.passing_and_attacking += parseFloat(perks[i].data.point_value);
				}
				
			break;
			case 'defending':
				if(perks[i].data.point_value !== 'undefined'){
					extra_points_value.defending += parseFloat(perks[i].data.point_value);
				}
			break;
			case 'goalkeeping':
				if(perks[i].data.point_value !== 'undefined'){
					extra_points_value.goalkeeping += parseFloat(perks[i].data.point_value);
				}

			break;
			case 'mistakes_and_errors':
				if(perks[i].data.point_value !== 'undefined'){
					extra_points_value.mistakes_and_errors -= parseFloat(perks[i].data.point_value);
				}

			break;
			default:
				//do nothing
			break;
		}
	}
	return extra_points_value;
}

function getExtraPoints(perk_data,points){
	var extra1 = 0; //extra points from point_percentage
	perk_data.point_percentage = parseFloat(perk_data.point_percentage);
	perk_data.point_value = parseFloat(perk_data.point_value);
	console.log('extra_points_value','getExtraPoints','points:',points,'%',perk_data.point_percentage,'v',perk_data.point_value);
	if(typeof perk_data.point_percentage !== 'undefined' && perk_data.point_percentage != 0){
		extra1 = points * (perk_data.point_percentage / 100);
	}
	console.log('extra_points_value','getExtraPoints',extra1,'+',perk_data.point_value,'=',(extra1 + perk_data.point_value));
	//return parseFloat(extra1 + perk_data.point_value);
	return parseFloat(extra1);
}

function saveExtraPoint(conn,game_id,matchday,game_team_id,modifier_name,extra_points,callback){
	conn.query("INSERT INTO "+config.database.statsdb+".game_team_extra_points\
				(game_id,matchday,game_team_id,modifier_name,extra_points)\
				VALUES\
				(?,?,?,?,?)\
				ON DUPLICATE KEY UPDATE\
				extra_points = VALUES(extra_points)\
				",
				[game_id,matchday,game_team_id,modifier_name,extra_points],
				function(err,rs){
					callback(err,rs);
				});
}
exports.saveExtraPoint = saveExtraPoint;


