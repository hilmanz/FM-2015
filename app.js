/**
 * Module dependencies.
 */

var express = require('express')
  , routes = require('./routes')
  , user = require('./routes/user')
  , http = require('http')
  , path = require('path');
var mysql = require('mysql');
var redis = require('redis');
var dummy_api_key = '1234567890';
var auth = require('./libs/api/auth');

var argv = require('optimist').argv;
var config = require('./config').config;

var league = 'epl';
if(typeof argv.league !== 'undefined'){
	switch(argv.league){
		case 'ita':
			console.log('Serie A Activated');
			config = require('./config.ita').config;
		break;
		case 'copa':
			console.log('COPA America Activated');
			config = require('./config.copa').config;
		break;
		default:
			console.log('EPL Activated');
			config = require('./config').config;
		break;
	}
	league = argv.league;

}





//our api libs
var users = require('./libs/services/users');
var team = require('./libs/services/team'); // soccer team
//var player = require('./libs/services/player'); //soccer player 
var gameplay = require('./libs/services/gameplay'); // gameplay service
var transfer = require('./libs/services/transfer'); //transfer window service
gameplay.setLeague(league);

//mysql pool
var pool  = mysql.createPool({
   host     : config.database.host,
   user     : config.database.username,
   password : config.database.password,
});
team.setConfig(config);
team.setPool(pool);
gameplay.setConfig(config);
gameplay.setPool(pool);
auth.setConfig(config);
auth.setPool(pool);
users.setConfig(config);
users.setPool(pool);
users.setLeague(league);
transfer.setConfig(config);
transfer.setPool(pool);
transfer.setLeague(league);


var app = express();
//for session
var RedisStore = require('connect-redis')(express);

// all environments
var app_port =  config.port;

if(typeof argv.port !== 'undefined'){
	app_port = argv.port;
}


app.set('port', app_port);
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

gameplay.setRedisClient(client);


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

app.get('/', routes.index);

app.get('/fixtures/', [auth.canAccess],gameplay.fixtures);
app.get('/fixtures', [auth.canAccess],gameplay.fixtures);
app.get('/match/list',[auth.canAccess],gameplay.fixtures);

app.get('/inbox/:team_id',[auth.canAccess],gameplay.getInbox);
app.get('/players/:team_id',[auth.canAccess],team.getPlayers);
app.get('/top_players/:total',[auth.canAccess],team.getMasterTopPlayers);
app.get('/teams', [auth.canAccess],team.getTeams);
app.get('/team/get/:fb_id',[auth.canAccess],team.getUserTeam);
app.get('/points/:fb_id',[auth.canAccess],team.getUserTeamPoints);
app.get('/teams/:id',[auth.canAccess],team.getTeamById);
app.get('/match/results/:game_id',[auth.canAccess],gameplay.match_results);
app.get('/match/user_match_results/:game_team_id/:game_id',[auth.canAccess],gameplay.match_results_for_user_team);
app.get('/next_match/:team_id',[auth.canAccess],gameplay.next_match);
app.get('/best_match/:game_team_id',[auth.canAccess],gameplay.best_match);
app.get('/best_player/:game_team_id',[auth.canAccess],gameplay.best_player);
app.get('/player/:id',[auth.canAccess],gameplay.player_data);
app.post('/user/register',[auth.canAccess],users.register);
app.post('/create_team',[auth.canAccess],team.create);
app.get('/nego/:nego_id',[auth.canAccess],transfer.nego);
app.post('/offer/:nego_id',[auth.canAccess],transfer.offer);
app.get('/user/financial_statement',user.list);
app.get('/game/rank',user.list);
app.post('/sale',[auth.canAccess],gameplay.sale);
//app.post('/buy',[auth.canAccess],gameplay.buy);
app.post('/buy',[auth.canAccess],transfer.buy);
app.post('/add_expenditure',[auth.canAccess],gameplay.add_expenditure);
app.get('/cash/:fb_id', [auth.canAccess],gameplay.getCash);
app.get('/getEcashUrl', [auth.canAccess],gameplay.getEcashUrl);
app.get('/ecash_validate', [auth.canAccess],gameplay.EcashValidate);
app.get('/bet_info/:game_id',[auth.canAccess],gameplay.bet_info);


app.get('/transfer_window',[auth.canAccess],gameplay.getTransferWindow);

app.get('/setInputAttempt',[auth.canAccess],gameplay.setInputAttempt);
app.get('/getInputAttempt',[auth.canAccess],gameplay.getInputAttempt);
app.get('/storeToTmp',[auth.canAccess],gameplay.storeToRedisTmp);
app.get('/getFromTmp',[auth.canAccess],gameplay.getFromRedisTmp);
app.get('/getTmpKeys',[auth.canAccess],gameplay.getRedisKeys);
app.post('/redeemCode',[auth.canAccess],gameplay.redeemCode);

app.get('/test',function(req,res){
	client.get(req.query.access_token,function(err,rs){
		console.log(rs);
	});
	client.ttl(req.query.access_token,function(err,rs){
		console.log('ttl',rs);
	});
	//console.log(req.query.access_token,req.session.access_token);
	res.send(200,'');

});


app.post('/team/lineup/save',[auth.canAccess],gameplay.setLineup);
app.get('/team/lineup/:id',[auth.canAccess],gameplay.getLineup);
//app.get('/team/getlineup/:id/:matchday',[auth.canAccess],gameplay.getLineupByMatchday);
app.get('/team/list/:id',[auth.canAccess],gameplay.getPlayers);
app.get('/team/player/:game_team_id/:id',[auth.canAccess],gameplay.player_team_data);
app.get('/team/sponsors/:game_team_id',[auth.canAccess],gameplay.getActiveSponsors);
app.get('/team/budget/:game_team_id',[auth.canAccess],gameplay.getBudget);
app.get('/official/list/:game_team_id',[auth.canAccess],gameplay.official_list);
app.get('/official/market/:game_team_id',[auth.canAccess],gameplay.official_market);
app.post('/official/hire',[auth.canAccess],gameplay.hire_staff);
app.post('/official/fire',[auth.canAccess],gameplay.fire_staff);
app.get('/sponsorship/list/:team_id',[auth.canAccess],gameplay.getSponsors);
app.post('/sponsorship/apply',[auth.canAccess],gameplay.applySponsorship);
app.get('/finance/:game_team_id',[auth.canAccess],gameplay.financial_statements);
app.get('/weekly_finance/:game_team_id/:week',[auth.canAccess],gameplay.weekly_finance);
app.get('/last_earning/:game_team_id',[auth.canAccess],gameplay.last_earning);
app.get('/last_expenses/:game_team_id',[auth.canAccess],gameplay.last_expenses);
app.get('/venue/:team_id',[auth.canAccess],gameplay.get_venue);
app.get('/leaderboard',[auth.canAccess],gameplay.leaderboard);
app.get('/matchstatus/:matchday',[auth.canAccess],gameplay.matchstatus);
app.get('/livestats/:game_id',[auth.canAccess],gameplay.livestats);
app.get('/livegoals/:game_id',[auth.canAccess],gameplay.livegoals);
app.get('/livematches/:matchday/:league',[auth.canAccess],gameplay.livematches);
app.get('/standings/:league',[auth.canAccess],gameplay.standings);
app.get('/apply_perk/:game_team_id/:perk_id',[auth.canAccess],gameplay.apply_perk);
app.get('/check_perk/:game_team_id/:perk_id',[auth.canAccess],gameplay.check_perk);

app.get('/postponed',[auth.canAccess],gameplay.setPostponedStatus);
app.get('/postponed_status',[auth.canAccess],gameplay.getPostponedStatus);
app.get('/checkSession',function(req,res){
	auth.checkSession(req,res,function(is_valid){
		if(is_valid){
			res.send(200,{status:1});
		}else{
			res.send(200,{status:0});
		}
	});
});
app.post('/auth',auth.authenticate);
app.get('/ping',function(req,res){
	res.send(200,{status:1,message:'Server Alive'});
});


http.createServer(app).listen(app.get('port'), function(){
  console.log('Express server listening on port ' + app.get('port'));
});



function accessDenied(req,res){
	res.send(401,'Access Denied');
}