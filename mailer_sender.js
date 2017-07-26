/*
* a mailer proxy.
* we setup these proxy in fm-en (singapore node)
* the mailer.js instead of directly send request to Amazon SES
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
			    	host: "email-smtp.us-east-1.amazonaws.com", // hostname
			    	secureConnection: true, // use SSL
			    	port: 465, // port for secure SMTP
			    	auth: {
			        	user: "AKIAJYPEIMSEIVGQHNTA",
			        	pass: "AqkTdt3g+a6jKvD6zYNUkLDnNwjskCkBQ4Joe7tpo9tP"
			    	}
			    });
/*
var transport = nodemailer.createTransport("SES",{
			    	AWSAccessKeyID: "AKIAJYPEIMSEIVGQHNTA",
    				AWSSecretKey: "AqkTdt3g+a6jKvD6zYNUkLDnNwjskCkBQ4Joe7tpo9tP",
    				debug: true
			    });
*/
var secret = 'x4asd1!234@!42b4b00n5';





var has_queue = true;
async.whilst(
	function(){

		return has_queue;
	},
	function(next){
		request(config.mailer.queue+'/get', function (error, response, body) {
		
		  if (!error && response.statusCode == 200) {
		    var resp = JSON.parse(body);
		    if(resp.status==1){
		    	sendMail(resp.data,function(err,rs){
		    		next();
		    	});
		    }else{
		    	has_queue = false;
		  		next();
		    }
		  }else{
		  	console.log('no more queue');
		  	has_queue = false;
		  	next();
		  }

		});
	},
	function(err){
		console.log('done');
		transport.close();
	}
);


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