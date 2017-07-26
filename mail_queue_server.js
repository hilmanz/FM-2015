/**
 * mail queue server
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
var config = require('./config').config;
//our api libs
var users = require('./libs/services/users');
var team = require('./libs/services/team'); // soccer team
//var player = require('./libs/services/player'); //soccer player 
var gameplay = require('./libs/services/gameplay'); // gameplay service
var S = require('string');

//mysql pool
var pool  = mysql.createPool({
   host     : config.database.host,
   user     : config.database.username,
   password : config.database.password,
});


var app = express();
var RedisStore = require('connect-redis')(express);

// all environments
app.set('port', process.env.PORT || config.port);
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
app.get('/', routes.index);

//mysql pool
var pool  = mysql.createPool({
   host     : config.database.host,
   user     : config.database.username,
   password : config.database.password,
});


app.get('/getQueue',[],function(req,res){
	getQueue(req.query.since_id,function(err,rs){
		if(rs!=null){
			since_id = rs.id;
		}
		res.send(200,{
						status:1,
						data:rs,
						since_id:since_id
				}
		);	
	});
	
});

app.get('/updateQueue',[],function(req,res){
	updateQueue(req.query.id,req.query.status,function(err,updateOk){
		if(updateOk){
			res.send(200,{status:1,n_status:req.query.status,queue_id:req.query.id});
		}else{
			res.send(200,{status:0,n_status:req.query.status,queue_id:req.query.id});
		}
	});
});


http.createServer(app).listen(3199, function(){
  console.log('Express server listening on port 3199');
});


function getQueue(since_id,callback){
	if(since_id==null){
		since_id = 0;
	}
	pool.getConnection(function(err,conn){
		async.waterfall([
			function(cb){
				conn.query("SELECT * FROM ffgame.email_queue\
					 WHERE id > ? \
					 AND n_status = 0 \
					 ORDER BY id ASC LIMIT 1",[since_id],
					 function(err,rs){
					 	cb(err,rs);
				});
			},
			function(rs,cb){
				if(rs!=null && rs.length > 0){
					var id = rs[0].id;
				}
				conn.query("UPDATE ffgame.email_queue SET n_status=? WHERE id = ?",
						[status,id],
						 function(err,rs){
						 	console.log(S(this.sql).collapseWhitespace().s);
						 	conn.end(function(err){
						 		console.log(rs);
						 		callback(err,true);
						 		
						 	});
						 });
			}
		],

		function(err,rs){
			conn.end(function(err){
		 		if(rs!=null && rs.length > 0){
		 			callback(err,rs[0]);	
		 		}else{
		 			
		 			callback(err,null);
		 		}
		 		
		 	});
		});
		
	});
	
}
function updateQueue(id,status,callback){
	if(id!=null && status !=null){
		pool.getConnection(function(err,conn){
			conn.query("UPDATE ffgame.email_queue SET n_status=? WHERE id = ?",
						[status,id],
						 function(err,rs){
						 	console.log(S(this.sql).collapseWhitespace().s);
						 	conn.end(function(err){
						 		console.log(rs);
						 		callback(err,true);
						 		
						 	});
						 });
		});
	}else{
		callback(null,false);
	}
	
}
function accessDenied(req,res){
	res.send(401,'Access Denied');
}