/*
* stats_report.js
* bot for generating the stats report. - for player stats
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
var player_profile = {};


function process_team_stats(stats,modifier,done){
	var teams = [];
	team_rawstats = {};
	team_totalstats = {};
	team_stats = {};//the final product of team statistics
	player_profile = {};
	modifier_stats = [];
	
	for(var i in stats){
		var is_found = false;
		for(var j in teams){
			if(stats[i].player_id == teams[j]){
				is_found = true;
				break;
			}
		}
		if(!is_found){
			teams.push(stats[i].player_id);
		}
	}
	for(var j in modifier){
		modifier_stats[modifier[j].name] = modifier[j].f;
	}

	async.eachSeries(teams,function(team,next){
		pull_teamdata(stats,team,function(err,rs){
			team_rawstats[team] = rs;
			team_totalstats[team] = getTotalStats(team);
			team_stats[team] = formulate_stats(team, modifier_stats);
			next();
		});
	},function(err){
		//console.log(team_rawstats);
		//console.log(team_totalstats);
		
		done(err,team_stats);
	});
}
exports.process_team_stats = process_team_stats;

function formatCsv(team_stats){
	
	var players = [];
	for(var i in player_profile){
		players.push({
			player_id:i,
			name:player_profile[i]
		});
	}

	async.eachSeries(players,
					function(item,next){
						console.log("'"+item.name+"';;");
						console.log(";;");
						for(var i in team_stats[item.player_id]){
							console.log("'"+i+"';'"+team_stats[item.player_id][i]+"';");
							
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
		'goals_openplay',
		'att_freekick_goal',
		'att_pen_goal',
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
		'att_openplay',
		'att_setpiece',
		'att_corner',
		'shot_fastbreak',
		'att_fastbreak',
		'post_scoring_att',
		'goal_assist',
		'goal_assist_openplay',
		'goal_assist_setplay',
		'goal_assist_deadball',
		'att_assist_openplay',
		'att_assist_setplay',
		'total_att_assist',
		'second_goal_assist',
		'big_chance_created',
		'accurate_through_ball',
		'total_through_ball',
		'accurate_cross_nocorner',
		'total_cross_nocorner',
		'accurate_pull_back',
		'total_pull_back',
		'won_contest',
		'total_contest',
		'last_man_contest',
		'total_attacking_pass',
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
		'won_tackle',
		'total_tackle',
		'last_man_tackle',
		'interception_won',
		'interception',
		'interceptions_in_box',
		'offside_provoked',
		'outfielder_block',
		'effective_blocked_cross',
		'blocked_cross',
		'effective_head_clearance',
		'effective_clearance',
		'clearance_off_line',
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
		'total_offside',
		'saves',
		'good_high_claim'
	];

	for(var i in ostats){
		if(typeof stats[ostats[i]] === 'undefined'){
			//console.log('check ',ostats[i]);
			stats[ostats[i]] = 0;
		}
	}
	return stats;
}
function fmPoints(stats, modifier){
	var ostats=[
		'goals',
		'goals_openplay',
		'att_freekick_goal',
		'att_pen_goal',
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
		'att_openplay',
		'att_setpiece',
		'att_corner',
		'shot_fastbreak',
		'att_fastbreak',
		'post_scoring_att',
		'goal_assist',
		'goal_assist_openplay',
		'goal_assist_setplay',
		'goal_assist_deadball',
		'att_assist_openplay',
		'att_assist_setplay',
		'total_att_assist',
		'second_goal_assist',
		'big_chance_created',
		'accurate_through_ball',
		'total_through_ball',
		'accurate_cross_nocorner',
		'total_cross_nocorner',
		'accurate_pull_back',
		'total_pull_back',
		'won_contest',
		'total_contest',
		'last_man_contest',
		'total_attacking_pass',
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
		'won_tackle',
		'total_tackle',
		'last_man_tackle',
		'interception_won',
		'interception',
		'interceptions_in_box',
		'offside_provoked',
		'outfielder_block',
		'effective_blocked_cross',
		'blocked_cross',
		'effective_head_clearance',
		'effective_clearance',
		'clearance_off_line',
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
		'total_offside',
		'saves',
		'good_high_claim'
	];

	var fm_point_result = {};

	for(var i in ostats){
		if(typeof modifier[ostats[i]] === 'undefined'){
			//console.log('check ',ostats[i]);
			fm_point_result[ostats[i]] = 0;
		}else{
			fm_point_result[ostats[i]] = stats[ostats[i]]*modifier[ostats[i]];
		}
	}
	return fm_point_result;
}

function formulate_stats(team_id, modifier){
	var o = team_totalstats[team_id];
	o = checkValues(o);
	var fm_points = fmPoints(o, modifier);
	
	var stats = {};
	for(var i in o){
		o[i] = parseInt(o[i]);
	}

	stats['Goals']= o.goals+'('+fm_points['goals']+')';
	stats['open play / freekicks / penalties / setpiece'] = o.goals_openplay +'/'+ o.att_freekick_goal +'/'+ o.att_pen_goal +'/'+ (o.goals - (o.goals_openplay + o.att_freekick_goal + o.att_pen_goal));
	stats['Headed Goals'] = o.att_hd_goal+'('+fm_points['att_hd_goal']+')';
	
	stats['Shooting']= o.ontarget_scoring_att +'/'+ o.total_scoring_att+'('+fm_points["ontarget_scoring_att"] +'/'+ fm_points["total_scoring_att"]+')' ;
	stats['from inside the box'] = (o.att_ibox_target + o.att_ibox_goal) +'/'+ (o.att_ibox_blocked + o.att_ibox_target + o.att_ibox_goal 
							+ o.att_ibox_miss + o.att_ibox_post);
	stats['from outside the box'] = (o.att_obox_target + o.att_obox_goal) +'/'+ (o.att_obox_blocked + o.att_obox_target + o.att_obox_goal 
								+ o.att_obox_miss + o.att_obox_post);
	stats['Headers (on target / total)'] = (o.att_hd_total - o.att_hd_miss) +'/'+ o.att_hd_total;
	stats['Open Play / Set piece'] = o.att_openplay +'/'+ o.att_setpiece+'('+fm_points["att_openplay"]+'/'+fm_points["att_setpiece"]+')';
	stats['From corners'] = o.att_corner+'('+fm_points["att_corner"]+')';
	stats['From counter attacks'] = o.shot_fastbreak + o.att_fastbreak;
	stats['Shots that hit the post'] = o.post_scoring_att+'('+fm_points["post_scoring_att"]+')' ;


	//stats['Assists']= o.goal_assist+'('+fm_points["goal_assist"]+')' ;
	stats['Assists'] = parseInt(o.goal_assist_openplay) + parseInt(o.goal_assist_setplay) + '('+parseInt(parseInt(fm_points["goal_assist_openplay"])+parseInt(fm_points["goal_assist_setplay"]))+')'
	stats['Assists from open play']=o.goal_assist_openplay+'('+fm_points["goal_assist_openplay"]+')' ;
	stats['Assist from set play']=o.goal_assist_setplay+'('+fm_points["goal_assist_setplay"]+')' ;
	stats['Assists from set piece']=o.goal_assist_deadball+'('+fm_points["goal_assist_deadball"]+')' ;

	stats['In open play']=o.att_assist_openplay+'('+fm_points["att_assist_openplay"]+')' ;
	stats['in set plays']=o.att_assist_setplay+'('+fm_points["att_assist_setplay"]+')' ;
	//stats['Total chance created']=o.total_att_assist+'('+fm_points["total_att_assist"]+')' ;
	stats['Total chance created']=parseInt(o.att_assist_openplay) + parseInt(o.att_assist_setplay) +'('+parseInt((parseInt(fm_points["att_assist_openplay"]))+(parseInt(fm_points["att_assist_setplay"])))+')' ;
	stats['Involved in chance buildup']=o.second_goal_assist+'('+fm_points["second_goal_assist"]+')' ;
	stats['Created a Clear Cut Chance']=o.big_chance_created+'('+fm_points["big_chance_created"]+')' ;

	stats['Through Balls']=o.accurate_through_ball  +'/'+ o.total_through_ball ;
	stats['Crossing']=o.accurate_cross_nocorner +'/'+ o.total_cross_nocorner;
	stats['Pull backs']=o.accurate_pull_back +'/'+ o.total_pull_back;
	stats['Dribbling']=o.won_contest +'/'+ o.total_contest ;
	stats['Beating last defender']=o.last_man_contest+'('+fm_points["last_man_contest"]+')' ;


	stats['Attacking passes']=o.total_attacking_pass+'('+fm_points["total_attacking_pass"]+')' ;
	stats['Long passes from own half']=o.long_pass_own_to_opp_success +'/'+ o.long_pass_own_to_opp ;
	stats['Long passes (35 yards)']=o.accurate_long_balls +'/'+ o.total_long_balls ;
	stats['Passes in the air']=o.accurate_chipped_pass +'/'+ o.total_chipped_pass ;
	stats['Flick ons']=o.accurate_flick_on +'/'+ o.total_flick_on ;
	stats['Layoffs']=o.accurate_layoffs +'/'+ o.total_layoffs ;

	stats['Corners']= o.accurate_corners_intobox +'/'+ o.total_corners_intobox ;
	stats['Free kicks']= o.accurate_freekick_cross +'/'+ o.freekick_cross ;
	stats['Direct free kicks']= (o.att_freekick_goal + o.att_freekick_target) +'/'+ o.att_freekick_total ;
	stats['Penalties - Goal']= o.att_pen_goal+'('+fm_points["att_pen_goal"]+')' ;
	stats['Penalties - missed']= o.att_pen_miss+'('+fm_points["att_pen_miss"]+')' ;
	stats['Penalties - Saved']= o.att_pen_target+'('+fm_points["att_pen_target"]+')' ;


	stats['Penalties Won']=o.penalty_won+'('+fm_points["penalty_won"]+')' ;
	stats['Corners Won']=o.won_corners+'('+fm_points["won_corners"]+')' ;
	stats['Freekicks earned']=o.fk_foul_won+'('+fm_points["fk_foul_won"]+')' ;
	stats['Fouled']=o.was_fouled+'('+fm_points["was_fouled"]+')' ;
	stats['Fouled in the final third']=o.fouled_final_third+'('+fm_points["fouled_final_third"]+')' ;


	stats['Challenge won']= o.duel_won+'('+fm_points["duel_won"]+')';
	stats['Aerial challenge Won']= o.aerial_won+'('+fm_points["aerial_won"]+')';
	stats['Loose ball recovered']= o.ball_recovery+'('+fm_points["ball_recovery"]+')';

	stats['Tackling']= o.won_tackle +'/'+ o.total_tackle+'('+fm_points["total_tackle"]+')';
	stats['Won tackle as last man']= o.last_man_tackle+'('+fm_points["last_man_tackle"]+')' ;
	stats['Intercepted a pass']= o.interception_won +'/'+ o.interception+'('+fm_points["interception_won"]+')';
	stats['Intercepted a pass inside the box']= o.interceptions_in_box+'('+fm_points["interceptions_in_box"]+')' ;
	stats['Successful Offside Trap']= o.offside_provoked+'('+fm_points["offside_provoked"]+')';

	stats['Shots blocked']= o.outfielder_block+'('+fm_points["outfielder_block"]+')' ;
	stats['Crosses blocked']= o.effective_blocked_cross +'/'+ o.blocked_cross;
	stats['Headed clearance']= o.effective_head_clearance+'('+fm_points["effective_head_clearance"]+')' ;
	stats['Cleared an attack']= o.effective_clearance+'('+fm_points["effective_clearance"]+')' ;
	stats['Cleared off the line']= o.clearance_off_line+'('+fm_points["clearance_off_line"]+')' ;

	stats['Penalty Conceded']= o.penalty_conceded+'('+fm_points["penalty_conceded"]+')' ;
	stats['Corners conceded']= o.lost_corners+'('+fm_points["lost_corners"]+')';
	stats['Free kicks conceded']= o.fk_foul_lost+'('+fm_points["fk_foul_lost"]+')';
	stats['Posession lost']= o.poss_lost_all+'('+fm_points["poss_lost_all"]+')';
	stats['Beaten by opponent']= o.challenge_lost+'('+fm_points["challenge_lost"]+')';
	stats['Error Led to a shot']= o.error_lead_to_shot+'('+fm_points["error_lead_to_shot"]+')' ;
	stats['Error led to a goal']= o.error_lead_to_goal+'('+fm_points["error_lead_to_goal"]+')' ;
	stats['Dangerous Play']= o.dangerous_play+'('+fm_points["dangerous_play"]+')' ;
	stats['Yellow Cards']= o.total_yel_card+'('+fm_points["total_yel_card"]+')' ;
	stats['Red Cards']= o.total_red_card+'('+fm_points["total_red_card"]+')' ;
	stats['Caught Offside']= o.total_offside+'('+fm_points["total_offside"]+')' ;
	stats['Saves']= o.saves+'('+fm_points["saves"]+')' ;
	stats['Good High Claim']= o.good_high_claim+'('+fm_points["good_high_claim"]+')' ;
	
	return stats;
}

function st(o,formulae){
	return eval(formulae);
}

function pull_teamdata(rs,player_id,done){
	
		var stats = [];
		
		for(var i in rs){
			if(player_id == rs[i].player_id){
				player_profile[rs[i].player_id] = rs[i].name;
				console.log(rs[i]);
				stats.push({
					game_id:rs[i].game_id,
					stats_name:rs[i].stats_name,
					total:parseInt(rs[i].total)
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