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
					perks,
					new_stats,
					function(err,extra_points){
						cb(err,perks,extra_points);
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
						saveExtraPoint(conn,
							           game_id,
							           matchday,
							           game_team_id,
							           modifier_name,
							           item.total,
						function(err,rs){
							next();
						});
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
	conn.query("SELECT * FROM ffgame.digital_perks a\
				INNER JOIN ffgame.master_perks b\
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

function process_player_stats_perks(conn,game_team_id,perks,new_stats,callback){
	var extra_points = [];
	async.waterfall([
		function(cb){
			POINTS_MODIFIER_PER_CATEGORY(
				conn,
				game_team_id,
				perks,
				new_stats,
				function(err,points){
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
		callback(err,extra_points);
	});
}
/**
* those who has jersey perk, will get additional points every match
*/
function apply_jersey_perks(conn,game_id,matchday,game_team_id,callback){
	console.log('apply_jersey_perks','starting');
	async.waterfall([
		function(cb){
			//get all perks available
			getAllPerks(conn,game_team_id,function(err,perks){
				console.log('apply_jersey_perks',game_team_id,'perks',perks);
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
								100,
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
	console.log('new_stats',new_stats);
	var extra_points = [];
	if(new_stats.length > 0){


		async.eachSeries(
			new_stats,
			function(stats,next){
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
								if(stats.category == 'goalkeeping'){
									extra_points.push({
										goalkeeping:getExtraPoints(perks[i].data,
																			 stats.points)});
								}
							break;
							case 'mistakes_and_errors':
								if(stats.category == 'mistakes_and_errors'){
									extra_points.push({
										mistakes_and_errors:getExtraPoints(perks[i].data,
																			 stats.points)});
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
				callback(err,extra_points);	
			});
	}else{
		callback(null,[]);	
	}
	
}

function getExtraPoints(perk_data,points){
	var extra1 = 0; //extra points from point_percentage
	perk_data.point_percentage = parseFloat(perk_data.point_percentage);
	perk_data.point_value = parseFloat(perk_data.point_value);
	console.log('getExtraPoints',points,perk_data.point_percentage,perk_data.point_value);
	if(perk_data.point_percentage > 0){
		extra1 = points * (perk_data.point_percentage / 100);
	}
	console.log('getExtraPoints',(extra1 + perk_data.point_value));
	return parseFloat(extra1 + perk_data.point_value);
}

function saveExtraPoint(conn,game_id,matchday,game_team_id,modifier_name,extra_points,callback){
	conn.query("INSERT INTO ffgame_stats.game_team_extra_points\
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

