/**
api related to user management.
*/

var crypto = require('crypto');
var fs = require('fs');
var path = require('path');
var xmlparser = require('xml2json');
var async = require('async');
var config = require(path.resolve('./config')).config;
var mysql = require('mysql');
var dateFormat = require('dateformat');
var redis = require('redis');

function prepareDb(){
	var connection = mysql.createConnection({
  		host     : config.database.host,
	   	user     : config.database.username,
	   	password : config.database.password,
	});
	
	return connection;
}
function authenticate(req,res){
	var api_key = req.body.api_key;
	var request_code = req.body.request_code;
	if(request_code==null){
		askForChallengeCode(req,res,api_key);
	}else{
		authenticateCode(req,res,api_key,request_code);
	}
}
function register(data,callback){
	conn = prepareDb();
	conn.query("INSERT INTO ffgame.game_users\
				(name,email,phone,fb_id,n_status,access_key,register_date)\
				VALUES(?,?,?,?,?,?,NOW());",
				[data.name,data.email,data.phone,data.fb_id,1,''],function(err,rs){
					conn.end(function(err){
						callback(err,rs);
					});
	});
}
function removeByFbId(fb_id,callback){
	conn = prepareDb();
	conn.query("DELETE FROM ffgame.game_users\
				WHERE fb_id = ?",
				[fb_id],function(err,rs){
					conn.end(function(err){
						callback(err,rs);
					});
	});
}

exports.register = register;
exports.removeByFbId = removeByFbId;