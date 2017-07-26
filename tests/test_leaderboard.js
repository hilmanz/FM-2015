/**
* test script for /libs/api/gameplay.js, 
* for master's leaderboard related functions
*/
var assert = require('assert');
var should = require('should');
var path = require('path');
var gameplay = require(path.resolve('./libs/api/gameplay'));
var mysql = require('mysql');
var config = require(path.resolve('./config')).config;

var dummy = {
	name: 'foo',
	fb_id: '111111',
	email:'foo@bar.com',
	phone:'123123123',
	game_team_id:378,
	player_id:'p43250',
}
describe('gameplay',function(){
		it('get leaderboard statistics',function(done){
			gameplay.leaderboard(function(err,rs){
				should.not.exist(err);
				should.exist(rs);
				done();
			});
		});
});