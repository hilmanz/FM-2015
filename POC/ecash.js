var http = require('http');
var path = require('path');
var S = require('string');
var async = require('async');
var crypto = require('crypto')
  , shasum = crypto.createHash('sha1');
var xmlparser = require('xml2json');
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

async.waterfall([
	function(cb){
		step1(function(err,body,statusCode){
			cb(err,body,statusCode);
		});
	},
	function(body,statusCode,cb){
		var response = JSON.parse(xmlparser.toJson(body));
		var retId = response['soap:Envelope']['soap:Body']['ns2:generateResponse']['return'];
		console.log('ecash return id',retId);
		console.log('redirect url : ',service.protocol+'://'+service.host+payment_URI+'?id='+retId);
		cb(null,null);
	}

],

function(err,rs){
	console.log('ALL FINISHED');
});

function step1(callback){
	console.log('STEP1');
	//step 1 - user memilih proses ecash, 
	// maka system akan meminta transaction id dari ecash webservice.
	// 
	var request_call = service.protocol+'://'+service.host+auth_URI;
	var postData = "\
	<soapenv:Envelope xmlns:soapenv=\"http://schemas.xmlsoap.org/soap/envelope/\"\
	 xmlns:ws=\"http://ws.service.gateway.ecomm.ptdam.com/\">\
	<soapenv:Header/>\
	<soapenv:Body>\
	<ws:generate>\
	<params>\
	<amount>10000</amount>\
	<clientAddress>139.228.244.247</clientAddress>\
	<description>Jersey Asli Man Utd</description>\
	<memberAddress>202.80.113.123</memberAddress>\
	<returnUrl>http://supersoccer.code18.us/files/test.php</returnUrl>\
	<toUsername>supersoccer</toUsername>\
	<trxid>SS-1234567891</trxid>\
	<hash>"+getSHA1Hash('SUPERSOCCER10000139.228.244.247')+"</hash>\
	</params>\
	</ws:generate>\
	</soapenv:Body>\
	</soapenv:Envelope>\
	";


	console.log('CALL',request_call);
	console.log('CALL',postData);
	request({
		 		url:request_call,
		 		method:'POST',
		 		headers: {'Content-Type': 'text/xml'},
		 		auth:{
		 			username:service.username,
		 			password:service.password
		 		},
		 		body:postData
		 		
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
					callback(error,body,response.statusCode);
			});
}

function getSHA1Hash(str){
	shasum.update(str);
	return shasum.digest('hex');
}