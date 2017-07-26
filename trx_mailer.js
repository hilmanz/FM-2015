/*
* 
* transactional email using mailgun
*/

/////THE MODULES/////////
var express = require('express')
  , routes = require('./routes')
  , user = require('./routes/user')
  , http = require('http')
  , path = require('path');
var fs = require('fs');
var S = require('string');

var config = require('./config').config;


var async = require('async');
var request = require('request');
var nodemailer = require('nodemailer');
var validator = require('validator');
var crypto = require('crypto');
var sha1sum = crypto.createHash('sha1');
//email setup

var transport = nodemailer.createTransport("SMTP",{
			    	host: "smtp.mailgun.org", // hostname
			    	secureConnection: true, // use SSL
			    	port: 465, // port for secure SMTP
			    	auth: {
			        	user: config.mailgun.user,
			        	pass: config.mailgun.pass
			    	}
			    });

var secret = 'x4asd1!234@!42b4b00n5';
var app = express();
var RedisStore = require('connect-redis')(express);
var queues = [];
app.set('port', 3103);
app.set('views', __dirname + '/views');
app.set('view engine', 'jade');
app.use(express.favicon());
app.use(express.logger('dev'));
app.use(express.bodyParser());
app.use(express.methodOverride());
app.use(express.cookieParser('i die, you die, everybody die'));
app.use(express.session({ store: new RedisStore(config.redis) }));

app.use(app.router);

app.use(express.static(path.join(__dirname, 'public')));

// development only
if ('development' == app.get('env')) {
  app.use(express.errorHandler());
}

app.get('/', routes.index);

app.post('/send', [],function(req,res){
	
	
	var mailOptions = {
	    from: config.mailgun.from,
	    to: req.body.to,
	    subject: req.body.subject,
	    generateTextFromHTML:true,
	    html: req.body.html+'\r\n'
	};

	console.log(mailOptions);

	sendMail(mailOptions,function(err,responseStatus){
		if(err){
			res.send(200,{status:0,
					  responseStatus:responseStatus,
					  err:err.message});	
		}else{
			res.send(200,{status:1,
					  responseStatus:responseStatus});
		}
	});

});
function sendMail(mailOptions,callback){

	console.log('sending',mailOptions.to,mailOptions.subject);
	transport.sendMail(mailOptions,function(error, responseStatus){
		if(error){
			console.log('ERROR',error.message);
		}
		console.log(mailOptions.to,mailOptions.subject,responseStatus);
		callback(error,responseStatus);
	});
}

http.createServer(app).listen(app.get('port'), function(){
  console.log('Express server listening on port ' + app.get('port'));
});


