/**
mailer.js
these app will process all email queue and sends it (for now) using Amazon's SES
these app will be act as worker. each worker will process 1 queue at a time.
we only process 4000 email queues, after that, the worker will quit.
we utilize foreverjs to spawn it again to process another 4000 queues.
**/
/////THE MODULES/////////
var fs = require('fs');
var path = require('path');
var config = require('./config').config;
var xmlparser = require('xml2json');
var master = require('./libs/master');
var async = require('async');
var mysql = require('mysql');
var nodemailer = require('nodemailer');
var validator = require('validator');
var transport = nodemailer.createTransport("SMTP",{
										    	host: "email-smtp.us-east-1.amazonaws.com", // hostname
										    	secureConnection: true, // use SSL
										    	port: 465, // port for secure SMTP
										    	auth: {
										        	user: "AKIAIVAZ23POJSA74BRQ",
										        	pass: "AtqAGkYl4FCvih1yQ7P/uu6qt3GyV4v9RIg77F29aIbM"
										    	}
										    });
var sleep = require('sleep');
var secret = 'x4asd1!234@!42b4b00n5';
var crypto = require('crypto');
var sha1sum = crypto.createHash('sha1');
var request = require('request');
/////DECLARATIONS/////////


/////THE LOGICS///////////////
//mysql pool
var pool  = mysql.createPool({
   host     : config.database.host,
   user     : config.database.username,
   password : config.database.password,
});
var no = 0;
var dt = new Date();
var last_t = dt.getTime();
var n_sent = 0;
var total_sent = 0;
var send_limit = 40000;
console.log('start time',last_t,'sending limit : ',send_limit);
//console.log(prev_t,last_t);
//console.log(Math.round((last_t - prev_t)/1000));
runLoop();

function runLoop(){

	pool.getConnection(function(err,conn){
		async.waterfall([
			function(callback){
				conn.query("SELECT * FROM ffgame.email_queue WHERE n_status=0 LIMIT 1;"
							,[],
				function(err,rs){
					try{
						callback(err,rs[0]);
					}catch(e){
						callback(err,null);
					}
				});
			},
			function(queue,callback){
				if(queue!=null){
					console.log('processing #',queue.id,'-',queue.email);
					//set sending status
					conn.query("UPDATE ffgame.email_queue SET n_status=1 WHERE id = ?",
							[queue.id],
							function(err,rs){
								callback(err,queue);
							});
				}else{
					callback(err,queue);
				}
				
			},
			function(queue,callback){
				//sending the email
				if(queue!=null){
					if(validator.isEmail(queue.email)){
						console.log(queue.id,'sending ',queue.email);

						var mailOptions = {
						    from: "Supersoccer Football Manager <footballmanager@supersoccer.co.id>",
						    to: queue.email,
						    subject: queue.subject,
						    generateTextFromHTML:true,
						    html: queue.html_text
						}
						sha1sum = crypto.createHash('sha1');
						var send_hash = sha1sum.update(mailOptions.to+mailOptions.subject+mailOptions.html+secret)
											   .digest('hex');

						request.post(config.mailer.host+'/send', {
								form:{
									from:mailOptions.from,
									to:mailOptions.to,
									subject:mailOptions.subject,
									html:mailOptions.html,
									hash:send_hash
								}
							},
						function (error, response, body) {
							console.log(response);
							console.log(body);
						  if (!error && response.statusCode == 200) {
						  	
						    var resp = JSON.parse(body);
						    console.log(resp);
						    var td = ((new Date()).getTime() - last_t)/1000;
							n_sent++;
							total_sent++;
							console.log(queue.id,'elapsed : ',td,'total sent : ',n_sent);
							
							if(td < 1 && n_sent > 5){
								console.log('sleep for 1s');
								//sleep 1 second
								sleep.sleep(1);
								console.log(queue.id,'resetting the timewatch and n_sent');
								n_sent = 0;
								last_t = (new Date()).getTime();
							}else if(td > 1 && n_sent < 5){
								console.log('reset time and counter');
								last_t = (new Date()).getTime();
								n_sent = 0;
							}else{
								//do nothing
							}
							if(!error){
								console.log(queue.id,'sent');
								callback(null,error,queue,resp.responseStatus);	
							}else{
								console.log(queue.id,'error : ',error.message);
								callback(null,null,queue,null);
							}
						  }else{
						  	console.log(error.message);
						  	callback(null,null,queue,null);
						  }
						})
						/*
						transport.sendMail(mailOptions,function(error, responseStatus){
							
							var td = ((new Date()).getTime() - last_t)/1000;
							n_sent++;
							total_sent++;
							console.log(queue.id,'elapsed : ',td,'total sent : ',n_sent);
							
							if(td < 1 && n_sent > 5){
								console.log('sleep for 1s');
								//sleep 1 second
								sleep.sleep(1);
								console.log(queue.id,'resetting the timewatch and n_sent');
								n_sent = 0;
								last_t = (new Date()).getTime();
							}else if(td > 1 && n_sent < 5){
								console.log('reset time and counter');
								last_t = (new Date()).getTime();
								n_sent = 0;
							}else{
								//do nothing
							}
							if(!error){
								console.log(queue.id,'sent');
								callback(null,error,queue,responseStatus);	
							}else{
								console.log(queue.id,'error : ',error.message);
								callback(null,null,queue,null);
							}
						});
						*/
					}else{
						console.log(queue.id,'cannot send ',queue.email);
						callback(null,null,queue,null);
					}
				}else{
					console.log('NO QUEUE');
					callback(null,null,queue,null);
				}
				
			},
			function(errorStatus,queue,responseStatus,callback){
				console.log(responseStatus);
				if(errorStatus!=null){
					console.log('error : ',errorStatus.message);
				}
				console.log('responseStatus',responseStatus);
				if(queue!=null && responseStatus!=null){
					if(responseStatus.message.search('250 OK')){
						console.log('SENT ! SUCCESSFULLY !');
						conn.query("UPDATE ffgame.email_queue SET n_status=2,send_dt=NOW() WHERE id=?",
									[queue.id],function(err,rs){
							callback(err,queue);	
						});
					}else{
						console.log('Failed:',responseStatus.message);
						conn.query("UPDATE ffgame.email_queue SET n_status=3 WHERE id=?",
									[queue.id],function(err,rs){
							callback(err,queue);	
						});
					}	
				}else if(queue!=null && responseStatus==null){
					console.log('Failed: no response status');
					conn.query("UPDATE ffgame.email_queue SET n_status=3 WHERE id=?",
									[queue.id],function(err,rs){
							callback(err,queue);	
						});
				}else{
					callback(err,queue);	
				}
			}
		],
		function(err,rs){
			conn.release();
			if(rs==null || (total_sent >= send_limit)){
				if(total_sent >= send_limit){
					console.log('Sending limit reached :( ---> ',total_sent);
				}
				pool.end(function(err){
					
				});
			}else{
				runLoop();	
			}
			
		});
	});
}

function processQueue(cb){
	console.log('process queue');
	cb();
}