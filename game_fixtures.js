/**
populating the "+config.database.database+".game_fixtures
**/
/////THE MODULES/////////
var fs = require('fs');
var path = require('path');
var config = require('./config').config;
//var config = require('./config_ucl').config;
var xmlparser = require('xml2json');
var master = require('./libs/master');
var async = require('async');
var mysql = require('mysql');
/////DECLARATIONS/////////

var argv = require('optimist').argv;


if(typeof argv.league !== 'undefined'){
	switch(argv.league){
		case 'ita':
			console.log('Serie A Activated');
			config = require('./config.ita').config;
		break;
		case 'copa':
			console.log('Copa Activated');
			config = require('./config.copa').config;
		break;
		default:
			console.log('EPL Activated');
			config = require('./config').config;
		break;
	}
}
var FILE_PREFIX = config.updater_file_prefix+config.competition.id+'-'+config.competition.year;



/////THE LOGICS///////////////
var conn = mysql.createConnection({
 	host     : config.database.host,
   user     : config.database.username,
   password : config.database.password,
});

async.waterfall([
	function(callback){
		open_match_file(FILE_PREFIX+'-results.xml',function(err,doc){
			callback(err,JSON.parse(xmlparser.toJson(doc.toString())));
		});
	},
	function(xmlContent,callback){
		process_match_data(conn,
			xmlContent.SoccerFeed.SoccerDocument.competition_id,
			xmlContent.SoccerFeed.SoccerDocument.season_id,
			xmlContent.SoccerFeed.SoccerDocument.MatchData,
			function(err,rs){
				callback(err,rs);	
		});
	}
],
function(err,result){
	conn.end(function(err){
		console.log('finished');
	});
});

/*
INSERT INTO "+config.database.database+".game_fixtures
(game_id,home_id,away_id,period,matchday,competition_id,session_id,home_score,away_score,attendance,is_dummy,is_processed,match_date)
VALUES
(?,?,?,?,?,?,?,?,?,?,?,?,?)
*/
function process_match_data(conn,competition_id,session_id,data,done){
	async.eachSeries(data,
		function(item,callback){
			var game_id = item.uID.replace('g','f');
			var team = getTeam(item.TeamData);
			
			var matchday = item.MatchInfo.MatchDay;
			var period = item.MatchInfo.Period;
			var match_date = item.MatchInfo.Date;
			console.log(item.MatchInfo);
			console.log(match_date);
			console.log('updating game #'+game_id);
			//var home_id = data.TeamData.
			//console.log('game_id',game_id);
			//console.log(item.TeamData);
			//console.log(team);
			//callback();
			
			conn.query("INSERT INTO "+config.database.database+".game_fixtures\
						(game_id,home_id,away_id,period,matchday,competition_id,\
						session_id,home_score,away_score,attendance,is_dummy,is_processed,\
						match_date)\
						VALUES\
						(?,?,?,?,?,?,?,?,?,?,?,?,?)\
						ON DUPLICATE KEY UPDATE\
						home_id = VALUES(home_id),\
						away_id = VALUES(away_id),\
						home_score = VALUES(home_score),\
						away_score = VALUES(away_score),\
						session_id = VALUES(session_id),\
						match_date = VALUES(match_date),\
						attendance = VALUES(attendance);",
						[game_id,team.home_id,team.away_id,period,matchday,competition_id,
						session_id,team.home_score,team.away_score,0,0,0,match_date],
						function(err,rs){
							callback();
						});
			
		},
		function(err){
			done(err,data);
		});
}
function getTeam(team){
	var home_id = '';
	var away_id = '';
	var home_score = 0;
	var away_score = 0;
	for(var i in team){
		if(team[i].Side == 'Home'){
			home_id = team[i].TeamRef;
			home_score = team[i].Score;
		}else{
			away_id = team[i].TeamRef;
			away_score = team[i].Score;
		}
	}
	return {home_id:home_id,
			home_score:home_score,
			away_id:away_id,
			away_score:away_score};
}
function open_match_file(the_file,done){
	var filepath = path.resolve('./data/'+the_file);
	fs.stat(filepath,onFileStat);
	function onFileStat(err,stats){
		if(!err){
			fs.readFile(filepath, function(err,data){
				if(!err){
					done(null,data);
				}else{
					done(new Error('file cannot be read !'),[]);
				}
			});
		}else{
			console.log(err.message);
			done(new Error('file is not exists !'),[]);
		}
	}
}
function handleError(err){
	done(err,'<xml><error>1</error></xml>');
}
/*
conn.query("SELECT * FROM "+config.database.database+".game_fixtures \
		WHERE is_processed=0 \
		ORDER BY id ASC LIMIT 100;",[],
		function(err,games){
			conn.end(function(err){
				generateReports(games);
		});
});

function generateReports(games){
	async.eachSeries(games,function(item,callback){
		//console.log(item.game_id);
		process_report(item.game_id,function(err,result){
			callback();	
		});
	},function(err){
		//console.log('Done generating report.');
		match_results.done();
		lineup_stats.done();
		business_stats.done();
	});
}
*/

