/**
* test script for /libs/api/transfer.js
*/
var assert = require('assert');
var should = require('should');
var path = require('path');
var users = require(path.resolve('./libs/api/users'));
var mysql = require('mysql');
var config = require(path.resolve('./config')).config;
var transfer = require(path.resolve('./libs/services/transfer'));
var buy_player = require(path.resolve('./libs/api/buy_player'));
var salary_nego = require(path.resolve('./libs/api/salary_nego'));
var notifications = require(path.resolve('./libs/api/notifications'));
var redis = require('redis');
var redisClient = redis.createClient(config.redis.port,config.redis.host);
redisClient.on("error", function (err) {
    console.log("Error " + err);
});
var dummy = {
	name: 'foo',
	fb_id: '111222111',
	email:'foo@bar.com',
	phone:'123123123',
	game_team_id:22691,
	player_id:'p59936'
}
var pool = mysql.createPool({
	host: config.database.host,
	user: config.database.username,
	password: config.database.password
});
buy_player.setConfig(config);
buy_player.setPool(pool);
salary_nego.setConfig(config);
salary_nego.setPool(pool);

var nego_id = 0;

transfer.init(config,pool);
describe('Transfer',function(){
		it('negotiate transfer',function(done){
			buy_player.negotiate_transfer(dummy.game_team_id,
											dummy.player_id,
											38000000,
											'epl',
											function(err,rs){
				should.exist(rs);
				done();
			});
		});
		it('check notification for the link',function(done){
			pool.getConnection(function(err,conn){
				notifications.getLastMessage(conn,dummy.game_team_id,'epl',function(err,rs){
					should.exist(rs);
					console.log(rs[0]);
					should.equal(1,rs.status);
					should.exist(rs.data[0].meta.nego_id);
					nego_id = rs.data[0].meta.nego_id;
					conn.release();
					done();
				});
			});
		});
		it('display salary negotiation detail',function(done){
			salary_nego.negotiate_salary_window(dummy.game_team_id,nego_id,
												40000,'epl',
			function(err,rs){
				should.exist(rs);
				done();
			});
		});
		it('offer a salary',function(done){
			salary_nego.negotiate_salary(redisClient,dummy.game_team_id,nego_id,
												100000,0,15000,'epl',
			function(err,rs){
				should.exist(rs);
				done();
			});
		});
		it('the player should be in club rooster now',
		function(done){
			buy_player.is_player_in_club(
				dummy.game_team_id,
				dummy.player_id,
				'epl',
			function(err,in_club){
				should.equal(true,in_club);
				done();
			});
		});

});