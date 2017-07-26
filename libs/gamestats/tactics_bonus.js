/*
* these module will helps to calculate the point bonus or penalty gained from perks
*/
var PHPUnserialize = require('php-unserialize');
var async = require('async');
var S = require('string');
var config = {};
exports.setConfig = function(c){
	config = c;
}
var affected_stats = {
	'1':[
			'att_ibox_target',
			'att_obox_target'
		],
	'2':[
			'accurate_cross_nocorner',
		],
	'3':[
			'accurate_through_ball'
		],
	'4':[
			'big_chance_created'
		],
	'5':[
			'won_tackle'
		],
	'6':[
			'won_contest'
		],
	'7':[
			'interception_won',
		]
}

/*
* apply perk for modified the player stats points.
* @params conn 
* @params game_team_id
* @params new_stats , the new generated stats before the points added with perk point bonuses
*/
exports.apply_bonus = function(conn,game_team_id,player_id,new_stats,matchday,done){

	if(new_stats.length > 0){
		console.log('TacticalBonus',game_team_id,player_id);
		var game_id = new_stats[0].game_id;
		var bonuses = [];
		async.waterfall([
			function(cb){
				conn.query("SELECT * FROM "+config.database.database+".game_team_instructions \
							WHERE game_team_id=? \
							AND matchday=? \
							AND player_id = ?\
							AND amount <> 0  LIMIT 1",
							[
								game_team_id,
								matchday,
								player_id
							],
							function(err,rs){
								console.log(S(this.sql).collapseWhitespace().s);
								cb(err,rs);
							});
			},
			function(tactic,cb){
				if(tactic != undefined && tactic.length>0){
					console.log(affected_stats[tactic[0].instruction_id]);
					console.log(new_stats);
					var the_stats = affected_stats[tactic[0].instruction_id];
					for(var i=0; i< new_stats.length;i++){
						for(var j in the_stats){
							if(new_stats[i].stats_name == the_stats[j]){
								console.log('matched',the_stats[j]);
								bonuses.push({stats:the_stats[j],
											  point:tactic[0].amount,
											  value:new_stats[i].stats_value});
							}	
						}
						
					}
				}
				console.log('TACTIC',bonuses);
				cb(null);
			},
			function(cb){
				async.eachSeries(bonuses,function(bonus,next){
					if(bonus.point > 5){
						bonus.point = 5;
					}else if(bonus.point < 0){
						bonus.point = 0;
					}
					console.log('TACTIC',player_id,'->',bonus.point,'x',bonus.value);
					saveExtraPoint(conn,game_id,matchday,game_team_id,
									'tactical_'+player_id+'_'+bonus.stats,
									(bonus.point * bonus.value),
									function(err,rs){

										next();
									});
				},function(err){
					cb(err,null);
				});
			}
		],
		function(err,rs){
			console.log(bonuses);
			done(err,rs);
		});
	}else{
		console.log(game_team_id,'no stats, so we ignore it');
		done(null,null);	
	}
	
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
					console.log(S(this.sql).collapseWhitespace().s);
					callback(err,rs);
				});
}
exports.saveExtraPoint = saveExtraPoint;