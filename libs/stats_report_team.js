/*
* stats_report.js
* bot for generating the stats report.
*/
var crypto = require('crypto');
var fs = require('fs');
var path = require('path');
var xmlparser = require('xml2json');
var async = require('async');
var config = require(path.resolve('./config')).config;
var mysql = require('mysql');
var dateFormat = require('dateformat');
var redis = require('redis');
var player_stats_category = require(path.resolve('./libs/game_config')).player_stats_category;
var S = require('string');
var argv = require('optimist').argv;
var request = require('request');
var url = require('url');

var pool  = mysql.createPool({
   host     : config.database.host,
   user     : config.database.username,
   password : config.database.password,
});

var team_rawstats = {};
var team_totalstats = {};
var team_stats = {};//the final product of team statistics
var team_profile = {};


function process_team_stats(stats,done){
 	team_rawstats = {};
	team_totalstats = {};
	team_stats = {};//the final product of team statistics
	team_profile = {};
	teams = [];
	for(var i in stats){
		var is_found = false;
		for(var j in teams){
			if(stats[i].team_id == teams[j]){
				is_found = true;
				break;
			}
		}
		if(!is_found){
			teams.push(stats[i].team_id);
		}
		if(teams.length==2){
			break;
		}
	}

	async.eachSeries(teams,function(team,next){

		pull_teamdata(stats,team,function(err,rs){
			team_rawstats[team] = rs;

			team_totalstats[team] = getTotalStats(team);
			team_stats[team] = formulate_stats(team);
			next();
		});
	},function(err){
		//console.log(team_rawstats);
		//console.log(team_totalstats);
		//formatCsv(team_stats);
		done(err,team_stats);
	});
}
exports.process_team_stats = process_team_stats;

function formatCsv(team_stats){
	
	var teams = [];
	for(var i in team_profile){
		teams.push({
			team_id:i,
			team_name:team_profile[i]
		});
	}
	async.eachSeries(teams,
					function(item,next){
						console.log("'"+item.team_name+"';;");
						console.log(";;");
						for(var i in team_stats[item.team_id]){
							console.log("'"+i+"';'"+team_stats[item.team_id][i]+"';");
							
						}
						console.log(";;");
						console.log(";;");
						console.log(";;");
						next();
					},function(err){

					});
	//console.log(team_stats);
}
function getTotalStats(team_id){
	var st = team_rawstats[team_id];
	var stats = {};
	for(var i in st){
		if(typeof stats[st[i].stats_name] === 'undefined'){
			stats[st[i].stats_name] = 0;
		}
		stats[st[i].stats_name] += parseInt(st[i].total);
	}
	return stats;
}


function checkValues(stats){
	var ostats=[
	'goals',
	'att_ibox_goal',
	'att_obox_goal',
	'goals_openplay',
	'att_freekick_goal',
	'att_pen_goal',
	'goal_fastbreak',
	'att_hd_goal',
	'ontarget_scoring_att',
	'total_scoring_att',
	'att_ibox_target',
	'att_ibox_goal',
	'att_ibox_blocked',
	'att_ibox_target',
	'att_ibox_goal',
	'att_ibox_miss',
	'att_ibox_post',
	'att_obox_target',
	'att_obox_goal',
	'att_obox_blocked',
	'att_obox_target',
	'att_obox_goal',
	'att_obox_miss',
	'att_obox_post',
	'att_hd_total',
	'att_hd_miss',
	'att_hd_total',
	'post_scoring_att',
	'accurate_through_ball',
	'total_through_ball',
	'accurate_cross_nocorner',
	'total_cross_nocorner',
	'accurate_pull_back',
	'total_pull_back',
	'total_fastbreak',
	'big_chance_created',
	'big_chance_missed',
	'att_openplay',
	'att_setpiece',
	'att_corner',
	'shot_fastbreak',
	'att_fastbreak',
	'att_one_on_one',
	'possession_percentage',
	'total_attacking_pass',
	'final_third_entries',
	'pen_area_entries',
	'successful_final_third_passes',
	'total_final_third_passes',
	'won_contest',
	'total_contest',
	'long_pass_own_to_opp_success',
	'long_pass_own_to_opp',
	'accurate_long_balls',
	'total_long_balls',
	'accurate_chipped_pass',
	'total_chipped_pass',
	'accurate_flick_on',
	'total_flick_on',
	'accurate_layoffs',
	'total_layoffs',
	'accurate_corners_intobox',
	'total_corners_intobox',
	'accurate_freekick_cross',
	'freekick_cross',
	'att_freekick_goal',
	'att_freekick_target',
	'att_freekick_total',
	'att_pen_goal',
	'att_pen_miss',
	'att_pen_target',
	'penalty_won',
	'won_corners',
	'fk_foul_won',
	'was_fouled',
	'fouled_final_third',
	'duel_won',
	'aerial_won',
	'ball_recovery',
	'poss_won_def_3rd',
	'poss_won_mid_3rd',
	'poss_won_att_3rd',
	'won_tackle',
	'total_tackle',
	'interception_won',
	'interception',
	'offside_provoked',
	'gk_smother',
	'accurate_keeper_sweeper',
	'total_keeper_sweeper',
	'good_one_on_one',
	'total_one_on_one',
	'penalty_save',
	'penalty_faced',
	'outfielder_block',
	'saves',
	'effective_blocked_cross',
	'blocked_cross',
	'cross_not_claimed',
	'good_high_claim',
	'total_high_claim',
	'effective_head_clearance',
	'effective_clearance',
	'goals_conceded_ibox',
	'goals_conceded_obox',
	'pen_goals_conceded',
	'attempts_conceded_ibox',
	'attempts_conceded_obox',
	'penalty_conceded',
	'lost_corners',
	'fk_foul_lost',
	'poss_lost_all',
	'challenge_lost',
	'error_lead_to_shot',
	'error_lead_to_goal',
	'dangerous_play',
	'total_yel_card',
	'total_red_card',
	'total_offside'];

	for(var i in ostats){
		if(typeof stats[ostats[i]] === 'undefined'){
			//console.log('check ',ostats[i]);
			stats[ostats[i]] = 0;
		}
	}
	return stats;
}
function formulate_stats(team_id){
	
	var o = team_totalstats[team_id];
	o = checkValues(o);
	
	var stats = {};
	for(var i in o){
		o[i] = parseInt(o[i]);
	}

	//GOALS

	stats['goals inside / outside the box'] = o.att_ibox_goal +'/'+ o.att_obox_goal;
	stats['goals from open play / freekicks / penalties'] = o.goals_openplay +'/'+ o.att_freekick_goal +'/'+ o.att_pen_goal;
	stats['goals From counter attacks'] = o.goal_fastbreak 
	stats['Headed Goals'] = o.att_hd_goal 

	//SHOOTING
	stats['On target / Total Shots'] = o.ontarget_scoring_att +'/'+o.total_scoring_att;
	stats['Inside the box (on target / total)'] = (o.att_ibox_target + o.att_ibox_goal) +'/'+ (o.att_ibox_blocked + o.att_ibox_target + o.att_ibox_goal + o.att_ibox_miss + o.att_ibox_post);
	stats['Outside the box (on target / total)'] = (o.att_obox_target + o.att_obox_goal) +'/'+ (o.att_obox_blocked + o.att_obox_target + o.att_obox_goal + o.att_obox_miss + o.att_obox_post);
	stats['Headers (on target/total)'] = (o.att_hd_total - o.att_hd_miss) +'/'+ o.att_hd_total;
	stats['Hit the post'] = o.post_scoring_att;

	//attacking styles
	stats['Through Balls'] = o.accurate_through_ball  +'/'+ o.total_through_ball;
	stats['Crossing'] = o.accurate_cross_nocorner +'/'+ o.total_cross_nocorner;
	stats['Pull backs'] = o.accurate_pull_back +'/'+ o.total_pull_back;
	stats['Counter attacks'] = o.total_fastbreak;

	//['CHANCES'] = 
	stats['Clear Cut Chances created'] = o.big_chance_created ;
	stats['Clear cut chances missed'] = o.big_chance_missed ;
	stats['Open Play / Set piece'] = o.att_openplay +'/'+ o.att_setpiece;
	stats['From corners'] = o.att_corner;
	stats['From counter attacks'] = o.shot_fastbreak + o.att_fastbreak;
	stats['One v one situation'] = o.att_one_on_one;

	//['BALL MOVEMENT'] = 
	stats['Possession'] = o.possession_percentage ;
	stats['Attacking passes'] = o.total_attacking_pass ;
	stats['Final 3rd Entry'] = o.final_third_entries 
	stats['Penalty area entry'] = o.pen_area_entries 
	stats['Passes in the final 3rd'] = o.successful_final_third_passes +'/'+ o.total_final_third_passes;
	stats['Dribble Attempts'] = o.won_contest +'/'+ o.total_contest;

	//['BUILD UP
	stats['Long passes from own half'] = o.long_pass_own_to_opp_success +'/'+ o.long_pass_own_to_opp;
	stats['Long passes (35 yards + )'] = o.accurate_long_balls +'/'+ o.total_long_balls;
	stats['Passes in the air'] = o.accurate_chipped_pass +'/'+ o.total_chipped_pass 
	stats['Flick ons'] = o.accurate_flick_on +'/'+ o.total_flick_on 
	stats['Layoffs'] = o.accurate_layoffs +'/'+ o.total_layoffs 

	//['SET PIECES EFFICIENCY'] = 
	stats['Corners Accuracy'] = o.accurate_corners_intobox +'/'+ o.total_corners_intobox;
	stats['Free kicks crossing accuracy'] = o.accurate_freekick_cross +'/'+ o.freekick_cross;
	stats['Direct free kicks'] = (o.att_freekick_goal + o.att_freekick_target) +'/'+ o.att_freekick_total;

	stats['Penalties - Goal'] = o.att_pen_goal;
	stats['Penalties - missed'] = o.att_pen_miss; 
	stats['Penalties - Saved'] = o.att_pen_target; 

	//['SET PIECES EARNED'] = 
	stats['Penalties Won'] = o.penalty_won; 
	stats['Corners Won'] = o.won_corners; 
	stats['Freekicks earned'] = o.fk_foul_won; 
	stats['Fouled'] = o.was_fouled; 
	stats['Fouled in the final third'] = o.fouled_final_third; 

	//['COMPETITIVENESS'] = 
	stats['Challenge won'] = o.duel_won;
	stats['Aerial challenge Won'] = o.aerial_won;
	stats['Loose ball recovered'] = o.ball_recovery;

	//['DEFENDING'] = 
	//['Ball Winning'] = 
	stats['Regained Possession'] = o.poss_won_def_3rd +'/'+ o.poss_won_mid_3rd +'/'+ o.poss_won_att_3rd; 
	stats['Tackling'] = o.won_tackle +'/'+ o.total_tackle;
	stats['Intercepted a pass'] = o.interception_won +'/'+ o.interception;
	stats['Successful Offside Trap'] = o.offside_provoked;
	stats['Goalkeeper came off the line'] = o.gk_smother;
	stats['Goalkeeper came out of the box'] = o.accurate_keeper_sweeper +'/'+ o.total_keeper_sweeper;
	stats['Goalkeeper one v one'] = o.good_one_on_one +'/'+ o.total_one_on_one;
	stats['Goalkeeper Penalties Saves'] = o.penalty_save +'/'+ o.penalty_faced;

	//['Dealing with Shots'] = 
	stats['Shots blocked'] = o.outfielder_block; 
	stats['Goalkeeper Saves'] = o.saves;

	//['Dealing with Crosses'] = 
	stats['Crosses blocked'] = o.effective_blocked_cross +'/'+ o.blocked_cross;
	stats['Crosses dealt by goalkeeper'] = o.cross_not_claimed +'/'+ o.good_high_claim +'/'+ o.total_high_claim;
	stats['Headed clearance'] = o.effective_head_clearance; 
	stats['Cleared an attack'] = o.effective_clearance;

	//['WEAKNESSES'] = 
	stats['Goals Conceded'] = o.goals_conceded_ibox +'/'+ o.goals_conceded_obox +'/'+ o.pen_goals_conceded; 
	stats['Attempts Conceded'] = o.attempts_conceded_ibox  +'/'+ o.attempts_conceded_obox; 
	stats['Penalty Conceded'] = o.penalty_conceded;
	stats['Corners conceded'] = o.lost_corners;
	stats['Free kicks conceded'] = o.fk_foul_lost;
	stats['Posession lost'] = o.poss_lost_all;
	stats['Beaten by opponent'] = o.challenge_lost;
	stats['Error Led to a shot'] = o.error_lead_to_shot; 
	stats['Error led to a goal'] = o.error_lead_to_goal; 
	stats['Dangerous Play'] = o.dangerous_play; 
	stats['Yellow Cards'] = o.total_yel_card; 
	stats['Red Cards'] = o.total_red_card; 
	stats['Caught Offside'] = o.total_offside; 
	
	stats['goals']	= o.goals;
	return stats;
}

function st(o,formulae){
	return eval(formulae);
}

function pull_teamdata(rs,team_id,done){
	
	var stats = [];
	for(var i in rs){
		if(team_id == rs[i].team_id){
			team_profile[rs[i].team_id] = rs[i].name;
			stats.push({
				game_id:rs[i].game_id,
				stats_name:rs[i].stats_name,
				total:rs[i].stats_value
			});
		}
	}
	done(null,stats);
	
}

/*
* get stats value from total stats
*/
function getStats(team_id,stats_name){

}