var nodemailer = require('nodemailer');
var smtpTransport = require('nodemailer-smtp-transport');
var config = require('./config').config;
var async = require('async');
var mysql = require('mysql');
var S = require('string');
var frontend_schema = config.database.frontend_schema;
var template = require('./privateleague_mailer_template').template;

var transport = nodemailer.createTransport("SMTP",{
    host: config.haraka.host,
    port: config.haraka.port,
    from: config.haraka.from,
    auth: {
        user: config.haraka.user,
        pass: config.haraka.pass
    },
    authMethod: "LOGIN"
});

console.log(config.haraka);

//var transport = nodemailer.createTransport(smtpTransport({
//    host: config.mailgun.host,
//    port: config.mailgun.port,
//    from: config.mailgun.from,
//    auth: {
//        user: config.mailgun.user,
//        pass: config.mailgun.pass
//    }
//}));

var pool  = mysql.createPool({
   host     : config.database.host,
   user     : config.database.username,
   password : config.database.password,
});

var mailOption = {
					to: "",
					subject: "Private League Invitation",
					html: template.invite
				};



var start = 0;
var limit = 100;
var doLoop = true;
pool.getConnection(function(err,conn){
	async.whilst(
	    function () { return doLoop; },
	    function (callback) {
	        conn.query("SELECT * FROM "+config.database.frontend_schema+".league_invitations \
						WHERE is_processed = 0 AND n_status = 0 \
						LIMIT ?,?",
				[start,limit],
				function(err,rs){
					console.log(S(this.sql).collapseWhitespace().s);
					console.log(rs);
					if(rs.length>0){
						console.log('length :'+rs.length);
						sendMail(conn, rs, function(err){
							callback();
						});
					}else{
						doLoop=false;
						callback();
					}
			});
	    },
	    function (err) {
	    	conn.release();
	    	pool.end(function(err){
				console.log('done');
			});
	    }
	);
});

function sendMail(conn,users,done){
	var i=0;
	var loop = true;
	async.whilst(
	    function () { return loop; },
	    function (callback) {
			if(i<users.length){
				var id = users[i].id;
				mailOption.to = users[i].email;

				transport.sendMail(mailOption, function(err, info){
					if(err){
						console.log(err);
						callback();
					}else{
						console.log("Message Sent "+JSON.stringify(info));
						conn.query("UPDATE "+config.database.frontend_schema+".league_invitations SET is_processed=1 \
									WHERE id=?",[id],function(err,rs){
										console.log(S(this.sql).collapseWhitespace().s);
										i++;
										done();
									});
					}
				});
				//callback();
			}else{
				loop = false;
				callback();
			}
			
	    },
	    function (err) {
	    	transport.close();
	    	done();
	    }
	);
}