/**
upgrade member worker
**/
/////THE MODULES/////////
var config = require('./config').config;
var async = require('async');
var mysql = require('mysql');
var S = require('string');
var nodemailer = require('nodemailer');
var smtpTransport = require('nodemailer-smtp-transport');

/////DECLARATIONS/////////
var frontend_schema = config.database.frontend_schema;
var pool  = mysql.createPool({
		host     : config.database.host,
		user     : config.database.username,
		password : config.database.password,
	});

var transport = nodemailer.createTransport(smtpTransport({
			    	host: "haraka.supersoccer.co.id", // hostname
			    	secure: false, // use SSL
			    	port: 587, // port for secure SMTP
			    	auth:{
			    		user:'test',
			    		pass:'password'
			    	},
			    	greetingTimeout:30000,
			    	authMethod:'CRAM-MD5',
			    	debug:true,
			    	name:'localhost'
			    }));

var template = require('./proleague_mailer_template').template;

var limit = 1000;

pool.getConnection(function(err, conn){

	async.waterfall([
		function(cb){
			//seven days notif
			console.log("Seven Days Notif");
			seven_days_notif(conn, cb);
		},
		function(cb){
			//three days notif
			console.log("Three Days Notif");
			three_days_notif(conn, cb);
		},
		function(cb){
			//set paid_member_status = 0 if expires
			console.log("Check expired member");
			expired_member(conn, cb);
		}
	], 
	function(err){
		conn.release();
		pool.end(function(err){
			console.log('done');
		});
	});

});

function seven_days_notif(conn, cb)
{
	var start = 0;
	var loop = true;
	async.whilst(
		function(){ return loop; },
		function(callback){
			conn.query("SELECT fb_id, email FROM "+frontend_schema+".member_billings a \
						INNER JOIN "+frontend_schema+".users b USING(fb_id) \
						WHERE is_sevendays_notif = 0 \
						AND DATE_ADD(NOW(), INTERVAL 7 DAY) > DATE(expire) \
						LIMIT ?,?", [start, limit], 
						function(err, rs){
							console.log(S(this.sql).collapseWhitespace().s);
							console.log(rs);
							if(rs!=null && typeof rs.length != "undefined"){
								start += limit;
								if(rs.length == 0){
									loop = false;
									callback();
								}
								sendMail(conn, transport, rs, "BAYAR BULANAN", template.sevendays, 
								function(data, err){
									console.log(data);
									if(data.length > 0){
										update_sevendays_notif(conn, data, callback);
									}else{
										loop = false;
										callback();
									}
								});
							}else{
								loop = false;
								callback();
							}
						});
		},
		function(err){
			console.log('seven_days_notif done');
			cb(err);
		}
	);
}

function three_days_notif(conn, cb)
{
	var start = 0;
	var loop = true;
	async.whilst(
		function(){ return loop; },
		function(callback){
			conn.query("SELECT fb_id, email FROM "+frontend_schema+".member_billings a \
						INNER JOIN "+frontend_schema+".users b USING(fb_id) \
						WHERE is_threedays_notif = 0 \
						AND DATE_ADD(NOW(), INTERVAL 3 DAY) > DATE(expire) \
						LIMIT ?,?", [start, limit], 
						function(err, rs){
							console.log(S(this.sql).collapseWhitespace().s);
							console.log(rs);
							if(rs!=null && typeof rs.length != "undefined"){
								start += limit;
								if(rs.length == 0){
									loop = false;
									callback();
								}
								sendMail(conn, transport, rs, "BAYAR BULANAN", template.threedays, 
								function(data, err){
									if(data.length > 0){
										update_threedays_notif(conn, data, callback);
									}else{
										loop = false;
										callback();
									}
								});
							}else{
								loop = false;
								callback();
							}
						});
		},
		function(err){
			console.log('three_days_notif done');
			cb(err);
		}
	);
}

function expired_member(conn, cb)
{
	var start = 0;
	var loop = true;
	async.whilst(
		function(){ return loop; },
		function(callback){
			conn.query("SELECT b.fb_id, b.email, b.paid_member, b.paid_member_status \
						FROM fantasy.member_billings a \
						INNER JOIN fantasy.users b \
						USING(fb_id) WHERE DATE(a.expire) < NOW() \
						AND b.paid_member_status = 1 \
						LIMIT ?,?", [start, limit], 
						function(err, rs){
							console.log(S(this.sql).collapseWhitespace().s);
							console.log(rs);
							if(rs!=null && typeof rs.length != "undefined"){
								start += limit;
								if(rs.length == 0){
									loop = false;
									callback();
								}
								update_paid_member_status(conn, rs, callback);
							}else{
								loop = false;
								callback();
							}
						});
		},
		function(err){
			console.log('expired_member done');
			cb(err);
		}
	);
}

function sendMail(conn, transport, email, subject, mailContent, cb)
{
	var mailOption = {
		from: 'support proleague <support.proleague@supersoccer.co.id>',
		to: "",
		subject: subject,
		html: mailContent
	};
	var loop = true;
	var i = 0;
	var data = [];
	async.whilst(
		function(){ return loop; },
		function(callback){

			if(i<email.length){
				mailOption.to = email[i].email;
				transport.sendMail(mailOption, function(err, info){
					if(!err){
						data[i] = email[i];
						console.log('email sent', data[i]);
					}else{
						console.log('error', err);
					}

					i++;
					callback();
				});
				
			}else{
				loop = false;
				callback();
			}
			
		},
		function(err){
			cb(data, err);
		}
	);
}

function update_sevendays_notif(conn, data, cb)
{
	var loop = true;
	var i = 0;
	async.whilst(
		function(){ return loop; },
		function(callback){
			if(i<data.length){
				conn.query("UPDATE "+frontend_schema+".member_billings \
												SET is_sevendays_notif=1 \
												WHERE fb_id=?", [data[i].fb_id], 
									function(err, rs){
										console.log(S(this.sql).collapseWhitespace().s);
										i++;
										callback();
									});
			}else{
				loop = false;
				callback();
			}
		},
		function(err){
			cb(err);
		}
	);
	
}

function update_threedays_notif(conn, data, cb)
{
	var loop = true;
	var i = 0;
	async.whilst(
		function(){ return loop; },
		function(callback){
			if(i<data.length){
				conn.query("UPDATE "+frontend_schema+".member_billings \
												SET is_threedays_notif=1 \
												WHERE fb_id=?", [data[i].fb_id], 
									function(err, rs){
										console.log(S(this.sql).collapseWhitespace().s);
										console.log(rs);
										i++;
										callback();
									});
			}else{
				loop = false;
				callback();
			}
		},
		function(err){
			cb(err);
		}
	);
	
}

function update_paid_member_status(conn, data, cb)
{
	var loop = true;
	var i = 0;
	async.whilst(
		function(){ return loop; },
		function(callback){
			if(i<data.length){
				conn.query("UPDATE "+frontend_schema+".users \
												SET paid_member_status=0 \
												WHERE fb_id=?", [data[i].fb_id], 
									function(err, rs){
										console.log(S(this.sql).collapseWhitespace().s);
										i++;
										callback();
									});
			}else{
				loop = false;
				callback();
			}
		},
		function(err){
			cb(err);
		}
	);
}