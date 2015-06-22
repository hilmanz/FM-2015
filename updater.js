/**
the application which responsible for updating game database with OPTA data.
the application will check if there's a  new file exists in data folder.
**/
var argv = require('optimist').argv;


if(typeof argv.league !== 'undefined'){
	switch(argv.league){
		case 'ita':
			console.log('Serie A Activated');
			config = require('./config.ita').config;
		break;
		case 'copa':
			console.log('Copa Activated');
			config = require('./config.copa').config;
		break;
		default:
			console.log('EPL Activated');
			config = require('./config').config;
		break;
	}
}


var fs = require('fs');
var path = require('path');
//var config = require('./config').config;
var xmlparser = require('xml2json');
var master = require('./libs/master');

var FILE_PREFIX = config.updater_file_prefix+config.competition.id+'-'+config.competition.year;


master.setConfig(config);



//first check if the file is exists
var squad_file = FILE_PREFIX+'-squads.xml';
open_squad_file(squad_file,function(err,doc){
		//console.log(xmlparser.toJson(doc.toString()));
		console.log('opening file',squad_file);
		master.update_team_data(JSON.parse(xmlparser.toJson(doc.toString())),onDataProcessed);
});


function onDataProcessed(team_data){
	console.log(team_data);
	console.log('squad data updated !');
}

function open_squad_file(squad_file,done){
	var filepath = path.resolve('./data/'+squad_file);
	fs.stat(filepath,onFileStat);
	function onFileStat(err,stats){
		if(!err){
			fs.readFile(filepath, function(err,data){
				if(!err){
					done(null,data);
				}else{
					done(err,'<xml><error>1</error></xml>');
				}
			});
		}else{
			console.log(err.message);
			done(err,'<xml><error>1</error></xml>');
		}
	}
}
