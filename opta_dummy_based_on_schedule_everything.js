/**
* Opta Dummy Server - based_on_schedule
* these application will simulate Opta Server.
* it will produce the following xml files on interval-basis
* 1. match-preview
* 2. match-results
*
* step 1 check unplayed game on game_fixtures.
* step 2 pick one. and then generate the report
* step 3 sleep for  ehm... 5 minutes and then go to step 1
*
* Usage : node opta_dumy_based_on_schedule --is_finished=1 (set 0 for unfinished match)
*/

var keeper_stats = [
'diving_save',
'game_started',
'long_pass_own_to_opp',
'accurate_pass',
'keeper_throws',
'total_final_third_passes',
'attempts_conceded_ibox',
'touches',
'passes_left',
'accurate_launches',
'poss_lost_all',
'total_fwd_zone_pass',
'keeper_pick_up',
'accurate_long_balls',
'accurate_fwd_zone_pass',
'accurate_goal_kicks',
'goals_conceded',
'saves',
'poss_lost_ctrl',
'attempts_conceded_obox',
'final_third_entries',
'ball_recovery',
'poss_won_def_3rd',
'accurate_back_zone_pass',
'passes_right',
'stand_save',
'total_back_zone_pass',
'pen_area_entries',
'total_long_balls',
'accurate_keeper_throws',
'goals_conceded_ibox',
'goals_conceded_obox',
'mins_played',
'error_lead_to_shot',
'cross_not_claimed',
'goal_kicks',
'long_pass_own_to_opp_success',
'saved_ibox',
'total_pass',
'total_launches',
'fwd_pass',
'formation_place'
];
var non_keeper_stats = [
'duel_lost',
'dispossessed',
'ontarget_att_assist',
'accurate_cross',
'game_started',
'long_pass_own_to_opp',
'accurate_chipped_pass',
'duel_won',
'accurate_pass',
'won_tackle',
'successful_final_third_passes',
'total_cross_nocorner',
'total_final_third_passes',
'rightside_pass',
'offside_provoked',
'attempts_conceded_ibox',
'touches',
'total_tackle',
'poss_lost_all',
'total_fwd_zone_pass',
'att_assist_openplay',
'accurate_long_balls',
'total_cross',
'accurate_fwd_zone_pass',
'total_chipped_pass',
'lost_corners',
'goals_conceded',
'poss_lost_ctrl',
'attempts_conceded_obox',
'final_third_entries',
'crosses_18yard',
'ball_recovery',
'accurate_cross_nocorner',
'poss_won_def_3rd',
'effective_clearance',
'accurate_back_zone_pass',
'passes_right',
'total_throws',
'won_corners',
'backward_pass',
'total_back_zone_pass',
'pen_area_entries',
'total_long_balls',
'accurate_throws',
'goals_conceded_obox',
'mins_played',
'error_lead_to_shot',
'total_contest',
'total_clearance',
'long_pass_own_to_opp_success',
'total_att_assist',
'total_pass',
'fwd_pass',
'outfielder_block',
'crosses_18yardplus',
'goals',
'goal_assist',
'formation_place'];
var fs = require('fs');
var path = require('path');
var mysql = require('mysql');
var dateformat = require('dateformat');
var async = require('async');
var config = require('./config').config;
var player_team = 't1'; //we use Manchester United as example
var squads = [];
var pool = mysql.createPool({
			host: config.database.host,
			user: config.database.username,
			password: config.database.password
		});

var argv = require('optimist').argv;
var is_finished = (typeof argv.is_finished !== 'undefined') ? argv.is_finished : 0;
var matchday = 0;

var has_queue = true;

async.waterfall([
		function(callback){
			pool.getConnection(function(err,conn){
				conn.query("SELECT a.id\
					FROM ffgame.game_fixtures a\
					WHERE a.is_dummy = 1 AND a.is_processed=0\
					AND a.period = 'PreMatch'\
					ORDER BY a.id ASC LIMIT 500;",
					[],
					function(err,fixtures){
						conn.end(function(err){
							console.log('total_matches : ',fixtures.length);
							callback(err,fixtures);
						});
					}
				);
			});
		},
		function(fixtures,callback){
			async.eachSeries(fixtures,
				function(game,callback){
					run(game,function(){
						callback();
					});
				},
				function(err){
					callback(err,null);
				});
		}
	],
	function(err,result){
		pool.end(function(err){
			console.log('ALL DONE NIH !');
		});
	});

function run(game,done){
	console.log('processing game #'+game.id);
	async.waterfall(
	[
		getUnProcessedSchedule,
		getTeams,
		setupMatch,
		flagDone
	],
		function(err,result){
			//console.log(result);
			if(err){
				console.log(err.message);
				has_queue = false;
			}
			done();
		}
	);
}

function flagDone(game_id,done){
	if(is_finished==1){
		pool.getConnection(function(err,conn){
			conn.query("UPDATE ffgame.game_fixtures SET period='FullTime' WHERE game_id=?",
						[game_id],
						function(err,fixtures){
				conn.end(function(err){
					done(err,game_id);
				});	
			});
		});
	}
}
function getUnProcessedSchedule(done){
	pool.getConnection(function(err,conn){
		conn.query("SELECT a.*,b.name AS home_name,b.stadium_capacity,\
					b.stadium_name,b.stadium_id,\
					c.name AS away_name\
					FROM ffgame.game_fixtures a\
					INNER JOIN ffgame.master_team b\
					ON a.home_id = b.uid\
					INNER JOIN ffgame.master_team c\
					ON a.away_id = c.uid\
					WHERE a.is_dummy = 1 AND a.is_processed=0\
					AND period = 'PreMatch'\
					ORDER BY a.id ASC LIMIT 1;",
					[],
					function(err,fixtures){
						conn.end(function(err){
							if(!err){
								if(fixtures[0].matchday==null){
									done(new Error('no more data'),null);
								}
								done(err,fixtures[0]);	
							}else{
								done(err,null);
							}
				
			});	
		});
		
	});
}
function getTeams(fixture,done){
	var home_team;
	var away_team;
	matchday = fixture.matchday;
	pool.getConnection(function(err,conn){
		async.waterfall(
			[
				function(callback){
					conn.query("SELECT * FROM ffgame.master_team\
								WHERE uid = ? LIMIT 1;",[fixture.home_id],
					function(err,team){
						callback(err,team);
					});
				},
				function(team,callback){
					home_team = team[0];
					conn.query("SELECT * FROM ffgame.master_player \
								WHERE team_id=? LIMIT 100;",[fixture.home_id],
					function(err,players){
						//console.log(this.sql);
						home_team.players = players;
						callback(err);
					});
				},
				function(callback){
					conn.query("SELECT * FROM ffgame.master_team\
								WHERE uid = ? LIMIT 1;",[fixture.away_id],
					function(err,team){
						callback(err,team);
					});
				},
				function(team,callback){
					away_team = team[0];
					conn.query("SELECT * FROM ffgame.master_player \
								WHERE team_id=? LIMIT 100;",[fixture.away_id],
					function(err,players){
						away_team.players = players;
						callback(err);
					});
				},
			],

			function(err,rs){
				conn.end(function(err){
					done(null,
								fixture.game_id,
								fixture.stadium_capacity,
								home_team,
								away_team);
				});
			}
		);
	});
}
function setupMatch(game_id,capacity,home_team,away_team,done){

	//console.log(home_team);
	var game_settings = {
		home:{
			team_id:home_team.uid,
			name:home_team.name,
			goals:getGoals(),
			bookings:getBookings(),
			substitutions:getSubstitutions(),
			lineup:getLineUp(home_team.players),
			officials:home_team.officials
		},
		away:{
			team_id:away_team.uid,
			name:away_team.name,
			goals:getGoals(),
			bookings:getBookings(),
			substitutions:getSubstitutions(),
			lineup:getLineUp(away_team.players),
			officials:away_team.officials
		}
	}
	
	var period = 'FullTime';
    if(is_finished!=1){
    	period = 'PreMatch';
    	game_settings.home.goals = 0;	
    	game_settings.away.goals = 0;
    }

    //console.log(game_settings);


	game_settings = generateStats(game_settings);
	
	var winner = '';
	if(game_settings.home.goals>game_settings.away.goals){
		winner = game_settings.home.team_id;
	}else{
		winner = game_settings.away.team_id;
	}

	var toTemplateData = {
		game_id: game_id,
		attendance: Math.ceil(capacity - (((Math.random()*30)/100)*capacity)),
		team_data: game_settings,
		winner : winner,
		period : period,
		matchday: matchday
	}
	var handlebars = require('handlebars'); // notice the "2" which matches the npm repo, sorry..
	handlebars.root = __dirname + '/views/templates';

	
	
	var strOut = "";
	var season_id = config.competition.year;
	var competition_id = config.competition.id;
	var file_output = "srml-"+competition_id+"-"+season_id+"-"+toTemplateData.game_id+"-matchresults.xml";
	var raw = fs.readFileSync(path.resolve(__dirname + '/views/templates/matchresults-freekick-goals.xml'), 'utf8');
	var template = handlebars.compile(raw.toString());
	strOut = template(toTemplateData);
	
	var filepath = path.resolve('./data/'+file_output);
	fs.writeFile(filepath, strOut, function(err) {
	    if(err) {
	        console.log(err);
	    }else {
	        console.log("The file was saved!");
	    }
	    try{
	    done(err,game_id);
		}catch(e){
			console.log(e.message);
		}
	}); 	
}

/**
simulate goals, using 24-dice
**/
function getGoals(){
	var n_roll = roll(24);
	//console.log('roll for goals -> '+n_roll);
	if(n_roll>10 && n_roll<16){
		return 1;
	}else if(n_roll>16 && n_roll<20){
		return 2;
	}else if(n_roll>20 && n_roll<23){
		return 3;
	}else if(n_roll>23){
		return Math.round(Math.random()*8);
	}else{
		return 0;
	}
}
/** simulate bookings
**/
function getBookings(){
	return roll(20);
}
/** simulate substitution
**/
function getSubstitutions(){
	return roll(3);
}
function roll(n){
	var t = Math.round(Math.random()*n);
	return t;
}
/**
* get 11 lineups both starter and substitution
* the rule is simple.
* we assume that each team using 4411 formation
* so they need 2 forward, 3 middle, and 4 defs and 1 goalie
* plus 5 substitution, 1 for goalie, 4 random except goalie
*/
function getLineUp(players){
	//console.log(players);
	var forward = [];
	var midfielder = [];
	var defender = [];
	var goalkeeper = [];
	var subs = [];
	for(var i in players){
		if(players[i].position=='Forward'){
			forward.push(players[i]);
		}
		if(players[i].position=='Midfielder'){
			midfielder.push(players[i]);
		}
		if(players[i].position=='Defender'){
			defender.push(players[i]);
		}
		if(players[i].position=='Goalkeeper'){
			goalkeeper.push(players[i]);
		}
	}
	forward = shuffle(forward);
	midfielder = shuffle(midfielder);
	defender = shuffle(defender);
	goalkeeper = shuffle(goalkeeper);

	//console.log(goalkeeper);
	//forward,midfielder,defender,goalkeeper
	var starters = [];

	//goalie
	starters.push({player:goalkeeper[0],stats:{}});
	
	//defs
	for(var i=0;i<4;i++){
		starters.push({player:defender[i],stats:{}});
	}
	subs.push({player:defender[4],stats:{}});
	//midfields
	for(var i=0;i<4;i++){
		starters.push({player:midfielder[i],stats:{}});
	}
	subs.push({player:midfielder[4],stats:{}});
	subs.push({player:midfielder[5],stats:{}});
	//forward
	for(var i=0;i<2;i++){
		starters.push({player:forward[i],stats:{}});
	}
	subs.push({player:forward[2],stats:{}});
	subs.push({player:forward[3],stats:{}});
	return {starters:starters,
			substitutions:subs};
}

function generateStats(settings){

	//home

	var players = shuffle(settings.home.lineup.starters);
	//bookings
	var home_bookings = [];
	var home_goals = [];
	for(var i=0; i<settings.home.bookings;i++){
		var n_player = Math.floor(Math.random()*players.length);
		for(var j=0;j<players.length;j++){
			if(settings.home.lineup.starters[j].player.uid==players[n_player].player.uid){
				home_bookings.push(addBook(settings.home.lineup.starters[j].player.uid));
				break;
			}
		}
	}
	settings.home.booking_data = home_bookings;
	//goals
	players = shuffle(players);
	for(var i=0;i < settings.home.goals;i++){
		var book_time = Math.floor(Math.random()*90);
		var period = (book_time<=45) ? 'FirstHalf' : 'SecondHalf';
		home_goals.push(
			{player_id: players[i].player.uid,
			 event_id: Math.ceil(Math.random()*100),
			 event_number: Math.ceil(Math.random()*10000),
			 time: book_time,
			 period: period
			}
		);
	}
	settings.home.goal_data = home_goals;

	//away
	var players = shuffle(settings.away.lineup.starters);
	var away_bookings = [];
	var away_goals = [];
	//bookings
	for(var i=0; i<settings.away.bookings;i++){
		var n_player = Math.floor(Math.random()*players.length);
		for(var j=0;j<players.length;j++){
			if(settings.away.lineup.starters[j].player.uid==players[n_player].player.uid){
				away_bookings.push(addBook(settings.away.lineup.starters[j].player.uid));
				break;
			}
		}
	}
	settings.away.booking_data = away_bookings;
	//goal
	players = shuffle(players);
	for(var i=0;i < settings.away.goals;i++){
		var book_time = Math.floor(Math.random()*90);
		var period = (book_time<=45) ? 'FirstHalf' : 'SecondHalf';
		away_goals.push(
			{player_id: players[i].player.uid,
			 event_id: Math.ceil(Math.random()*100),
			 event_number: Math.ceil(Math.random()*10000),
			 time: book_time,
			 period: period
			}
		);
	}
	settings.away.goal_data = away_goals;

	//player stats
	//home
	for(var i in settings.home.lineup.starters){
		var person = settings.home.lineup.starters[i];
	

		if(person.player.position=='Goalkeeper'){

			for(var s in keeper_stats){
				person.stats[keeper_stats[s]] = Math.floor(Math.random()*11);
			}
			person.stats['goals'] = 0;
			person.stats['yellow_card'] = 0;
			person.stats['red_card'] = 0;
			for(var t in home_goals){
				if(home_goals[t].player_id==person.player.uid){
					person.stats['goals']++;
				}
			}
			for(var t in home_bookings){
				if(home_bookings[t].player_id == person.player.uid){
					if(home_bookings[t].card_type=="Yellow"){
						person.stats['yellow_card']++;
					}else{
						person.stats['red_card']++;
					}
				}
			}
		}else{
			for(var s in non_keeper_stats){
				person.stats[non_keeper_stats[s]] = Math.floor(Math.random()*11);
			}
			person.stats['goals'] = 0;
			person.stats['yellow_card'] = 0;
			person.stats['red_card'] = 0;

			for(var t in home_goals){
				if(home_goals[t].player_id==person.player.uid){
					person.stats['goals']++;
				}
			}

			for(var t in home_bookings){
				if(home_bookings[t].player_id == person.player.uid){
					if(home_bookings[t].card_type=="Yellow"){
						person.stats['yellow_card']++;
					}else{
						person.stats['red_card']++;
					}
				}
			}
		}
		person.stats_data = [];
		for(var t in person.stats){
			person.stats_data.push({name:t,value:person.stats[t]});
		}
		settings.home.lineup.starters[i] = person;
	}

	//away
	for(var i in settings.away.lineup.starters){
		var person = settings.away.lineup.starters[i];
		if(person.player.position=='Goalkeeper'){
			for(var s in keeper_stats){
				person.stats[keeper_stats[s]] = Math.floor(Math.random()*11);
			}
			person.stats['goals'] = 0;
			person.stats['yellow_card'] = 0;
			person.stats['red_card'] = 0;


			for(var t in away_goals){
				if(away_goals[t].player_id==person.player.uid){
					person.stats['goals']++;
				}
			}

			for(var t in away_bookings){
				if(away_bookings[t].player_id == person.player.uid){
					if(away_bookings[t].card_type=="Yellow"){
						person.stats['yellow_card']++;
					}else{
						person.stats['red_card']++;
					}
				}
			}

		}else{
			for(var s in non_keeper_stats){
				person.stats[non_keeper_stats[s]] = Math.floor(Math.random()*11);
			}
			person.stats['goals'] = 0;
			person.stats['yellow_card'] = 0;
			person.stats['red_card'] = 0;
			
			for(var t in away_goals){
				if(away_goals[t].player_id==person.player.uid){
					person.stats['goals']++;
				}
			}

			for(var t in away_bookings){
				if(away_bookings[t].player_id == person.player.uid){
					if(away_bookings[t].card_type=="Yellow"){
						person.stats['yellow_card']++;
					}else{
						person.stats['red_card']++;
					}
				}
			}
		}
		person.stats_data = [];
		for(var t in person.stats){
			person.stats_data.push({name:t,value:person.stats[t]});
		}
		settings.away.lineup.starters[i] = person;

	}
	//substitute
	//home
	settings.home.lineup.substitutions = shuffle(settings.home.lineup.substitutions);
	settings.away.lineup.substitutions = shuffle(settings.away.lineup.substitutions);
	settings.home.substitute_data = [];
	for(var i=0; i< settings.home.substitutions; i++){
		//settings.home.substitutions[i].player
		var p = settings.home.lineup.substitutions[i];
		
		if(typeof p.player !== 'undefined'){
			settings.home.substitute_data.push({player_id:p.player.uid,
												 event_id: Math.ceil(Math.random()*100),
												 event_number: Math.ceil(Math.random()*10000),
												 time: Math.ceil(Math.random()*90),
												 suboff: settings.home.lineup.starters[Math.ceil(Math.random()*9)+1].player.uid,
												 reason:'Tactical'
												}
			);
		}
		for(var s in non_keeper_stats){
			p.stats[non_keeper_stats[s]] = Math.floor(Math.random()*11);
		}
		p.stats['goals'] = 0;
		p.stats_data = [];
		for(var t in p.stats){
			p.stats_data.push({name:t,value:p.stats[t]});
		}
		settings.home.lineup.substitutions[i] = p;
	}
	//console.log(settings.home.substitute_data);

	settings.away.substitute_data = [];
	for(var i=0; i< settings.away.substitutions; i++){
		
		var p = settings.away.lineup.substitutions[i];
		
		if(typeof p.player !== 'undefined'){
			settings.away.substitute_data.push({player_id:p.player.uid,
												 event_id: Math.ceil(Math.random()*100),
												 event_number: Math.ceil(Math.random()*10000),
												 time: Math.ceil(Math.random()*90),
												 suboff: settings.away.lineup.starters[Math.ceil(Math.random()*9)+1].player.uid,
												 reason:'Tactical'
												}
			);
		}
		for(var s in non_keeper_stats){
			p.stats[non_keeper_stats[s]] = Math.floor(Math.random()*11);
		}
		p.stats['goals'] = 0;
		p.stats_data = [];
		for(var t in p.stats){
			p.stats_data.push({name:t,value:p.stats[t]});
		}
		settings.away.lineup.substitutions[i] = p;
	}
	

	return settings;
}

function shuffle(o){ //v1.0
    for(var j, x, i = o.length; i; j = parseInt(Math.random() * i), x = o[--i], o[i] = o[j], o[j] = x);
    return o;
};

function addBook(player_id){
	var reason = ['Foul','Hand','Referee abuse','Crowd Interaction','Fight','Time Wasting','Argument','Excessive Celebration','Dive','Other'];
	var cards = ['Yellow','Red'];
	var n_roll = roll(24);
	var booking_type = "";
	var card = "";
	if(n_roll<18){
		booking_type = 'Foul';
	}else{
		booking_type = reason[Math.floor(Math.random()*(reason.length))];
	}
	var card_roll = roll(24);
	if(n_roll<20){
		card = cards[0];
	}else{
		card = cards[1];
	}
	var book_time = Math.floor(Math.random()*90);
	var period = (book_time<=45) ? 'FirstHalf' : 'SecondHalf';
	return {
		event_number:Math.ceil(Math.random()*10000),
		event_id:Math.ceil(Math.random()*10000),
		time:book_time,
		player_id:player_id,
		reason: booking_type,
		card_type: card,
		period:period
	};
}