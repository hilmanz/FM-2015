/**
script for updating multipliers
**/
/////THE MODULES/////////
var fs = require('fs');
var path = require('path');
var config = require('./config').config;
var xmlparser = require('xml2json');
var master = require('./libs/master');
var async = require('async');
var mysql = require('mysql');
/////DECLARATIONS/////////
var FILE_PREFIX = config.updater_file_prefix+config.competition.id+'-'+config.competition.year;



/////THE LOGICS///////////////
var conn = mysql.createConnection({
 	host     : config.database.host,
   user     : config.database.username,
   password : config.database.password,
});


function getRandomInt(min, max) {
    return Math.floor(Math.random() * (max - min + 1)) + min;
}
async.waterfall([
	function(callback){
		open_file('modifier2.csv',function(err,content){
			callback(err,content.toString());
		});
	},
	function(strData,callback){

		var lines = strData.split('\n');
		var data = [];
		for(var i in lines){
			if(lines[i].length>0){
				//console.log(lines[i]);
				
				var a = lines[i].split(',');
				
				
				if(a[0]!=''){
					if(a[1]==''){
						a[1]=0;
					}
					if(a[2]==''){
						a[2]=0;
					}
					if(a[3]==''){
						a[3]=0;
					}
					if(a[4]==''){
						a[4]=0;
					}
					data.push({
						name:a[0],
						g:parseInt(a[1]),
						d:parseInt(a[2]),
						m:parseInt(a[3]),
						f:parseInt(a[4]),
					});
				}
			}
		}
		callback(null,data);
	},
	
	function(data,callback){
		var total_found = 0;
		console.log('total data',data.length);
		async.eachSeries(
			data,
			function(item,next){
				console.log(item);
				conn.query("INSERT INTO ffgame.game_matchstats_modifier\
							(name,g,d,m,f)\
							VALUES\
							(?,?,?,?,?)\
							ON DUPLICATE KEY UPDATE\
							g = VALUES(g),\
							d = VALUES(d),\
							m = VALUES(m),\
							f = VALUES(f);",
							[item.name,item.g,item.d,item.m,item.f],
							function(err,rs){
								if(err){
									console.log(err.message);
								}
								console.log(this.sql);
								next();
				});
				
			},function(err){
				callback(err,data);
			});
	}
	
],
function(err,result){
	conn.end(function(err){
		console.log('finished');
	});
});

function open_file(the_file,done){
	var filepath = path.resolve('./updates/'+the_file);
	fs.stat(filepath,onFileStat);
	function onFileStat(err,stats){
		if(!err){
			fs.readFile(filepath, function(err,data){
				if(!err){
					done(null,data);
				}else{
					done(new Error('file cannot be read !'),[]);
				}
			});
		}else{
			console.log(err.message);
			done(new Error('file is not exists !'),[]);
		}
	}
}
