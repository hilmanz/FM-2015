/**
* API for notification inbox messages
* 2015/08/28
* authors
* @author hapsoro renaldy <hapsoro.renaldy@msp-entertainment.net>
*/
var config = {};


var fs = require('fs');
var path = require('path');
var xmlparser = require('xml2json');
var async = require('async');
var mysql = require('mysql');
var S = require('string');


exports.setConfig = function(c){
	config = c;

}
exports.send = function(conn,game_team_id,league,url,message,msg_id,msg_type,meta,callback){
	var meta = JSON.stringify(meta);
	console.log("meta",meta);
	conn.query("INSERT INTO "+config.database.frontend_schema+".notifications\
			(content,url,dt,game_team_id,msg_id,league,msg_type,meta)\
			VALUES\
			(?,?,NOW(),?,?,?,?,?);",
			[message,url,game_team_id,msg_id,league,msg_type,meta],
			function(err,rs){
				console.log(S(this.sql).collapseWhitespace().s);
				callback(err,rs);
			});	
}
exports.getMessage = function(conn,game_team_id,league,since_id,callback){
	if(typeof since_id === 'undefined'){
		since_id = 0;
	}
	since_id = parseInt(since_id);
	conn.query("SELECT * FROM "+config.database.frontend_schema+".notifications\
			WHERE id > ? AND game_team_id = ? AND league = ? ORDER BY id DESC LIMIT 20",
			[since_id,game_team_id,league],
			function(err,rs){
				console.log(S(this.sql).collapseWhitespace().s);
				if(rs!=null && rs.length > 0){
					since_id = rs[0].id;
				}
				for(var i in rs){
					if(rs[i].meta.length > 0){
						rs[i].meta = JSON.parse(rs[i].meta);
					}
				}
				callback(err,{status:1,data:rs,since_id:since_id});
			});	
}
exports.getLastMessage = function(conn,game_team_id,league,callback){
	conn.query("SELECT * FROM "+config.database.frontend_schema+".notifications\
			WHERE game_team_id = ? AND league = ? ORDER BY id DESC LIMIT 100",
			[game_team_id,league],
			function(err,rs){
				console.log(S(this.sql).collapseWhitespace().s);
				if(rs!=null && rs.length > 0){
					since_id = rs[0].id;
				}
				for(var i in rs){
					if(rs[i].meta.length > 0){
						rs[i].meta = JSON.parse(rs[i].meta);
					}
				}
				callback(err,{status:1,data:rs});
			});	
}