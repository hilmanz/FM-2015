var http = require('http');
var path = require('path');
var S = require('string');
var async = require('async');
var crypto = require('crypto')
  , shasum = crypto.createHash('sha1');
var xmlparser = require('xml2json');
var request = require('request');

var payment_URI = '/ecommgateway/payment.html';
var validation_URI = '/ecommgateway/validation.html';
var auth_URI = '/ecommgateway/services/ecommgwws';


var service = '';

var config = {};

exports.setConfig = function(c){
	config = c;
	service = config.ecash;
}

exports.getEcashUrl = function(transaction_id,clientIpAddress,description,amount,source,callback){
	process.env.NODE_TLS_REJECT_UNAUTHORIZED = '0';
	async.waterfall([
		function(cb){
			step1(transaction_id,clientIpAddress,description,amount,source,
					function(err,body,statusCode){
				cb(err,body,statusCode);
			});
		},
		function(body,statusCode,cb){
			var response = JSON.parse(xmlparser.toJson(body));
			console.log(response);
			try{
				var retId = response['soap:Envelope']['soap:Body']['ns2:generateResponse']['return'];
				console.log('CALL','ecash return id',retId);
				console.log('CALL','redirect url : ',service.protocol+'://'+service.host+payment_URI+'?id='+retId);
				if(retId!=null){
					cb(null,service.protocol+'://'+service.host+payment_URI+'?id='+retId);	
				}else{
					cb(null,'#');
				}
			}catch(e){
				console.log(e.message);
				cb(null,'#');
			}
			
			
		}

	],
	function(err,returnUrl){
		callback(err,returnUrl);
	});
}

function step1(transaction_id,clientIpAddress,description,amount,source,callback){
	console.log('STEP1');
	//step 1 - user memilih proses ecash, 
	// maka system akan meminta transaction id dari ecash webservice.
	// 
	var request_call = service.protocol+'://'+service.host+auth_URI;
	var returnUrl = '';
	if(source=='FM'){
		returnUrl = service.returnUrl;
	}else if(source=='FMPAY'){
		returnUrl = service.returnUrl3;
	}else if(source=='SSPAY'){
		returnUrl = service.returnUrl4;
	}else if(source=='FMUPGRADE'){
		returnUrl = service.returnUrl5;
	}else if(source=='FMRENEWAL'){
		returnUrl = service.returnUrl6;
	}else if(source=='FMPAYMENTMOBILE'){
		returnUrl = service.returnUrl7;
	}else{
		returnUrl = service.returnUrl2;
	}

	var postData = "\
	<soapenv:Envelope xmlns:soapenv=\"http://schemas.xmlsoap.org/soap/envelope/\"\
	 xmlns:ws=\"http://ws.service.gateway.ecomm.ptdam.com/\">\
	<soapenv:Header/>\
	<soapenv:Body>\
	<ws:generate>\
	<params>\
	<amount>"+amount+"</amount>\
	<clientAddress>"+clientIpAddress+"</clientAddress>\
	<description>"+description+"</description>\
	<memberAddress>202.43.162.250</memberAddress>\
	<returnUrl>"+returnUrl+"</returnUrl>\
	<toUsername>"+service.username+"</toUsername>\
	<trxid>"+transaction_id+"</trxid>\
	<hash>"+getSHA1Hash(service.username.toUpperCase()+amount+clientIpAddress)+"</hash>\
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
exports.validate = function(id,callback){
		process.env.NODE_TLS_REJECT_UNAUTHORIZED = '0';
		console.log('ECASH','VALIDATE');
		var request_call = service.protocol+'://'+service.host+validation_URI+'?id='+id;
		console.log('CALL',request_call);
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
				callback(error,{data:body});
		});
}

function getSHA1Hash(str){
	console.log('CALL',str);
	try{
		return require('crypto').createHash('sha1').update(str).digest('hex');
	}catch(e){
		console.log('CALL','ERROR',e.message);
		return '';
	}
	
}