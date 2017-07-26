/**
* adhoc script to check weekly data migration
*/

/**
* the module to read match_results file.
*/
var fs = require('fs');
var path = require('path');
var async = require('async');
var xmlparser = require('xml2json');
var config = require(path.resolve('./config')).config;
var S = require('string');
var mysql = require('mysql');
var pool  = mysql.createPool({
   host     : config.database.host,
   user     : config.database.username,
   password : config.database.password,
});
var punishment = require(path.resolve('./libs/gamestats/punishment_rules'));
var player_stats_category = require(path.resolve('./libs/game_config')).player_stats_category;

var conn = mysql.createConnection({
 	host     : config.database.host,
   user     : config.database.username,
   password : config.database.password,
});


async.waterfall([
	function(callback){
		//get the original points on week 8
		conn.query("SELECT game_id,game_team_id,SUM(points) as total \
FROM ffgame_stats.game_match_player_points WHERE \
game_id IN (\
'f694980',\
'f694972',\
'f694974',\
'f694975',\
'f694976',\
'f694977',\
'f694978',\
'f694979',\
'f694971',\
'f694973'\
)\
 AND \
game_team_id IN (\
'13',\
'89',\
'193',\
'216',\
'224',\
'286',\
'296',\
'310',\
'328',\
'385',\
'401',\
'627',\
'710',\
'933',\
'959',\
'1055',\
'1146',\
'1404',\
'1415',\
'1490',\
'1517',\
'1935',\
'2139',\
'2197',\
'2439',\
'2515',\
'2526',\
'3201',\
'3217',\
'3266',\
'3887',\
'3934',\
'4023',\
'4719',\
'4791',\
'4841',\
'4996',\
'5116',\
'5312',\
'5348',\
'5451',\
'5493',\
'5562',\
'5958',\
'6008',\
'6067',\
'6183',\
'6197',\
'6835',\
'6840',\
'6883',\
'7337',\
'7521',\
'8148',\
'8325',\
'8364',\
'8540',\
'8624',\
'8849',\
'9007',\
'9033',\
'9190',\
'9378',\
'9498',\
'9862',\
'10034',\
'10092',\
'10126',\
'10186',\
'10464',\
'10471',\
'10525',\
'10876',\
'10905',\
'10945',\
'11152',\
'11320',\
'11426',\
'11652',\
'11763',\
'11945',\
'11981',\
'12029',\
'12302',\
'12325',\
'12437',\
'12606',\
'13195',\
'13387',\
'13812',\
'14164',\
'14242',\
'14304',\
'14513',\
'14623',\
'14819',\
'14848',\
'15137',\
'15203',\
'15301',\
'15311',\
'15339',\
'15479',\
'15575',\
'15953',\
'16098',\
'16289',\
'16351',\
'16599',\
'16737',\
'17106',\
'17270',\
'17449',\
'17452',\
'17686',\
'17737',\
'18079',\
'18430',\
'18586',\
'18631',\
'18730',\
'18868',\
'18992',\
'19014',\
'19202',\
'19419',\
'19480',\
'19504',\
'19548',\
'19819',\
'19859',\
'20018',\
'20022',\
'20044',\
'20052',\
'20053',\
'20067',\
'20068',\
'20090',\
'20094',\
'20102',\
'20103',\
'20116',\
'20132',\
'20147',\
'20151',\
'20154',\
'20180',\
'20191',\
'20196',\
'20223',\
'20241',\
'20265',\
'20267',\
'20269',\
'20320',\
'20324',\
'20378',\
'20391',\
'20399',\
'20408',\
'20412',\
'20436',\
'20440',\
'20448',\
'20455',\
'20461',\
'20472',\
'20480',\
'20503',\
'20542',\
'20555',\
'20571',\
'20596',\
'20602',\
'20613',\
'20633',\
'20651',\
'20655',\
'20669',\
'20675',\
'20699',\
'20707',\
'20715',\
'20717',\
'20729',\
'20732',\
'20749',\
'20756',\
'20768',\
'20777',\
'20812',\
'20821',\
'20838',\
'20843',\
'20868',\
'20877',\
'20902',\
'20914',\
'20974',\
'20977',\
'21033',\
'21041',\
'21045',\
'21054',\
'21074',\
'21088',\
'21097',\
'21106',\
'21113',\
'21118',\
'21125',\
'21137',\
'21249',\
'21413',\
'21689',\
'21900',\
'21910',\
'22168',\
'22261',\
'22490'\
)\
 GROUP BY game_team_id;",[],function(err,rs){
			callback(err,rs);
			if(err){
				console.log(err.message);
			}
			console.log(S(this.sql).collapseWhitespace().s);
			console.log(rs);
		});
	},
	function(teams,callback){
		async.eachSeries(teams,function(team,next){
			async.waterfall([
				function(cb){
					conn.query("SELECT SUM(points) AS total \
						FROM ffgame_stats.game_team_player_weekly \
						WHERE game_team_id=? AND matchday=?;",
						[team.game_team_id,8],
						function(err,rs){
							cb(err,rs[0].total);
						});
				},
				function(new_point,cb){
					//get original team_id
					conn.query("SELECT team_id FROM ffgame.game_teams WHERE id = ? LIMIT 1",
								[team.game_team_id],
								function(err,rs){
									cb(err,rs[0].team_id,new_point);
								});
				},
				function(team_id,new_point,cb){
					//get original game_id
					conn.query("SELECT game_id FROM ffgame.game_fixtures WHERE matchday=8 AND \
								(home_id = ? OR away_id = ?)",[team_id,team_id],function(err,rs){
									cb(err,rs[0].game_id,new_point);
								});
				},
				function(the_game_id,new_point,cb){
					if(new_point == null){
						new_point = 0;
					}
					if(new_point != team.total){
						
						
						difference = team.total - new_point;
						
						
						conn.query("INSERT INTO \
						ffgame_stats.game_team_extra_points\
						(game_id,matchday,game_team_id,modifier_name,extra_points)\
						VALUE\
						(?,8,?,'ADJUSTMENT_POINT',?)\
						ON DUPLICATE KEY \
						UPDATE extra_points = VALUES(extra_points);",
						[the_game_id,team.game_team_id,(difference)],
						function(err,c){

							//console.log(S(this.sql).collapseWhitespace().s);
							//console.log(team.game_id,' team#',team.game_team_id,' ',new_point,'/',
							//		team.total,'-->',(difference));
							cb(err,c);
						});
						

					}else{
						console.log('OVER', team.game_team_id,'->',new_point,'/',team.total);
						cb(null,new_point);
					}
					
				}
			],

			function(e,r){
				next();
			});

		},function(err){
			callback(err,null);
		});
	}
],

function(err,rs){
	conn.end(function(err){
		console.log('done');
	});
})