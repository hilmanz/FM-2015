/**
 * result servers will process live data from opta.
 */

var express = require('express')
  , routes = require('./routes')
  , user = require('./routes/user')
  , http = require('http')
  , async = require('async')
  , path = require('path');
var S = require('string');
var fs = require('fs');
var redis = require('redis');
var dummy_api_key = '1234567890';
var auth = require('./libs/api/auth');
var config = require('./config').config;
var mysql = require('mysql');
var dateFormat = require('dateformat');
var pool  = mysql.createPool({
   host     : config.database.host,
   user     : config.database.username,
   password : config.database.password,
});
var xmlparser = require('xml2json');

//our api libs

var app = express();
var RedisStore = require('connect-redis')(express);

// all environments
app.set('port', 3080);
app.set('views', __dirname + '/views');
app.set('view engine', 'jade');
app.use(express.favicon());
app.use(express.logger('dev'));
app.use(express.bodyParser());
app.use(express.methodOverride());
app.use(express.cookieParser('i die, you die, everybody die'));
app.use(express.session({ store: new RedisStore(config.redis) }));

var client = redis.createClient(config.redis.port,config.redis.host);
client.on("error", function (err) {
    console.log("Error " + err);
});
app.use(function(req,res,next){
	//bind everything we need
	req.redisClient = client;
	next();
});
app.use(app.router);

app.use(express.static(path.join(__dirname, 'public')));

// development only
if ('development' == app.get('env')) {
  app.use(express.errorHandler());
}

app.get('/', function(req,res){
	extractData(req.query.file,function(err,rs){
		res.send(200,{status:1,data:rs});
	});
});


app.get('/ping',function(req,res){
	res.send(200,{status:1,message:'Server Alive'});
});


http.createServer(app).listen(app.get('port'), function(){
  console.log('Express server listening on port ' + app.get('port'));
});



function accessDenied(req,res){
	res.send(401,'Access Denied');
}

function extractData(filename,callback){
	async.waterfall([
			function(callback){
				console.log('opening ',filename);
				open_file(filename,function(err,content){
					callback(null,content);
				});
			},
			function(doc,callback){
				var json = JSON.parse(xmlparser.toJson(doc.toString()));
				callback(null,json);
			},
			function(json,callback){
				if(json.SoccerFeed.SoccerDocument.length > 1){
					json.SoccerFeed.SoccerDocument = json.SoccerFeed.SoccerDocument[0];
				}
				try{
					console.log(json.SoccerFeed.SoccerDocument);

					var data = {
						game_id: json.SoccerFeed.SoccerDocument.uID,
						competition_id: json.SoccerFeed.SoccerDocument.Competition.uID,
					};
					var competition_stat = json.SoccerFeed.SoccerDocument.Competition.Stat;
					for(var i in competition_stat){
						data[competition_stat[i].Type] = competition_stat[i]['$t'];

					}
					data.matchtype = json.SoccerFeed.SoccerDocument.MatchData.MatchInfo.MatchType;
					data.period = json.SoccerFeed.SoccerDocument.MatchData.MatchInfo.Period;
					data.matchdate = toDate(json.SoccerFeed.SoccerDocument.MatchData.MatchInfo.Date);
				
					try{
						data.result_type = json.SoccerFeed.SoccerDocument.MatchData.MatchInfo.Result.Type;
					}catch(e){
						data.result_type = '';
					}
					try{
						data.result_winner = json.SoccerFeed.SoccerDocument.MatchData.MatchInfo.Result.Winner;
					}catch(e){
						data.result_winner = '';	
					}
					data.referee = json.SoccerFeed.SoccerDocument.MatchData.MatchOfficial.OfficialName;
					

					if(json.SoccerFeed.SoccerDocument.MatchData.Stat.length>1){
						for(var ii in json.SoccerFeed.SoccerDocument.MatchData.Stat){
							if(json.SoccerFeed.SoccerDocument.MatchData.Stat[ii].Type == 'match_time'){
								data.matchtime = json.SoccerFeed.SoccerDocument.MatchData.Stat[ii]['$t'];	
								break;
							}
						}
					}else{
						data.matchtime = json.SoccerFeed.SoccerDocument.MatchData.Stat['$t'];	
					}
					
					var teamData = json.SoccerFeed.SoccerDocument.MatchData.TeamData;
					for(var i in teamData){
						if(teamData[i].Side=='Home'){
							data.home_team = teamData[i].TeamRef;
							data.home_score = teamData[i].Score;
						}else{
							data.away_team = teamData[i].TeamRef;
							data.away_score = teamData[i].Score;
						}
					}
					teamData = null;
					data.attendance = json.SoccerFeed.SoccerDocument.MatchData.MatchInfo.Attendance;
					data.venue = json.SoccerFeed.SoccerDocument.Venue;
					data.team = json.SoccerFeed.SoccerDocument.Team;
					callback(null,json,data);
				}catch(e){
					console.log(e.message);
					callback(new Error('document is empty'),null,null);
				}
			},
			function(json,data,callback){
				update_match_data(data,function(err,rs){
					callback(null,json,data);
				});
				
			},
			function(json,data,callback){
				update_bookings(data.game_id,
								json.SoccerFeed.SoccerDocument.MatchData.TeamData,
								function(err,rs){
					callback(err,json,data);
				});
			},
			function(json,data,callback){
				update_goals(data.game_id,
								json.SoccerFeed.SoccerDocument.MatchData.TeamData,
								function(err,rs){
					callback(err,json,data);
				});
			},
			
			function(json,data,callback){
				update_team_stats(data.game_id,
								json.SoccerFeed.SoccerDocument.MatchData.TeamData,
								function(err,rs){
					callback(err,json,data);
				});
			},
			function(json,data,callback){
				update_substitutions(data.game_id,
								json.SoccerFeed.SoccerDocument.MatchData.TeamData,
								function(err,rs){
					callback(err,json,data);
				});
			},
			
			function(json,data,callback){
				update_lineup(data.game_id,
								json.SoccerFeed.SoccerDocument.MatchData.TeamData,
								function(err,rs){
					callback(err,json,data);
				});
			},
			function(json,data,callback){
				update_team_ref(data.game_id,
								json.SoccerFeed.SoccerDocument.Team,
								function(err,rs){
									callback(err,json,data);					
								});				
			},
			function(json,data,callback){
				update_player_stats(data.game_id,
								json.SoccerFeed.SoccerDocument.MatchData.TeamData,
								function(err,rs){
									callback(err,data);					
								});				
			}
		],
	function(err,result){
		callback(err,result);
	});
}
function open_file(the_file,done){
	var filepath = path.resolve('./data/'+the_file);
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

function update_match_data(data,done){
	
	pool.getConnection(function(err,conn){
		if(!err){
			conn.query("INSERT INTO "+config.database.optadb+".matchinfo\
					(game_id,competition_id,season_id,competition_symid,\
					matchday,matchtype,period,matchdate,result_type,result_winner,\
					referee,matchtime,match_timestamp,home_team,home_score,away_team,away_score,\
					venue_id,venue_name,venue_country,attendance,last_update)\
					VALUES\
					(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,NOW())\
					ON DUPLICATE KEY UPDATE\
					matchday = VALUES(matchday),\
					matchtype = VALUES(matchtype),\
					period = VALUES(period),\
					matchdate = VALUES(matchdate),\
					result_type = VALUES(result_type),\
					result_winner = VALUES(result_winner),\
					referee = VALUES(referee),\
					matchtime = VALUES(matchtime),\
					match_timestamp = VALUES(match_timestamp),\
					home_team = VALUES(home_team),\
					home_score = VALUES(home_score),\
					away_team = VALUES(away_team),\
					away_score = VALUES(away_score),\
					attendance = VALUES(attendance),\
					last_update = VALUES(last_update);",
				[data.game_id,
				 data.competition_id,
				 data.season_id,
				 data.symid,
				 data.matchday,
				 data.matchtype,
				 data.period,
				 data.matchdate,
				 data.result_type,
				 data.result_winner,
				 data.referee.First+' '+data.referee.Last,
				 data.matchtime,
				 data.matchdate,
				 data.home_team,
				 data.home_score,
				 data.away_team,
				 data.away_score,
				 data.venue.uID,
				 data.venue.Name,
				 data.venue.Country,
				 data.attendance],
				function(err,rs){
					console.log(S(this.sql).collapseWhitespace().s);
					conn.release();
						done(err,rs);
					
			});
		}else{
			done(new Error('db lost'),null);
		}
	});
}
function update_bookings(game_id,teams,done){
	pool.getConnection(function(err,conn){
		if(!err){
			async.eachSeries(teams,
			function(team,next){
				console.log(team.TeamRef);
				console.log(team.Booking);
				if(typeof team.Booking !== 'undefined'){
					if(!Array.isArray(team.Booking)){
						team.Booking = [team.Booking];
					}
					console.log('processing ',team.TeamRef,'bookings');
					async.eachSeries(team.Booking,function(booking,finish){
						conn.query("INSERT INTO "+config.database.optadb+".bookings\
							(game_id,team_id,card,card_type,event_id,event_number,\
								period,player_id,reason,time,uid)\
							VALUES\
							(?,?,?,?,?,?,?,?,?,?,?)\
							ON DUPLICATE KEY UPDATE\
							card = VALUES(card),\
							card_type = VALUES(card_type),\
							period = VALUES(period),\
							reason = VALUES(reason),\
							player_id= VALUES(player_id);",
							[
								game_id,
								team.TeamRef,
								booking.Card,
								booking.CardType,
								booking.EventID,
								booking.EventNumber,
								booking.Period,
								booking.PlayerRef,
								booking.Reason,
								booking.Time,
								booking.uID
							],
							function(err,rs){
								finish();
							});
					},function(err){
						next();
					});
				}else{
					next();
				}
			},function(err){
				conn.release();
				done(err,'ok');
			});
		}else{
			done(new Error('db lost'),null);
		}
	});
	
}
function update_goals(game_id,teams,done){
	pool.getConnection(function(err,conn){
		if(!err){
			async.eachSeries(teams,
			function(team,next){
				console.log(team.TeamRef);
				console.log(team.Goal);
				if(typeof team.Goal !== 'undefined'){
					
					console.log('processing ',team.TeamRef,'goals');
					if(!Array.isArray(team.Goal)){
						team.Goal = [team.Goal];
					}
					async.eachSeries(team.Goal,function(Goal,finish){
						var player_assist = "";
						if(typeof Goal.Assist !== 'undefined'){
							player_assist = Goal.Assist.PlayerRef;
						}
						conn.query("INSERT INTO "+config.database.optadb+".goals\
							(game_id,team_id,goal_type,event_id,event_number,\
								period,player_id,time,uid,assist_player_id)\
							VALUES\
							(?,?,?,?,?,?,?,?,?,?)\
							ON DUPLICATE KEY UPDATE\
							goal_type = VALUES(goal_type),\
							period = VALUES(period),\
							player_id= VALUES(player_id),\
							assist_player_id = VALUES(assist_player_id);",
							[
								game_id,
								team.TeamRef,
								Goal.Type,
								Goal.EventID,
								Goal.EventNumber,
								Goal.Period,
								Goal.PlayerRef,							
								Goal.Time,
								Goal.uID,
								player_assist
							],
							function(err,rs){
								
								finish();
							});
					},function(err){
						next();
					});
				}else{
					next();
				}
			},function(err){
				conn.release();
				done(err,'ok');
				
			});
		}else{
			done(new Error('db lost'),null);
		}
	});
	
}
function toDate(str){
	var td = str.substr(0,4)+'-'+str.substr(4,2)+'-'+str.substr(6,3)+str.substr(9,2)+':'+str.substr(11,2)+':'+str.substr(13,8);
	return td;
}

function update_team_stats(game_id,teams,done){
	pool.getConnection(function(err,conn){
		if(!err){
			async.eachSeries(teams,
			function(team,next){
				console.log(team.TeamRef);
				console.log(team.Stat);
				if(typeof team.Stat !== 'undefined'){
					console.log('processing ',team.TeamRef,'stats');
					if(!Array.isArray(team.Stat)){
						team.Stat = [team.Stat];
					}
					async.eachSeries(team.Stat,function(Stat,finish){
						conn.query("INSERT INTO "+config.database.optadb+".team_stats\
							(game_id,team_id,stats_name,stats_value)\
							VALUES\
							(?,?,?,?)\
							ON DUPLICATE KEY UPDATE\
							stats_name = VALUES(stats_name),\
							stats_value = VALUES(stats_value);",
							[
								game_id,
								team.TeamRef,
								Stat.Type,
								Stat['$t']
							],
							function(err,rs){
								finish();
							});
					},function(err){
						next();
					});
				}else{
					next();
				}
			},function(err){
				conn.release();
				done(err,'ok');
				
			});
		}else{
			done(new Error('db lost'),null);
		}
	});
}

function update_substitutions(game_id,teams,done){
	pool.getConnection(function(err,conn){
		if(!err){
			async.eachSeries(teams,
			function(team,next){
				console.log(team.TeamRef);
				console.log(team.Substitution);
				if(typeof team.Substitution !== 'undefined'){
					
					console.log('processing ',team.TeamRef,'Substitutions');
					if(!Array.isArray(team.Substitution)){
						team.Substitution = [team.Substitution];
					}
					//console.log(team);
					async.eachSeries(team.Substitution,function(Substitution,finish){
						console.log(Substitution);
						conn.query("INSERT INTO "+config.database.optadb+".substitutions\
							(game_id,team_id,SubOff,SubOn,event_id,event_number,\
								period,substitution_pos,reason,time,uid)\
							VALUES\
							(?,?,?,?,?,?,?,?,?,?,?)\
							ON DUPLICATE KEY UPDATE\
							SubOff = VALUES(SubOff),\
							SubOn = VALUES(SubOn),\
							period = VALUES(period),\
							reason = VALUES(reason),\
							substitution_pos= VALUES(substitution_pos);",
							[
								game_id,
								team.TeamRef,
								Substitution.SubOff,
								Substitution.SubOn,
								Substitution.EventID,
								Substitution.EventNumber,
								Substitution.Period,
								Substitution.SubstitutePosition,
								Substitution.Reason,
								Substitution.Time,
								Substitution.uID
							],
							function(err,rs){
								console.log(err);
								console.log(this.sql);
								finish();
							});
					},function(err){
						next();
					});
				}else{
					next();
				}
			},function(err){
				conn.release();
				done(err,'ok');
			});
		}else{
			done(new Error('db lost'),null);
		}
	});
	
}
function update_lineup(game_id,teams,done){

	pool.getConnection(function(err,conn){
		if(!err){
			async.eachSeries(teams,
			function(team,next){
				console.log(team.TeamRef);
				console.log(team.PlayerLineUp);
				if(typeof team.PlayerLineUp !== 'undefined'){
					
					
					//console.log(team);
					async.eachSeries(team.PlayerLineUp.MatchPlayer,function(player,finish){
						
						conn.query("INSERT INTO "+config.database.optadb+".lineup\
									(game_id,team_id,player_id,position,shirt_number,status)\
									VALUES\
									(?,?,?,?,?,?)\
									ON DUPLICATE KEY UPDATE\
									position = VALUES(position),\
									shirt_number = VALUES(shirt_number),\
									status = VALUES(status)",
							[
								game_id,
								team.TeamRef,
								player.PlayerRef,
								player.Position,
								player.ShirtNumber,
								player.Status
							],
							function(err,rs){
								//console.log(err);
								finish();
							});
					},function(err){
						next();
					});
				}else{
					next();
				}
			},function(err){
				conn.release();
				done(err,'ok');
				
			});
		}else{
			done(new Error('db lost'),null);
		}
	});
}

function update_team_ref(game_id,teams,done){
	console.log('update team ref ',game_id);
	pool.getConnection(function(err,conn){
		if(!err){
			async.eachSeries(teams,function(team,next){
				//console.log(team);
				async.waterfall([
					function(callback){
						conn.query("INSERT INTO "+config.database.optadb+".teamrefs\
									(game_id,\
									team_id,team_name,team_manager,\
									team_country,\
									last_update)\
									VALUES(\
									?,\
									?,?,?,?,\
									NOW()\
									)\
									ON DUPLICATE KEY UPDATE\
									team_id = VALUES(team_id),\
									team_name = VALUES(team_name),\
									team_manager = VALUES(team_manager),\
									team_country = VALUES(team_country),\
									last_update = VALUES(last_update)\
									",
							[
								game_id,
								team.uID,
								team.Name,
								team.TeamOfficial.PersonName.First+' '+team.TeamOfficial.PersonName.Last,
								team.Country
							],
							function(err,rs){
								callback(err,rs);
							});
					},
					function(rs,callback){
						async.eachSeries(team.Player,function(player,next_player){
							//inserting the player refs
							conn.query("INSERT INTO\
										"+config.database.optadb+".playerrefs\
										(game_id,team_id,player_id,name,position,last_update)\
										VALUES\
										(?,?,?,?,?,NOW())\
										ON DUPLICATE KEY UPDATE\
										name = VALUES(name),\
										position = VALUES(position),\
										last_update = VALUES(last_update);",
										[	game_id,
											team.uID,
											player.uID,
											player.PersonName.First+' '+player.PersonName.Last,
											player.Position
										],
										function(err,rs){
											next_player();
										});
						},function(err){
							callback(err,'done');
						});
					},
				],

				function(err,result){
					next();
				});


			},function(err){
				conn.release();
				done(err,'ok');
				
			});
		}else{
			done(new Error('db lost'),null);
		}
	});
}


function update_player_stats(game_id,teams,done){

	pool.getConnection(function(err,conn){
		if(!err){
			async.eachSeries(teams,
			function(team,next){
				console.log(team.TeamRef);
				
				if(typeof team.PlayerLineUp !== 'undefined'){
					
					//console.log(team);
					async.eachSeries(team.PlayerLineUp.MatchPlayer,function(player,finish){
						if(typeof player.Stat !== 'undefined'){
							if(!Array.isArray(player.Stat)){
								player.Stat = [player.Stat];
							}
							async.eachSeries(player.Stat,function(player_stats,next_stats){
								console.log(player.PlayerRef,player_stats);
								conn.query(	"INSERT INTO "+config.database.optadb+".player_stats\
											(game_id,team_id,player_id,stats_name,stats_value)\
											VALUES\
											(?,?,?,?,?)\
											ON DUPLICATE KEY UPDATE\
											stats_name = VALUES(stats_name),\
											stats_value = VALUES(stats_value);",
											[	game_id,
												team.TeamRef,
												player.PlayerRef,
											 	player_stats.Type,
											 	player_stats['$t']
											 ],
											function(err,rs){
												next_stats();
											});
							},function(err){
								finish();
							});
						}else{
							finish();
						}
					},function(err){
						next();
					});
				}else{
					next();
				}
			},function(err){
				conn.release();
				done(err,'ok');
				
			});
		}else{
			done(new Error('db lost'),null);
		}
	});
}