/**
populating no salary players.
run these script after you run import_salary.js
**/
/////THE MODULES/////////
var fs = require('fs');
var path = require('path');
var config = require('./config').config;
var xmlparser = require('xml2json');

var async = require('async');
var mysql = require('mysql');
var S = require('string');

var argv = require('optimist').argv;


if(typeof argv.league !== 'undefined'){
	switch(argv.league){
		case 'ita':
			console.log('Serie A Activated');
			config = require('./config.ita').config;
		break;
		default:
			console.log('EPL Activated');
			config = require('./config').config;
		break;
	}
}
var filedata = argv.file;
var master = require('./libs/master');

/////DECLARATIONS/////////
var FILE_PREFIX = config.updater_file_prefix+config.competition.id+'-'+config.competition.year;



/////THE LOGICS///////////////
var conn = mysql.createConnection({
 	host     : config.database.host,
   user     : config.database.username,
   password : config.database.password,
});

var salary_range = {
	VH:[180000,180000],
	H:[85000,90000,100000,110000,120000,130000,140000,150000,160000,170000],
	M:[40000,45000,50000,55000,60000,65000,70000,75000,80000],
	L:[20000,30000,35000],
	VL:[5000,10000,15000],
	F:[5000]
};
/*
function getRandomInt(min, max) {
    return Math.floor(Math.random() * (max - min + 1)) + min;
}
*/
function getRandomInt(category) {
	var min = 0;
	console.log(category);
	console.log(salary_range);
	var max = salary_range[category].length - 1;
    return salary_range[category][(Math.floor(Math.random() * (max - min + 1)) + min)];
}
async.waterfall([
	function(callback){
		open_file(filedata,function(err,content){
			callback(err,content.toString());
		});
	},
	function(strData,callback){

		var lines = strData.split('\n');
		var data = [];
		for(var i in lines){
			if(lines[i].length>0){

				lines[i] = lines[i].replace(',','');
				lines[i] = lines[i].split('\"').join('');
				
				var a = lines[i].split(';');
				

				console.log(a);
				data.push({
					player_id:a[0],
					salary:getRandomInt(a[5])
				});

				
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
				conn.query("UPDATE "+config.database.database+".master_player SET salary = ? WHERE uid = ?",
							[item.salary,item.player_id],
							function(err,rs){
								console.log(S(this.sql).collapseWhitespace().s);
								if(!err&&rs.length>0){
									total_found++;
								}else{
									console.log(item.name);
								}
								next();
				});
				
			},function(err){
				console.log('total_found',total_found);
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
