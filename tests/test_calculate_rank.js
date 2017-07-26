var assert = require('assert');
var should = require('should');
var async = require('async');
var path = require('path');
var gameplay = require(path.resolve('./libs/api/gameplay'));
var mysql = require('mysql');
var config = require(path.resolve('./config')).config;
var ranks = require(path.resolve('./libs/gamestats/ranks'));
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
			ranks.update(function(err,rs){
				callback(err,rs);
			});
		},
	],
function(err,rs){
	conn.end(function(e){
		console.log('finished');
	});
});