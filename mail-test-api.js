/*
* API service for email testing
* it will send introductionary email to user. 
* if the email is delivered, then return success
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

var nodemailer = require('nodemailer');
//var sendmailTransport = require('nodemailer-sendmail-transport');
var smtpTransport = require('nodemailer-smtp-transport');
var validator = require('validator');
var crypto = require('crypto');
var sha1sum = crypto.createHash('sha1');
//email setup

process.env.NODE_TLS_REJECT_UNAUTHORIZED = "0";
var transport = nodemailer.createTransport(smtpTransport({
			    	host: "sg.supersoccer.co.id", // hostname
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

transport.on('log',function(data){
	console.log(data);
});
console.log(transport);
/*
var transport = nodemailer.createTransport(sendmailTransport({
    path: '/usr/sbin/sendmail'
}));
*/
/*
var transport = nodemailer.createTransport("SES",{
			    	AWSAccessKeyID: "AKIAJYPEIMSEIVGQHNTA",
    				AWSSecretKey: "AqkTdt3g+a6jKvD6zYNUkLDnNwjskCkBQ4Joe7tpo9tP",
    				debug: true
			    });
*/
var secret = 'x4asd1!234@!42b4b00n5';
var app = express();
var RedisStore = require('connect-redis')(express);

app.set('port', 3101);
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
	
	sha1sum = crypto.createHash('sha1');
	var mailOptions = {
	    from: 'noreply@sg.supersoccer.co.id',
	    to: req.body.email,
	    subject: 'Welcome to Supersoccer',
	    generateTextFromHTML:true,
	    html: "Selamat Datang di SuperSoccer!<br/>Saat ini, lo sudah resmi terdaftar sebagai anggota FM Supersoccer.\
	    		Jika tidak ingin menerima update terbaru dan paling menarik dari kami, \
	    		silahkan klik link ini: http://fm.supersoccer.co.id/pages/unsubcribe<br/>\
	    		<br/>\
				Terima Kasih!<br/>\
				SuperSoccer Team.<br/>\r\n",
	    forceEmbeddedImages: true,
	    debug:true
	};
	//console.log('sending',mailOptions.to);
	
	sendMail(mailOptions,function(err,responseStatus){
		if(!err){
			res.send(200,{status:1,responseStatus:responseStatus,error:err});	
		}else{
			res.send(200,{status:0,responseStatus:responseStatus,error:err});
		}
		
	});
	
	
});

http.createServer(app).listen(app.get('port'), function(){
  console.log('Express server listening on port ' + app.get('port'));
});


function sendMail(mailOptions,callback){

	transport.sendMail(mailOptions,function(error, responseStatus){
		//console.log(mailOptions,'---',responseStatus);
		//console.log('------------');
		if(error){
			console.log('ERROR',error.message);
		}
		//console.log('sent ',mailOptions.to,mailOptions.subject,responseStatus);
		callback(error,responseStatus);
	});
}