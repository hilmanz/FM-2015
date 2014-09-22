/**
populating no transfer value players.
**/
/////THE MODULES/////////
var fs = require('fs');
var path = require('path');
var config = require('./config').config;
var xmlparser = require('xml2json');
var master = require('./libs/master');
var async = require('async');
var mysql = require('mysql');
var S = require('string');
/////DECLARATIONS/////////
///
///
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
var FILE_PREFIX = config.updater_file_prefix+config.competition.id+'-'+config.competition.year;
var filedata = argv.file;


/////THE LOGICS///////////////
var conn = mysql.createConnection({
 	host     : config.database.host,
   user     : config.database.username,
   password : config.database.password,
});

/*
var price_range = {
	High:[7000000,7500000,8000000,8500000,9000000,9500000,10000000],
	Low:[1000000,1500000,2000000],
	Middle:[4000000,4500000,5000000,5500000,6000000,6500000,7000000],
	Medium:[4000000,4500000,5000000,5500000,6000000,6500000,7000000],
}
*/



var price_range = {
	VH:[40000000,42000000,42500000,44000000,45000000,46000000,47000000,48000000,48500000,50000000],
	H:[30000000,32000000,32500000,34000000,35000000,36000000,37000000,38000000,38500000,39000000],
	M:[20000000,22000000,22500000,24000000,25000000,26000000,27000000,28000000,28500000,29000000],
	L:[10000000,12000000,12500000,14000000,15000000,16000000,17000000,18000000,18500000,19000000],
	VL:[1500000,2500000,3000000,3400000,3600000,3800000,4500000,4800000,5000000,6000000,6800000,7500000,8000000,9000000,9500000],
	F:[0]
}
function getRandomInt(category) {
	var min = 0;
	var max = price_range[category].length - 1;
    return price_range[category][(Math.floor(Math.random() * (max - min + 1)) + min)];
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
				
				try{
					data.push({
						player_id:a[0],
						transfer_value:getRandomInt(a[5])
					});
				}catch(e){
					console.log('-->'+a[1]+':'+a[5]);
				}
				
				
			}
		}
		console.log(data);
		callback(null,data);
	},
	
	function(data,callback){
		var total_found = 0;
		console.log('total data',data.length);
		async.eachSeries(
			data,
			function(item,next){
				console.log(item);
				
				conn.query("UPDATE "+config.database.database+".master_player SET transfer_value = ? WHERE uid = ?",
							[item.transfer_value,item.player_id],
							function(err,rs){
								console.log(S(this.sql).collapseWhitespace().s);
								if(!err&&rs.length>0){
									total_found++;
								}else{
									console.log(item.name);
								}
								next();
				});
				
				next();
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
