/**
* test script for /libs/api/team.js
*/
var assert = require('assert');
var should = require('should');
var path = require('path');
var team = require(path.resolve('./libs/api/team'));
var mysql = require('mysql');
var config = require(path.resolve('./config')).config;

var team_id = 123;
var fb_id = '100001023465395';

var tactics= require(path.resolve('./libs/gamestats/tactics_bonus'));

var pool = mysql.createPool({
host: config.database.host,
user: config.database.username,
password: config.database.password
});


var player_id = 'p12297';
var game_team_id = 2;
var matchday = 1;
console.log('player id ',player_id);
var weekly=[];

weekly.push({
			game_id:'f123',
			category:'passing_and_attacking',
			game_team_id:2,
			player_id:'p12297',
			matchday:1,
			stats_name:'att_ibox_target',
			stats_value:1,
			points: 0,
			position_no: 1});

weekly.push({
			game_id:'f123',
			category:'passing_and_attacking',
			game_team_id:2,
			player_id:'p12297',
			matchday:1,
			stats_name:'att_obox_target',
			stats_value:1,
			points: 0,
			position_no: 1});

weekly.push({
			game_id:'f123',
			category:'passing_and_attacking',
			game_team_id:2,
			player_id:'p12297',
			matchday:1,
			stats_name:'aerial_won',
			stats_value:1,
			points: 0,
			position_no: 1});


weekly.push({
			game_id:'f123',
			category:'defending',
			game_team_id:2,
			player_id:'p12297',
			matchday:1,
			stats_name:'aerial_won',
			stats_value:1,
			points: 0,
			position_no: 1});



pool.getConnection(function(err,conn){
	tactics.apply_bonus(conn,game_team_id,player_id,weekly,matchday,function(err){
		console.log('done');
		conn.release();
		pool.end(function(err){});
	});	
});


/*
describe('we give the bonus for the player',function(){
	
		it('getTeams',function(done){
			team.getTeams(function(err,rs){
				
				should.equal(rs.length,20);
				done();
			});
		});		
});
*/