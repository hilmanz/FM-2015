/**
worker for automatic formation setup
**/
/////THE MODULES/////////
var config = require('./config').config;
var async = require('async');
var mysql = require('mysql');
var S = require('string');
var nodemailer = require('nodemailer');
var smtpTransport = require('nodemailer-smtp-transport');
var argv = require('optimist').argv;
/////DECLARATIONS/////////
var frontend_schema = config.database.frontend_schema;
var pool  = mysql.createPool({
		host     : config.database.host,
		user     : config.database.username,
		password : config.database.password,
	});


var limit = 10;


var league = 'epl';
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
	league = argv.league;

}


pool.getConnection(function(err, conn){

	async.waterfall([
		function(cb){
			get_current_matchday(conn,function(err,current_matchday){
				cb(err,current_matchday);
			});
		},
		function(current_matchday,cb){
			process_pro_team(conn,current_matchday,function(err){
				cb(err);
			});
		}
		
	], 
	function(err){
		conn.release();
		pool.end(function(err){
			console.log('done');
		});
	});

});


function get_current_matchday(conn,done){
	conn.query("SELECT MIN(matchday) AS current_matchday FROM "+config.database.database+".game_fixtures WHERE is_processed = 0;",
				[],function(err,rs){
					done(err,rs[0].current_matchday);
				});
}


function process_pro_team(conn,current_matchday,done){
	var has_data = true;
	var last_id = 0;
	var limit = 10;
	async.whilst(
	    function () { return has_data; },
	    function (callback) {
	        conn.query("SELECT c.* FROM fantasy.users a INNER JOIN "+config.database.database+".game_users b\
					ON a.fb_id = b.fb_id \
					INNER JOIN "+config.database.database+".game_teams c\
					ON c.user_id = b.id\
					WHERE paid_member = 1 AND paid_member_status=1 \
					AND c.id > "+last_id+" ORDER BY c.id ASC LIMIT 10;",
					[],function(err,rs){
						console.log(S(this.sql).collapseWhitespace().s);

	        	if(rs.length==0){
	        		has_data = false;
	        		callback(null);
	        	}else{
	        		last_id = rs[rs.length-1].id;
	        		console.log('last_id:',last_id);
	        		setFormation(conn,rs,current_matchday,function(err,rs){
	        			callback(err);
	        		});
	        	}
	        });
	    },
	    function (err) {
	        // 5 seconds have passed
	        done(err);
	    }
	);
}

function setFormation(conn,teams,current_matchday,done){
	async.eachSeries(teams,function(item,next){
		console.log('TEAM : ',item);
		setTeamFormation(conn,item,current_matchday,function(err,rs){
			next();
		});
	},function(err){
		done(err);
	});
}
function setTeamFormation(conn,team,current_matchday,done){
	async.waterfall([
		function(cb){
			//get team's last matchday
			conn.query("SELECT MAX(matchday) AS last_matchday \
						FROM "+config.database.database+".game_team_lineups \
						WHERE game_team_id=?",[team.id],
						function(err,rs){
							if(typeof rs !== 'undefined' && rs.length > 0){
								//console.log(team.id,'lastmatch:',rs[0].last_matchday);
								console.log(S(this.sql).collapseWhitespace().s);
								cb(err,rs[0].last_matchday);	
							}else{
								cb(err,0);
							}
						});
		},
		function(last_matchday,cb){
			console.log('pass here');
			console.log(last_matchday)
			//console.log(current_matchday,last_matchday,'--->');
			if(current_matchday > last_matchday){
				console.log('team#',team.id,'last_match:',last_matchday,' - current match : ',current_matchday);
			

				conn.query("INSERT INTO "+config.database.database+".game_team_lineups\
							(game_team_id,player_id,position_no,matchday)\
							SELECT a.game_team_id,a.player_id,position_no,"+current_matchday+" AS matchday \
							FROM "+config.database.database+".game_team_lineups a\
							INNER JOIN ffgame.game_team_players b\
							ON a.game_team_id = b.game_team_id AND a.player_id = b.player_id\
							WHERE a.game_team_id=? AND  matchday="+last_matchday+";",[team.id],function(err,rs){
								console.log(S(this.sql).collapseWhitespace().s);
								cb(err,rs);
							});
			}else{
				cb(null,null);
			}
		}
	],
	function(err,rs){
		done(err);
	});
}