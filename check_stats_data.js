/**
* adhoc script to check the total points each team after ranks and points has been processed.

*/

/**
* the module to read match_results file.
*/
var fs = require('fs');
var path = require('path');
var async = require('async');
var xmlparser = require('xml2json');
var config = require(path.resolve('./config')).config;
var S = require('string');
var util = require('util');
var argv = require('optimist').argv;
var mysql = require('mysql');
var pool  = mysql.createPool({
   host     : config.database.host,
   user     : config.database.username,
   password : config.database.password,
});
var punishment = require(path.resolve('./libs/gamestats/punishment_rules'));
var player_stats_category = require(path.resolve('./libs/game_config')).player_stats_category;

var conn = mysql.createConnection({
 	host     : config.database.host,
   user     : config.database.username,
   password : config.database.password,
});


//var weeks = [8,9,10];
var week = (typeof argv.week !=='undefined') ? argv.bot_id : 8;

var weeks = [week];
var matched = 0;
var total_rows = 0;
var unmatched = 0;
var unmatched_list = [];
async.waterfall([
	function(callback){
		//get all the team_id
		conn.query("SELECT id FROM ffgame.game_teams LIMIT 100000",[],function(err,teams){
			callback(err,teams);
		});
	},
	function(teams,callback){
		async.eachSeries(weeks,function(week,next){
			//compare each weeks stats
			console.log('Checking week ',week);
			compare(conn,week,teams,function(err){
				next();
			});
		},function(err){
			callback(err,null);
		});
	}
],
function(err,rs){
	conn.end(function(err){
		//completed
		console.log('Matched : ',matched,' / ',total_rows);
		console.log('UNMATCHED : ',unmatched,' / ',total_rows);
		console.log('done');
	});
});

function compare(conn,matchday,teams,done){
	//compare each teams
	async.eachSeries(teams,function(team,next){
		async.waterfall([
			function(cb){
				//get the sum of its weekly
				conn.query("SELECT SUM(points) as total\
							FROM ffgame_stats.game_team_player_weekly \
							WHERE game_team_id=?",
							[team.id],
							function(err,rs){
								try{
									cb(err,rs[0].total);
								}catch(e){
									cb(err,0);
								}
							});
			},
			function(weekly_points,cb){
				//get the sum of its overall points
				conn.query("SELECT points\
							FROM ffgame_stats.game_team_points \
							WHERE game_team_id=? LIMIT 1",
							[team.id],
							function(err,rs){
								try{
									cb(err,weekly_points,rs[0].points);
								}catch(e){
									cb(err,0,0);
								}
							});
			},
			function(weekly_points,overall_points,cb){
				console.log('checkin team#',team.id,' : ',weekly_points,' - ',overall_points);
				//compare the value
				if(weekly_points==overall_points){
					matched++;
				}else{
					unmatched++;
					unmatched_list.push(team.id);
				}
				total_rows++;
				cb(null,true);
			}
		],
		function(err,rs){
			next();
		});
	},function(err){
		done(err);
	});
}