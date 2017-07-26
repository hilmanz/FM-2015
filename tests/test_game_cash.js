var assert = require('assert');
var should = require('should');
var async = require('async');
var path = require('path');
var gameplay = require(path.resolve('./libs/api/gameplay'));
var mysql = require('mysql');
var config = require(path.resolve('./config')).config;
var cash = require(path.resolve('./libs/gamestats/game_cash'));
var conn = mysql.createConnection({
	host     : config.database.host,
   	user     : config.database.username,
   	password : config.database.password,
});

var game_team_id=11516;
var game_id = 'f694961'; //f694954,f694946
var team_id = 't97';
var insertedId = 0;
var test_name = 'test add'+Math.random()*9999999;
describe('cash',function(){
		it('add cash',function(done){
			cash.adding_cash(conn,
							game_team_id,
							test_name,
							100,
							'unit test',
							function(err,rs){
								should.not.exist(err);
								should.exist(rs);
								insertedId = rs.insertId;
								should.notEqual(insertedId,0);
								done();
							}
			);
		});
		
		it('verify the inserted data',function(done){
			conn.query("SELECT * FROM ffgame.game_transactions WHERE id = ? LIMIT 1",
						[insertedId],function(err,rs){
							should.not.exist(err);
							should.exist(rs);
							should.equal(insertedId,rs[0].id);
							should.equal(game_team_id,rs[0].game_team_id);
							should.equal(100,rs[0].amount);
							should.equal(test_name,rs[0].transaction_name);
							should.equal("unit test",rs[0].details);
							done();
						});
		});

		it('can update the game_team_cash',function(done){
			
			async.waterfall([
				function(callback){
					conn.query("SELECT * FROM ffgame.game_team_cash WHERE game_team_id = ? LIMIT 1",
								[game_team_id],
								function(err,rs){
									callback(err,rs[0]);
								});
				},
				function(current_cash,callback){
					cash.update_cash_summary(conn,game_team_id,function(err,rs){
						callback(err,current_cash);
					});
				},
				function(current_cash,callback){
					conn.query("SELECT * FROM ffgame.game_team_cash WHERE game_team_id = ? LIMIT 1",
								[game_team_id],
								function(err,rs){
									callback(err,current_cash,rs[0]);
								});
				}

			],
			function(err,old_cash,new_cash){
				
				if(typeof old_cash === 'undefined'){
					old_cash = {cash:0};
				}
				if(typeof new_cash === 'undefined'){
					new_cash = {cash:0};
				}
				should.notEqual(old_cash.cash,new_cash.cash);
				done();
			});

		});

		it('we clear the data after that',function(done){
			conn.query("DELETE FROM ffgame.game_transactions WHERE id = ?",
						[insertedId],function(err,rs){
							done();
						});

		});

		it('we clear the data after that',function(done){
			conn.query("UPDATE ffgame.game_team_cash set cash = 0 WHERE game_team_id = ?",
						[game_team_id],function(err,rs){
							done();
						});

		});

		it('closes the connection successfully',function(done){
			conn.end(function(err){
				should.not.exists(err);
				console.log('connection closed');
				done();
			});
		});

});