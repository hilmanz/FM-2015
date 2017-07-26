var assert = require('assert');
var should = require('should');
var async = require('async');
var path = require('path');
var gameplay = require(path.resolve('./libs/api/gameplay'));
var mysql = require('mysql');
var config = require(path.resolve('./config')).config;
var punishment = require(path.resolve('./libs/gamestats/punishment_rules'));
var conn = mysql.createConnection({
	host     : config.database.host,
   	user     : config.database.username,
   	password : config.database.password,
});

var game_team_id=11516;
var game_id = 'f694961'; //f694954,f694946
var team_id = 't97';

async.waterfall([
		function(callback){
			//test check violation
			punishment.check_violation(conn,game_id,game_team_id,team_id,function(err,rs){
				callback(err,rs);
			});
		},
		function(rs,callback){
			//test redeeme punishment
			punishment.execute_punishment(conn,game_id,game_team_id,team_id,function(err,rs){
				callback(err,rs);	
			});
			
		}
	],
function(err,rs){
	conn.end(function(e){
		console.log('finished');
	});
});