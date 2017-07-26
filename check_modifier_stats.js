/**
script for updating multipliers
**/
/////THE MODULES/////////
var fs = require('fs');
var path = require('path');
var config = require('./config').config;
var xmlparser = require('xml2json');
var master = require('./libs/master');
var async = require('async');
var mysql = require('mysql');
var S = require('string');
/////DECLARATIONS/////////
var FILE_PREFIX = config.updater_file_prefix+config.competition.id+'-'+config.competition.year;



/////THE LOGICS///////////////
var conn = mysql.createConnection({
 	host     : config.database.host,
   user     : config.database.username,
   password : config.database.password,
});

var game_id='f694945';

function getRandomInt(min, max) {
    return Math.floor(Math.random() * (max - min + 1)) + min;
}
async.waterfall([
	function(callback){
		conn.query("SELECT name AS stats_name, g AS Goalkeeper,d AS Defender,m AS Midfielder,f AS Forward \
					FROM ffgame.game_matchstats_modifier;",[],
					function(err,rs){
						callback(err,rs);
					}
		);
	},
	function(stats,callback){
		var modifiers = {};
		for(var i in stats){
			modifiers[stats[i].stats_name] = {
				Goalkeeper: stats[i].Goalkeeper,
				Defender: stats[i].Defender,
				Midfielder: stats[i].Midfielder,
				Forward: stats[i].Forward,
			};
		}
		callback(null,modifiers);
	},
	function(modifiers,callback){
		conn.query("SELECT a.game_id,a.team_id,b.name AS team,c.name,\
					c.position,a.stats_name,a.stats_value \
					FROM ffgame_stats.master_player_stats a\
					INNER JOIN ffgame.master_team b\
					ON a.team_id = b.uid\
					INNER JOIN ffgame.master_player c\
					ON a.player_id = c.uid\
					WHERE a.game_id = ? LIMIT 10000;",[game_id],function(err,rs){
						callback(err,modifiers,rs);
					});
	},

	function(modifiers,players,callback){
		
		for(var i in players){
			players[i].modifier_point = modifiers[players[i].stats_name][players[i].position];
			players[i].total_points = parseInt(players[i].modifier_point) * parseInt(players[i].stats_value);
		}
		
		callback(null,players);
	}

],
function(err,result){
	conn.end(function(err){
		//console.log(S(result[0]).toCSV().s)
		console.log(S(result[0]).toCSV({keys:true}).s);
		for(var i in result){
			console.log(S(result[i]).toCSV().s);
		}
	});
});