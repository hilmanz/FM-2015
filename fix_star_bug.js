/**
 * Module dependencies.
 */

var express = require('express')
  , routes = require('./routes')
  , user = require('./routes/user')
  , http = require('http')
  , path = require('path');
var mysql = require('mysql');
var redis = require('redis');
var dummy_api_key = '1234567890';
var auth = require('./libs/api/auth');
var config = require('./config').config;
var async = require('async');
var request = require('request');
var argv = require('optimist').argv;
var report_team = require(path.resolve('./libs/stats_report_team'));
var report_player = require(path.resolve('./libs/stats_report_player'));
var S = require('string');
//mysql pool
var pool  = mysql.createPool({
   host     : config.database.host,
   user     : config.database.username,
   password : config.database.password,
});


pool.getConnection(function(err,conn){
	async.waterfall([
			function(cb){
				conn.query("SELECT id as game_team_id FROM ffgame.game_teams",[],function(err,teams){
					
					cb(err,teams);
				});
			},
			function(teams,cb){
				async.eachSeries(teams,function(team,next){
					checkTeamPurchase(conn,team,function(err,rs){
						next();
					});
				},function(err){
					cb(err,{});
				});
			}
		],
		function(err,rs){
			conn.release();
			pool.end(function(err){
				console.log('done');
			});
		}
	);

});


function checkTeamPurchase(conn,team,done){
	
	async.waterfall([
		//check for its total transactions
		function(cb){
			conn.query("SELECT SUM(amount) AS total \
						FROM ffgame.game_team_expenditures \
						WHERE item_name IN ('player_sold','buy_player') \
						AND game_team_id=?;",[team.game_team_id],
						function(err,rs){
							try{
								if(rs[0].total==null){
									rs[0].total = 0;
								}
								cb(err,parseInt(rs[0].total));
							}catch(e){
								cb(err,0);
							}
			});
		},
		function(total_transaction,cb){
			//check total real buy
			conn.query("SELECT SUM(transfer_value) AS total \
						FROM ffgame.game_transfer_history \
						WHERE game_team_id=? AND transfer_type=1;",
						[team.game_team_id],
						function(err,rs){
							try{
								if(rs[0].total==null){
									rs[0].total = 0;
								}
								cb(err,total_transaction,(parseInt(rs[0].total)*-1));
							}catch(e){
								cb(err,total_transaction,0);
							}
			});
		},
		function(total_transaction,total_buy,cb){
			//check total real sale
			conn.query("SELECT SUM(transfer_value) AS total \
						FROM ffgame.game_transfer_history \
						WHERE game_team_id=? AND transfer_type=2;",
						[team.game_team_id],
						function(err,rs){
							try{
								
								if(rs[0].total==null){
									rs[0].total = 0;
								}
								cb(err,total_transaction,total_buy,(parseInt(rs[0].total)));
							}catch(e){
								cb(err,total_transaction,total_buy,0);
							}
			});	
		},
		function(total_trx,buy,sale,cb){
			
			var real_total = sale + buy;
			if(real_total != total_trx){
				
				var d = 0;
				if(real_total > total_trx){
					d = real_total - total_trx;
				}else if(real_total < total_trx){
					//kalau real total lebih kecil dari total transaction di expenditures
					//maka kita hitung selisihnya.  itu berarti kurang bayar kita.

					// real_total = -10
					// total-trx = 20
					// d = 20 - (-10) = 30
					// d = -30 (kalo lebih besar dari 0, maka kita set jadi negatif)
					//real total = 10
					//total-trx = 20
					// d = 20 - 10 = 10
					// d = -10
					d = total_trx - real_total;

					if(d>0){
						d *= -1;
					}
				}
				var item_type = 2;
				var item_name = 'player_sold';
				if(d<0){
					item_name = 'buy_player'
					item_type = 1;
				}	
				conn.query("INSERT INTO ffgame.game_team_expenditures\
							(game_team_id,item_name,item_type,amount,game_id,\
							match_day,item_total,base_price)\
							VALUES\
							(?,'buy_player',?,?,'',0,1,1)\
							ON DUPLICATE KEY UPDATE\
							amount = amount + VALUES(amount)",
							[
								team.game_team_id,
								item_type,
								d
							],
							function(err,rs){
								console.log(S(this.sql).collapseWhitespace().s);
								console.log(team.game_team_id,buy,sale, real_total,total_trx,'fix : ',d);
								cb(null);
							});
				
				
			}else{
				//console.log('check ',team.game_team_id,real_total,total_trx);
				cb(null);
			}
			
		}
	],

	function(err,rs){
		done(err,rs);	
	});
	
}