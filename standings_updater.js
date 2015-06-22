/*
INSERT INTO ffgame.master_standings
(
team_id,
AGAINST,
away_against,
away_drawn,
away_for,
away_lost,
away_played,
away_points,
away_position,
away_won,
drawn,
FOR,
home_against,
home_drawn,
home_for,
home_lost,
home_played,
home_points,
home_position,
home_won,
lost,
played,
points,
POSITION,
startday_position,
won
)
VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)
ON DUPLICATE KEY UPDATE
AGAINST = VALUES(AGAINST),
away_against = VALUES(away_against),
away_drawn = VALUES(away_drawn),
away_for = VALUES(away_for),
away_lost = VALUES(away_lost),
away_played = VALUES(away_played),
away_points = VALUES(away_points),
away_position = VALUES(away_position),
away_won = VALUES(away_won),
drawn = VALUES(drawn),
FOR = VALUES(FOR),
home_against = VALUES(home_against),
home_drawn = VALUES(home_drawn),
home_for = VALUES(home_for),
home_lost = VALUES(home_lost),
home_played = VALUES(home_played),
home_points = VALUES(home_points),
home_position = VALUES(home_position),
home_won = VALUES(home_won),
lost = VALUES(lost),
played = VALUES(played),
points = VALUES(points),
POSITION = VALUES(POSITION),
startday_position = VALUES(startday_position),
won = VALUES(won);
*/

/**
the application which responsible for updating EPL official standings.
**/
var fs = require('fs');
var path = require('path');
var config = require('./config').config;
var xmlparser = require('xml2json');
var async = require('async');
var mysql = require('mysql');

var argv = require('optimist').argv;


if(typeof argv.league !== 'undefined'){
	switch(argv.league){
		case 'ita':
			console.log('Serie A Activated');
			config = require('./config.ita').config;
		break;
		case 'copa':
			console.log('COPA America Activated');
			config = require('./config.copa').config;
		break;
		default:
			console.log('EPL Activated');
			config = require('./config').config;
		break;
	}
}

var FILE_PREFIX = config.updater_file_prefix+config.competition.id+'-'+config.competition.year;

var conn = mysql.createConnection({
 	host     : config.database.host,
   user     : config.database.username,
   password : config.database.password,
});

//first check if the file is exists
var the_file = FILE_PREFIX+'-standings.xml';
open_the_file(the_file,function(err,doc){
		//console.log(xmlparser.toJson(doc.toString()));
		console.log('opening file',the_file);
		//console.log(doc.toString());
		update_standings(JSON.parse(xmlparser.toJson(doc.toString())),onDataProcessed);
});
function update_standings(data,callback){
	//console.log(data.SoccerFeed.SoccerDocument.Competition.TeamStandings[0].Round.Name.id);
	
	if(Array.isArray(data.SoccerFeed.SoccerDocument.Competition.TeamStandings)){
		//kalo team record ada lebih dari satu. berarti standingsnya dibagi berdasarkan group.
		//group A - H misalnya
		async.eachSeries(data.SoccerFeed.SoccerDocument.Competition.TeamStandings,
				function(record,next){
					var teams = record.TeamRecord;
					update_table_data(teams,record.Round.Name.id,function(err){
						next();
					});
				},function(err){
					conn.end(function(err){
						callback(null,[]);
						console.log('finished');
					});
				});
	}else{
		var teams = data.SoccerFeed.SoccerDocument.Competition.TeamStandings.TeamRecord;
		console.log(teams);
		update_table_data(teams,'',function(err){
			conn.end(function(err){
				callback(null,teams);
				console.log('finished');
			});
		});
	}
	
}
function update_table_data(teams,group_name,done){
	async.eachSeries(teams,function(team,next){
			update_data(team,group_name,function(err,rs){
				next();	
			});
		},function(err){
			done(err);
		});
}
function update_data(team,group_name,done){

	var d = team.Standing;
	var team_id = team.TeamRef;
	
	
	conn.query("INSERT INTO "+config.database.database+".master_standings\
				(\
				team_id,\
				t_against,\
				away_against,\
				away_drawn,\
				away_for,\
				away_lost,\
				away_played,\
				away_points,\
				away_position,\
				away_won,\
				drawn,\
				t_for,\
				home_against,\
				home_drawn,\
				home_for,\
				home_lost,\
				home_played,\
				home_points,\
				home_position,\
				home_won,\
				lost,\
				played,\
				points,\
				t_position,\
				startday_position,\
				won,\
				group_name\
				)\
				VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)\
				ON DUPLICATE KEY UPDATE\
				t_against = VALUES(t_against),\
				away_against = VALUES(away_against),\
				away_drawn = VALUES(away_drawn),\
				away_for = VALUES(away_for),\
				away_lost = VALUES(away_lost),\
				away_played = VALUES(away_played),\
				away_points = VALUES(away_points),\
				away_position = VALUES(away_position),\
				away_won = VALUES(away_won),\
				drawn = VALUES(drawn),\
				t_for = VALUES(t_for),\
				home_against = VALUES(home_against),\
				home_drawn = VALUES(home_drawn),\
				home_for = VALUES(home_for),\
				home_lost = VALUES(home_lost),\
				home_played = VALUES(home_played),\
				home_points = VALUES(home_points),\
				home_position = VALUES(home_position),\
				home_won = VALUES(home_won),\
				lost = VALUES(lost),\
				played = VALUES(played),\
				points = VALUES(points),\
				t_position = VALUES(t_position),\
				startday_position = VALUES(startday_position),\
				won = VALUES(won);",
			[
				team_id,
				d.Against,
				d.AwayAgainst,
				d.AwayDrawn,
				d.AwayFor,
				d.AwayLost,
				d.AwayPlayed,
				d.AwayPoints,
				d.AwayPosition,
				d.AwayWon,
				d.Drawn,
				d.For,
				d.HomeAgainst,
				d.HomeDrawn,
				d.HomeFor,
				d.HomeLost,
				d.HomePlayed,
				d.HomePoints,
				d.HomePosition,
				d.HomeWon,
				d.Lost,
				d.Played,
				d.Points,
				d.Position,
				d.StartDayPosition,
				d.Won,
				group_name
			],
			function(err,rs){
				if(!err){
					console.log('updating #',team_id,' OK');
				}else{
					console.log(err.message);
					console.log('updating #',team_id,' FAILED');
				}
				done(err,rs);
			});
}
function onDataProcessed(err,data){
	console.log('EPL Standings data updated !');
}

function open_the_file(the_file,done){
	var filepath = path.resolve('./data/'+the_file);
	fs.stat(filepath,onFileStat);
	function onFileStat(err,stats){
		if(!err){
			fs.readFile(filepath, function(err,data){
				if(!err){
					done(null,data);
				}else{
					handleError(err);
				}
			});
		}else{
			console.log(err.message);
			handleError(err);
		}
	}
}
function handleError(err){
	done(err,'<xml><error>1</error></xml>');
}