/**
populating no salary players.
run these script after you run import_salary.js
**/
/////THE MODULES/////////
var fs = require('fs');
var path = require('path');
var config = require('./config').config;
var xmlparser = require('xml2json');
var master = require('./libs/master');
var async = require('async');
var mysql = require('mysql');
/////DECLARATIONS/////////
var FILE_PREFIX = config.updater_file_prefix+config.competition.id+'-'+config.competition.year;



/////THE LOGICS///////////////
var conn = mysql.createConnection({
 	host     : config.database.host,
   user     : config.database.username,
   password : config.database.password,
});


async.waterfall([
	function(callback){
		conn.query("SELECT * FROM ffgame.tmp_transfer",[],function(err,rs){
			callback(err,rs);
		});
	},
	function(players,callback){
		async.eachSeries(players,function(player,next){
			conn.query("UPDATE ffgame.master_player SET transfer_value = ?\
						WHERE (last_name = ? OR name = ? OR known_name = ?) AND team_id=?",
						[player.transfer_value,
						 player.last_name,
						 player.last_name,
						 player.last_name,
						 player.team_id],
						function(err,rs){
							console.log(this.sql);
							next();
						});
		},function(err){
			callback(err,players);
		});
	},
],
function(err,result){
	conn.end(function(err){
		console.log('finished');
	});
});


