/*
* the module for dealing with punishment rules.
* there's 2 kind of punishment.
* income cuts, or balance deduction.
* so every rule must returns an object that consists income_cuts and balance_deduction.

*
* Update for worldcup 
* - home income punishment is disabled.
* home balance cuts and away balance cuts penalty now is -5 star
*
*/
var path = require('path');
var config = {};
var league = '';

exports.setLeague = function(l){
	league = l;
}
exports.setConfig = function(c){
	config = c;
}
//income dikurangi untuk 2 home game berikutnya atau sampai transfer window berikutnya
var home_income_cuts = function(){
	return {income_cuts:0.75,balance_cuts:0,terms:{type:'2home_or_next_transfer',amount:1}};
}
exports.home_income_cuts = home_income_cuts;


//balance dikurangi untuk 2 home game berikutnya atau sampai transfer window berikutnya
var home_balance_cuts = function(){
	return {income_cuts:0,balance_cuts:500000,terms:{type:'2home_or_next_transfer',amount:1}};
}
exports.home_balance_cuts = home_balance_cuts;

//balance dikurangi selama 4 minggu berturut2
var away_balance_cuts = function(){
	return {income_cuts:0,balance_cuts:500000,terms:{type:'weekly',amount:1}};
}
exports.away_balance_cuts = away_balance_cuts;

exports.execute_punishment = function(conn,game_id,game_team_id,team_id,callback){

	var async = require('async');
	console.log('execute punishment',game_id,game_team_id,team_id);
	async.waterfall([
		function(cb){
			//check if the team has home punishment in effect.
			conn.query("SELECT id,game_type,punishment \
			FROM "+config.database.database+".game_punishments \
			WHERE game_team_id=? AND \
			game_type = 'home' AND \
			n_status=0 GROUP BY punishment;",
			[game_team_id],
			function(err,rs){
				try{
					console.log('execute punishment',sqlOut(this.sql));
					if(rs!=null && rs.length > 0){
						cb(err,rs);
					}else{
						//no punishment
						cb(err,[]);
					}
				}catch(e){
					//no punishment
					cb(err,[]);
				}
			});
		},
		function(punish,cb){
			
			if(punish.length >0){
				async.eachSeries(punish,function(item,next){
						doPunish(conn,game_id,game_team_id,team_id,item,function(err){
							next();
						});
					},
					function(err){
						cb(err);
					}
				);
			}else{
				cb(null);
			}
		},
		function(cb){
			//check if the team has home punishment in effect.
			conn.query("SELECT id,game_type,punishment \
			FROM "+config.database.database+".game_punishments \
			WHERE game_team_id=? AND \
			game_type = 'away' AND \
			n_status=0 GROUP BY punishment;",
			[game_team_id],
			function(err,rs){
				try{
					console.log(sqlOut(this.sql));
					if(rs!=null && rs.length > 0){
						cb(err,rs);
					}else{
						//no punishment
						cb(err,[]);
					}
				}catch(e){
					//no punishment
					cb(err,[]);
				}
			});
		},
		function(punish,cb){
			
			if(punish.length >0){
				async.eachSeries(punish,function(item,next){
						doPunish(conn,game_id,game_team_id,team_id,item,function(err){
							next();
						});
					},
					function(err){
						cb(err,null);
					}
				);
			}else{
				cb(null,null);
			}
		}
	],

	function(err,rs){
		callback(err,rs);
	});
}
exports.check_violation = function(conn,game_id,game_team_id,original_team_id,callback){
	var async = require('async');
	var S = require('string');
	async.waterfall([
		function(cb){
			//check the current lineup setup in history
			conn.query("SELECT b.team_id,COUNT(b.team_id) AS total \
						FROM "+config.database.database+".game_team_players a\
						INNER JOIN "+config.database.database+".master_player b\
						ON a.player_id = b.uid \
						WHERE game_team_id=? GROUP BY team_id;",
						[game_team_id],
						function(err,rs){
							console.log(S(this.sql).collapseWhitespace().s);
							console.log(rs);
							var total_players = 0;
							var total_ori = 0;
							if(rs!=null && rs.length > 0){
								console.log(original_team_id);
								for(var i in rs){
									total_players += rs[i].total;
									if(rs[i].team_id == original_team_id){
										total_ori+=rs[i].total;
									}
								}
							}
							cb(err,{original:total_ori,
									total:total_players});
						});
		},
		function(check,cb){
			/*if((check.original/check.total) < 0.5){

			}*/
			//check jenis game.. home apa away ?
			conn.query(
				"SELECT home_id,away_id FROM "+config.database.database+".game_fixtures WHERE game_id = ? LIMIT 1;",
				[game_id],
				function(err,rs){
					var type = '';
					try{
						if(rs[0].home_id == original_team_id){
							type='home';
						}else if(rs[0].away_id == original_team_id){
							type='away';
						}else{
							type='';
						}
					}catch(e){
						type = '';
					}
					cb(err,check,type);
			});
			
		},
		function(check,game_type,cb){
			//rule dicabut dulu
			//untuk babak knockout Worldcup
			
			if((check.original/check.total) < 0.5){
				
				add_rules(conn,game_id,game_team_id,game_type,function(err){
					console.log('done');
					cb(null,null);
				});
				
			}else{
				cb(null,null);
			}
		}
	],
	function(err,rs){
		console.log('done');
		callback(err,rs);
	});
	
}
//Update
//12/02/2014
//the penalty only given once per season
function add_rules(conn,game_id,game_team_id,game_type,done){
	var async = require('async');

	if(game_type=='home'){
		console.log('#',game_team_id,'home nih');
		async.waterfall([
			function(cb){
				var can_punish = true;
				//we can only give new punishment if all punishment is done.
				conn.query("SELECT COUNT(*) AS total FROM "+config.database.database+".game_punishments \
							WHERE game_team_id = ? AND punishment='home_income_cuts';",
							[game_team_id],function(err,rs){
								try{
									if(rs[0].total > 0){

										can_punish = false;
									}
								}catch(e){
									can_punish = false;
								}
								cb(err,can_punish);
							});
			},
			function(can_punish,cb){
				console.log('#',game_team_id,'home_income_cuts');
				if(can_punish){
					console.log('can punish');
					var setting = home_income_cuts();
					async.times(setting.terms.amount,function(n,next){
						conn.query("INSERT INTO "+config.database.database+".game_punishments\
								(game_id,game_team_id,game_type,punishment,n_status,submit_dt)\
								VALUES\
								(?,?,?,?,0,NOW());",
								[game_id,game_team_id,game_type,'home_income_cuts'],
								function(err,rs){
									console.log(sqlOut(this.sql));
									next(err,rs);
								});
						},
					function(err,results){
						cb(err);
					});	
				}else{
					console.log('cannot punish');
					cb(null);
				}
				
			},
			function(cb){
				var can_punish = true;
				//we can only give new punishment if all punishment is done.
				conn.query("SELECT COUNT(*) AS total FROM "+config.database.database+".game_punishments \
							WHERE game_team_id = ? AND punishment='home_balance_cuts';",
							[game_team_id],function(err,rs){
								try{
									if(rs[0].total > 0){
										can_punish = false;
									}
								}catch(e){
									can_punish = false;
								}
								cb(err,can_punish);
							});
			},
			function(can_punish,cb){
				console.log('#',game_team_id,'home_balance_cuts');
				if(can_punish){
					console.log('can punish');
					var setting = home_balance_cuts();
					async.times(setting.terms.amount,function(n,next){
						conn.query("INSERT INTO "+config.database.database+".game_punishments\
								(game_id,game_team_id,game_type,punishment,n_status,submit_dt)\
								VALUES\
								(?,?,?,?,0,NOW());",
								[game_id,game_team_id,game_type,'home_balance_cuts'],
								function(err,rs){
									console.log(sqlOut(this.sql));
									next(err,rs);
								});
						},
					function(err,results){
						cb(err);
					});	
				}else{
					console.log('cannot punish');
					cb(null,null);
				}
				
			},
		],

		function(e,r){
			console.log('done');
			done(e);
		});

	}else if(game_type=='away'){
		console.log('#',game_team_id,'away nih');
		async.waterfall([
			function(cb){
				var can_punish = true;
				//--we can only give new punishment if all punishment is done.-- Obseleted

				conn.query("SELECT COUNT(*) AS total FROM "+config.database.database+".game_punishments \
							WHERE game_team_id = ? AND punishment='away_balance_cuts';",
							[game_team_id],function(err,rs){
								try{
									if(rs[0].total > 0){

										can_punish = false;
									}
								}catch(e){
									can_punish = false;
								}
								cb(err,can_punish);
							});
			},
			function(can_punish,cb){
				
				console.log('#',game_team_id,'away_balance_cuts');
				if(can_punish){
					console.log('can punish');
					async.waterfall([
						function(c){
							var setting = away_balance_cuts();
							async.times(setting.terms.amount,function(n,next){
								conn.query("INSERT INTO "+config.database.database+".game_punishments\
										(game_id,game_team_id,game_type,punishment,n_status,submit_dt)\
										VALUES\
										(?,?,?,?,0,NOW());",
										[game_id,game_team_id,game_type,'away_balance_cuts'],
										function(err,rs){
											console.log(sqlOut(this.sql));
											next(err,rs);
										});
								},
							function(err,results){
								c(err);
							});	
						},
						function(c){
							sendNotification(conn,game_id,game_team_id,3,function(err){
								c(err);
							});
						}
					],
					function(err,r){
						cb(err);
					});
					
					
				}else{
					console.log('cannot punish');
					cb(null);
				}
				
			},
		],

		function(e,r){
			console.log('done');
			done(e);
		})

	}else{
		console.log('#',game_team_id,'no game type specified');
		done(null);
	}
}

function doPunish(conn,game_id,game_team_id,team_id,item,cb){
	console.log('doPunish',game_team_id,item,team_id);
	var async = require('async');
	var t = {};

	switch(item.punishment){
		case 'home_balance_cuts':
			t = home_balance_cuts();
		break;
		case 'home_income_cuts':
			t = home_income_cuts();
		break;
		case 'away_balance_cuts':
			t = away_balance_cuts();
		break;
		default:
			t = null;
		break;
	}
	if(t!=null){
		var ticket_sold = 0;
		var matchday = 0;
		var cut_ok = false;
		var is_home = false;
		async.waterfall([
			function(callback){
				conn.query("SELECT home_id,away_id,matchday \
							FROM "+config.database.database+".game_fixtures \
							WHERE game_id=? \
							LIMIT 1",[game_id],
							function(err,r){
								try{
									matchday = r[0].matchday;
									if(r[0].home_id==team_id){
										is_home=true;
									}
								}catch(e){
									
								}
								callback(err);
							});
			},
			function(callback){
				//get tickets sold
				conn.query("SELECT amount FROM "+config.database.database+".game_team_expenditures \
							WHERE game_id = ? AND game_team_id = ? \
							AND item_name = 'tickets_sold';",
							[game_id,game_team_id],
							function(err,rs){
								try{
									ticket_sold = rs[0].amount;
								}catch(e){}
								callback(err);
							});
			},
			function(callback){
				if(!is_home){
					t.income_cuts = 0;
				}
				if(t.income_cuts >0){
					console.log(game_team_id,'we cut the ticket sold income by ',t.income_cuts);
					var amount = (-1) * (t.income_cuts * ticket_sold);
					addCost(conn,game_id,game_team_id,'ticket_sold_penalty',amount,matchday,
					function(err,rs){
						try{
							if(rs.affectedRows>0){
								cut_ok = true;
								console.log('#',game_team_id,'CUT OK');
							}else{
								console.log('#',game_team_id,'we already cut the income');
							}
						}catch(err){}
						callback(err);
					});

				}else{
					callback(null);
				}
			},
			function(callback){
				if(!is_home && item.punishment=='home_balance_cuts'){
					t.balance_cuts = 0;
				}
				if(t.balance_cuts > 0){
					console.log(game_team_id,item.punishment,'we cut the balance by ',t.balance_cuts);
					var amount = (-1) * t.balance_cuts;
					if(item.punishment=='home_balance_cuts'){
						var item_name = 'security_overtime_fee';
					}else{
						var item_name = 'compensation_fee';
					}
					addCost(conn,game_id,game_team_id,item_name,amount,matchday,
					function(err,rs){
						try{
							if(rs.affectedRows>0){
								console.log('#',game_team_id,'CUT OK');
								cut_ok = true;
							}else{
								console.log('#',game_team_id,'we already cut the balance');
							}
						}catch(err){}
						callback(err);
					});
				}else{
					callback(null);
				}
			},
			function(callback){
				if(cut_ok){
					conn.query("UPDATE "+config.database.database+".game_punishments SET n_status=1 WHERE id = ?",
								[item.id],function(err,rs){
									callback(err);
								});
				}else{
					callback(null);
				}
			},
			function(callback){
				if(cut_ok){
					if(item.punishment=='home_income_cuts'){
						sendNotification(conn,game_id,game_team_id,1,function(err){
							callback(err);
						});
						//callback(null);

					}else if(item.punishment=='home_balance_cuts'){
						sendNotification(conn,game_id,game_team_id,2,function(err){
							callback(err);
						});
					}else{
						callback(null);
					}
				}else{
					callback(null);
				}
			}
		],
		function(err,rs){
			cb(null);
		});
	}else{
		cb(null);	
	}
	
}
function addCost(conn,game_id,game_team_id,item_name,amount,matchday,callback){

	conn.query("INSERT IGNORE INTO "+config.database.database+".game_team_expenditures\
				(game_team_id,item_name,item_type,amount,game_id,match_day,item_total,base_price)\
				VALUES\
				(?,?,?,?,?,?,?,?);",
				[game_team_id,item_name,2,amount,game_id,matchday,1,1],
				function(err,rs){
					console.log(sqlOut(this.sql));
					console.log(rs);
					callback(err,rs);
				});
}

function sendNotification(conn,game_id,game_team_id,type,callback){
	var msg = "";
	if(type==1){
		msg = "Hi, apa kabar? <br/>Saya  Ben Dover dari bagian penjualan tiket pertandingan.<br/>\
			 Sepertinya keputusan Anda mengobral Tim telah membuat para supporter merasa tim kita kehilangan identitasnya.<br/>\
			 Mereka memutuskan untuk memboikot tim kita dan tidak menghadiri pertandingan kemarin.<br/>\
			 Sekelompok kecil fans melakukan demo di luar stadion, dan menghalangi fans lain datang ke stadion,<br/>\
			 sehingga jumlah star anda berkurang.<br/> \
			Mohon maaf atas berita buruk ini. <br/>\
			Good luck in the next game.<br/>\
				<br/>\
			Salam,<br/>\
			<br/>\
			Ben Dover<br/>";
			conn.query("INSERT INTO "+config.database.frontend_schema+".notifications\
						(content,url,dt,game_team_id,league)\
						VALUES\
						(?,'#',NOW(),?,?)",[msg,game_team_id,league],function(err,rs){
							console.log(sqlOut(this.sql));
							callback(err);
				});
	}else if(type==2){
		msg = "Dengan hormat,<br/>\
				Sehubungan dengan terjadinya keributan di daerah stadion Anda akhir pekan ini, \
				kami terpaksa memberlakukan biaya over time kepada seluruh polisi yang bertugas menjaga keamanan di stadion Anda. \
				Sesuai peraturan yang berlaku, maka kami membebankan biaya over time sebesar -5 star kepada Anda. \
				<br/>Terima kasih atas perhatiannya.<br/><br/>\
				Hormat kami,<br/>\
				<br/>\
				Chris P. Nutts<br/>\
				Inspektur Polisi";
				conn.query("INSERT INTO "+config.database.frontend_schema+".notifications\
						(content,url,dt,game_team_id,league)\
						VALUES\
						(?,'#',NOW(),?,?)",[msg,game_team_id,league],function(err,rs){
							console.log(sqlOut(this.sql));
							callback(err);
				});
	}else if(type==3){
		msg = "Hi, <br/>\
			   sebagai manajer tim, Anda berhak menentukan siapa yang Anda inginkan menjadi pemain klub Anda,\
			   namun beberapa pemain yang Anda jual secara paksa baru baru ini merasa dirugikan karena baru saja \
			   mengeluarkan cukup banyak biaya rumah tangga dan biaya lainnya yang kini harus mereka keluarkan lagi \
			   setelah berpindah klub.<br/>\
			   Setelah membicarakan hal ini dengan tim legal klab, kami sepakat untuk membayarkan kompensasi \
			   sebesar -5 star. <br/><br/>\
			   Terimakasih<br/><br/>\
				Salam,<br/>\
				<br/>\
				Anita Hanjaab<br/>\
				Head of legal division<br/>\
				";
				conn.query("INSERT INTO "+config.database.frontend_schema+".notifications\
						(content,url,dt,game_team_id,league)\
						VALUES\
						(?,'#',NOW(),?,?)",[msg,game_team_id,league],function(err,rs){
							console.log(sqlOut(this.sql));
							callback(err);
				});
	}else{
		//do nothing
	}
}
function sqlOut(sql){
	var S = require('string');
	
	return S(sql).collapseWhitespace().s
}


