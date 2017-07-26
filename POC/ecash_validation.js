var http = require('http');
var path = require('path');
var S = require('string');
var async = require('async');
var crypto = require('crypto')
  , shasum = crypto.createHash('sha1');
var xmlparser = require('xml2json');
var argv = require('optimist').argv;

/*
var payment_url = 'https://182.253.203.90:9443/ecommgateway/payment.html';
var validation_url = 'https://182.253.203.90:9443/ecommgateway/validation.html';

*/
process.env.NODE_TLS_REJECT_UNAUTHORIZED = '0';

var payment_URI = '/ecommgateway/payment.html';
var validation_URI = '/ecommgateway/validation.html';
var auth_URI = '/ecommgateway/services/ecommgwws';


var service = {
	protocol:'https',
	host:'182.253.203.90:9443',
	username:'supersoccer',
	password: '123456'
}

var request = require('request');
var request_call = service.protocol+'://'+service.host+validation_URI+'?id='+argv.id;
console.log(request_call);

request({
 		url:request_call,
 		method:'GET'
 		
 		
}, function (error, response, body) {
		if(!error){
			//console.log(response);
			console.log('CALL','status : '+response.statusCode);
			console.log('CALL',body);
		}else{
			console.log('CALL',error.message);
		}
		if (!error && response.statusCode == 200) {
		    //console.log('CALL',body) // Print the google web page.
		    console.log('CALL','FINISHED');
		}
		
});

function getSHA1Hash(str){
	shasum.update(str);
	return shasum.digest('hex');
}