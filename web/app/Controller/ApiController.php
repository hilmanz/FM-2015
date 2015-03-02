<?php
/**
 * API controller.
 *
 * This file will serves as API endpoint
 *
 * PHP 5
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (cd) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @package       app.Controller
 * @since         CakePHP(tm) v 0.2.9
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
App::uses('AppController', 'Controller');
App::uses('Sanitize', 'Utility');
require_once APP . 'Vendor' . DS. 'Thumbnail.php';

//we use password-hash for dealing with wordpress's password hash
require_once APP.DS.'Vendor'.DS.'password-hash.php';

class ApiController extends AppController {

	public $components = array('ActivityLog');

/**
 * Controller name
 *
 * @var string
 */
	public $name = 'Api';
	public $uses = array();
	private $weekly_balances = null;
	private $expenditures = null;
	private $starting_budget = 0;
	private $finance_total_items_raw = null;
	private $tickets_sold = null;
	private $league;
	private $ffgamedb = 'ffgame';
	private $ffgamestatsdb = 'ffgame_stats';

	public function beforeFilter(){
		parent::beforeFilter();
		if(@$_REQUEST['league']=='ita'){
			$this->league = 'ita';
			$this->ffgamedb = 'ffgame_ita';
			$this->ffgamestatsdb = 'ffgame_stats_ita';
		}else{
			$this->league = 'epl';
			$this->ffgamedb = 'ffgame';
			$this->ffgamestatsdb = 'ffgame_stats';
		}

		$this->User->bindModel(array(
						'hasOne'=>array(
							'Team'=>array(
								'conditions' => array(
									'Team.league' => $this->league)
								)
						)
					)
				);
	}
	public function auth(){
		$fb_id = $this->request->query('fb_id');
		$user = $this->User->findByFb_id($fb_id);
		$refresh_at = intval(@$this->request->query['refresh']);
		$check_current_session = $this->Session->read('API_CURRENT_ACCESS_TOKEN');
		if($refresh_at == 1){
			$this->Session->write('API_CURRENT_ACCESS_TOKEN',null);
		}
		if(strlen($fb_id) > 2 && $this->validateAPIAccessToken($check_current_session) && $refresh_at==0){
			$this->gameApiAccessToken = $check_current_session;
			$api_session = $this->readAccessToken();
			$session_fb_id = $api_session['fb_id'];
			CakeLog::write('debug', $fb_id.' == '.$session_fb_id);
			if($fb_id==$session_fb_id){
				$this->set('response',array('status'=>1,'access_token'=>$check_current_session));	
			}else{
				$this->set('response',array('status'=>400,'error'=>'user not found'));
			}
			
		}else{
			CakeLog::write('debug', $fb_id);
			if(strlen($fb_id)>2 && isset($user['User'])){
				$rs = $this->Apikey->findByApi_key($this->request->query['api_key']);
				if(isset($rs['Apikey']) && $rs['Apikey']['api_key']!=null){
					$access_token = encrypt_param(serialize(array('fb_id'=>$fb_id,
															'api_key'=>$rs['Apikey']['api_key'],
															  'valid_until'=>time()+24*60*60)));

					$this->redisClient->set($access_token,serialize(array('api_key'=>$rs['Apikey']['api_key'],
																		  'fb_id'=>$fb_id)));
					$this->redisClient->expire($access_token,24*60*60);//expires in 1 day
					$this->Session->write('API_CURRENT_ACCESS_TOKEN',$access_token);
					$this->set('response',array('status'=>1,'access_token'=>$access_token));
				}else{
					$this->set('response',array('status'=>403,'error'=>'invalid api_key'));
				}
			}else{
				$this->set('response',array('status'=>400,'error'=>'user not found'));
			}
		}
		CakeLog::write('MOBILE', 'auth - '.$fb_id.' - '.$this->Session->read('API_CURRENT_ACCESS_TOKEN'));
		$this->gameApiAccessToken = $this->Session->read('API_CURRENT_ACCESS_TOKEN');
		$api_session = $this->readAccessToken();
		$session_fb_id = $api_session['fb_id'];
		if($fb_id!=$session_fb_id){
			CakeLog::write('AUTH_ERROR', 'auth - '.$fb_id.' - '.$session_fb_id.' - '.$this->Session->read('API_CURRENT_ACCESS_TOKEN'));
			$this->gameApiAccessToken = null;
			$this->Session->write('API_CURRENT_ACCESS_TOKEN',null);
			CakeLog::write('AUTH_ERROR', 'auth - '.$fb_id.' - '.$session_fb_id.' - '.$this->Session->read('API_CURRENT_ACCESS_TOKEN').' DELETED');

		}
		$this->render('default');
	}
	//api for login using email & password
	public function login(){
		$email = Sanitize::clean($this->request->query['email']);
		$password = Sanitize::clean($this->request->query['password']);
		
		if(strlen($email) > 0){
			$user = $this->User->findByEmail($email);
			$passHasher 	= new PasswordHash(8, true);
			$check_password = $passHasher->CheckPassword($password.$user['User']['secret'] ,
									$user['User']['password']);
			if($check_password){
				$rs = $this->Apikey->findByApi_key($this->request->query['api_key']);
				$fb_id = $user['User']['fb_id'];
				if(isset($rs['Apikey']) && $rs['Apikey']['api_key']!=null){
					$access_token = encrypt_param(serialize(array('fb_id'=>$fb_id,
															'api_key'=>$rs['Apikey']['api_key'],
															  'valid_until'=>time()+24*60*60)));
					
					$this->redisClient->set($access_token,serialize(array('api_key'=>$rs['Apikey']['api_key'],
																		  'fb_id'=>$fb_id)));
					$this->redisClient->expire($access_token,24*60*60);//expires in 1 day
					$this->Session->write('API_CURRENT_ACCESS_TOKEN',$access_token);
					$this->set('response',array('status'=>1,'access_token'=>$access_token));
				}else{
					$this->set('response',array('status'=>403,'error'=>'invalid api_key'));
				}
			}else{
				$this->set('response',array('status'=>401,'error'=>'Wrong username / password'));
			}
		}else{
			$this->set('response',array('status'=>400,'error'=>'user not found'));
		}
		$this->render('default');
	}
	public function index(){
		$this->set('response',array('status'=>1));
		$this->render('default');
	}
	
	public function team(){
		$api_session = $this->readAccessToken();
		$fb_id = $api_session['fb_id'];

		$user = $this->User->findByFb_id($fb_id);
		if(strlen($user['User']['avatar_img'])<2){
			$user['User']['avatar_img'] = "http://graph.facebook.com/".$fb_id."/picture";
		}else{
			$user['User']['avatar_img'] = Configure::read('avatar_web_url').'120x120_'.$user['User']['avatar_img'];
		}
		$game_team = $this->Game->getTeam($fb_id);
		$this->loadModel('Point');

		$point = $this->Point->findByTeam_id($user['Team']['id']);

		$response['user'] = array('id'=>$user['User']['id'],
									'fb_id'=>$user['User']['fb_id'],
									'name'=>$user['User']['name'],
									'avatar_img'=>$user['User']['avatar_img']);

		$response['stats']['points'] = ceil(floatval(@$point['Point']['points']));
		$response['stats']['rank'] = intval(@$point['Point']['rank']);



		//list of players
		$players = $this->Game->get_team_players($fb_id);
		$response['players'] = $players;

		//lineup starters
		$lineup = $this->Game->getLineup($game_team['id']);
		$response['lineup_settings'] = $lineup;
		
		//budget
		$budget = $this->Game->getBudget($game_team['id']);
		
		$response['budget'] = $budget;
		$response['stats']['club_value'] = intval($budget) + $response['stats']['points'];
		//club
		$club = $this->Team->findByUser_id($user['User']['id']);
		
		$response['club'] = array('id'=>$club['Team']['id'],
									'team_name'=>$club['Team']['team_name'],
									'team_id'=>$club['Team']['team_id'],
								  );

		//get original club
		$original_club = $this->Game->getClub($game_team['team_id']);
		
		$response['original_club'] = $original_club;

		$next_match = $this->Game->getNextMatch($game_team['team_id']);
		$next_match['match']['home_original_name'] = $next_match['match']['home_name'];
		$next_match['match']['away_original_name'] = $next_match['match']['away_name'];
		if($next_match['match']['home_id']==$game_team['team_id']){
			$next_match['match']['home_name'] = $club['Team']['team_name'];
		}else{
			$next_match['match']['away_name'] = $club['Team']['team_name'];
		}
		$next_match['match']['match_date_ts'] = strtotime($next_match['match']['match_date']);
		$this->getCloseTime($next_match);

		$response['next_match'] = array('game_id'=>$next_match['match']['game_id'],
										'home_name'=>$next_match['match']['home_name'],
										'away_name'=>$next_match['match']['away_name'],
										'home_original_name'=>$next_match['match']['home_original_name'],
										'away_original_name'=>$next_match['match']['away_original_name'],
										'match_date'=>date("Y-m-d H:i:s",strtotime($next_match['match']['match_date'])),
										'match_date_ts'=>strtotime($next_match['match']['match_date'])
										);

		//match venue
		$match_venue = $this->Game->getVenue($next_match['match']['home_id']);
		$response['match_venue'] = $match_venue;

		//best match
		$best_match = $this->Game->getBestMatch($game_team['id']);
		$team_id = $game_team['team_id'];
		
		if($best_match['status']==0){
			$this->set('best_match','N/A');
			$response['stats']['best_match'] = 'N/A';
		}else{
			$best_match['data']['points'] = ceil($best_match['data']['points']);
			if($best_match['data']['match']['home_id']==$team_id){
				$against = $best_match['data']['match']['away_name'];
			}else if($best_match['data']['match']['away_id']==$team_id){
				$against = $best_match['data']['match']['home_name'];
			}
			
			$response['stats']['best_match'] = "VS. {$against} (+{$best_match['data']['points']})";
		}

		//last earnings
		$rs = $this->Game->getLastEarnings($game_team['id']);
		if($rs['status']==1){
			$this->set('last_earning',$rs['data']['total_earnings']);
			$response['stats']['last_earning'] = $rs['data']['total_earnings'];
		}else{
			$response['stats']['last_earning'] = 0;
		}

		//best player
		$rs = $this->Game->getBestPlayer($game_team['id']);
		
		if($rs['status']==1){
			$this->set('best_player',$rs['data']);
			$response['stats']['best_player'] = $rs['data'];
		}

		//close time
		$response['close_time'] = $this->closeTime;
		//can updte formation
		if($this->closeTime > time() && $this->openTime < time()){
			$response['can_update_formation'] = 1;	
		}else{
			$response['can_update_formation'] = 0;
		}
		
		$this->set('response',array('status'=>1,'data'=>$response));
		$this->render('default');
	}
	public function save_formation(){
		$this->loadModel('Team');
		$this->loadModel('User');
		$this->loadModel('Info');

		$api_session = $this->readAccessToken();
		$fb_id = $api_session['fb_id'];
		$user = $this->User->findByFb_id($fb_id);
		$game_team = $this->Game->getTeam($fb_id);


		//can updte formation
		$can_update_formation = true;


		
		$next_match = $this->Game->getNextMatch($game_team['team_id']);
		$next_match['match']['home_original_name'] = $next_match['match']['home_name'];
		$next_match['match']['away_original_name'] = $next_match['match']['away_name'];

		if($next_match['match']['home_id']==$game_team['team_id']){
			$next_match['match']['home_name'] = $club['Team']['team_name'];
		}else{
			$next_match['match']['away_name'] = $club['Team']['team_name'];
		}
		$next_match['match']['match_date_ts'] = strtotime($next_match['match']['match_date']);
		$this->getCloseTime($next_match);


		
		if(time() > $this->closeTime['ts'] && Configure::read('debug') == 0){
		    
		    $can_update_formation = false;
		    if(time() > $this->openTime){
		       
		        $can_update_formation = true;
		    }
		}else{
		    if(time() < $this->openTime){
		       
		        $can_update_formation = false;
		    }
		}

		if($can_update_formation){
			
			if($this->request->is('post')){
				
				

				$formation = $this->request->data['formation'];

				$players = array();
				foreach($this->request->data as $n=>$v){
					if(eregi('player-',$n)&&$v!=0){
						$players[] = array('player_id'=>str_replace('player-','',$n),'no'=>intval($v));
					}
				}
				$lineup = $this->Game->setLineup($game_team['id'],$formation,$players);
				
				if($lineup['status']==1){
					$msg = "@p1_".$user['User']['id']." telah menentukan formasinya.";
					$this->Info->write('set formation',$msg);
					$this->set('response',array('status'=>1,'message'=>'Formation is been saved successfully !'));
				}else{
					$this->set('response',array('status'=>0,'error'=>'There is an error in formation setup !'));
				}
				
			}else{
				$this->set('response',array('status'=>404,'error'=>'method not found'));
			}
			
		}else{
			$this->set('response',array('status'=>0,'error'=>'you cannot update formation at these moment, please wait until the matches is over.'));
		}

		$this->render('default');
	}
	public function club(){

		$this->loadModel('Point');

		$api_session = $this->readAccessToken();
		$fb_id = $api_session['fb_id'];
		
		$user = $this->User->findByFb_id($fb_id);
		

		CakeLog::write('AUTH_ERROR', 'browse - '.$fb_id.' - club - '.$this->request->query['access_token']);

		if(strlen($user['User']['avatar_img'])<2){
			$user['User']['avatar_img'] = "http://graph.facebook.com/".$fb_id."/picture";
		}else{
			$user['User']['avatar_img'] = Configure::read('avatar_web_url').'120x120_'.$user['User']['avatar_img'];
		}
		$game_team = $this->Game->getTeam($fb_id);
		
		$response = array();

		$point = $this->Point->findByTeam_id($user['Team']['id']);

		$response['user'] = array('id'=>$user['User']['id'],
									'fb_id'=>$user['User']['fb_id'],
									'name'=>$user['User']['name'],
									'avatar_img'=>$user['User']['avatar_img']);

		$response['stats']['points'] = ceil(floatval(@$point['Point']['points']) + floatval(@$point['Point']['extra_points']));
		$response['stats']['rank'] = intval(@$point['Point']['rank']);

		//budget
		$budget = $this->Game->getBudget($game_team['id']);
		$response['budget'] = $budget;

		$response['stats']['club_value'] = intval($budget) + $response['stats']['points'];

		//club
		$conditions = array('conditions' => array(
												'user_id'=>$user['User']['id'], 
												'league'=>$this->league)
							);

		$club = $this->Team->find('first', $conditions);
		$response['club'] = array('id'=>$club['Team']['id'],
									'team_name'=>$club['Team']['team_name'],
									'team_id'=>$club['Team']['team_id'],
								  );

		//get original club
		$original_club = $this->Game->getClub($club['Team']['team_id']);
		$this->set('original',$original_club);
		$response['original_club'] = $original_club;

		//list of players
		$players = $this->Game->get_team_players($fb_id);
		$response['game_team_id'] = intval($game_team['id']);

		if($players == null)
		{
			$this->set('response', array('status'=>1,'data'=> $response));
			$this->render('default');
			return ;
		}

		foreach($players as $n=>$p){
			$last_performance = floatval($p['last_performance']);
			$performance_bonus = getTransferValueBonus($last_performance,intval($p['transfer_value']));
			$player[$n]['base_transfer_value'] = $p['transfer_value'];
			$players[$n]['transfer_value'] = intval($p['transfer_value']) + $performance_bonus;

		}

		$response['players'] = $players;
		
		$best_players = subval_rsort($players,'points');

		if($best_players[0]['points'] == 0){
			$best_players = array();
		}
		$response['best_players'] = $best_players;
		if(sizeof($best_players)==0){
			$response['stats']['best_player'] = new stdClass();
		}
		//players weekly salaries
		$weekly_salaries = 0;
		foreach($players as $p){
			$weekly_salaries += intval(@$p['salary']);
		}

		//lineup starters
		$lineup = $this->Game->getLineup($game_team['id']);
		$response['lineup_settings'] = $lineup;



		//list of staffs
		//get officials

		$officials = $this->Game->getAvailableOfficials($game_team['id']);
		
		$staffs = array();
		foreach($officials as $official){
			if(isset($official['hired'])){
				$staffs[] = $official;
			}
		}
		
		//staff's weekly salaries
		foreach($staffs as $p){
			$weekly_salaries += intval(@$p['salary']);
		}
		$response['weekly_salaries'] = $weekly_salaries;

		$response['staffs'] = $staffs;

		//financial statements
		$finance = $this->getFinancialStatements($fb_id);
		$financial_statement['finance'] = $finance;
		$financial_statement['weekly_balances'] = $this->weekly_balances;
		$financial_statement['total_items'] = $this->finance_total_items_raw;
		$financial_statement['tickets_sold'] = $this->tickets_sold;
		//last earnings
		$rs = $this->Game->getLastEarnings($game_team['id']);
		if($rs['status']==1){
			$financial_statement['last_earning'] = $rs['data']['total_earnings'];
		}else{
			$financial_statement['last_earning'] = 0;
		}

		//last expenses
		$rs = $this->Game->getLastExpenses($game_team['id']);
		if($rs['status']==1){
			$financial_statement['last_expenses'] = $rs['data']['total_expenses'];
		}else{
			$financial_statement['last_expenses'] = 0;
		}
		$financial_statement['expenditures'] = $this->expenditures;
		$financial_statement['starting_budget'] = $this->starting_budget;

		$response['finance'] = $finance;
		$response['finance_details'] = $financial_statement;

		$next_match = $this->Game->getNextMatch($game_team['team_id']);
		$next_match['match']['home_original_name'] = $next_match['match']['home_name'];
		$next_match['match']['away_original_name'] = $next_match['match']['away_name'];

		if($next_match['match']['home_id']==$game_team['team_id']){
			$next_match['match']['home_name'] = $club['Team']['team_name'];
		}else{
			$next_match['match']['away_name'] = $club['Team']['team_name'];
		}

		$next_match['match']['match_date_ts'] = strtotime($next_match['match']['match_date']);
		$next_match['match']['last_match_ts'] = strtotime($next_match['match']['last_match']);

		$this->getCloseTime($next_match);

		$response['next_match'] = array('game_id'=>$next_match['match']['game_id'],
										'home_name'=>$next_match['match']['home_name'],
										'away_name'=>$next_match['match']['away_name'],
										'home_original_name'=>$next_match['match']['home_original_name'],
										'away_original_name'=>$next_match['match']['away_original_name'],
										'match_date'=>date("Y-m-d H:i:s",strtotime($next_match['match']['match_date'])),
										'match_date_ts'=>strtotime($next_match['match']['match_date'])
										);
		//match venue
		$match_venue = $this->Game->getVenue($next_match['match']['home_id']);
		$response['match_venue'] = $match_venue;

		//best match
		$best_match = $this->Game->getBestMatch($game_team['id']);
		$team_id = $game_team['team_id'];
		$against = "";
		if($best_match['status']==0){
			$this->set('best_match','N/A');
			$response['stats']['best_match'] = 'N/A';
		}else{
			$best_match['data']['points'] = ceil($best_match['data']['points']);
			if(@$best_match['data']['match']['home_id']==$team_id){
				$against = @$best_match['data']['match']['away_name'];
			}else if(@$best_match['data']['match']['away_id']==$team_id){
				$against = @$best_match['data']['match']['home_name'];
			}
			
			$response['stats']['best_match'] = "VS. {$against} (+{$best_match['data']['points']})";
		}

		//last earnings
		$rs = $this->Game->getLastEarnings($game_team['id']);
		if($rs['status']==1){
			$this->set('last_earning',$rs['data']['total_earnings']);
			$response['stats']['last_earning'] = $rs['data']['total_earnings'];
		}else{
			$response['stats']['last_earning'] = 0;
		}

		//best player
		$rs = $this->Game->getBestPlayer($game_team['id']);
		
		if($rs['status']==1){
			$this->set('best_player',$rs['data']);
			$response['stats']['best_player'] = $rs['data'];
		}

		//close time
		$response['close_time'] = $this->closeTime;

		//can updte formation
		if($this->closeTime > time() && $this->openTime < time()){
			$response['can_update_formation'] = 1;	
		}else{
			$response['can_update_formation'] = 0;
		}

		//weekly points and weekly balances

		//for weekly points, make sure the points from other player are included
		$this->loadModel('Weekly_point');
		$this->Weekly_point->virtualFields['TotalPoints'] = 'SUM(Weekly_point.points + Weekly_point.extra_points)';
		$options = array('fields'=>array('Weekly_point.id', 'Weekly_point.team_id', 
							'Weekly_point.game_id', 'Weekly_point.matchday', 'Weekly_point.matchdate', 
							'SUM(Weekly_point.points + Weekly_point.extra_points) AS TotalPoints', 'Team.id', 'Team.user_id', 
							'Team.team_id','Team.team_name'),
			'conditions'=>array('Weekly_point.team_id'=>$club['Team']['id'], 'Weekly_point.league' => $this->league),
	        'limit' => 100,
	        'group' => 'Weekly_point.matchday',
	        'order' => array(
	            'matchday' => 'asc'
	        ));
		$weekly_points = $this->Weekly_point->find('all',$options);
		if(sizeof($weekly_points) > 0){
			$weekly_team_points = array();
			while(sizeof($weekly_points) > 0){
				$p = array_shift($weekly_points);
				$weekly_team_points[] = array(
						'game_id'=>$p['Weekly_point']['game_id'],
						'matchday'=>$p['Weekly_point']['matchday'],
						'matchdate'=>$p['Weekly_point']['matchdate'],
						'points'=>@ceil($p[0]['TotalPoints'])
					);
			}
		}else{
			$weekly_team_points[] = array(
						'game_id'=>'',
						'matchday'=>@$p['Weekly_point']['matchday'],
						'matchdate'=>@$p['Weekly_point']['matchdate'],
						'points'=>@$p[0]['TotalPoints']
					);
		}
		

		unset($weekly_points);


		$response['weekly_stats']['balances'] = $financial_statement['weekly_balances'];
		$response['weekly_stats']['points'] = $weekly_team_points;


		//matches
		$matches = $this->getMatches($game_team['id'],$game_team['team_id'],
										$weekly_team_points,
										$financial_statement['expenditures'],
										$financial_statement['tickets_sold']);

		$response['previous_matches'] = $matches;


		//user's coin
		//get recent cash
		$response['coins'] = intval($this->Game->getCash($fb_id));
		$this->set('response',array('status'=>1,'data'=>$response));
		$this->render('default');
	}

	public function team_points()
	{
		$this->layout = 'ajax';
		$this->loadModel('Weekly_point');

		$fb_id = Sanitize::clean($this->request->query('fb_id'));
		$current_matchday = Sanitize::clean($this->request->query('gameweek'));
		$user = $this->User->findByFb_id($fb_id);

		$session_id = Configure::read('FM_SESSION_ID');

		if($this->request->query('gameweek') == NULL)
		{
			//current matchday
			$matchday = $this->Game->query("SELECT matchday FROM ".$this->ffgamedb.".game_fixtures a
												 WHERE period='FullTime' AND is_processed = 1 
												 AND session_id = '{$session_id}'
												 ORDER BY matchday DESC LIMIT 1");
			$current_matchday = $matchday[0]['a']['matchday'];
		}
		Cakelog::write('debug', 'Api.team_points matchday:'.json_encode($matchday));

		$options = array('fields'=>'game_id',
			'conditions'=>array('Weekly_point.team_id'=>$user['Team']['id'],
								'Weekly_point.league'=>$_SESSION['league'],
								'Weekly_point.matchday'=>$current_matchday),
	        'limit' => 1);
		$game_id = $this->Weekly_point->find('all',$options);

		$current_game_id = $game_id[0]['Weekly_point']['game_id'];

		$game_team_id = $this->Game->query("SELECT b.id FROM ".$this->ffgamedb.".game_users a 
											INNER JOIN ".$this->ffgamedb.".game_teams b ON a.id = b.user_id 
											WHERE a.fb_id = '{$fb_id}' LIMIT 1");
		Cakelog::write('debug', 'Api.team_points '.json_encode($game_team_id).' '.json_encode($game_id));

		$players = $this->Game->getMatchDetailsByGameTeamId($game_team_id[0]['b']['id'],$current_game_id);

		CakeLog::write('debug', 'api.team_points players '.json_encode($players));

		$rs_player = $this->Game->query("SELECT * FROM game_team_lineups_history WHERE 
										game_team_id ={$game_team_id[0]['b']['id']}
										AND game_id = '{$current_game_id}'
										LIMIT 16;");
		$array_key = 'game_team_lineups_history';
		
		if(count($rs_player) == 0)
		{
			$array_key = 'game_team_lineups';
			$rs_player = $this->Game->query("SELECT * FROM game_team_lineups WHERE 
										game_team_id ={$game_team_id[0]['b']['id']}
										AND matchday = '{$current_matchday}'
										LIMIT 16;");
		}

		$player_position = array();
		foreach ($rs_player as $key => $value)
		{
			$data_player_position =  $value[$array_key];
			$player_position[$data_player_position['player_id']] = $data_player_position['position_no'];
		}

		if($players['status'] == 1)
		{
			$response = array();
			$total_points = 0;
			foreach ($players['data'] as $key => $value)
			{
				$response[] = array('uid' => $key,
									'game_id' => $value['game_id'],
									'name' => $value['name'],
									'points' => $value['points'],
									'position_no' => $player_position[$key]);
				$total_points += $value['points'];
			}

			$this->set('response',array('status'=>1,'data'=>array('players' => $response, 
																'total_points' => $total_points)));
		}
		else
		{
			$this->set('response',array('status'=>1,'data' => null, 
										'message'=>'Point loe sedang di proses atau loe gak pasang formasi di matchday ini'));
		}
		
		$this->render('default');
	}

	public function matchinfo($game_id){
		$game_id = Sanitize::paranoid($game_id);

		$api_session = $this->readAccessToken();

		$fb_id = $api_session['fb_id'];
		
		$user = $this->User->findByFb_id($fb_id);
		
		
		$game_team = $this->Game->getTeam($fb_id);
		$response = array();
		
		//match details

		$players = $this->Game->getMatchDetailsByGameTeamId($game_team['id'],$game_id);

		//poin modifiers
		$rs = $this->Team->query("SELECT name,
										g as Goalkeeper,
										d as Defender,
										m as Midfielder,
										f as Forward
										FROM ".$this->ffgamedb.".game_matchstats_modifier as stats;");

		$modifier = array();
		foreach($rs as $r){
			$modifier[$r['stats']['name']] = $r['stats'];
		}
		$rs = null;
		unset($rs);

		$fixture  = $this->Game->query("SELECT a.*,b.name as home_name,c.name as away_name
										FROM ".$this->ffgamedb.".game_fixtures a
										INNER JOIN ".$this->ffgamedb.".master_team b
										ON a.home_id = b.uid
										INNER JOIN ".$this->ffgamedb.".master_team c
										ON a.away_id = c.uid
										WHERE a.game_id='{$game_id}'
										LIMIT 1");
		$match = $fixture[0]['a'];
		$match['home_name'] = $fixture[0]['b']['home_name'];
		$match['away_name'] = $fixture[0]['c']['away_name'];
		
		if($user['Team']['team_id'] == $match['home_id']){
		    $home = $user['Team']['team_name'];
		    $away = $match['away_name'];
		}else{
		    $away = $user['Team']['team_name'];
		    $home = $match['home_name'];
		}

		$response['home'] = $home;
		$response['away'] = $away;
		$response['players'] = $this->compilePlayerPerformance($players['data']);
		$this->set('response',array('status'=>1,'data'=>$response));
		$this->render('default');
	
	}
	private function compilePlayerPerformance($players){
		$overall_points = 0;

        foreach($players as $player_id=>$detail){
            $games = $this->getTotalPoints('game_started,total_sub_on',$detail['ori_stats']);

            
            $attacking_and_passing = $this->getTotalPoints('att_freekick_goal,att_ibox_goal,att_obox_goal,att_pen_goal,att_freekick_post,ontarget_scoring_att,att_obox_target,big_chance_created,big_chance_scored,goal_assist,total_att_assist,second_goal_assist,final_third_entries,fouled_final_third,pen_area_entries,won_contest,won_corners,penalty_won,last_man_contest,accurate_corners_intobox,accurate_cross_nocorner,accurate_freekick_cross,accurate_launches,long_pass_own_to_opp_success,successful_final_third_passes,accurate_flick_on',
                                    $detail['ori_stats']);
            $defending = $this->getTotalPoints('aerial_won,ball_recovery,duel_won,effective_blocked_cross,effective_clearance,effective_head_clearance,interceptions_in_box,interception_won,poss_won_def_3rd,poss_won_mid_3rd,poss_won_att_3rd,won_tackle,offside_provoked,last_man_tackle,outfielder_block',$detail['ori_stats']);

            $goalkeeping = $this->getTotalPoints('dive_catch,dive_save,stand_catch,stand_save,cross_not_claimed,good_high_claim,punches,good_one_on_one,accurate_keeper_sweeper,gk_smother,saves,goals_conceded',$detail['ori_stats']);
            $mistakes_and_errors = $this->getTotalPoints('penalty_conceded,red_card,yellow_card,challenge_lost,dispossessed,fouls,overrun,total_offside,unsuccessful_touch,error_lead_to_shot,error_lead_to_goal',$detail['ori_stats']);

            $total_poin = $games + $attacking_and_passing + $defending +
                          $goalkeeping + $mistakes_and_errors;

            $overall_points += $total_poin;
            $players[$player_id]['statistics'] = array('games'=>$games,
            											'attacking_and_passing'=>$attacking_and_passing,
            											'defending'=>$defending,
            											'goalkeeping'=>$goalkeeping,
            											'mistakes_and_errors'=>$mistakes_and_errors,
            											'total_poin'=>$total_poin);
        }
        return $players;
	}
	private function getPoin($position,$stats_name,$modifier){
   
	    return intval(@$modifier[$stats_name][$position]);
	}
	private function getTotalPoints($str,$stats){
	    $arr = explode(",",$str);
	    $total = 0;
	    foreach($arr as $a){
	        $total += floatval(@$stats[$a]['points']);
	    }
	    return $total;
	}
	public function player($player_id){
		require_once APP . 'Vendor' . DS. 'stats.locale.php';
		$this->loadModel('Point');

		$api_session = $this->readAccessToken();
		$fb_id = $api_session['fb_id'];
		
		$user = $this->User->findByFb_id($fb_id);
		
		if(strlen($user['User']['avatar_img'])<2){
			$user['User']['avatar_img'] = "http://graph.facebook.com/".$fb_id."/picture";
		}else{
			$user['User']['avatar_img'] = Configure::read('avatar_web_url').'120x120_'.$user['User']['avatar_img'];
		}
		$game_team = $this->Game->getTeam($fb_id);
		
		$response = array();

		$point = $this->Point->findByTeam_id($user['Team']['id']);

		$response['user'] = array('id'=>$user['User']['id'],
									'fb_id'=>$user['User']['fb_id'],
									'name'=>$user['User']['name'],
									'avatar_img'=>$user['User']['avatar_img']);

		$response['stats']['points'] = ceil(floatval(@$point['Point']['points']) + floatval(@$point['Point']['extra_points']));
		$response['stats']['rank'] = intval(@$point['Point']['rank']);

		//budget
		$budget = $this->Game->getBudget($game_team['id']);
		$response['budget'] = $budget;

		$response['stats']['club_value'] = intval($budget) + $response['stats']['points'];

		//club
		$club = $this->Team->findByUser_id($user['User']['id']);
		$response['club'] = array('id'=>$club['Team']['id'],
									'team_name'=>$club['Team']['team_name'],
									'team_id'=>$club['Team']['team_id'],
								  );

		//get original club
		$original_club = $this->Game->getClub($club['Team']['team_id']);
		$this->set('original',$original_club);
		$response['original_club'] = $original_club;


		//player detail : 
		$rs = $this->Game->get_team_player_info($fb_id,$player_id);

		
		//stats modifier
		$modifiers = $this->Game->query("SELECT * FROM ".$this->ffgamedb.".game_matchstats_modifier as Modifier");
		
		if($rs['status']==1){

			if(isset($rs['data']['daily_stats'])&&sizeof($rs['data']['daily_stats'])>0){
				
				foreach($rs['data']['daily_stats'] as $n=>$v){
					$fixture = $this->Team->query("SELECT matchday,match_date,
										UNIX_TIMESTAMP(match_date) as ts
										FROM ".$this->ffgamedb.".game_fixtures 
										WHERE game_id='{$n}' 
										LIMIT 1");

					$rs['data']['daily_stats'][$n]['fixture'] = $fixture[0]['game_fixtures'];
					$rs['data']['daily_stats'][$n]['fixture']['ts'] = $fixture[0][0]['ts'];
				}
			}

			//generate stats from overall data.

			

		}
		$games = array(
		        'game_started'=>'game_started',
		        'sub_on'=>'total_sub_on'
		    );

		$passing_and_attacking = array(
		        'Freekick Goal'=>'att_freekick_goal',
		        'Goal inside the box'=>'att_ibox_goal',
		        'Goal Outside the Box'=>'att_obox_goal',
		        'Penalty Goal'=>'att_pen_goal',
		        'Freekick Shots'=>'att_freekick_post',
		        'On Target Scoring Attempt'=>'ontarget_scoring_att',
		        'Shot From Outside the Box'=>'att_obox_target',
		        'big_chance_created'=>'big_chance_created',
		        'big_chance_scored'=>'big_chance_scored',
		        'goal_assist'=>'goal_assist',
		        'total_assist_attempt'=>'total_att_assist',
		        'Second Goal Assist'=>'second_goal_assist',
		        'final_third_entries'=>'final_third_entries',
		        'fouled_final_third'=>'fouled_final_third',
		        'pen_area_entries'=>'pen_area_entries',
		        'won_contest'=>'won_contest',
		        'won_corners'=>'won_corners',
		        'penalty_won'=>'penalty_won',
		        'last_man_contest'=>'last_man_contest',
		        'accurate_corners_intobox'=>'accurate_corners_intobox',
		        'accurate_cross_nocorner'=>'accurate_cross_nocorner',
		        'accurate_freekick_cross'=>'accurate_freekick_cross',
		        'accurate_launches'=>'accurate_launches',
		        'long_pass_own_to_opp_success'=>'long_pass_own_to_opp_success',
		        'successful_final_third_passes'=>'successful_final_third_passes',
		        'accurate_flick_on'=>'accurate_flick_on'
		    );


		$defending = array(
		        'aerial_won'=>'aerial_won',
		        'ball_recovery'=>'ball_recovery',
		        'duel_won'=>'duel_won',
		        'effective_blocked_cross'=>'effective_blocked_cross',
		        'effective_clearance'=>'effective_clearance',
		        'effective_head_clearance'=>'effective_head_clearance',
		        'interceptions_in_box'=>'interceptions_in_box',
		        'interception_won' => 'interception_won',
		        'possession_won_def_3rd' => 'poss_won_def_3rd',
		        'possession_won_mid_3rd' => 'poss_won_mid_3rd',
		        'possession_won_att_3rd' => 'poss_won_att_3rd',
		        'won_tackle' => 'won_tackle',
		        'offside_provoked' => 'offside_provoked',
		        'last_man_tackle' => 'last_man_tackle',
		        'outfielder_block' => 'outfielder_block'
		    );

		$goalkeeper = array(
		                'dive_catch'=> 'dive_catch',
		                'dive_save'=> 'dive_save',
		                'stand_catch'=> 'stand_catch',
		                'stand_save'=> 'stand_save',
		                'cross_not_claimed'=> 'cross_not_claimed',
		                'good_high_claim'=> 'good_high_claim',
		                'punches'=> 'punches',
		                'good_one_on_one'=> 'good_one_on_one',
		                'accurate_keeper_sweeper'=> 'accurate_keeper_sweeper',
		                'gk_smother'=> 'gk_smother',
		                'saves'=> 'saves',
		                'goals_conceded'=>'goals_conceded'
		                    );


		$mistakes_and_errors = array(
		            'penalty_conceded'=>'penalty_conceded',
		            'red_card'=>'red_card',
		            'yellow_card'=>'yellow_card',
		            'challenge_lost'=>'challenge_lost',
		            'dispossessed'=>'dispossessed',
		            'fouls'=>'fouls',
		            'overrun'=>'overrun',
		            'total_offside'=>'total_offside',
		            'unsuccessful_touch'=>'unsuccessful_touch',
		            'error_lead_to_shot'=>'error_lead_to_shot',
		            'error_lead_to_goal'=>'error_lead_to_goal'
		            );
		$map = array('games'=>$games,
		              'passing_and_attacking'=>$passing_and_attacking,
		              'defending'=>$defending,
		              'goalkeeper'=>$goalkeeper,
		              'mistakes_and_errors'=>$mistakes_and_errors
		             );

		$data = $rs['data'];
		switch($data['player']['position']){
		    case 'Forward':
		        $pos = "f";
		    break;
		    case 'Midfielder':
		        $pos = "m";
		    break;
		    case 'Defender':
		        $pos = "d";
		    break;
		    default:
		        $pos = 'g';
		    break;
		}
		$total_points = 0;
		$main_stats_vals = array('games'=>0,
		                            'passing_and_attacking'=>0,
		                            'defending'=>0,
		                            'goalkeeper'=>0,
		                            'mistakes_and_errors'=>0,
		                         );


		
		if(isset($data['overall_stats'])){
			foreach($data['overall_stats'] as $stats){
		        $total_points += $stats['points'];
		        $main_stats_vals[$stats['stats_category']]+= $stats['points'];
		    }
		    

			
		    $profileStats = $this->getStatsIndividual('games',$pos,$modifiers,$map,$data['overall_stats']);
		    $games = array();
		    foreach($profileStats as $statsName=>$statsVal){
		    	$statsName = stats_translated($statsName,'id');
		    	$games[$statsName] = $statsVal;
		    }
			$profileStats = $this->getStatsIndividual('passing_and_attacking',$pos,$modifiers,$map,$data['overall_stats']);
		    $passing_and_attacking = array();
		    foreach($profileStats as $statsName=>$statsVal){
		    	$statsName = stats_translated($statsName,'id');
		    	$passing_and_attacking[$statsName] = $statsVal;
		    }
		    $profileStats = $this->getStatsIndividual('defending',$pos,$modifiers,$map,$data['overall_stats']);
		    $defending = array();
		    foreach($profileStats as $statsName=>$statsVal){
		    	$statsName = stats_translated($statsName,'id');
		    	$defending[$statsName] = $statsVal;
		    }
           
		    $profileStats = $this->getStatsIndividual('goalkeeper',$pos,$modifiers,$map,$data['overall_stats']);
		    $goalkeeper = array();
		    foreach($profileStats as $statsName=>$statsVal){
		    	$statsName = stats_translated($statsName,'id');
		    	$goalkeeper[$statsName] = $statsVal;
		    }

		    $profileStats = $this->getStatsIndividual('mistakes_and_errors',$pos,$modifiers,$map,$data['overall_stats']);
		    $mistakes_and_errors = array();
		    foreach($profileStats as $statsName=>$statsVal){
		    	$statsName = stats_translated($statsName,'id');
		    	$mistakes_and_errors[$statsName] = $statsVal;
		    }

			$stats = array(
				'games'=>$games,
				'passing_and_attacking'=>$passing_and_attacking,
				'defending'=>$defending,
				'goalkeeping'=>$goalkeeper,
				'mistakes_and_errors'=>$mistakes_and_errors,

			);
		}else{
			$stats = array();
			$main_stats_vals = array();
		}


		$performance = 0;

        if(sizeof($data['stats'])>0){
            if(intval(@$data['stats'][sizeof($data['stats'])-1]['points'])!=0){

            	$performance = getTransferValueBonus(
                                $data['stats'][sizeof($data['stats'])-1]['performance'],
                                $data['player']['transfer_value']);
            }  
        }
        
        $data['player']['transfer_value'] = $data['player']['transfer_value'] + $performance;

		$response['player'] = array('info'=>$data['player'],
									 'summary'=>$main_stats_vals,
										'stats'=>$stats);

		
		$this->set('response',array('status'=>1,'data'=>$response));
		$this->render('default');
	}

	public function player_points($player_id){
		require_once APP . 'Vendor' . DS. 'stats.locale.php';
		$this->loadModel('Point');
		$this->loadModel('Weekly_point');

		$api_session = $this->readAccessToken();
		$fb_id = $api_session['fb_id'];
		
		$user = $this->User->findByFb_id($fb_id);

		$game_id = $this->request->query('game_id');
		
		if(strlen($user['User']['avatar_img'])<2){
			$user['User']['avatar_img'] = "http://graph.facebook.com/".$fb_id."/picture";
		}else{
			$user['User']['avatar_img'] = Configure::read('avatar_web_url').'120x120_'.$user['User']['avatar_img'];
		}
		$game_team = $this->Game->getTeam($fb_id);
		
		$response = array();

		$point = $this->Point->findByTeam_id($user['Team']['id']);

		$response['user'] = array('id'=>$user['User']['id'],
									'fb_id'=>$user['User']['fb_id'],
									'name'=>$user['User']['name'],
									'avatar_img'=>$user['User']['avatar_img']);

		$response['stats']['points'] = ceil(floatval(@$point['Point']['points']) + floatval(@$point['Point']['extra_points']));
		$response['stats']['rank'] = intval(@$point['Point']['rank']);

		//budget
		$budget = $this->Game->getBudget($game_team['id']);
		$response['budget'] = $budget;

		$response['stats']['club_value'] = intval($budget) + $response['stats']['points'];

		//club
		$club = $this->Team->findByUser_id($user['User']['id']);
		$response['club'] = array('id'=>$club['Team']['id'],
									'team_name'=>$club['Team']['team_name'],
									'team_id'=>$club['Team']['team_id'],
								  );

		//get original club
		$original_club = $this->Game->getClub($club['Team']['team_id']);
		$this->set('original',$original_club);
		$response['original_club'] = $original_club;


		//player detail : 
		$rs = $this->Game->get_team_player_info($fb_id,$player_id);
		Cakelog::write('debug', 'api.player_points rs '.json_encode($rs));

		$session_id = Configure::read('FM_SESSION_ID');


		$current_game_id = $game_id;

		$daily_stats = array();
		foreach ($rs['data']['daily_stats']['stats'] as $key => $value) {
			if($value['game_id'] == $current_game_id){
				$daily_stats[] = $value;
			}
		}

		Cakelog::write('debug', 'api.player_points current_game_id '.$current_game_id.' team_id '.$user['Team']['id']);

		$rs['data']['daily_stats']['stats'] = $daily_stats;
		//stats modifier
		$modifiers = $this->Game->query("SELECT * FROM ".$this->ffgamedb.".game_matchstats_modifier as Modifier");
		
		if($rs['status']==1){

			if(isset($rs['data']['daily_stats'])&&sizeof($rs['data']['daily_stats'])>0){
				
				foreach($rs['data']['daily_stats'] as $n=>$v){
					$fixture = $this->Team->query("SELECT matchday,match_date,
										UNIX_TIMESTAMP(match_date) as ts
										FROM ".$this->ffgamedb.".game_fixtures 
										WHERE game_id='{$n}' 
										LIMIT 1");

					$rs['data']['daily_stats'][$n]['fixture'] = $fixture[0]['game_fixtures'];
					$rs['data']['daily_stats'][$n]['fixture']['ts'] = $fixture[0][0]['ts'];
				}
			}

			//generate stats from overall data.

			

		}
		$games = array(
		        'game_started'=>'game_started',
		        'sub_on'=>'total_sub_on'
		    );

		$passing_and_attacking = array(
		        'Freekick Goal'=>'att_freekick_goal',
		        'Goal inside the box'=>'att_ibox_goal',
		        'Goal Outside the Box'=>'att_obox_goal',
		        'Penalty Goal'=>'att_pen_goal',
		        'Freekick Shots'=>'att_freekick_post',
		        'On Target Scoring Attempt'=>'ontarget_scoring_att',
		        'Shot From Outside the Box'=>'att_obox_target',
		        'big_chance_created'=>'big_chance_created',
		        'big_chance_scored'=>'big_chance_scored',
		        'goal_assist'=>'goal_assist',
		        'total_assist_attempt'=>'total_att_assist',
		        'Second Goal Assist'=>'second_goal_assist',
		        'final_third_entries'=>'final_third_entries',
		        'fouled_final_third'=>'fouled_final_third',
		        'pen_area_entries'=>'pen_area_entries',
		        'won_contest'=>'won_contest',
		        'won_corners'=>'won_corners',
		        'penalty_won'=>'penalty_won',
		        'last_man_contest'=>'last_man_contest',
		        'accurate_corners_intobox'=>'accurate_corners_intobox',
		        'accurate_cross_nocorner'=>'accurate_cross_nocorner',
		        'accurate_freekick_cross'=>'accurate_freekick_cross',
		        'accurate_launches'=>'accurate_launches',
		        'long_pass_own_to_opp_success'=>'long_pass_own_to_opp_success',
		        'successful_final_third_passes'=>'successful_final_third_passes',
		        'accurate_flick_on'=>'accurate_flick_on'
		    );


		$defending = array(
		        'aerial_won'=>'aerial_won',
		        'ball_recovery'=>'ball_recovery',
		        'duel_won'=>'duel_won',
		        'effective_blocked_cross'=>'effective_blocked_cross',
		        'effective_clearance'=>'effective_clearance',
		        'effective_head_clearance'=>'effective_head_clearance',
		        'interceptions_in_box'=>'interceptions_in_box',
		        'interception_won' => 'interception_won',
		        'possession_won_def_3rd' => 'poss_won_def_3rd',
		        'possession_won_mid_3rd' => 'poss_won_mid_3rd',
		        'possession_won_att_3rd' => 'poss_won_att_3rd',
		        'won_tackle' => 'won_tackle',
		        'offside_provoked' => 'offside_provoked',
		        'last_man_tackle' => 'last_man_tackle',
		        'outfielder_block' => 'outfielder_block'
		    );

		$goalkeeper = array(
		                'dive_catch'=> 'dive_catch',
		                'dive_save'=> 'dive_save',
		                'stand_catch'=> 'stand_catch',
		                'stand_save'=> 'stand_save',
		                'cross_not_claimed'=> 'cross_not_claimed',
		                'good_high_claim'=> 'good_high_claim',
		                'punches'=> 'punches',
		                'good_one_on_one'=> 'good_one_on_one',
		                'accurate_keeper_sweeper'=> 'accurate_keeper_sweeper',
		                'gk_smother'=> 'gk_smother',
		                'saves'=> 'saves',
		                'goals_conceded'=>'goals_conceded'
		                    );


		$mistakes_and_errors = array(
		            'penalty_conceded'=>'penalty_conceded',
		            'red_card'=>'red_card',
		            'yellow_card'=>'yellow_card',
		            'challenge_lost'=>'challenge_lost',
		            'dispossessed'=>'dispossessed',
		            'fouls'=>'fouls',
		            'overrun'=>'overrun',
		            'total_offside'=>'total_offside',
		            'unsuccessful_touch'=>'unsuccessful_touch',
		            'error_lead_to_shot'=>'error_lead_to_shot',
		            'error_lead_to_goal'=>'error_lead_to_goal'
		            );
		$map = array('games'=>$games,
		              'passing_and_attacking'=>$passing_and_attacking,
		              'defending'=>$defending,
		              'goalkeeper'=>$goalkeeper,
		              'mistakes_and_errors'=>$mistakes_and_errors
		             );

		$data = $rs['data'];
		switch($data['player']['position']){
		    case 'Forward':
		        $pos = "f";
		    break;
		    case 'Midfielder':
		        $pos = "m";
		    break;
		    case 'Defender':
		        $pos = "d";
		    break;
		    default:
		        $pos = 'g';
		    break;
		}
		$total_points = 0;
		$main_stats_vals = array('games'=>0,
		                            'passing_and_attacking'=>0,
		                            'defending'=>0,
		                            'goalkeeper'=>0,
		                            'mistakes_and_errors'=>0,
		                         );


		
		if(isset($data['daily_stats']['stats'])){
			foreach($data['daily_stats']['stats'] as $stats){
		        $total_points += $stats['points'];
		        $main_stats_vals[$stats['stats_category']]+= $stats['points'];
		    }
		    

			
		    $profileStats = $this->getStatsIndividual('games',$pos,$modifiers,$map,$data['daily_stats']['stats']);
		    $games = array();
		    foreach($profileStats as $statsName=>$statsVal){
		    	$statsName = stats_translated($statsName,'id');
		    	$games[$statsName] = $statsVal;
		    }
			$profileStats = $this->getStatsIndividual('passing_and_attacking',$pos,$modifiers,$map,$data['daily_stats']['stats']);
		    $passing_and_attacking = array();
		    foreach($profileStats as $statsName=>$statsVal){
		    	$statsName = stats_translated($statsName,'id');
		    	$passing_and_attacking[$statsName] = $statsVal;
		    }
		    $profileStats = $this->getStatsIndividual('defending',$pos,$modifiers,$map,$data['daily_stats']['stats']);
		    $defending = array();
		    foreach($profileStats as $statsName=>$statsVal){
		    	$statsName = stats_translated($statsName,'id');
		    	$defending[$statsName] = $statsVal;
		    }
           
		    $profileStats = $this->getStatsIndividual('goalkeeper',$pos,$modifiers,$map,$data['daily_stats']['stats']);
		    $goalkeeper = array();
		    foreach($profileStats as $statsName=>$statsVal){
		    	$statsName = stats_translated($statsName,'id');
		    	$goalkeeper[$statsName] = $statsVal;
		    }

		    $profileStats = $this->getStatsIndividual('mistakes_and_errors',$pos,$modifiers,$map,$data['daily_stats']['stats']);
		    $mistakes_and_errors = array();
		    foreach($profileStats as $statsName=>$statsVal){
		    	$statsName = stats_translated($statsName,'id');
		    	$mistakes_and_errors[$statsName] = $statsVal;
		    }

			$stats = array(
				'games'=>$games,
				'passing_and_attacking'=>$passing_and_attacking,
				'defending'=>$defending,
				'goalkeeping'=>$goalkeeper,
				'mistakes_and_errors'=>$mistakes_and_errors,

			);
		}else{
			$stats = array();
			$main_stats_vals = array();
		}


		$performance = 0;

        if(sizeof($data['stats'])>0){
            if(intval(@$data['stats'][sizeof($data['stats'])-1]['points'])!=0){

            	$performance = getTransferValueBonus(
                                $data['stats'][sizeof($data['stats'])-1]['performance'],
                                $data['player']['transfer_value']);
            }  
        }
        
        $data['player']['transfer_value'] = $data['player']['transfer_value'] + $performance;

		$response['player'] = array('info'=>$data['player'],
									 'summary'=>$main_stats_vals,
										'stats'=>$stats);

		
		$this->set('response',array('status'=>1,'data'=>$response));
		$this->render('default');
	}

	private function getModifierValue($modifiers,$statsName,$pos){
	    foreach($modifiers as $m){
	        if($m['Modifier']['name']==$statsName){
	            return ($m['Modifier'][$pos]);
	        }
	    }
	    return 0;
	}
	private function getStatsIndividual($category,$pos,$modifiers,$map,$stats){
	    $collection = array();
	    $statTypes = $map[$category];
	    foreach($stats as $st){
	        if($st['stats_category']==$category){
	            foreach($statTypes as $n=>$v){
	                if(!isset($collection[$n])){
		                $collection[$n] = array('total'=>0,'points'=>0);
		            }
	                if($st['stats_name'] == $v){

	                    $collection[$n] = array('total'=>$st['total'],
	                                            'points'=>$st['points']);
	                }
	            }
	        }
	    }
	    return $collection;
	}
	private function getStats($category,$pos,$modifiers,$map,$stats){
	    
	    
	    $statTypes = $map[$category];
	    //pr($statTypes);
	    $collection = array();
	    foreach($stats as $s){
	        foreach($statTypes as $n=>$v){
	            if(!isset($collection[$n])){
	                $collection[$n] = array('total'=>0,'points'=>0);
	            }
	            if($s['stats_name'] == $v){
	                $collection[$n] = array('total'=>$s['total'],
	                                    'points'=>$s['total'] * $this->getModifierValue($modifiers,$v,$pos));
	            }
	        }
	    }
	    
	    return $collection;
	}
	/*
	private function getFinancialStatements($fb_id){
		$finance = $this->Game->financial_statements($fb_id);
		if($finance['status']==1){

			$report = array('total_matches' => $finance['data']['total_matches'],
							'budget' => $finance['data']['budget']);
			foreach($finance['data']['report'] as $n=>$v){
				$report[$v['item_name']] = $v['total'];
			}
			$report['total_earnings'] = intval(@$report['tickets_sold'])+
										intval(@$report['commercial_director_bonus'])+
										intval(@$report['marketing_manager_bonus'])+
										intval(@$report['public_relation_officer_bonus'])+
										intval(@$report['win_bonus']);
			return $report;
		}
	}*/
	
	private function getMatches($game_team_id,$team_id,$arr,$expenditures,$tickets_sold){
		
		$matches = array();
		if(sizeof($arr)>0){
			$game_ids = array();

			foreach($arr as $a){
				$game_ids[] = "'".$a['game_id']."'";
			}

			$a_game_ids = implode(',',$game_ids);
			$sql = "SELECT game_id,home_id,away_id,b.name AS home_name,c.name AS away_name,
					a.matchday,a.match_date,a.home_score,a.away_score
					FROM ".$this->ffgamedb.".game_fixtures a
					INNER JOIN ".$this->ffgamedb.".master_team b
					ON a.home_id = b.uid
					INNER JOIN ".$this->ffgamedb.".master_team c
					ON a.away_id = c.uid
					WHERE (a.home_id = '{$team_id}' 
							OR a.away_id = '{$team_id}')
					AND EXISTS (SELECT 1 FROM ".$this->ffgamestatsdb.".game_match_player_points d
								WHERE d.game_id = a.game_id 
								AND d.game_team_id = {$game_team_id} LIMIT 1)
					ORDER BY a.game_id";
			$rs = $this->Game->query($sql);
			

			foreach($rs as $n=>$r){
				$points = 0;
				$balance = 0;
				$income = 0;
				foreach($arr as $a){
					if($r['a']['matchday']==$a['matchday']){
						$points = $a['points'];
						break;
					}
				}
				foreach($tickets_sold as $b){
					if($r['a']['game_id']==$b['game_id']){
						$income = $b['total_income'];
						break;
					}
				}
				$match = $r['a'];
				
				if($r['a']['home_id'] == $team_id){
					$match['against'] = $r['c']['away_name'];
				}else{
					$match['against'] = $r['b']['home_name'];
				}
				$match['home_name'] = $r['b']['home_name'];
				$match['away_name'] = $r['c']['away_name'];
				$match['points'] = intval(@$points);
				$match['income'] = intval(@$income);
				$matches[] = $match;
			}

			//clean memory
			$rs = null;
			unset($rs);
		}
		return $matches;
	}
	public function finance(){
		$this->loadModel('Point');

		$api_session = $this->readAccessToken();
		$fb_id = $api_session['fb_id'];
		
		$user = $this->User->findByFb_id($fb_id);		
		
		$game_team = $this->Game->getTeam($fb_id);

		
		
		
		//getting staffs
		$officials = $this->Game->getAvailableOfficials($game_team['id']);
		$staffs = array();
		foreach($officials as $official){
			if(isset($official['hired'])){
				$staffs[] = $official;
			}
		}
		unset($officials);


		$finance = $this->getFinancialStatements($fb_id);
		$financial_statement['transaction'] = $finance;
		$financial_statement['weekly_balances'] = $this->weekly_balances;
		$financial_statement['total_items'] = $this->finance_total_items_raw;
		$financial_statement['tickets_sold'] = $this->tickets_sold;
		

		$response = $this->populateFinancialStatement(0,
													$finance,
													$staffs,
													$this->weekly_balances,
													$this->starting_budget,
													$this->finance_total_items_raw);
		$this->set('response',$response);
		$this->render('default');
	}
	private function populateFinancialStatement($week,$finance,$staffs,$weekly_balances,
												$starting_budget,$total_items){


		$response['status'] = 1;
		//financial statements
		$total_expenses = $this->getFinanceTotalExpenses($finance);


		$sponsor = 0;
		$sponsor += intval(@$finance['Joining_Bonus']);
		$sponsor += intval(@$finance['sponsorship']);


		//income from other events
		$other = $this->getFinanceOtherIncomes($finance);

		//expenses from other events
		$other_expenses = $this->getFinanceOtherExpenses($finance);
		$total_expenses -= $other_expenses;


		

		//get starting balance and running balance
		$balance_health = $this->getFinancialBalances($week,$weekly_balances,$starting_budget);
		$running_balance = $balance_health['running_balance'];
		$starting_balance = $balance_health['starting_balance'];
		//---- end of starting and running balance

		$staff_token = array();
		foreach($staffs as $staff){
		  $staff_token[] = str_replace(" ","_",strtolower($staff['name']));
		}
		

		//render arrays
		$income = $this->getFinanceIncomes($finance,$sponsor,$other,$total_items,$staff_token);
		$expense = $this->getFinanceExpenses($finance,$other_expenses,$total_items,$staff_token);
		$financial = array(
			'last_week_balance'=>array(
									'name'=>'Neraca Minggu Lalu',
									'description'=>'',
									'total'=>'ss$ '.number_format($starting_balance),
								),
			'incomes'=>$income,
			'expenses'=>$expense,
			'total_income'=>array(
				'name'=>'Total Perolehan',
				'description'=>'',
				'total'=>'ss$ '.number_format(abs(@$finance['total_earnings']))
			),
			'total_expenses'=>array(
				'name'=>'Total Pengeluaran',
				'description'=>'',
				'total'=>'ss$ '.number_format((@$total_expenses))
			),
			'running_balance'=>array(
				'name'=>'Neraca Berjalan',
				'description'=>'',
				'total'=>'ss$ '.number_format(@$running_balance)
			)
		);


		$response['data'] = $financial;

		return $response;

	}
	private function getFinanceExpenses($finance,$other_expenses,$total_items,$staff_token){
		$expenses = array();
		array_push($expenses,array(
					'name'=>'Biaya Operasional',
					'description'=>'',
					'total'=>'ss$ '.number_format(abs(@$finance['operating_cost']))
			));

		array_push($expenses,array(
					'name'=>'Gaji Pemain',
					'description'=>'',
					'total'=>'ss$ '.number_format(abs(@$finance['player_salaries']))
			));

		if(isset($finance['compensation_fee'])){
			array_push($expenses,array(
					'name'=>'Biaya Kompesansi',
					'description'=>'',
					'total'=>'ss$ '.number_format(abs(@$finance['compensation_fee']))
			));
		}
		if(isset($finance['ticket_sold_penalty'])){
			array_push($expenses,array(
					'name'=>'Pinalti hasil penjualan tiket',
					'description'=>'',
					'total'=>'ss$ '.number_format(abs(@$finance['ticket_sold_penalty']))
			));
		}
		if(isset($finance['security_overtime_fee'])){
			array_push($expenses,array(
					'name'=>'Biaya Overtime Sekuriti',
					'description'=>'',
					'total'=>'ss$ '.number_format(abs(@$finance['security_overtime_fee']))
			));
		}
		if(isset($finance['buy_player'])){
			array_push($expenses,array(
					'name'=>'Pembelian Pemain',
					'description'=>'',
					'total'=>'ss$ '.number_format(abs(@$finance['buy_player']))
			));
		}
		if($other_expenses > 0){
			array_push($expenses,array(
					'name'=>'Pengeluaran Lainnya',
					'description'=>'',
					'total'=>'ss$ '.number_format(abs(@$other_expenses))
			));
		}
		return $expenses;
	}
	private function getFinanceIncomes($finance,$sponsor,$other,$total_items,$staff_token){

		$income = array(
						array(
							'name'=>'Tiket Terjual',
							'description'=>'ss$'.@round($finance['tickets_sold']/$total_items['tickets_sold'],2).
											' x '.number_format(@$total_items['tickets_sold']),
							'total'=>'ss$ '.number_format(@$finance['tickets_sold'])
						),
					);

	  	if($this->isStaffExist($staff_token,'commercial_director')){
		  	array_push($income,array(
					'name'=>'Bonus Commercial Director',
					'description'=>'',
					'total'=>'ss$ '.number_format(abs(@$finance['commercial_director_bonus']))
			));
		}
		if($this->isStaffExist($staff_token,'marketing_manager')){
		  	array_push($income,array(
					'name'=>'Bonus Marketing Manager',
					'description'=>'',
					'total'=>'ss$ '.number_format(abs(@$finance['marketing_manager_bonus']))
			));
		}
		if($this->isStaffExist($staff_token,'public_relation_officer')){
		  	array_push($income,array(
					'name'=>'Bonus Public Relation Officer',
					'description'=>'',
					'total'=>'ss$ '.number_format(abs(@$finance['public_relation_officer_bonus']))
			));
		}

       
		//sponsor
		array_push($income,array(
					'name'=>'Sponsor',
					'description'=>'',
					'total'=>'ss$ '.number_format(abs(@$sponsor))
			));

		//player sold
		if(isset($finance['player_sold'])){
			array_push($income,array(
					'name'=>'Penjualan Pemain',
					'description'=>'',
					'total'=>'ss$ '.number_format($finance['player_sold'])
			));
		}

		if(isset($finance['win_bonus'])){
			array_push($income,array(
					'name'=>'Bonus',
					'description'=>'Kemenangan',
					'total'=>'ss$ '.number_format($finance['win_bonus'])
			));
		}
		if($other > 0){
			array_push($income,array(
					'name'=>'Bonus',
					'description'=>'Lain-lain',
					'total'=>'ss$ '.number_format($other)
			));
		}
		return $income;
	}
	private function getFinanceOtherIncomes($finance){
		$other = 0;
		foreach($finance as $item_name => $item_value){
		  if($item_value > 0 && @eregi('other_',$item_name)){
		    $other += $item_value;
		  }
		  if($item_value > 0 && @eregi('event',$item_name)){
		    $other += $item_value;
		  }
		  if($item_value > 0 && @eregi('perk',$item_name)){
		    $other += $item_value;
		  }
		}
		return $other;
	}
	private function getFinancialBalances($week,$weekly_balances,$starting_budget){
		
		$first_week = $weekly_balances[0];
		$my_balance = $weekly_balances;
		$previous_balances = array();
		for($i=1;$i<$first_week['week'];$i++){
		  $previous_balances[] = array('week'=>$i,
		                              'balance'=>intval(@$starting_budget));
		}

		
		$weekly_balances = array_merge($previous_balances,$weekly_balances);

		if($week<=1){
		  $starting_balance = intval(@$starting_budget);
		}else{
		  $starting_balance = $weekly_balances[$week-2]['balance'];

		}
		if($week==0){
		  $running_balance = intval(@$weekly_balances[sizeof($weekly_balances)-1]['balance']);
		}else{
		  $running_balance = intval(@$weekly_balances[$week-1]['balance']);  
		}
		return array(
			'starting_balance'=>$starting_balance,
			'running_balance'=>$running_balance
		);
	}
	private function getFinanceOtherExpenses($finance){
		$other_expenses = 0;
		foreach($finance as $item_name => $item_value){
		  if($item_value < 0 && @eregi('other_',$item_name)){
		    $other_expenses += abs($item_value);
		  }
		  if($item_value < 0 && @eregi('transaction_fee',$item_name)){
		    $other_expenses += abs($item_value);
		  }
		}
		return $other_expenses;
	}
	private function getFinanceTotalExpenses($finance){
		$total_expenses = 0;
		$total_expenses+= intval(@$finance['operating_cost']);
		$total_expenses+= intval(@$finance['player_salaries']);
		$total_expenses+= intval(@$finance['commercial_director']);
		$total_expenses+= intval(@$finance['marketing_manager']);
		$total_expenses+= intval(@$finance['public_relation_officer']);
		$total_expenses+= intval(@$finance['head_of_security']);
		$total_expenses+= intval(@$finance['football_director']);
		$total_expenses+= intval(@$finance['chief_scout']);
		$total_expenses+= intval(@$finance['general_scout']);
		$total_expenses+= intval(@$finance['finance_director']);
		$total_expenses+= intval(@$finance['tax_consultant']);
		$total_expenses+= intval(@$finance['accountant']);
		$total_expenses+= intval(@$finance['buy_player']);

		$total_expenses+= intval(@$finance['compensation_fee']);
		$total_expenses+= intval(@$finance['ticket_sold_penalty']);
		$total_expenses+= intval(@$finance['security_overtime_fee']);
		return $total_expenses;
	}
	public function weekly_finance($week=1){
		$this->loadModel('Point');

		$api_session = $this->readAccessToken();
		$fb_id = $api_session['fb_id'];
		
		$user = $this->User->findByFb_id($fb_id);		
		
		$game_team = $this->Game->getTeam($fb_id);


		
		//get overall data first so that we can retrieve weekly_balances and starting budget
		//getting staffs
		$officials = $this->Game->getAvailableOfficials($game_team['id']);
		$staffs = array();
		foreach($officials as $official){
			if(isset($official['hired'])){
				$staffs[] = $official;
			}
		}
		unset($officials);


		$finance = $this->getFinancialStatements($fb_id);

		
		$weekly_finance = $this->Game->weekly_finance($fb_id,$week);
		$weekly_statement = $this->getWeeklyFinancialStatement($weekly_finance);


		
		$response = $this->populateFinancialStatement($week,
													$weekly_statement['transaction'],
													$staffs,
													$this->weekly_balances,
													$this->starting_budget,
													$weekly_statement['total_items']);
		$this->set('response',$response);
		$this->render('default');


	}
	private function isStaffExist($staff_token,$name){ 
	  foreach($staff_token as $token){
	    if($token==$name){
	      return true;
	    }
	  }
	}
	private function getWeeklyFinancialStatement($weekly_finance){
		$weekly_statement = array();
		$total_items = array();
		$weekly_statement['total_earnings'] = 0;
		$weekly_statement['other_income'] = 0;
		$weekly_statement['other_expenses'] = 0;
		while(sizeof($weekly_finance['transactions'])>0){
			$p = array_shift($weekly_finance['transactions']);
			$weekly_statement[$p['item_name']] = $p['amount'];

			$total_items[$p['item_name']] = $p['item_total'];
			if($p['amount'] > 0 && @eregi('other_',$p['item_name'])){
				$weekly_statement['other_income']+= intval($p['amount']);
				unset($weekly_statement[$p['item_name']]);
			}
			if($p['amount'] > 0 && @eregi('perk-',$p['item_name'])){
				$weekly_statement['other_income']+= intval($p['amount']);
				unset($weekly_statement[$p['item_name']]);
			}
			if($p['amount'] < 0 && @eregi('other_',$p['item_name'])){
				$weekly_statement['other_expenses']+= intval($p['amount']);
				unset($weekly_statement[$p['item_name']]);
			}
			if($p['amount'] < 0 && @eregi('perk-',$v['item_name'])){
				$weekly_statement['other_expenses']+= intval($p['amount']);
				unset($weekly_statement[$p['item_name']]);
			}
			if($p['amount'] < 0 && @eregi('transaction_fee_',$p['item_name'])){
				$weekly_statement['other_expenses']+= intval($p['amount']);
				unset($weekly_statement[$p['item_name']]);
			}
			if($p['amount'] > 0){
				$weekly_statement['total_earnings'] += $p['amount'];
			}
		}
		if(isset($weekly_statement['Joining_Bonus'])){
			$weekly_statement['sponsorship'] = $weekly_statement['Joining_Bonus'];
			unset($weekly_statement['Joining_Bonus']);
		}
		

		return array('transaction'=>$weekly_statement,'total_items'=>$total_items);
	}
	private function getFinancialStatements($fb_id){
		$finance = $this->Game->financial_statements($fb_id);
		
		$this->weekly_balances = @$finance['data']['weekly_balances'];
		$this->expenditures = @$finance['data']['expenditures'];
		$this->starting_budget = @intval($finance['data']['starting_budget']);
		$this->tickets_sold = @$finance['data']['tickets_sold'];

		if($finance['status']==1){

			$report = array('total_matches' => $finance['data']['total_matches'],
							'budget' => $finance['data']['budget']);
			$total_items = array();
			$report['total_earnings'] = 0;
			$report['other_income'] = 0;
			$report['other_expenses'] = 0;
			foreach($finance['data']['report'] as $n=>$v){
				$report[$v['item_name']] = $v['total'];
				$total_items[$v['item_name']] = $v['item_total'];

				if($v['total'] > 0 && @eregi('other_',$v['item_name'])){
					$report['other_income']+= intval($v['total']);
					unset($report[$v['item_name']]);
				}
				if($v['total'] > 0 && @eregi('perk-',$v['item_name'])){
					$report['other_income']+= intval($v['total']);
					unset($report[$v['item_name']]);
				}
				if($v['total'] < 0 && @eregi('other_',$v['item_name'])){
					$report['other_expenses']+= intval($v['total']);
					unset($report[$v['item_name']]);
				}
				if($v['total'] < 0 && @eregi('perk-',$v['item_name'])){
					$report['other_expenses']+= intval($v['total']);
					unset($report[$v['item_name']]);
				}
				if($v['total'] < 0 && @eregi('transaction_fee_',$v['item_name'])){
					$report['other_expenses']+= intval($v['total']);
					unset($report[$v['item_name']]);
				}
				if($v['total'] > 0){
					$report['total_earnings'] += $v['total'];
				}
			}
			if(isset($report['Joining_Bonus'])){
				$report['sponsorship'] = $report['Joining_Bonus'];
				unset($report['Joining_Bonus']);
			}
			$this->finance_total_items_raw = $total_items;
			return $report;
		}
	}
	public function profile($act=null){
		$this->loadModel('User');
		$api_session = $this->readAccessToken();
		$fb_id = $api_session['fb_id'];
		$user = $this->User->findByFb_id($fb_id);
		if(strlen($user['User']['avatar_img'])<2){
			$user['User']['avatar_img'] = "http://graph.facebook.com/".$fb_id."/picture";
		}else{
			$user['User']['avatar_img'] = Configure::read('avatar_web_url').'120x120_'.$user['User']['avatar_img'];
		}
		
		$game_team = $this->Game->getTeam($fb_id);
		//club
		$conditions = array('conditions' => array(
												'user_id'=>$user['User']['id'], 
												'league'=>$this->league)
							);

		$club = $this->Team->find('first', $conditions);

		$next_match = $this->Game->getNextMatch($game_team['team_id']);
		$next_match['match']['home_original_name'] = $next_match['match']['home_name'];
		$next_match['match']['away_original_name'] = $next_match['match']['away_name'];

		if($next_match['match']['home_id']==$game_team['team_id']){
			$next_match['match']['home_name'] = $club['Team']['team_name'];
		}else{
			$next_match['match']['away_name'] = $club['Team']['team_name'];
		}
		$next_match['match']['match_date_ts'] = strtotime($next_match['match']['match_date']);
		$this->getCloseTime($next_match);
		
		if($act=='save'){
			if($this->request->is('post')){
				$data = array(
					'name'=>@$this->request->data['name'],
					'email'=>@$this->request->data['email'],
					'location'=>@$this->request->data['location'],
					'phone_number'=>$this->request->data['handphone']
				);
				//update team name
				$this->loadModel('Team');
				$this->Team->id = intval($user['Team']['id']);
				$this->Team->save(array(
						'team_name' => $this->request->data['club']
				));
				$this->User->id = $user['User']['id'];
				$rs = $this->User->save($data);
				$rs['User']['next_match'] = array('game_id'=>$next_match['match']['game_id'],
										'home_name'=>$next_match['match']['home_name'],
										'away_name'=>$next_match['match']['away_name'],
										'home_original_name'=>$next_match['match']['home_original_name'],
										'away_original_name'=>$next_match['match']['away_original_name'],
										'match_date'=>date("Y-m-d H:i:s",strtotime($next_match['match']['match_date'])),
										'match_date_ts'=>strtotime($next_match['match']['match_date'])
										);
				$user['User']['close_time'] = $this->closeTime;
				$this->set('response',array('status'=>1,'data'=>$rs['User']));
			}else{
				$this->set('response',array('status'=>0,'error'=>'Cannot save profile'));
			}
			
		}else{
			$user['User']['next_match'] = array('game_id'=>$next_match['match']['game_id'],
										'home_name'=>$next_match['match']['home_name'],
										'away_name'=>$next_match['match']['away_name'],
										'home_original_name'=>$next_match['match']['home_original_name'],
										'away_original_name'=>$next_match['match']['away_original_name'],
										'match_date'=>date("Y-m-d H:i:s",strtotime($next_match['match']['match_date'])),
										'match_date_ts'=>strtotime($next_match['match']['match_date'])
										);
			$user['User']['close_time'] = $this->closeTime;
			$user['User']['team_name'] = $club['Team']['team_name'];
			$this->set('response',array('status'=>1,'data'=>$user['User']));
		}
		$this->render('default');
	}
	public function save_avatar(){
		$this->loadModel('User');
		$api_session = $this->readAccessToken();
		$fb_id = $api_session['fb_id'];
		$user = $this->User->findByFb_id($fb_id);
		if(isset($_FILES['name'])&&strlen($_FILES['name'])>0){
			$_FILES['file']['name'] = str_replace(array(' ','\''),"_",$_FILES['file']['name']);
			if(move_uploaded_file($_FILES['file']['tmp_name'],
					Configure::read('avatar_img_dir').$_FILES['file']['name'])){
				//resize to 120x120 pixels
				$thumb = new Thumbnail();
				$thumb->resizeImage('resizeCrop', $_FILES['file']['name'], 
								Configure::read('avatar_img_dir'), 
								'120x120_'.$_FILES['file']['name'], 
								120, 
								120, 
								100);
				//save to db
				$data = array(
					'avatar_img'=>$_FILES['file']['name']
				);
				if(intval($user['User']['id']) > 0){
					$this->User->id = $user['User']['id'];
					$rs = $this->User->save($data);
					$this->set('response',array('status'=>1,'files'=>$_FILES['file']['name']));	
				}else{
					$this->set('response',array('status'=>400,'error'=>'User not found'));
				}
				
			}else{
				$this->set('response',array('status'=>0,'error'=>'cannot save the uploaded file.'));
			}
		}else if(isset($_POST['file'])){
			$buffer = base64_decode($_POST['file']);
			$new_filename = 'f'.time().rand(0,99999).".jpg";
			$fp = fopen(Configure::read('avatar_img_dir').$new_filename, "wb");
			$w = fwrite($fp, $buffer);
			fclose($fp);
			
			//resize to 120x120 pixels
			$thumb = new Thumbnail();
			$thumb->resizeImage('resizeCrop', $new_filename, 
							Configure::read('avatar_img_dir'), 
							'120x120_'.$new_filename, 
							120, 
							120, 
							100);
			
			if($w){
				//save to db
				$data = array(
					'avatar_img'=>$new_filename
				);
				if(intval($user['User']['id']) > 0){
					$this->User->id = $user['User']['id'];
					$rs = $this->User->save($data);
					$this->set('response',array('status'=>1,'files'=>$new_filename));	
				}else{
					$this->set('response',array('status'=>400,'error'=>'User not found'));
				}
			}else{
				$this->set('response',array('status'=>501,'error'=>'no file uploaded'));
			}
			
			//$this->set('response',array('status'=>2,'error'=>'masih testing','file'=>Configure::read('avatar_img_dir').$new_filename));
			
		}else{
			$this->set('response',array('status'=>500,'error'=>'no file uploaded'));
		}
		$this->render('default');
	}
	private function getCloseTime($nextMatch){
		
		$this->nextMatch = $nextMatch;
		
		$previous_match = @$this->nextMatch['match']['previous_setup'];
				
		$upcoming_match = $this->nextMatch['match']['matchday_setup'];
		
		try{
			$last_matchday = @$this->nextMatch['match']['matchday'] - 1;
		
			$previous_match = @$this->nextMatch['match']['previous_setup'];
			
			$upcoming_match = @$this->nextMatch['match']['matchday_setup'];
		}catch(Exception $e){
			$last_matchday = 0;
			$previous_match = null;
			$upcoming_match = null;
		}



		if($previous_match!=null && $upcoming_match !=null){
			//check the previous match backend proccess status

			$matchstatus = $this->Game->getMatchStatus($previous_match['matchday']);
			
			if($matchstatus['is_finished']==0){

				//if the backend process is not finished,
				//we use the previous match's close time, but use the next match's opentime + 30 days
				$close_time = array("datetime"=>$previous_match['start_dt'],
								"ts"=>strtotime($previous_match['start_dt']));

				$open_time = strtotime($upcoming_match['end_dt']) + (60*60*24*30);
				
			}	
			else if(
				//get close time and open time compare to previous match
				(time() < strtotime($previous_match['start_dt']))
				||
				(time() <= strtotime($previous_match['end_dt']))

			  ){
				$close_time = array("datetime"=>$previous_match['start_dt'],
								"ts"=>strtotime($previous_match['start_dt']));

				$open_time = strtotime($previous_match['end_dt']);
				$matchstatus = $this->Game->getMatchStatus($previous_match['matchday']);
				if($matchstatus['is_finished']==0){
					$open_time += (60*60*24*30);
				}
				
			}else{
				if(time() < strtotime($upcoming_match['start_dt'])){
					//jika pertandingan belum di mulai.. maka open time itu diset berdasarkan
					//opentime minggu lalu
					$open_time = strtotime($previous_match['end_dt']);
				}else if(time() > strtotime($upcoming_match['start_dt'])
						 && time() <= strtotime($upcoming_match['end_dt'])){
					//jika tidak, menggunakan open time berikutnya
					$open_time = strtotime($upcoming_match['end_dt']);
				}else{
					$open_time = strtotime($upcoming_match['end_dt']);
					$matchstatus = $this->Game->getMatchStatus($upcoming_match['matchday']);
					if($matchstatus['is_finished']==0){
						$open_time += (60*60*24*30);
					}
				}

				
				$close_time = array("datetime"=>$upcoming_match['start_dt'],
								"ts"=>strtotime($upcoming_match['start_dt']));

				
			}
		}

		$this->closeTime = @$close_time;

		

		//formation open time
		
		$this->openTime = @$open_time;
				
	}

	public function test(){
		$this->set('response',array('status'=>1,'data'=>array()));
		$this->render('default');
	}
	private function getRingkasanClub(){

	}

	//transfer players stuffs//////
	public function team_list(){
		$teams = $this->Game->getMatchResultStats();

		foreach($teams['data'] as $n=>$v){
			$teams['data'][$n]['stats']['points_earned'] = ($v['stats']['wins'] * 3) + 
															($v['stats']['draws']);
		}
		$rs = $this->sortTeamByPoints($teams['data']);
		$this->set('response',array('status'=>1,'data'=>$rs));
		$this->render('default');
	}
	private function sortTeamByPoints($teams){
		
		$changes = false;
		$n = sizeof($teams);
		for($i=1; $i < sizeof($teams); $i++){
			$swap = false;
			$p = $teams[$i-1];
			$q = $teams[$i];
			$p['stats']['goals'] = intval(@$p['stats']['goals']);
			$p['stats']['conceded'] = intval(@$p['stats']['conceded']);

			$q['stats']['goals'] = intval(@$q['stats']['goals']);
			$q['stats']['conceded'] = intval(@$q['stats']['conceded']);

			if($q['stats']['points_earned'] > $p['stats']['points_earned']){
				$swap = true;
			}else if($q['stats']['points_earned'] == $p['stats']['points_earned']){
				//the most goals wins
				if(($q['stats']['goals'] - $q['stats']['conceded']) > ($p['stats']['goals'] - $p['stats']['conceded'])){
					$swap = true;
				}else if(($q['stats']['goals'] - $q['stats']['conceded']) == ($p['stats']['goals'] - $p['stats']['conceded'])){
					if($q['stats']['goals'] > $p['stats']['goals']){
						$swap = true;
					}
				}
			}
			
			if($swap){
				$changes = true;
				$teams[$i] = $p;
				$teams[$i-1] = $q;
			}

		}
		if($changes){
			return $this->sortTeamByPoints($teams);
		}
		return $teams;

	}

	public function view_team($team_id){
		$this->loadModel('User');
		$api_session = $this->readAccessToken();
		$fb_id = $api_session['fb_id'];
		$userData = $this->User->findByFb_id($fb_id);

		
		$club = $this->Game->getClub($team_id);
		
		$players = $this->Game->getMasterTeam($team_id);

		//list of players
		$my_players = $this->Game->get_team_players($fb_id);
		
		$player_list = array();
		while(sizeof($players)>0){
			$p = array_shift($players);
			$p['stats']['points'] = floatval($p['stats']['points']);
			if(!$this->isMyPlayer($p['uid'],$my_players)){
				if($p['transfer_value']>0){
					$player_list[] = $p;	
				}
				
			}
		}
		foreach($player_list as $n=>$player){
                       
            if($player['transfer_value']>0){
            
                if(intval(@$player['stats']['last_point'])!=0){
                    $player['transfer_value'] = round($player['transfer_value'] + 
                                                getTransferValueBonus(
                                                    floatval(@$player['stats']['performance']),
                                                    $player['transfer_value']));
                }
            }
            $player_list[$n] = $player;
        }
		$rs = array('club'=>$club,
					'players'=>$player_list);

		$this->set('response',array('status'=>1,'data'=>$rs));
		$this->render('default');
	}

	private function isMyPlayer($player_id,$my_players){
		foreach($my_players as $m){
			if($m['uid']==$player_id){
				return true;
			}
		}
	}
	public function view_player($player_id){
		require_once APP . 'Vendor' . DS. 'stats.locale.php';
		$this->loadModel('User');
		$this->loadModel('Point');
		$api_session = $this->readAccessToken();
		$fb_id = $api_session['fb_id'];
		$user = $this->User->findByFb_id($fb_id);
	
		
		if(strlen($user['User']['avatar_img'])<2){
			$user['User']['avatar_img'] = "http://graph.facebook.com/".$fb_id."/picture";
		}else{
			$user['User']['avatar_img'] = Configure::read('avatar_web_url').'120x120_'.$user['User']['avatar_img'];
		}

		$game_team = $this->Game->getTeam($fb_id);
		
		$response = array();
		
		

		$point = $this->Point->findByTeam_id($user['Team']['id']);
		$response['user'] = array('id'=>$user['User']['id'],
									'fb_id'=>$user['User']['fb_id'],
									'name'=>$user['User']['name'],
									'avatar_img'=>$user['User']['avatar_img']);

		$response['stats']['points'] = ceil(floatval(@$point['Point']['points']) + floatval(@$point['Point']['extra_points']));
		$response['stats']['rank'] = intval(@$point['Point']['rank']);

		//budget
		$budget = $this->Game->getBudget($game_team['id']);
		$response['budget'] = $budget;

		//club
		$club = $this->Team->findByUser_id($user['User']['id']);
		$response['club'] = array('id'=>$club['Team']['id'],
									'team_name'=>$club['Team']['team_name'],
									'team_id'=>$club['Team']['team_id'],
								  );

		//get original club
		$original_club = $this->Game->getClub($club['Team']['team_id']);
		$this->set('original',$original_club);
		$response['original_club'] = $original_club;


		//player detail : 
		$rs = $this->Game->get_player_info($player_id);
		
		
		
		
		//stats modifier
		$modifiers = $this->Game->query("SELECT * FROM ".$this->ffgamedb.".game_matchstats_modifier as Modifier");

		if($rs['status']==1){

			if(isset($rs['data']['daily_stats'])&&sizeof($rs['data']['daily_stats'])>0){
				foreach($rs['data']['daily_stats'] as $n=>$v){
					$fixture = $this->Team->query("SELECT matchday,match_date,
										UNIX_TIMESTAMP(match_date) as ts
										FROM ".$this->ffgamedb.".game_fixtures 
										WHERE game_id='{$n}' 
										LIMIT 1");
					
					$rs['data']['daily_stats'][$n]['fixture'] = $fixture[0]['game_fixtures'];
					$rs['data']['daily_stats'][$n]['fixture']['ts'] = $fixture[0][0]['ts'];
				}
			}
			
			
		}
		$games = array(
		        'game_started'=>'game_started',
		        'sub_on'=>'total_sub_on'
		    );

				$passing_and_attacking = array(
		    'goals'=>'goals',
		    'att_freekick_goal'=>'att_freekick_goal',
		    'att_pen_goal'=>'att_pen_goal',
		    'att_ibox_target'=>'att_ibox_target',
		    'att_obox_target'=>'att_obox_target',
		    'goal_assist_openplay'=>'goal_assist_openplay',
		    'goal_assist_setplay'=>'goal_assist_setplay',
		    'att_assist_openplay'=>'att_assist_openplay',
		    'att_assist_setplay'=>'att_assist_setplay',
		    'second_goal_assist'=>'second_goal_assist',
		    'big_chance_created'=>'big_chance_created',
		    'accurate_through_ball'=>'accurate_through_ball',
		    'accurate_cross_nocorner'=>'accurate_cross_nocorner',
		    'accurate_pull_back'=>'accurate_pull_back',
		    'won_contest'=>'won_contest',
		    'long_pass_own_to_opp_success'=>'long_pass_own_to_opp_success',
		    'accurate_long_balls'=>'accurate_long_balls',
		    'accurate_flick_on'=>'accurate_flick_on',
		    'accurate_layoffs'=>'accurate_layoffs',
		    'penalty_won'=>'penalty_won',
		    'won_corners'=>'won_corners',
		    'fk_foul_won'=>'fk_foul_won'
		  );


		$defending = array(
		'duel_won'  =>  'duel_won',
		'aerial_won'    =>  'aerial_won',
		'ball_recovery' =>  'ball_recovery',
		'won_tackle'    =>  'won_tackle',
		'interception_won'  =>  'interception_won',
		'interceptions_in_box'  =>  'interceptions_in_box',
		'offside_provoked'  =>  'offside_provoked',
		'outfielder_block'  =>  'outfielder_block',
		'effective_blocked_cross'   =>  'effective_blocked_cross',
		'effective_head_clearance'  =>  'effective_head_clearance',
		'effective_clearance'   =>  'effective_clearance',
		'clearance_off_line'    =>  'clearance_off_line'  


		  );

		$goalkeeper = array(
		              'good_high_claim'=> 'good_high_claim',
		              'saves'=> 'saves',
		             
		                  );


		$mistakes_and_errors = array(
		            'penalty_conceded'=>    'penalty_conceded',
		            'fk_foul_lost'=>    'fk_foul_lost',
		            'poss_lost_all'=>   'poss_lost_all',
		            'challenge_lost'=>  'challenge_lost',
		            'error_lead_to_shot'=>  'error_lead_to_shot',
		            'error_lead_to_goal'=>  'error_lead_to_goal',
		            'total_offside'=>   'total_offside',
		            'yellow_card'=>   'yellow_card',
		            'red_card'=>   'red_card'
		          );
		$map = array('games'=>$games,
		              'passing_and_attacking'=>$passing_and_attacking,
		              'defending'=>$defending,
		              'goalkeeper'=>$goalkeeper,
		              'mistakes_and_errors'=>$mistakes_and_errors
		             );
		
		$data = $rs['data'];

		
		switch($data['player']['position']){
		    case 'Forward':
		        $pos = "f";
		    break;
		    case 'Midfielder':
		        $pos = "m";
		    break;
		    case 'Defender':
		        $pos = "d";
		    break;
		    default:
		        $pos = 'g';
		    break;
		}
		$total_points = 0;
		$main_stats_vals = array('games'=>0,
		                            'passing_and_attacking'=>0,
		                            'defending'=>0,
		                            'goalkeeper'=>0,
		                            'mistakes_and_errors'=>0,
		                         );



		if(isset($data['overall_stats'])){
		    foreach($data['overall_stats'] as $stats){
		        foreach($map as $mainstats=>$substats){
		            foreach($substats as $n=>$v){
		                
		                if($v==$stats['stats_name']){
		                    if(!isset($main_stats_vals[$mainstats])){
		                        $main_stats_vals[$mainstats] = 0;
		                        $main_stats_ori[$mainstats] = 0;
		                    }
		                    $main_stats_vals[$mainstats] += ($stats['total'] *
		                                                    $this->getModifierValue($modifiers,
		                                                                            $v,
		                                                                            $pos));

		                   
		                }
		            }
		        }
		    }
		    foreach($main_stats_vals as $n){
		        $total_points += $n;
		    }

			

			
		    $profileStats = $this->getStats('games',$pos,$modifiers,$map,$data['overall_stats']);
		    $games = array();
		    foreach($profileStats as $statsName=>$statsVal){
		    	$statsName = stats_translated($statsName,'id');
		    	$games[$statsName] = $statsVal;
		    }
			$profileStats = $this->getStats('passing_and_attacking',$pos,$modifiers,$map,$data['overall_stats']);
		    $passing_and_attacking = array();
		    foreach($profileStats as $statsName=>$statsVal){
		    	$statsName = stats_translated($statsName,'id');
		    	$passing_and_attacking[$statsName] = $statsVal;
		    }
		    $profileStats = $this->getStats('defending',$pos,$modifiers,$map,$data['overall_stats']);
		    $defending = array();
		    foreach($profileStats as $statsName=>$statsVal){
		    	$statsName = stats_translated($statsName,'id');
		    	$defending[$statsName] = $statsVal;
		    }
           
		    $profileStats = $this->getStats('goalkeeper',$pos,$modifiers,$map,$data['overall_stats']);
		    $goalkeeper = array();
		    foreach($profileStats as $statsName=>$statsVal){
		    	$statsName = stats_translated($statsName,'id');
		    	$goalkeeper[$statsName] = $statsVal;
		    }

		    $profileStats = $this->getStats('mistakes_and_errors',$pos,$modifiers,$map,$data['overall_stats']);
		    $mistakes_and_errors = array();
		    foreach($profileStats as $statsName=>$statsVal){
		    	$statsName = stats_translated($statsName,'id');
		    	$mistakes_and_errors[$statsName] = $statsVal;
		    }

			$stats = array(
				'games'=>$games,
				'passing_and_attacking'=>$passing_and_attacking,
				'defending'=>$defending,
				'goalkeeping'=>$goalkeeper,
				'mistakes_and_errors'=>$mistakes_and_errors,

			);
			
		}
		$performance = 0;
        if(sizeof($data['stats'])>0){
            if(intval(@$data['stats'][sizeof($data['stats'])-1]['points'])!=0){
                $performance = getTransferValueBonus(
                                                $data['stats'][sizeof($data['stats'])-1]['performance'],
                                               $data['player']['transfer_value']);
            }
        }
      
        $data['player']['transfer_value'] = round($data['player']['transfer_value'] + $performance);
		$response['player'] = array('info'=>$data['player'],
									 'summary'=>$main_stats_vals,
										'stats'=>$stats);
		
		
		$this->set('response',array('status'=>1,'data'=>$response));
		$this->render('default');
	}
	/**
	* sale a player
	*/
	public function sale($player_id){
		$this->loadModel('Team');
		$this->loadModel('User');
		$api_session = $this->readAccessToken();
		$fb_id = $api_session['fb_id'];

		$user = $this->User->findByFb_id($fb_id);
		
		Cakelog::write('debug', 'Api.sale fb_id:'.$fb_id.' user:'.json_encode($user).' player_id:'.$player_id);
		if(strlen($user['User']['avatar_img'])<2){
			$user['User']['avatar_img'] = "http://graph.facebook.com/".$fb_id."/picture";
		}else{
			$user['User']['avatar_img'] = Configure::read('avatar_web_url').'120x120_'.$user['User']['avatar_img'];
		}

		$game_team = $this->Game->getTeam($fb_id);

		$player_id = Sanitize::clean($player_id);

		$window = $this->Game->transfer_window();

		//check if the transfer window is opened, or the player is just registered within 24 hours
		$is_new_user = false;
		$can_transfer = false;
		
		if(time()<strtotime($this->user['User']['register_date'])+(24*60*60)){
			$is_new_user = true;
		}
		if(!$is_new_user){
			if(strtotime(@$window['tw_open']) <= time() && strtotime(@$window['tw_close'])>=time()){
				$can_transfer = true;
				
			}
		}else{
			$can_transfer = true;
		}
		
		if(strlen($player_id)<2){
			
			$rs = array('status'=>'0','error'=>'no data available');

		}else{

			if($can_transfer){
				$window_id = $window['id'];
				$rs = $this->Game->sale_player($window_id,$game_team['id'],$player_id);

				//reset financial statement
				$this->Session->write('FinancialStatement',null);
				
				
				if(@$rs['status']==1){
					//do nothing
				}else if(@$rs['status']==2){
					$rs = array('status'=>2,'message'=>'No Money');
					
				}else if(@$rs['status']==-1){
					$rs = array('status'=>-1,'message'=>'you cannot sale a player who already bought from the same transfer window');
					
				}else if(isset($rs['error'])){
					$rs = array('status'=>'0','error'=>'Transaction Failed');
				}
			}else{
				$msg = 'Transfer window SuperSoccer Football Manager sedang tutup, silahkan balik lagi tanggal 11';
				if($this->league= 'ita'){
					$msg = 'Transfer window SuperSoccer Football Manager sedang tutup, silahkan balik lagi tanggal 17';
				}
				$rs = array('status'=>3,'message'=>$msg,'open'=>strtotime(@$window['tw_open']),
							'close'=> strtotime(@$window['tw_close']), 'now'=>time());
			}
		}
		
		$this->set('response',$rs);
		$this->render('default');
	}
	public function livestats($game_id){
		$game_id = Sanitize::paranoid($game_id);
		if($this->ffgamedb==null){
			$this->ffgamedb = "ffgame";
		}
		$rs = $this->Game->query("SELECT home_id,away_id,b.name AS home_name,c.name AS away_name 
							FROM ".$this->ffgamedb.".game_fixtures a
							INNER JOIN ".$this->ffgamedb.".master_team b
							ON a.home_id = b.uid
							INNER JOIN ".$this->ffgamedb.".master_team c
							ON a.away_id = c.uid
							WHERE a.game_id='{$game_id}' AND a.session_id=2014
							LIMIT 1;");

		$response = $this->Game->livestats($game_id);
		$data = json_decode($response,true);
		$data['fixture']  = array(
			'home'=>@$rs[0]['b']['home_name'],
			'away'=>@$rs[0]['c']['away_name'],
			'home_id'=>@$rs[0]['a']['home_id'],
			'away_id'=>@$rs[0]['a']['away_id']
		);
		$data['raw'] = $rs;
		
		$this->set('response',$data);
		$this->render('default');
	}
	
	public function livegoals($game_id){
		$response = $this->Game->livegoals($game_id);
		$this->set('response',$response);
		$this->set('raw',true);
		$this->render('default');
	}
	public function livematches(){
		//first we have to know our current
		$matchday = 1;
		$response = $this->Game->livematches($this->league);
		$this->set('response',$response);
		$this->set('raw',true);
		$this->render('default');
	}
	/**
	* buy a player
	*/
	public function buy($player_id){
		$this->loadModel('Team');
		$this->loadModel('User');
		$api_session = $this->readAccessToken();
		$fb_id = $api_session['fb_id'];
		$user = $this->User->findByFb_id($fb_id);
		
		
		if(strlen($user['User']['avatar_img'])<2){
			$user['User']['avatar_img'] = "http://graph.facebook.com/".$fb_id."/picture";
		}else{
			$user['User']['avatar_img'] = Configure::read('avatar_web_url').'120x120_'.$user['User']['avatar_img'];
		}

		$game_team = $this->Game->getTeam($fb_id);

		$player_id = Sanitize::clean($player_id);

		$window = $this->Game->transfer_window();
		$window_id = intval(@$window['id']);
		
		//check if the transfer window is opened, or the player is just registered within 24 hours
		$is_new_user = false;
		$can_transfer = false;

		if(time()<strtotime($user['User']['register_date'])+(24*60*60)){
			$is_new_user = true;
		}

		if(!$is_new_user){
			if(strtotime(@$window['tw_open']) <= time() && strtotime(@$window['tw_close'])>=time()){
				$can_transfer = true;
				
			}
		}else{
			$can_transfer = true;
		}

		if(strlen($player_id)<2){
			
			$rs = array('status'=>'0','error'=>'no data available');

		}else{
			if($can_transfer){
				$rs = $this->Game->buy_player($window_id,$game_team['id'],$player_id);
			
				//reset financial statement
				$this->Session->write('FinancialStatement',null);
				

				if(@$rs['status']==1){
					$msg = "@p1_".$user['User']['id']." telah membeli {$rs['data']['name']} seharga SS$".number_format($rs['data']['transfer_value']);
					
				}else if(@$rs['status']==2){
					$rs = array('status'=>2,'message'=>'No Money');
					
				}else if(@$rs['status']==-1){
					$rs = array('status'=>-1,'message'=>'you cannot buy a player who already sold from the same transfer window');
					
				}else if(isset($rs['error'])){
					$rs = array('status'=>'0','error'=>'Transaction Failed');
				}
			}else{
				$msg = 'Transfer window SuperSoccer Football Manager sedang tutup, silahkan balik lagi tanggal 11';
				if($this->league == 'ita'){
					$msg = 'Transfer window SuperSoccer Football Manager sedang tutup, silahkan balik lagi tanggal 17';
				}
				$rs = array('status'=>3,'message'=>$msg);
			}
		}

		

		$this->set('response',$rs);
		$this->render('default');
	}
	public function transfer_status(){
		$this->loadModel('Team');
		$this->loadModel('User');
		$api_session = $this->readAccessToken();
		$fb_id = $api_session['fb_id'];
		$user = $this->User->findByFb_id($fb_id);
	

		$window = $this->Game->transfer_window();
		$window_id = intval(@$window['id']);
		
		//check if the transfer window is opened, or the player is just registered within 24 hours
		$is_new_user = false;
		$can_transfer = false;
		
		if(time()<strtotime($user['User']['register_date'])+(24*60*60)){
			$is_new_user = true;
		}

		if(!$is_new_user){
			if(strtotime(@$window['tw_open']) <= time() && strtotime(@$window['tw_close'])>=time()){
				$can_transfer = true;
				
			}
		}else{
			$can_transfer = true;
		}

		
		if($can_transfer){
			$rs = array('status'=>1,'message'=>'Transfer window is open');
		}else{
			$msg = 'Transfer window SuperSoccer Football Manager sedang tutup, silahkan balik lagi tanggal 11';
			if($this->league= 'ita'){
				$msg = 'Transfer window SuperSoccer Football Manager sedang tutup, silahkan balik lagi tanggal 17';
			}
			$rs = array('status'=>0,'message'=>$msg);
		}

	
		$this->set('response',$rs);
		$this->render('default');
	}


	//online catalog API
	public function catalog(){
		$this->loadModel('MerchandiseItem');
		$this->loadModel('MerchandiseCategory');
		$this->loadModel('MerchandiseOrder');

		if(isset($this->request->query['ctoken'])){
			$catalog_token = unserialize(decrypt_param($this->request->query['ctoken']));	
		}else{
			$catalog_token = '';
		}
		
		$user_fb_id = Sanitize::clean(@$catalog_token['fb_id']);

		$since_id = intval(@$this->request->query['since_id']);

		$start = intval(@$this->request->query['start']);
		$total = intval(@$this->request->query['total']);

		if($total > 10){
			$total = 15;
		}

		$response = array();

		if(isset($this->request->query['cid'])){
			$category_id = intval($this->request->query['cid']);
		}else{
			$category_id = 0;
		}
		
		$merchandise = $this->MerchandiseItem->find('count',array('conditions'=>array('merchandise_type'=>0,'n_status'=>1)));

		if($merchandise > 0){
			$response['has_merchandise'] = true;
		}else{
			$response['has_merchandise'] = false;
		}
		

		//bind the model's association first.
		//i'm too lazy to create a new Model Class :P
		$this->MerchandiseItem->bindModel(array(
			'belongsTo'=>array('MerchandiseCategory'=>array('type'=>'inner',
					'conditions'=>array(
						"is_mobile = 0"
					)))
		));

		//we need to populate the category
		$categories = $this->getCatalogMainCategories();
		$response['main_categories'] = $categories;

		$total_rows = 0;
		//if category is set, we filter the query by category_id
		if($category_id != 0 && 
			intval($category_id) != intval(Configure::read('DIGITAL_ITEM_CATEGORY'))){
			$category_ids = array($category_id);
			//check for child ids, and add it into category_ids
			$category_ids = $this->getChildCategoryIds($category_id,$category_ids);
			$options = array('conditions'=>array(
									'merchandise_category_id'=>$category_ids,
									'MerchandiseItem.parent_id'=>0,
									'merchandise_type'=>0,
									'is_pro_item'=>0,
									'n_status'=>1),
									'offset'=>$start,
									'limit'=>$total,
									'order'=>array('MerchandiseItem.id'=>'DESC')
									);



			//maybe the category has children in it.
			//so we try to populate it
			$child_categories = $this->getChildCategories($category_id);
			$response['child_categories'] = $child_categories;

			//we need to know the category details
			$category = $this->MerchandiseCategory->findById($category_id);
			$response['current_category'] = $category['MerchandiseCategory'];
			

		}else{
			//if doesnt, we query everything.
			$options = array(
						'conditions'=>array('merchandise_type'=>0,
											'is_pro_item'=>0,
											'MerchandiseItem.parent_id'=>0,
											'price_money > 0','n_status'=>1),
						'offset'=>$start,
						'limit'=>$total,
						'order'=>array('MerchandiseItem.id'=>'DESC')
						);
		}


		

		//retrieve the results.
		$rs = $this->MerchandiseItem->find('all',$options);

		//retrieve the total rows
		unset($options['limit']);
		unset($options['offset']);
		$total_rows = $this->MerchandiseItem->find('count',$options);
		
		//check the stock for each items
		for($i=0;$i<sizeof($rs);$i++){
			//get the available stock
			
			
			$rs[$i]['MerchandiseItem']['available'] = $rs[$i]['MerchandiseItem']['stock'];

			//prepare the picture url
			$pic = Configure::read('avatar_web_url').
										"merchandise/thumbs/0_".
										$rs[$i]['MerchandiseItem']['pic'];
			$rs[$i]['MerchandiseItem']['picture'] = $pic;

			//check if the item has child_items
			$opts = array('conditions'=>array('parent_id'=>$rs[$i]['MerchandiseItem']['id']));
			$child_items = $this->MerchandiseItem->find('count',$opts);
			
			if($child_items > 0){
				$rs[$i]['has_child'] = 1;
			}else{
				$rs[$i]['has_child'] = 0;
			}

		}
		//assign it.
		
		$response['items'] = $rs;

		//setup new offset pointers
		if(sizeof($rs) > 0){
			$next_offset = $start + $total;
		}else{
			$next_offset = $start;
		}
		
		$previous_offset = $start - $total;
		if($previous_offset < 0){
			$previous_offset = 0;
		}
		//-->


		//and here's the JSON output
		$this->layout="ajax";
		$this->set('response',array('status'=>1,
									'data'=>$response,
									'offset'=>$start,
									'limit'=>$total,
									'next_offset'=>$next_offset,
									'previous_offset'=>$previous_offset,
									'total_rows'=>$total_rows));


		$this->render('default');
	}

	///api for displaying the catalog's item
	public function catalog_item($item_id){
		$this->loadModel('MerchandiseItem');
		$this->loadModel('MerchandiseCategory');
		$this->loadModel('MerchandiseOrder');


		//we need to populate the category
		$categories = $this->getCatalogMainCategories();
		$response['main_categories'] = $categories;

		
		//parno mode.
		$item_id = intval(Sanitize::clean($item_id));


		$where = array('conditions'=>array('id'=> $item_id, 'is_pro_item'=>0, 'n_status'=>1));
		//get the item detail
		$item = $this->MerchandiseItem->find('first', $where);
		
			
		$item['MerchandiseItem']['available'] = $item['MerchandiseItem']['stock'];

		//prepare the picture url
		$pic = Configure::read('avatar_web_url').
									"merchandise/thumbs/0_".
									$item['MerchandiseItem']['pic'];
		$item['MerchandiseItem']['picture'] = $pic;

		$response['item'] = $item['MerchandiseItem'];
		

		$category = $this->MerchandiseCategory->findById($item['MerchandiseItem']['merchandise_category_id']);
		$response['current_category'] = $category['MerchandiseCategory'];

		$child_opts = array('conditions'=>array('parent_id'=>$item['MerchandiseItem']['id'],
												'n_status'=>1),
							'limit'=>100,
							'order'=>'MerchandiseItem.id');

		$response['children'] = $this->MerchandiseItem->find('all',$child_opts);
		$response['parent'] = $this->MerchandiseItem->findById($item['MerchandiseItem']['parent_id']);
		$this->layout="ajax";
		$status = 1;
		if($category['MerchandiseCategory']['is_mobile'] == 1){
			$status = 0;
			$response = '';
		}
		$this->set('response',array('status'=>$status,'data'=>$response));
		$this->render('default');
	}


	//online catalog API for mobile apps
	public function m_catalog(){
		$this->loadModel('MerchandiseItem');
		$this->loadModel('MerchandiseCategory');
		$this->loadModel('MerchandiseOrder');

		if(isset($this->request->query['ctoken'])){
			$catalog_token = unserialize(decrypt_param($this->request->query['ctoken']));	
		}else{
			$catalog_token = '';
		}
		
		$user_fb_id = Sanitize::clean(@$catalog_token['fb_id']);

		$since_id = intval(@$this->request->query['since_id']);

		$start = intval(@$this->request->query['start']);
		$total = intval(@$this->request->query['total']);

		if($total > 10){
			$total = 15;
		}

		$response = array();

		if(isset($this->request->query['cid'])){
			$category_id = intval($this->request->query['cid']);
		}else{
			$category_id = 0;
		}
		
		$merchandise = $this->MerchandiseItem->find('count',array('conditions'=>array('merchandise_type'=>0,'n_status'=>1)));

		if($merchandise > 0){
			$response['has_merchandise'] = true;
		}else{
			$response['has_merchandise'] = false;
		}
		

		//bind the model's association first.
		//i'm too lazy to create a new Model Class :P
		$this->MerchandiseItem->bindModel(array(
			'belongsTo'=>array('MerchandiseCategory'=>array(
								'type' => 'inner',
								'conditions' => array('is_mobile' => 1)
							))
		));

		//we need to populate the category
		$categories = $this->getCatalogMainCategoriesMobile();
		$response['main_categories'] = $categories;

		$total_rows = 0;
		//if category is set, we filter the query by category_id
		if($category_id != 0){
			$category_ids = array($category_id);
			//check for child ids, and add it into category_ids
			$category_ids = $this->getChildCategoryIds($category_id,$category_ids);
			$options = array('conditions'=>array(
									'price_credit != 0',
									'merchandise_category_id'=>$category_ids,
									'MerchandiseItem.parent_id'=>0,
									'merchandise_type'=>0,'n_status'=>1),
									'offset'=>$start,
									'limit'=>$total,
									'order'=>array('MerchandiseItem.id'=>'DESC')
									);



			//maybe the category has children in it.
			//so we try to populate it
			$child_categories = $this->getChildCategories($category_id);
			$response['child_categories'] = $child_categories;

			//we need to know the category details
			$category = $this->MerchandiseCategory->findById($category_id);
			$response['current_category'] = $category['MerchandiseCategory'];
			

		}else{
			//if doesnt, we query everything.
			$options = array(
						'conditions'=>array('merchandise_type'=>0,'MerchandiseItem.parent_id'=>0,
											'price_credit != 0','n_status'=>1),
						'offset'=>$start,
						'limit'=>$total,
						'order'=>array('MerchandiseItem.id'=>'DESC')
						);
		}

		//retrieve the results.
		$rs = $this->MerchandiseItem->find('all',$options);

		//retrieve the total rows
		unset($options['limit']);
		unset($options['offset']);
		$total_rows = $this->MerchandiseItem->find('count',$options);
		
		//check the stock for each items
		for($i=0;$i<sizeof($rs);$i++){
			//get the available stock
			
			
			$rs[$i]['MerchandiseItem']['available'] = $rs[$i]['MerchandiseItem']['stock'];

			//prepare the picture url
			$pic = Configure::read('avatar_web_url').
										"merchandise/thumbs/0_".
										$rs[$i]['MerchandiseItem']['pic'];
			$rs[$i]['MerchandiseItem']['picture'] = $pic;

			//check if the item has child_items
			$opts = array('conditions'=>array('parent_id'=>$rs[$i]['MerchandiseItem']['id']));
			$child_items = $this->MerchandiseItem->find('count',$opts);
			
			if($child_items > 0){
				$rs[$i]['has_child'] = 1;
			}else{
				$rs[$i]['has_child'] = 0;
			}

		}
		//assign it.
		
		$response['items'] = $rs;

		//setup new offset pointers
		if(sizeof($rs) > 0){
			$next_offset = $start + $total;
		}else{
			$next_offset = $start;
		}
		
		$previous_offset = $start - $total;
		if($previous_offset < 0){
			$previous_offset = 0;
		}
		//-->


		//and here's the JSON output
		$this->layout="ajax";
		$this->set('response',array('status'=>1,
									'data'=>$response,
									'offset'=>$start,
									'limit'=>$total,
									'next_offset'=>$next_offset,
									'previous_offset'=>$previous_offset,
									'total_rows'=>$total_rows));


		$this->render('default');
	}

	///api for displaying the catalog's item
	public function m_catalog_item($item_id){
		$this->loadModel('MerchandiseItem');
		$this->loadModel('MerchandiseCategory');
		$this->loadModel('MerchandiseOrder');


		//we need to populate the category
		$categories = $this->getCatalogMainCategories();
		$response['main_categories'] = $categories;

		
		//parno mode.
		$item_id = intval(Sanitize::clean($item_id));


		$where = array('conditions'=>array('id'=> $item_id, 'n_status'=>1));
		//get the item detail
		$item = $this->MerchandiseItem->find('first', $where);
		
		
			
		$item['MerchandiseItem']['available'] = $item['MerchandiseItem']['stock'];

		//prepare the picture url
		$pic = Configure::read('avatar_web_url').
									"merchandise/thumbs/0_".
									$item['MerchandiseItem']['pic'];
		$item['MerchandiseItem']['picture'] = $pic;

		$response['item'] = $item['MerchandiseItem'];
		

		$category = $this->MerchandiseCategory->findById($item['MerchandiseItem']['merchandise_category_id']);
		$response['current_category'] = $category['MerchandiseCategory'];

		$child_opts = array('conditions'=>array('parent_id'=>$item['MerchandiseItem']['id'],
												'n_status'=>1),
							'limit'=>100,
							'order'=>'MerchandiseItem.id');

		$response['children'] = $this->MerchandiseItem->find('all',$child_opts);
		$response['parent'] = $this->MerchandiseItem->findById($item['MerchandiseItem']['parent_id']);
		$this->layout="ajax";
		$status = 1;
		if($category['MerchandiseCategory']['is_mobile'] == 0){
			$status = 0;
			$response = '';
		}

		$this->set('response',array('status'=>$status,'data'=>$response));
		$this->render('default');
	}
	/*
	* ecash url untuk pembayaran ongkir
	*/
	public function payment_url($order_id){
		$this->loadModel('MerchandiseOrder');
		$this->loadModel('Ongkir');
		$ongkir = $this->Ongkir->find('all',array('limit'=>10000));

		$rs = $this->MerchandiseOrder->find('first', array(
				    	'conditions' => array(
				    			'id' => $order_id,
				    			'payment_method' => 'coins'
				    	)));
		
		foreach($ongkir as $ok){
			if($ok['Ongkir']['id'] == $rs['MerchandiseOrder']['ongkir_id']){
				$city = $ok['Ongkir'];
			}
		}
	
		$kg = 0;
		for($i=0;$i<sizeof($items);$i++){
			$kg = intval($items[$i]['qty']) * ceil(floatval(@$items[$i]['data']['MerchandiseItem']['weight']));
		}

		$total_ongkir = $kg * $city['cost'];
		//add suffix -1 to define that its the payment for shipping for these po number.
		$transaction_id =  $rs['MerchandiseOrder']['po_number'].'-1';

		//ecash url
		$rs = $this->Game->getEcashUrl(array(
			'transaction_id'=>$transaction_id,
			'amount'=>$total_ongkir,
			'clientIpAddress'=>$this->request->clientIp(),
			'description'=>'Shipping Fee #'.$transaction_id,
			'source'=>'SSPAY'
		));

		$this->set('transaction_id',$transaction_id);
		$this->set('ecash_url',$rs['data']);

		$this->layout="ajax";
		$this->set('response',array('status'=>1,'data'=>$rs['data'],'transaction_id'=>$transaction_id));
		$this->render('default');

	}

	/*
	* API untuk mendapatkan url pembayaran ecash.
	*/
	public function ecash_url($game_team_id){
		$this->loadModel('MerchandiseItem');
		$this->loadModel('MerchandiseCategory');
		$this->loadModel('MerchandiseOrder');

		CakeLog::write('debug',json_encode($this->request->data));

		$shopping_cart = unserialize(decrypt_param($this->request->data['param']));
		$transaction_id = intval(@$game_team_id).'-'.date("YmdHis").'-'.rand(0,99);
		$description = 'Purchase Order #'.$transaction_id;
		$fb_id = $this->request->data['fb_id'];

		//get total money to be spent.
		$total_price = 0;
		$all_digital = true;
		$kg = 0;
		$category = array();
		$total_admin_fee = 0;
		for($i=0;$i<sizeof($shopping_cart);$i++){

			$shopping_cart[$i]['data'] = $this->MerchandiseItem->findById($shopping_cart[$i]['item_id']);
			$item = $shopping_cart[$i]['data']['MerchandiseItem'];
			$kg += floatval($item['weight']) * intval($shopping_cart[$i]['qty']);
			$total_price += (intval($shopping_cart[$i]['qty']) * intval($item['price_money']));
			$total_admin_fee += $item['admin_fee'];
			CakeLog::write('debug', "aaaaaaaaaa".json_encode($item));
			$category[$i] = $item['merchandise_category_id'];
			//is there any non-digital item ?
			if($item['merchandise_type']==0){
				$all_digital = false;
			}
		}
		$kg = ceil($kg);


		//book the item stocks
		$this->book_items($shopping_cart,$transaction_id,$game_team_id,$fb_id);
		
		$admin_fee = $total_admin_fee;
		$enable_ongkir = true;
		if(count($shopping_cart) > 1)
		{
			//$admin_fee = Configure::read('PO_ADMIN_FEE'); -> skip
		}
		else
		{
			//check enable or disable admin fee

			//check ongkir
			if($item['enable_ongkir'] == 0)
			{
				$enable_ongkir = false;
			}
		}

		$contain_tiket = $this->check_category_ticket($category);

		if($contain_tiket){
			$admin_fee = 5000;
			$enable_ongkir = false;
		}

		if($all_digital){
			$admin_fee = 0;
		}
		$total_price += $admin_fee;

		//include ongkir
		if($enable_ongkir)
		{
			$ongkirList = $this->getOngkirList();
			foreach($ongkirList as $ongkir){
				if($ongkir['Ongkir']['id'] == intval($this->request->data['city_id'])){
					$base_ongkir = intval($ongkir['Ongkir']['cost']);
					break;
				}
			}
		}

		$total_ongkir = $base_ongkir*$kg;
		
		$total_price += $total_ongkir;

		$transaction_data = array('profile'=>$this->request->data,
								 'shopping_cart'=>$shopping_cart,
								 'base_ongkir_value'=>$base_ongkir);
		

		$rs = $this->Game->getEcashUrl(array(
			'transaction_id'=>$transaction_id,
			'description'=>$description,
			'amount'=>$total_price,
			'clientIpAddress'=>$this->request->clientIp(),
			'source'=>'fm'
		));
		if($rs['data']!='#'){
			$this->Game->storeToTmp(intval(@$game_team_id),$transaction_id,encrypt_param(serialize($transaction_data)));
		}
		CakeLog::write('debug',intval(@$game_team_id).'-'.$transaction_id.'-'.json_encode($transaction_data));

		$this->layout="ajax";
		$this->set('response',array('status'=>1,'data'=>$rs['data'],'transaction_id'=>$transaction_id));
		$this->render('default');
	}

	public function doku_create_order($game_team_id, $payment_channel)
	{
		$this->loadModel('MerchandiseItem');
		$this->loadModel('MerchandiseCategory');
		$this->loadModel('MerchandiseOrder');
		$this->loadModel('Doku');

		CakeLog::write('doku','doku_create_order '.json_encode($this->request->data));

		$shopping_cart = unserialize(decrypt_param($this->request->data['param']));

		$transaction_id = $this->request->data['transaction_id'];
		
		$description = 'Purchase Order #'.$transaction_id;
		$fb_id = $this->request->data['fb_id'];

		//get total money to be spent.
		$total_price = 0;
		$all_digital = true;
		$kg = 0;
		$category = array();
		$basket = "Pembelian ";
		$total_admin_fee = 0;
		for($i=0;$i<sizeof($shopping_cart);$i++){

			$shopping_cart[$i]['data'] = $this->MerchandiseItem->findById($shopping_cart[$i]['item_id']);
			$item = $shopping_cart[$i]['data']['MerchandiseItem'];
			$basket .= htmlspecialchars($item['name']).','.$item['price_money'].','.$item['id'].','.$item['price_money'].';';
			$kg += floatval($item['weight']) * intval($shopping_cart[$i]['qty']);
			$total_admin_fee += $item['admin_fee'];
			$total_price += (intval($shopping_cart[$i]['qty']) * intval($item['price_money']));
			$category[$i] = $item['merchandise_category_id'];
			//is there any non-digital item ?
			if($item['merchandise_type']==0){
				$all_digital = false;
			}
		}
		$basket = rtrim($basket,',');
		$kg = ceil($kg);


		//book the item stocks
		$this->book_items($shopping_cart,$transaction_id,$game_team_id,$fb_id);
		
		$admin_fee = $total_admin_fee;
		$enable_ongkir = true;
		if(count($shopping_cart) > 1)
		{
			//$admin_fee = Configure::read('PO_ADMIN_FEE'); -> skip
		}
		else
		{
			//check ongkir
			if($item['enable_ongkir'] == 0)
			{
				$enable_ongkir = false;
			}
		}

		$contain_tiket = $this->check_category_ticket($category);

		if($contain_tiket){
			$admin_fee = 5000;
			$enable_ongkir = false;
		}

		if($all_digital){
			$admin_fee = 0;
		}
		$total_price += $admin_fee;

		//include ongkir
		if($enable_ongkir)
		{
			$ongkirList = $this->getOngkirList();
			foreach($ongkirList as $ongkir){
				if($ongkir['Ongkir']['id'] == intval($this->request->data['city_id'])){
					$base_ongkir = intval($ongkir['Ongkir']['cost']);
					break;
				}
			}
		}
		$total_ongkir = ($kg*$base_ongkir);
		$total_price += $total_ongkir;

		$transaction_data = array('profile'=>$this->request->data,
								 'shopping_cart'=>$shopping_cart,
								 'base_ongkir_value'=>$base_ongkir);

		$doku_mid = Configure::read('DOKU_MALLID');
		$doku_sharedkey = Configure::read('DOKU_SHAREDKEY');
		$hash_words = sha1(number_format($total_price,2,'.','').
						  $doku_mid.
						  $doku_sharedkey.
						  str_replace('-', '', $transaction_id));
		$trx_session_id = sha1(time());
		$data = array('MALLID'=>$doku_mid,
						'CHAINMERCHANT'=>'NA',
						'AMOUNT'=>number_format($total_price,2,'.',''),
						'PURCHASEAMOUNT'=>number_format($total_price,2,'.',''),
						'TRANSIDMERCHANT'=>str_replace('-', '', $transaction_id),
						'WORDS'=>$hash_words,
						'REQUESTDATETIME'=>date("YmdHis"),
						'CURRENCY'=>'360',
						'PURCHASECURRENCY'=>'360',
						'SESSIONID'=>$trx_session_id,
						'NAME'=>$this->request->data['first_name'].' '.$this->request->data['last_name'],
						'EMAIL'=>$this->request->data['email'],
						'ADDITIONALDATA'=>encrypt_param($transaction_id),
						'PAYMENTCHANNEL'=>$payment_channel,
						'BASKET'=>$basket
						);

		try{

			$rs_order = $this->MerchandiseOrder->findByPo_number($transaction_id);
			$rs_user = $this->User->findByFb_id($fb_id);
			$dataSource = $this->MerchandiseOrder->getDataSource();
			$dataSource->begin();

			$payment_method = 'cc';
			if($payment_channel != '01'){
				$payment_method = 'va';
			}
			if(count($rs_order) > 0){
				$rs_doku = $this->Doku->findByPo_number($transaction_id);
				throw new Exception("entry already exists");
			}

			$this->MerchandiseOrder->create();
			$rs_order = $this->MerchandiseOrder->save(array(
					'fb_id'=>$this->request->data['fb_id'],
					'po_number'=>$transaction_id,
					'game_team_id'=>intval($game_team_id),
					'user_id'=>$rs_user['User']['id'],
					'first_name'=>$this->request->data['first_name'],
					'last_name'=>$this->request->data['last_name'],
					'ktp'=>$this->request->data['ktp'],
					'email'=>$this->request->data['email'],
					'phone'=>$this->request->data['mobile_phone'],
					'city'=>$this->request->data['kota'],
					'address'=>$this->request->data['address'],
					'province'=>$this->request->data['province'],
					'country'=>$this->request->data['country'],
					'zip'=>$this->request->data['zip'],
					'order_date'=>date('Y-m-d H:i:s'),
					'data'=>serialize($shopping_cart),
					'payment_method'=>$payment_method,
					'total_sale'=>$total_price,
					'ongkir_id'=>$this->request->data['city_id'],
					'ongkir_value' => $base_ongkir,
					'total_weight' => $kg,
					'total_ongkir' => $total_ongkir,
					'total_admin_fee' => $admin_fee,
					'n_status' => 0
			));


			$this->Doku->create();
			$rs_doku = $this->Doku->save(array(
					'catalog_order_id'=>$this->MerchandiseOrder->getInsertID(),
					'po_number'=>$transaction_id,
					'transidmerchant'=>str_replace('-', '', $transaction_id),
					'totalamount'=>number_format($total_price,2,'.',''),
					'words'=>$hash_words,
					'statustype'=>'',
					'response_code'=>'',
					'approvalcode'=>'',
					'trxstatus'=>'Requested',
					'payment_channel'=>$payment_channel,
					'paymentcode'=>'',
					'session_id'=>$trx_session_id,
					'bank_issuer'=>'',
					'creditcard'=>'',
					'payment_date_time'=>date("Y-m-d H:i:s"),
					'verifyid'=>'',
					'verifyscore'=>'',
					'verifystatus'=>'',
			));
			CakeLog::write('doku',date("Y-m-d H:i:s").' - [request] create doku entry '.json_encode($rs_doku));
			$dataSource->commit();

		}catch(Exception $e){
			$dataSource->rollback();
			CakeLog::write('doku',date("Y-m-d H:i:s").' - [request] error '.$e->getMessage());

			try{
				$dataSource2 = $this->MerchandiseOrder->getDataSource();
				$dataSource2->begin();
				//update
				$this->MerchandiseOrder->id = $rs_order['MerchandiseOrder']['id'];

				$this->MerchandiseOrder->save(array(
						'fb_id'=>$this->request->data['fb_id'],
						'po_number'=>$transaction_id,
						'game_team_id'=>intval($game_team_id),
						'user_id'=>$rs_user['User']['id'],
						'first_name'=>$this->request->data['first_name'],
						'last_name'=>$this->request->data['last_name'],
						'ktp'=>$this->request->data['ktp'],
						'email'=>$this->request->data['email'],
						'phone'=>$this->request->data['mobile_phone'],
						'city'=>$this->request->data['kota'],
						'address'=>$this->request->data['address'],
						'province'=>$this->request->data['province'],
						'country'=>$this->request->data['country'],
						'zip'=>$this->request->data['zip'],
						'order_date'=>date('Y-m-d H:i:s'),
						'data'=>serialize($shopping_cart),
						'payment_method'=>$payment_method,
						'total_sale'=>$total_price,
						'ongkir_id'=>$this->request->data['city_id'],
						'ongkir_value' => $base_ongkir,
						'total_weight' => $kg,
						'total_ongkir' => $total_ongkir,
						'total_admin_fee' => $admin_fee,
						'n_status' => 0
				));

				$this->Doku->id = $rs_doku['Doku']['id'];
		    	$this->Doku->save(array(
						'catalog_order_id'=>$rs_order['MerchandiseOrder']['id'],
						'po_number'=>$transaction_id,
						'transidmerchant'=>str_replace('-', '', $transaction_id),
						'totalamount'=>number_format($total_price,2,'.',''),
						'words'=>$hash_words,
						'statustype'=>'',
						'response_code'=>'',
						'approvalcode'=>'',
						'trxstatus'=>'Requested',
						'payment_channel'=>$payment_channel,
						'paymentcode'=>'',
						'session_id'=>$trx_session_id,
						'bank_issuer'=>'',
						'creditcard'=>'',
						'payment_date_time'=>date("Y-m-d H:i:s"),
						'verifyid'=>'',
						'verifyscore'=>'',
						'verifystatus'=>'',
				));
				CakeLog::write('doku',date("Y-m-d H:i:s").' - [request] 
									update doku entry order_id '.$rs_order['MerchandiseOrder']['id']);
				$dataSource2->commit();
			}catch(Exception $e2){
				$dataSource2->rollback();
				//overide data variable
				$data = NULL;
				CakeLog::write('doku',date("Y-m-d H:i:s").' - [request] error doku entry '.json_encode($rs_doku));
			}
		}

		$this->layout="ajax";
		$this->set('response',array('status'=>1,'data'=>$data));
		$this->render('default');

	}

	public function doku_notify()
	{
		$this->loadModel('MerchandiseItem');
		$this->loadModel('MerchandiseOrder');
		$this->loadModel('Doku');

		$data = $this->request->data;


		$doku_mid = Configure::read('DOKU_MALLID');
		$doku_sharedkey = Configure::read('DOKU_SHAREDKEY');

		$message = "CONTINUE";
		try{
			if(isset($data['TRANSIDMERCHANT']))
			{
				$TRANSIDMERCHANT = $data['TRANSIDMERCHANT'];
			}
			else
			{
				throw new Exception("TRANSIDMERCHANT NOT SET");	
			}

			$totalamount = $data['AMOUNT'];
		    $words    = $data['WORDS'];
		    $statustype = $data['STATUSTYPE'];
		    $response_code = $data['RESPONSECODE'];
		    $approvalcode   = $data['APPROVALCODE'];
		    $status         = $data['RESULTMSG'];
		    $paymentchannel = $data['PAYMENTCHANNEL'];
		    $paymentcode = $data['PAYMENTCODE'];
		    $session_id = $data['SESSIONID'];
		    $bank_issuer = $data['BANK'];
		    $cardnumber = $data['MCN'];
		    $payment_date_time = $data['PAYMENTDATETIME'];
		    $verifyid = $data['VERIFYID'];
		    $verifyscore = $data['VERIFYSCORE'];
		    $verifystatus = $data['VERIFYSTATUS'];

		    $doku = $this->Doku->findByTransidmerchant($TRANSIDMERCHANT);
		    $additionaldata = $doku['Doku']['additionaldata'];

		    $data['catalog_order_id'] = $doku['Doku']['catalog_order_id'];

   		    $valid_words = sha1($totalamount.$doku_mid.$doku_sharedkey.$TRANSIDMERCHANT.$status.$verifystatus);
   		    if($valid_words!=$words)
   		    {
   		    	CakeLog::write('doku','api.doku_notify - '.date("Y-m-d H:i:s").' - ERROR - INVALID WORDS - EXPECTING : '.$valid_words.' : '.json_encode($data));
   		    	throw new Exception("INVALID WORDS");
   		    }

	    	if($doku['Doku']['trxstatus']=='Requested' && $doku['Doku']['session_id'] == $session_id && $status != 'FAILED')
	    	{
		    	CakeLog::write('doku','api.doku_notify - '.date("Y-m-d H:i:s").' - FOUND TRANSACTION : '.json_encode($data));

		    	$this->Doku->id = $doku['Doku']['id'];
		    	$this->Doku->save(
		    		array(
							'totalamount'=>$totalamount,
							'words'=>$words,
							'statustype'=>$statustype,
							'response_code'=>$response_code,
							'approvalcode'=>$approvalcode,
							'trxstatus'=>$status,
							'payment_channel'=>$paymentchannel,
							'paymentcode'=>$paymentcode,
							'session_id'=>$session_id,
							'bank_issuer'=>$bank_issuer,
							'creditcard'=>$cardnumber,
							'payment_date_time'=>$payment_date_time,
							'verifyid'=>$verifyid,
							'verifyscore'=>$verifyscore,
							'verifystatus'=>$verifystatus)
		    	);
				if($additionaldata == 'app-purchase')
		    	{
					$rs_redis = $this->redisClient->get($TRANSIDMERCHANT);
					$data_redis = unserialize($rs_redis);
					$this->app_purchase($data_redis);
					Cakelog::write('debug', 'redis data '.$rs_redis);
		    	}
		    	else if($additionaldata == 'mobile-ongkir-payment' || $additionaldata == 'ongkir-payment'
		    		 || $additionaldata == 'fm-ongkir-payment')
		    	{
		    		$rs_redis = $this->redisClient->get($TRANSIDMERCHANT);
		    		$data_redis = unserialize($rs_redis);

		    		//set n_status = 1
		    		$this->MerchandiseOrder->id = $data_redis['order_id'];

		    		$this->MerchandiseOrder->save(array('n_status' => 1));
		    	}else if($additionaldata == 'fm-subscribe')
		    	{
					CakeLog::write('doku','NOTIFY - '.date("Y-m-d H:i:s").' - Processing fm-subscribe : '.json_encode($data));
					$this->pro_subscribe($data,$doku);
		    	}
		    	else
		    	{
		    		if(!$this->update_transaction($data))
			    	{
			    		throw new Exception("Error Processing Request");
			    	}
		    	}
		    	
		    }
		    else if($doku['Doku']['trxstatus']=='SUCCESS' && $doku['Doku']['session_id'] == $session_id)
		    {
		    	CakeLog::write('doku','NOTIFY - '.date("Y-m-d H:i:s").' - TRANSACTION ALREADY PROCESSED AND SUCCEED : '.json_encode($data));
		    }
		    else if($doku['Doku']['trxstatus']=='FAILED' && $doku['Doku']['session_id'] == $session_id && $status == 'SUCCESS')
		    {
		    	//jika transaksi sudah di flag FAILED, tapi ada transaksi baru yg flagnya SUCCESS
		    	//selama session_id nya sama kita anggap valid.
		    	$this->Doku->id = $doku['Doku']['id'];
		    	$this->Doku->save(
		    		array(
							'totalamount'=>$totalamount,
							'words'=>$words,
							'statustype'=>$statustype,
							'response_code'=>$response_code,
							'approvalcode'=>$approvalcode,
							'trxstatus'=>$status,
							'payment_channel'=>$paymentchannel,
							'paymentcode'=>$paymentcode,
							'session_id'=>$session_id,
							'bank_issuer'=>$bank_issuer,
							'creditcard'=>$cardnumber,
							'payment_date_time'=>$payment_date_time,
							'verifyid'=>$verifyid,
							'verifyscore'=>$verifyscore,
							'verifystatus'=>$verifystatus)
		    	);
		    	CakeLog::write('doku','api.doku_notify - '.date("Y-m-d H:i:s").' - UPDATE doku entry (was failed)'.json_encode($data));
		    	
		    	if($additionaldata == 'app-purchase')
		    	{
					$rs_redis = $this->redisClient->get($TRANSIDMERCHANT);
					$data_redis = unserialize($rs_redis);
					$this->app_purchase($data_redis);
					Cakelog::write('debug', 'redis data '.$rs_redis);
		    	}
		    	else if($additionaldata == 'mobile-ongkir-payment' || $additionaldata == 'ongkir-payment'
		    		 || $additionaldata == 'fm-ongkir-payment')
		    	{
		    		$rs_redis = $this->redisClient->get($TRANSIDMERCHANT);
		    		$data_redis = unserialize($rs_redis);

		    		//set n_status = 1
		    		$this->MerchandiseOrder->id = $data_redis['order_id'];

		    		$this->MerchandiseOrder->save(array('n_status' => 1));
		    	}
		    	else
		    	{
		    		if(!$this->update_transaction($data))
			    	{
			    		throw new Exception("Error Processing Request");
			    	}
		    	}
		    	
		    }
		    else
		    {
		    	CakeLog::write('doku','api.doku_notify - '.date("Y-m-d H:i:s").' - ERROR - MISSING TRANSACTION : '.json_encode($data));
		    	throw new Exception("Error Processing Request");
		    }
		}catch(Exception $e){
			CakeLog::write('doku','api.doku_notify - 
							'.date("Y-m-d H:i:s").' - ERROR - TRANSACTION message: '.$e->getMessage().'
							data'.json_encode($data));
			$message = "STOP";
		}

		$this->layout="ajax";
		$this->set('response',array('status'=>1,'data'=>$data,'message'=>$message));
		$this->render('default');

	}
	private function pro_subscribe($trx,$doku){
		//get transaction_id
		if($trx['RESULTMSG']=='SUCCESS'){
			$this->loadModel('MembershipTransactions');
			$trans = $this->MembershipTransactions->findByPo_number($doku['Doku']['po_number']);
			$this->MembershipTransactions->id = $trans['MembershipTransactions']['id'];
			$this->MembershipTransactions->save(array(
				'n_status'=>1
			));

			$this->MembershipTransactions->query("INSERT INTO member_billings
												(fb_id,log_dt,expire)
												VALUES('{$trans['MembershipTransactions']['fb_id']}',
														NOW(), NOW() + INTERVAL 1 MONTH)");
			$this->User->query("UPDATE users SET paid_member=1,paid_member_status=1 
											WHERE fb_id='{$trans['MembershipTransactions']['fb_id']}'");	
		}
		
	}
	//param POST fb_id, order_id, payment_method
	public function doku_ongkir_payment()
	{
		try{
			$this->loadModel('MerchandiseOrder');
			$this->loadModel('Doku');

			$fb_id = $this->request->data['fb_id'];
			$order_id = $this->request->data['order_id'];
			$payment_method = $this->request->data['payment_method'];

			$payment_channel = '01';
			if($payment_method == "va")
			{
				$payment_channel = '05';
			}

			if($fb_id == NULL || $order_id == NULL)
			{
				throw new Exception("Error Param ".json_encode($this->request->data));
			}

			$rs_order = $this->MerchandiseOrder->find('first', array(
				    	'conditions' => array(
				    			'id' => $order_id,
				    			'payment_method' => 'coins'
				    	)));

			if(count($rs_order) == 0)
			{
				throw new Exception("Order not found param ".json_encode($this->request->data));
			}

			if($rs_order['MerchandiseOrder']['fb_id'] != $fb_id)
			{
				throw new Exception("facebook id didn't match ".json_encode($this->request->data));
			}

			$trx_session_id = sha1(time());
			$doku_mid = Configure::read('DOKU_MALLID');
			$doku_sharedkey = Configure::read('DOKU_SHAREDKEY');

			$po_number = $rs_order['MerchandiseOrder']['po_number'];

			//add suffix -1 to define that its the payment for shipping for these po number.
			$transaction_id =  $po_number.'-1';
			$transaction_id_merchant = str_replace('-', '', $transaction_id);
			$total_ongkir = $rs_order['MerchandiseOrder']['total_ongkir'];
			$admin_fee = $rs_order['MerchandiseOrder']['total_admin_fee'];
			$total_amount =  $total_ongkir + $admin_fee;

			$hash_words = sha1(number_format($total_amount,2,'.','').
						  $doku_mid.
						  $doku_sharedkey.
						  $transaction_id_merchant);

			$basket = 'ONGKIR PAYMENT PO#'.$po_number.','.$total_amount.','.$total_amount.','.$total_amount.';';

			$doku_data = array(
								'catalog_order_id'=>$order_id,
								'po_number'=>$transaction_id,
								'transidmerchant'=>$transaction_id_merchant,
								'totalamount'=>number_format($total_amount,2,'.',''),
								'words'=>$hash_words,
								'statustype'=>'',
								'response_code'=>'',
								'approvalcode'=>'',
								'trxstatus'=>'Requested',
								'payment_channel'=>$payment_channel,
								'paymentcode'=>'',
								'session_id'=>$trx_session_id,
								'bank_issuer'=>'',
								'creditcard'=>'',
								'payment_date_time'=>date("Y-m-d H:i:s"),
								'verifyid'=>'',
								'verifyscore'=>'',
								'verifystatus'=>'',
								'additionaldata'=> 'ongkir-payment'
						);

			try{
				$this->Doku->create();
				$this->Doku->save($doku_data);
			}catch(Exception $f){
				//catch here because mostly duplicate entry key
				Cakelog::write('error', 'api.doku_ongkir_payment nested try-catch'.$f->getMessage());
				$rs_doku = $this->Doku->findByTransidmerchant($transaction_id_merchant);
				$trx_session_id = $rs_doku['Doku']['session_id'];
				$hash_words = $rs_doku['Doku']['words'];
			}
			
			
			$data_param = array('MALLID'=>$doku_mid,
							'CHAINMERCHANT'=>'NA',
							'AMOUNT'=>number_format($total_amount,2,'.',''),
							'PURCHASEAMOUNT'=>number_format($total_amount,2,'.',''),
							'TRANSIDMERCHANT'=>$transaction_id_merchant,
							'WORDS'=>$hash_words,
							'REQUESTDATETIME'=>date("YmdHis"),
							'CURRENCY'=>'360',
							'PURCHASECURRENCY'=>'360',
							'SESSIONID'=>$trx_session_id,
							'NAME'=>$rs_order['MerchandiseOrder']['first_name'].' '.$rs_order['MerchandiseOrder']['last_name'],
							'EMAIL'=>$rs_order['MerchandiseOrder']['email'],
							'ADDITIONALDATA'=>'ongkir-payment',
							'PAYMENTCHANNEL'=>$payment_channel,
							'BASKET'=>$basket
						);

			$data_redis = array('doku_data' => $doku_data,
								'doku_param' => $doku_param,
								'order_id' => $order_id,
								'trx_type' => $basket,
								'amount' => $total_amount
								);



			$this->redisClient->set($transaction_id_merchant, serialize($data_redis));

			$this->redisClient->expire($transaction_id_merchant, 24*60*60);//expires in 1 day
			if($payment_channel == '05')
			{
				$this->redisClient->expire($transaction_id_merchant, 6*60*60);//expires in 6 hours
			}

			$status = 1;

		}catch(Exception $e){
			$status = 0;
			$data_param = NULL;
			Cakelog::write('error', 'api.doku_ongkir_payment '.$e->getMessage());
		}

		$this->layout="ajax";
		$this->set('response',array('status'=>$status, 'data' => $data_param));
		$this->render('default');
		
	}

	public function get_doku_transaction($session_id)
	{
		$this->loadModel("Doku");
		$this->loadModel("MerchandiseOrder");

		$rs_doku = $this->Doku->findBySession_id($session_id);
		$rs_order = $this->MerchandiseOrder->findById($rs_doku['Doku']['catalog_order_id']);
		$rs_merge = array_merge($rs_doku, $rs_order);

		Cakelog::write('doku', 'get_doku_transaction rs_merge '.json_encode($rs_merge));

		$this->layout = "ajax";
		$this->set('response', array('status'=>1, 'data'=>$rs_merge));
		$this->render('default');
	}

	public function update_doku_order()
	{
		try{
			$this->loadModel("Doku");
			$data = $this->request->data;
			CakeLog::write('doku', 'api.update_doku_order data:'.json_encode($data));

			$this->Doku->query("UPDATE ".Configure::read('FRONTEND_SCHEMA').".doku SET 
								paymentcode='{$data['paymentcode']}' 
								WHERE session_id='{$data['session_id']}'");
			
	    	$status = 1;
		}catch(Exception $e){
			CakeLog::write('doku', 'api.update_doku_order data:'.json_encode($data).' message:'.$e->getMessage());
			$status = 0;
		}
		$this->layout = "ajax";
		$this->set('response', array('status'=>$status, 'data'=>$data));
		$this->render('default');
	}

	public function get_url_scheme()
	{
		$url_scheme = Configure::read('URL_SCHEME');
		$data = array('url_scheme' => $url_scheme);
		$this->layout = "ajax";
		$this->set('response', array('status'=>1, 'data'=>$data));
		$this->render('default');
	}

	//step
	//1. cek stock
	//2. reduce stok
	//3. update MerchandiseOrder status
	private function update_transaction($data){
		CakeLog::write('doku','api.update_transaction - '.date("Y-m-d H:i:s").' - UPDATE TRANSACTION '.json_encode($data));
		$this->loadModel('MerchandiseOrder');
		$this->loadModel('MerchandiseItem');
		$this->loadModel('Doku');

		$catalog_order_id = intval($data['catalog_order_id']);
		
		try{
			$dataSource = $this->MerchandiseOrder->getDataSource();
			$dataSource->begin();

			if($data['RESULTMSG']=='SUCCESS' && $data['RESPONSECODE']=='0000')
			{
				$rs_order = $this->MerchandiseOrder->findById($catalog_order_id);
				$shopping_cart = unserialize($rs_order['MerchandiseOrder']['data']);

				//reduce stock broo
				foreach ($shopping_cart as $key => $value)
				{
					$rs_item = $this->MerchandiseItem->findById($value['item_id']);
					if($rs_item['MerchandiseItem']['stock'] < intval($value['qty']))
					{
						throw new Exception("Stok tidak cukup");
					}
					else
					{
						$stock = $rs_item['MerchandiseItem']['stock'] - intval($value['qty']);
						$this->MerchandiseItem->id = $value['item_id'];
						$this->MerchandiseItem->save(
				    		array(
									'stock'=>$stock
								)
				    	);
					}//end if
				}//end foreach

				Cakelog::write('doku', 'api.update_transaction reduce stock berhasil');
			}
			else
			{
				throw new Exception("Error Processing Request");
			}

			$this->MerchandiseOrder->id = $catalog_order_id;
			$this->MerchandiseOrder->save(
	    		array(
						'n_status'=>1
					)
	    	);

			$dataSource->commit();
			return true;
		}catch(Exception $e){
			$dataSource->rollback();

			$this->MerchandiseOrder->id = $catalog_order_id;
			$this->MerchandiseOrder->save(
	    		array(
						'n_status'=>4
					)
	    	);

	    	Cakelog::write('doku', 'api.update_transaction error message '.$e->getMessage().' 
	    				data '.json_encode($data));

	    	return false;
		}

	}

	private function app_purchase($data)
	{
		$this->loadModel('MembershipTransactions');
		$transaction_name = 'Purchase Order #'.$data['doku_data']['po_number'];
		$transaction_type = 'UNLOCK '.$data['trx_type'];
		$insert_data = array(
								'fb_id' => $data['fb_id'],
								'transaction_dt' => date("Y-m-d H:i:s"),
								'transaction_name' => $transaction_name,
								'transaction_type' =>$transaction_type,
								'amount' => $data['amount'],
								'details' => serialize($data)
							);
		$fb_id = $data['fb_id'];
		$array_session = array('fb_id' => $fb_id, 'trx_type' => $data['trx_type']);

		$this->MembershipTransactions->create();
		$this->MembershipTransactions->save($insert_data);

		//notif to mobile apps
		$url_mobile_notif = Configure::read('URL_MOBILE_NOTIF').'fm_payment_notification';

		$result_mobile = curlPost($url_mobile_notif,$array_session);
		$result_mobile = json_decode($result_mobile, TRUE);

		if($result_mobile['code'] == 1){
			Cakelog::write('debug', 
			'Payment.success result_mobile:'.json_encode($result_mobile).
			' data:'.json_encode($data).' fb_id:'.$fb_id);
		}else{
			Cakelog::write('error', 
			'Payment.success result_mobile:'.json_encode($result_mobile).
			' data:'.json_encode($data).' fb_id:'.$fb_id);
		}

	}


	/*
	*return true if order contain ticket category
	*/
	private function check_category_ticket($category_id = array())
	{
		$ticket_category_id = Configure::read('ticket_category_id');
		foreach ($category_id as $value) {
			if($value == $ticket_category_id)
			{
				return true;
			}
		}

		return false;
	}



	
	/*
	* book items stock.
	* upon checkout, the stock will be locked for 5 minutes to prevent other people to order
	*/
	private function book_items($items,$order_id,$game_team_id,$fb_id){	
		$this->loadModel('MerchandiseItemPerk');
		
		for($i=0; $i < sizeof($items); $i++){
			
			$qty = $items[$i]['qty'];
			$keyname = 'claim_stock_'.$items[$i]['item_id'].'_'.$game_team_id.'_'.$fb_id;
			$ttl = 15*60; //user have 15 minutes to complete the payment.
			$this->Game->storeToTmp($game_team_id,
									$keyname,
									$qty,
									$ttl);

			$this->Game->storeToTmp($game_team_id,
									'purchase_order_'.$order_id,
									'1',
									$ttl);

			CakeLog::write('api','lock item - '.$order_id.' - '.$items[$i]['item_id'].' - qty : '.$qty.' key:'.$keyname);
		}
	}

	/**get ongkir list **/
	private function getOngkirList(){
		$this->loadModel('Ongkir');
		$ongkir = $this->Ongkir->find('all',array('limit'=>10000));
		return $ongkir;
	}
	/*
	* validate ecash id
	*/
	public function ecash_validate(){
		$id = $this->request->query['id'];

		$rs = $this->Game->EcashValidate($id);
		CakeLog::write('debug','ecash_validate - '.$id.' - '.json_encode($rs));

		list($id,$trace_number,$nohp,$transaction_id,$status) = explode(',',$rs['data']);

		$result = array(
			'id'=>trim($id),
			'trace_number'=>trim($trace_number),
			'nohp'=>trim($nohp),
			'transaction_id'=>trim($transaction_id),
			'status'=>trim($status)
		);
		
		$is_valid = true;
		
		if(Configure::read('debug')==0){
			/*
			todo - besok dinyalain

			$ecash_validate = $this->Session->read('ecash_return');
			if($result['id']==$ecash_validate['id'] &&
				$result['trace_number']==$ecash_validate['trace_number'] &&
				$result['nohp']==$ecash_validate['nohp'] &&
				$result['transaction_id']==$ecash_validate['transaction_id'] &&
				$result['status']==$ecash_validate['status']
				){
				$is_valid = true;
			}else{
				
				$is_valid = false;
			}*/
		}
		CakeLog::write('debug','ecash_validate - '.$id.' - '.json_encode($result));
		CakeLog::write('debug','ecash_validate - is valid : '.$id.' - '.json_encode($is_valid));
		CakeLog::write('debug',strtoupper(trim($result['status'])).' <-> SUCCESS');
		$this->layout="ajax";
		if(strtoupper(trim($result['status']))=='SUCCESS' && $is_valid){
			CakeLog::write('debug','ecash_validate - '.$id.' - '.'success');
			$status = "SUCCESS";
			$this->set('response',array('status'=>1,'data'=>$result));
		}else{
			CakeLog::write('debug','ecash_validate - '.$id.' - '.'failed');
			$status = "FAILED";
			$this->set('response',array('status'=>0,'data'=>$result));
		}
		
		
		$this->render('default');
	}
	public function catalog_save_order($game_team_id){
		

		$rs = $this->pay_with_ecash_completed($game_team_id,$this->request->data);

		CakeLog::write('debug','pay_with_ecash_completed - finished '.json_encode($rs));
		$this->layout="ajax";
		if($rs){
			$last_order = $this->Game->getFromTmp(intval(@$game_team_id),
										$this->request->data['transaction_id'].'_order_id');
			$order_id = $last_order['data'];
			$this->set('response',array('status'=>1,'order_id'=>$order_id));
			CakeLog::write('debug','pay_with_ecash_completed - status : 1, order_id:'.$order_id);

		}else{
			$this->set('response',array('status'=>0,'error'=>@$rs['error']));
			CakeLog::write('debug','pay_with_ecash_completed - status : 0');
		}
		
		$this->render('default');
	}
	private function pay_with_ecash_completed($game_team_id,$ecash_data){
		$this->loadModel('MerchandiseItem');
		$this->loadModel('MerchandiseCategory');
		$this->loadModel('MerchandiseOrder');
		$this->loadModel('Ongkir');
		

		$this->userData['team']['id'] = intval($game_team_id);
		

		$transaction_id = $ecash_data['transaction_id'];

		//get data from redis store
		$rs = $this->Game->getFromTmp(intval(@$game_team_id),$transaction_id);
		$transaction_tmp = unserialize(decrypt_param($rs['data']));


		CakeLog::write('debug','pay_with_ecash_completed'.'-'.json_encode($transaction_tmp));
		$shopping_cart = unserialize(decrypt_param($transaction_tmp['profile']['param']));
		
		CakeLog::write('debug','pay_with_ecash_completed - shopping_cart : '.json_encode($shopping_cart));
		
		$total_price = 0;
		
		$all_digital = true;
		
		$is_ticket = false;

		$kg = 0;
		$total_admin_fee = 0;
		for($i=0;$i<sizeof($shopping_cart);$i++){
			if($shopping_cart[$i]['item_id'] > 0){
				$shopping_cart[$i]['data'] = $this->MerchandiseItem->findById($shopping_cart[$i]['item_id']);
				$item = $shopping_cart[$i]['data']['MerchandiseItem'];
				//check parent, if parent exists, then we concat the parent's name into item's name.
				$parent_item = $this->MerchandiseItem->findById(intval($item['parent_id']));
				$total_admin_fee += $item['admin_fee'];
				if(isset($parent_item['MerchandiseItem'])){
					$shopping_cart[$i]['data']['MerchandiseItem']['name'] = $parent_item['MerchandiseItem']['name'].' '.
																		$item['name'];	
				}
				if($item['merchandise_category_id'] == Configure::read('ticket_category_id')){
					$is_ticket = true;
				}

				$kg += floatval($item['weight']) * intval($shopping_cart[$i]['qty']);
				$total_price += (intval($shopping_cart[$i]['qty']) * intval($item['price_money']));
				//is there any non-digital item ?
				if($item['merchandise_type']==0){
					$all_digital = false;
				}
			}
		}

		$kg = ceil($kg);

		$admin_fee = $total_admin_fee;
		$enable_ongkir = true;
		if(count($shopping_cart) > 1)
		{
			//$admin_fee = Configure::read('PO_ADMIN_FEE');
		}
		else
		{
			//check ongkir
			if($item['enable_ongkir'] == 0)
			{
				$enable_ongkir = false;
			}
		}
		
		if($all_digital){
			$admin_fee = 0;
		}
		$total_price += $admin_fee;

		


		//$data = unserialize(decrypt_param($ecash_data['profile']));
		$data = $transaction_tmp['profile'];
		CakeLog::write('debug','pay_with_ecash_completed - profile : '.json_encode($data));
		

		if($enable_ongkir)
		{
			//calculate ongkir
			$ongkirList = $this->getOngkirList();
			foreach($ongkirList as $ongkir){
				if($ongkir['Ongkir']['id'] == intval($data['city_id'])){
					$base_ongkir = intval($ongkir['Ongkir']['cost']);
					break;
				}
			}
		}

		$total_ongkir = $base_ongkir * $kg;

		$total_price += $total_ongkir;



		$data['merchandise_item_id'] = 0;
		$data['user_id'] = 0;
		$data['order_type'] = 1;
		$data['game_team_id'] = intval($game_team_id);
		if($all_digital){
			$data['n_status'] = 3;	
		}else{
			$data['n_status'] = 1;
		}

		$data['order_date'] = date("Y-m-d H:i:s");
		$data['data'] = serialize($shopping_cart);
		$data['po_number'] = $ecash_data['transaction_id'];
		$data['total_sale'] = intval($total_price);
		$data['payment_method'] = 'ecash';
		$data['trace_code'] = $ecash_data['trace_number'];
		$data['ongkir_id'] = intval(@$data['city_id']);
		//we need ongkir value
		$data['ongkir_value'] = $base_ongkir;
		$data['total_weight'] = $kg;
		$data['total_ongkir'] = $total_ongkir;
		$data['total_admin_fee'] = $admin_fee;
		

		CakeLog::write('debug','TO BE SAVED : '.json_encode($data));

		$this->MerchandiseOrder->create();
		try{
			$rs = $this->MerchandiseOrder->save($data);	
			if($this->MerchandiseOrder->getInsertID() > 0){
				$order_id = $this->MerchandiseOrder->getInsertID();
				$this->Game->storeToTmp(intval(@$game_team_id),
										$transaction_id.'_order_id',
										$order_id,
										10*60);
				if($is_ticket){
					//send ticket email
					$this->sendEmailTicket($data['email'],$data['po_number'],$order_id);
					//generate ticket voucher
					$this->generateVouchers($order_id,$data,$shopping_cart);

				}
				$this->process_items($shopping_cart,$data['po_number']);	
			}
			
		}catch(Exception $e){
			//$rs = array('MerchandiseOrder'=>$data);
			$rs['error'] = $e->getMessage();
		}
		
		
		CakeLog::write('debug','INPUT : '.json_encode(@$rs));

		
		
		//di production di nyalain ya.
		$this->Game->storeToTmp(intval(@$game_team_id),$transaction_id,'');


		if(isset($rs['MerchandiseOrder'])){
			$this->Game->storeToTmp(intval(@$game_team_id),'final_'.$transaction_id,'SUCCESS');
			return true;
		}

		$final_transaction = $this->Game->getFromTmp(intval(@$game_team_id),'final_'.$transaction_id);
		

		if($final_transaction['data']=='SUCCESS'){
			CakeLog::write('debug','final_'.$transaction_id.'[SUCCESS]:'.json_encode($final_transaction));
			return true;
		}else{
			CakeLog::write('debug','final_'.$transaction_id.'[FAILED]:'.json_encode($final_transaction));
			$this->Game->storeToTmp(intval(@$game_team_id),'final_'.$transaction_id,'FAILED');	
			return false;
		}
	}
	/*
	* generate ticket voucher, and saves it in ".Configure::read('FRONTEND_SCHEMA').".merchandise_vouchers
	*/
	private function generateVouchers($order_id,$order_data,$shopping_cart){
		
		CakeLog::write('generateVoucher',' shopping_cart - >'.json_encode($shopping_cart));
		CakeLog::write('generateVoucher',' order_data - >'.json_encode($order_data));


		$no = 1;
		for($i=0;$i<sizeof($shopping_cart);$i++){
			$item = $shopping_cart[$i]['data']['MerchandiseItem'];
			$qty = $shopping_cart[$i]['qty'];
			if(Configure::read('ticket_category_id') == $item['merchandise_category_id']){
				for($j=0;$j < $qty;$j++){
					$voucher_code = $order_data['po_number'].$no;
					CakeLog::write('generateVoucher',date("Y-m-d H:i:s")." - ".$order_data['po_number'].' - '.$voucher_code);
					
					$sql = "
					INSERT IGNORE INTO ".Configure::read('FRONTEND_SCHEMA').".merchandise_vouchers
					(merchandise_order_id,merchandise_item_id,voucher_code,created_dt,n_status)
					VALUES
					({$order_id},
					 {$item['id']},
					 '{$voucher_code}',
					 NOW(),
					 0)";
					CakeLog::write('generateVoucher',' - >'.$sql);
					$rs = $this->Game->query($sql);
					
					$no++;

					
				}
			}	
		}
	}
	//test function for generate a voucher
	private function test_generate_voucher(){
		$rs = $this->Game->query("SELECT * FROM ".Configure::read('FRONTEND_SCHEMA').".merchandise_orders a WHERE id=388 LIMIT 1");
		$data = $rs[0]['a'];
		$shopping_cart = unserialize($data['data']);
		
		$this->generateVouchers($data['id'],$data,$shopping_cart);
		$this->layout="ajax";
		$this->set('response',array('status'=>1));
		$this->render('default');
	}

	private function sendEmailTicket($email,$po_number,$order_id){
		$url = "http://www.supersoccer.co.id/onlinecatalog/view_order/".intval($order_id);
		$view = new View($this, false);
		$body = $view->element('email_ticket',array('subject'=>'Transaksi Berhasil !',
													'voucher_url'=>$url,
													'po_number'=>$po_number));
		
		$body = mysql_escape_string($body);
		$rs = $this->Game->query("INSERT IGNORE INTO ".$this->ffgamedb.".email_queue
							(subject,email,plain_txt,html_text,queue_dt,n_status)
							VALUES
							('transaksi berhasil !','{$email}','{$body}','{$body}',NOW(),0) ;");

	}

	public function view_voucher($id){
		$id = intval($id);
		$this->Game->query("UPDATE ".Configure::read('FRONTEND_SCHEMA').".merchandise_vouchers 
							SET n_status = 1 WHERE id = {$id}");
		$this->layout="ajax";
		$this->set('response',array('status'=>1));
		$this->render('default');
	}
	/*api call for purchasing item using coins
	$game_team_id -> user's game_team_id
	we can require the game_team_id after calling get_fm_profile api.
	
	$param -> encrypted serialized array of :
	$items[0]['item_id']
	$items[0]['qty']
	$items[1]['item_id']
	$items[1]['qty']
	*/
	public function catalog_purchase($game_team_id){
		
		$this->layout="ajax";

		$param = unserialize(decrypt_param(@$this->request->data['param']));
		if(!isset($this->request->data['param'])){
			$item_id = $this->request->data['item_id'];
			$qty = $this->request->data['qty'];
			$param = array();

			for($i=0;$i<count($item_id);$i++){
				$param[$i] = array('item_id' => $item_id[$i],
									'qty' => $qty[$i]
								);
			}
		}
		$fb_id = intval($this->request->data['fb_id']);
		CakeLog::write('debug','param '.json_encode($param));
		$result = $this->pay_with_coins($fb_id,$game_team_id,$param);
		CakeLog::write('debug',$game_team_id.'-team_id');
		CakeLog::write('debug',$game_team_id,'data ->'.json_encode($this->request->data));
		CakeLog::write('debug','catalog - '.json_encode($result));
		$is_transaction_ok = $result['is_transaction_ok'];
		$no_fund = @$result['no_fund'];
		$order_id = @$result['order_id'];
		
		if($is_transaction_ok == true)
		{
			//check accross the items, we apply the perk for all digital items
			$this->process_items($result['items'],$order_id);
			$this->set('response',array('status'=>1,'data'=>$result));
		}
		else
		{
			$data_error = array(
									"game_team_id" => $game_team_id,
									"request_data" => $this->request->data,
									"result" => $result
								);
			CakeLog::write('error', 'api.catalog_purchase '.json_encode($data_error));
			$this->set('response',array('status'=>0));
		}
		$this->render('default');
	}


	public function m_catalog_purchase($game_team_id){
		
		$this->layout="ajax";

		try{
			$param = unserialize(decrypt_param(@$this->request->data['param']));
			if(!isset($this->request->data['param'])){
				$item_id = $this->request->data['item_id'];
				$qty = $this->request->data['qty'];
				$param = array();

				for($i=0;$i<count($item_id);$i++){
					$param[$i] = array('item_id' => $item_id[$i],
										'qty' => $qty[$i]
									);
				}
			}
			$fb_id = intval($this->request->data['fb_id']);
			CakeLog::write('debug','param '.json_encode($param));
			$result = $this->pay_with_coins($fb_id,$game_team_id,$param);
			CakeLog::write('debug',$game_team_id.'-team_id');
			CakeLog::write('debug',$game_team_id,'data ->'.json_encode($this->request->data));
			CakeLog::write('debug','catalog - '.json_encode($result));
			$is_transaction_ok = $result['is_transaction_ok'];
			$no_fund = @$result['no_fund'];
			$order_id = @$result['order_id'];
			
			if($is_transaction_ok == true)
			{
				//check accross the items, we apply the perk for all digital items
				$this->process_items($result['items'],$order_id);
				$this->set('response',array('status'=>1,'data'=>$result));
			}
			else
			{
				$data_error = array(
										"game_team_id" => $game_team_id,
										"request_data" => $this->request->data,
										"result" => $result
									);
				CakeLog::write('error', 'api.m_catalog_purchase '.json_encode($data_error));
				$this->set('response',array('status'=>0));
			}
		}catch(Exception $e){
			$data_error = array(
										"game_team_id" => $game_team_id,
										"request_data" => $this->request->data,
										"result" => $result
									);
			CakeLog::write('error', 'api.m_catalog_purchase '.json_encode($data_error));
			$this->set('response',array('status'=>0));
		}
		
		$this->render('default');
	}
	/*
	* process digital items
	* when the digital items redeemed, we reduce its stock.
	*/
	private function process_items($items,$order_id){	
		CakeLog::write('debug',json_encode($items));
		$this->loadModel('MerchandiseItemPerk');
		for($i=0; $i<sizeof($items); $i++){
			$item = $items[$i]['data']['MerchandiseItem'];
			$qty = $items[$i]['qty'];
			CakeLog::write('apply_digital_perk',json_encode($this->userData));
			if($item['merchandise_type']==1){
				$this->apply_digital_perk($this->userData['team']['id'],
											$item['perk_id'],$order_id);

			}else if($item['perk_id'] == 0){
				$perks = $this->MerchandiseItemPerk->find('all',
													array('conditions'=>array('merchandise_item_id'=>$item['id']),
														 'limit'=>20)
													);
				CakeLog::write('apply_digital_perk',date("Y-m-d H:i:s").'-'.$item['id'].'-'.json_encode($perks));
				for($j=0;$j<sizeof($perks);$j++){
					CakeLog::write('apply_digital_perk',date("Y-m-d H:i:s").'#'.$j.' -'.$item['id'].'-'.$perks[$j]['MerchandiseItemPerk']['perk_id'].'- applying');
					$this->apply_digital_perk($this->userData['team']['id'],
											$perks[$j]['MerchandiseItemPerk']['perk_id'],$order_id);
				}
			
			}
			
			$this->reduceStock($item['id'],$qty);
			CakeLog::write('stock','process_items - '.$order_id.' - '.$item['id'].' - '.$qty.' REDUCED');
		}
		
		
	}
	


	private function ReduceStock($item_id,$qty=1){
		try{
			$item_id = intval($item_id);
			$sql1 = "UPDATE ".Configure::read('FRONTEND_SCHEMA').".merchandise_items SET stock = stock - {$qty} WHERE id = {$item_id}";
			$this->MerchandiseItem->query($sql1);

			$sql2 = "UPDATE ".Configure::read('FRONTEND_SCHEMA').".merchandise_items SET stock = 0 WHERE id = {$item_id} AND stock < 0";
			$this->MerchandiseItem->query($sql2);
			CakeLog::write('api_stock','Api.ReduceStock sql1:'.$sql1);
			CakeLog::write('api_stock','Api.ReduceStock sql2:'.$sql2);

			CakeLog::write('api_stock','stock '.$item_id." {$qty} reduced");
		}catch(Exception $e){
			CakeLog::write('api_stock','Api.ReduceStock error sql1:'.$sql1);
			CakeLog::write('api_stock','Api.ReduceStock error sql2:'.$sql2);

			CakeLog::write('api_stock','Api.ReduceStock error msg:'.$e->getMessage());
		}
	}

	private function pay_with_coins($fb_id, $game_team_id, $shopping_cart){

		CakeLog::write('debug',$game_team_id.' - pay with coins');
		$game_team_id = intval($game_team_id);


		$this->loadModel('MerchandiseItem');
		$this->loadModel('MerchandiseCategory');
		$this->loadModel('MerchandiseOrder');
		$this->loadModel('Ongkir');

		$result = array('is_transaction_ok'=>false);

		//get total coins to be spent.
		$total_coins = 0;
		$total_admin_fee = 0;
		$all_digital = true;
		$kg = 0;
		$all_stock_ok = true;

		for($i=0;$i<sizeof($shopping_cart);$i++){
			if(intval($shopping_cart[$i]['qty']) <= 0){
				$shopping_cart[$i]['qty'] = 1;
			}
			$shopping_cart[$i]['data'] = $this->MerchandiseItem->findById($shopping_cart[$i]['item_id']);
			$item = $shopping_cart[$i]['data']['MerchandiseItem'];
			$total_admin_fee += $item['admin_fee'];
			$kg += floatval($item['weight']) * intval($shopping_cart[$i]['qty']);
			$total_coins += (intval($shopping_cart[$i]['qty']) * intval($item['price_credit']));
			//is there any non-digital item ?
			if($item['merchandise_type']==0){
				$all_digital = false;
			}
			if($item['stock'] <= 0){
				$all_stock_ok = false;
			}
		}
		$kg = ceil($kg);
		$cash = $this->Game->getCash($fb_id);
		CakeLog::write('debug',$game_team_id.' cash : '.$cash);
		CakeLog::write('debug',$game_team_id.' total coins : '.$total_coins);

		//1. check if the coins are sufficient
		if(intval($cash) >= $total_coins){
			$no_fund = false;
		}else{
			$no_fund = true;
		}
		
		//2. if fund is available, we create transaction id and order detail.
		if(!$no_fund && $all_stock_ok){

			$data = $this->request->data;
			$data['merchandise_item_id'] = 0;
			$data['game_team_id'] = $game_team_id;
			$data['user_id'] = 0;

			$data['order_type'] = 1;

			if($all_digital){
				$data['n_status'] = 3;	
			}else{
				$data['n_status'] = 0;
			}
			$data['order_date'] = date("Y-m-d H:i:s");
			$data['data'] = serialize($shopping_cart);
			$data['po_number'] = $game_team_id.'-'.date("ymdhis");
			$data['total_sale'] = intval($total_coins);
			$data['payment_method'] = 'coins';
			$data['ongkir_id'] = $this->request->data['city_id'];
			$data['phone'] = $this->request->data['mobile_phone'];

			//we need ongkir value
			$ok = $this->Ongkir->findById($data['ongkir_id']);

			$data['ongkir_value'] = $ok['Ongkir']['cost'];
			$data['total_weight'] = $kg;
			$data['total_ongkir'] = $kg * intval($ok['Ongkir']['cost']);
			$data['total_admin_fee'] = $total_admin_fee;


			$this->MerchandiseOrder->create();
			$rs = $this->MerchandiseOrder->save($data);	
			if($rs){
				$result['admin_fee'] = array(
											'admin_fee_ongkir' => $total_admin_fee,
											'ongkir_cost' => $data['total_ongkir']
										);

				$result['po_number'] = $data['po_number'];

				$result['order_id'] = $this->MerchandiseOrder->id;
				//time to deduct the money
				$this->Game->query("
				INSERT IGNORE INTO ".Configure::read('FRONTEND_SCHEMA').".game_transactions
				(fb_id,transaction_name,transaction_dt,amount,
				 details)
				VALUES
				({$data['fb_id']},'purchase_{$data['po_number']}',
					NOW(),
					-{$total_coins},
					'{$data['po_number']} - {$result['order_id']}');");
				
				//update cash summary
				$this->Game->query("INSERT INTO ".Configure::read('FRONTEND_SCHEMA').".game_team_cash
				(fb_id,cash)
				SELECT fb_id,SUM(amount) AS cash 
				FROM ".Configure::read('FRONTEND_SCHEMA').".game_transactions
				WHERE fb_id = {$data['fb_id']}
				GROUP BY fb_id
				ON DUPLICATE KEY UPDATE
				cash = VALUES(cash);");

				//flag transaction as ok
				$is_transaction_ok = true;
				$result['is_transaction_ok'] = $is_transaction_ok;
				$result['items'] = $shopping_cart;
			}
		}

		$result['no_fund'] = $no_fund;
		return $result;
	}

	
	private function apply_digital_perk($game_team_id,$perk_id,$unique=false){
		$this->loadModel('MasterPerk');
		$this->MasterPerk->useDbConfig = $this->ffgamedb;
		
		$perk = $this->MasterPerk->findById($perk_id);
		$perk['MasterPerk']['data'] = unserialize($perk['MasterPerk']['data']);
		switch($perk['MasterPerk']['data']['type']){
			case "jersey":
				CakeLog::write('apply_digital_perk',date("Y-m-d H:i:s").'-'.$game_team_id.'-'.$perk_id.'- apply jersey');
				return $this->apply_jersey_perk($game_team_id,$perk['MasterPerk']);
			break;
			case "free_player":
				$player_id = $perk['MasterPerk']['data']['player_id'];
				$amount = intval($perk['MasterPerk']['data']['money_reward']);
				CakeLog::write('apply_digital_perk',date("Y-m-d H:i:s").'-'.$game_team_id.'-'.$perk_id.'- apply free player');
				return $this->apply_free_player_perk($game_team_id,$player_id,$unique,$amount);
			break;
			default:
				//check if it's a money perk
				if($perk['MasterPerk']['perk_name']=='IMMEDIATE_MONEY'){
					CakeLog::write('apply_digital_perk',date("Y-m-d H:i:s").'-'.$game_team_id.'-'.$perk_id.'- apply money');
					$this->apply_money_perk($game_team_id,$unique,$perk['MasterPerk']['amount']);
				}else{
					CakeLog::write('apply_digital_perk',date("Y-m-d H:i:s").'-'.$game_team_id.'-'.$perk_id.'- apply etc');
					//for everything else, let the game API handle the task
					$rs = $this->Game->apply_digital_perk($game_team_id,$perk_id);

					CakeLog::write('apply_digital_perk',date("Y-m-d H:i:s").' result - '.json_encode($rs));
					if($rs['data']['can_add'] && $rs['data']['success']){
						return true;
					}else if(!$rs['data']['can_add']){
						//tells us that the perk cannot be redeemed because these perk is already redeemed before
						$this->Session->write('apply_digital_perk_error','1');
					}else{
						//tells us that the perk cannot be redeemed because we cannot save the perk.
						$this->Session->write('apply_digital_perk_error','2');
					}
				}
				
			break;
			
		}
		
	}
	/*
	* $unique_id -> can be item_id, or any unique identifier we can use as transaction name.
	*/
	private function apply_money_perk($game_team_id,$unique_id,$amount){
		$transaction_name = 'apply_money_perk_'.$unique_id;
		CakeLog::write('apply_digital_perk',date("Y-m-d H:i:s").' - '.$game_team_id.' - apply_money_perk_'.$unique_id.' -> '.$amount);
		$rs = $this->Game->add_expenditure($game_team_id,$transaction_name,intval($amount));
		if($rs['status']==1){
			return true;
		}
	}
	private function apply_free_player_perk($game_team_id,$player_id,$unique_id,$amount){
		//check if the user has the player
		$my_player = $this->Game->query("SELECT * FROM ".$this->ffgamedb.".game_team_players a
							WHERE game_team_id={$game_team_id} 
							AND player_id='{$player_id}'",false);
		if(@$my_player[0]['a']['player_id'] == $player_id){
			CakeLog::write('apply_digital_perk',date("Y-m-d H:i:s").' - '.$game_team_id.' - '.$unique_id.' - player exists, reward money instead : '.$amount);
			//reward money instead
			return $this->apply_money_perk($game_team_id,$unique_id,$amount);
		}else{
			CakeLog::write('apply_digital_perk',date("Y-m-d H:i:s").' - '.$game_team_id.' - '.$unique_id.' - free player : '.$player_id);
			return $this->Game->query("INSERT IGNORE INTO ".$this->ffgamedb.".game_team_players
								(game_team_id,player_id)
								VALUES({$game_team_id},'{$player_id}')",false);
		}

	}
	private function apply_jersey_perk($game_team_id,$perk_data){
		$this->loadModel('DigitalPerk');

		$this->DigitalPerk->useDbConfig = $this->ffgamedb;
		$this->DigitalPerk->cache = false;


		//only 1 jersey can be used


		//so we disabled all existing jersey
		$this->DigitalPerk->bindModel(
			array('belongsTo'=>array(
				'MasterPerk'=>array(
					'type'=>'inner',
					'foreignKey'=>false,
					'conditions'=>array(
						"MasterPerk.id = DigitalPerk.master_perk_id",
						"MasterPerk.perk_name = 'ACCESSORIES'"
					)
				)
			))
		);
		$current_perks = $this->DigitalPerk->find('all',array(
			'conditions'=>array('game_team_id'=>$game_team_id),
			'limit'=>40
		));
		$has_bought = false;
		$bought_id = 0;
		//we only take the jersey perks
		$jerseys = array();
		while(sizeof($current_perks)>0){
			$p = array_pop($current_perks);
			$p['MasterPerk']['data'] = unserialize($p['MasterPerk']['data']);
			if($p['MasterPerk']['data']['type']=='jersey'){
				$jerseys[] = $p['DigitalPerk']['id'];
			}
			if($p['DigitalPerk']['master_perk_id'] == $perk_data['id']){
				$has_bought = true;
				$bought_id = $p['DigitalPerk']['id'];
			}
		}
		//check if these jersy has been bought before.
		
		//disable the current jerseys
		for($i=0;$i<sizeof($jerseys);$i++){

			$this->DigitalPerk->id = intval($jerseys[$i]);
			$this->DigitalPerk->save(array(
				'n_status'=>0
			));
		}


		//add new jersey
		if(!$has_bought){
			$this->DigitalPerk->create();
			$rs = $this->DigitalPerk->save(
				array('game_team_id'=>$game_team_id,
					  'master_perk_id'=>$perk_data['id'],
					  'n_status'=>1,
					  'redeem_dt'=>date("Y-m-d H:i:s"),
					  'available'=>99999)
			);
			if(isset($rs['DigitalPerk'])){
				return true;
			}
		}else{
			//update the status only
			$this->DigitalPerk->id = intval($bought_id);
			$rs = $this->DigitalPerk->save(array(
				'n_status'=>1
			));
			if($rs){
				return true;
			}
		}
		
	}
	/**
	*	get catalog's main categories
	*/
	private function getCatalogMainCategories(){
		//retrieve main categories
		$categories = $this->MerchandiseCategory->find('all',
						array('conditions'=>array('parent_id'=>0,'is_mobile'=>0),
							  'limit'=>100)
					);
		for($i=0;$i<sizeof($categories);$i++){
			$categories[$i]['Child'] = $this->getChildCategories($categories[$i]['MerchandiseCategory']['id']);
		}
		return $categories;
	}
	/**
	*	get catalog's main categories for mobile
	*/
	private function getCatalogMainCategoriesMobile(){
		//retrieve main categories
		$categories = $this->MerchandiseCategory->find('all',
						array('conditions'=>array('parent_id'=>0,'is_mobile'=>1),
							  'limit'=>100)
					);
		for($i=0;$i<sizeof($categories);$i++){
			$categories[$i]['Child'] = $this->getChildCategories($categories[$i]['MerchandiseCategory']['id']);
		}
		return $categories;
	}
	/*
	* 
	*/
	private function getChildCategories($category_id){
		//retrieve main categories
		$categories = $this->MerchandiseCategory->find('all',
														array('conditions'=>
															array('parent_id'=>$category_id),
															      'limit'=>100)
													);
		return $categories;
	}
	/**
	*	get the list of child categories, 1 level under only.
	*/
	private function getChildCategoryIds($category_id,$category_ids){
		$categories = $this->MerchandiseCategory->find('all',
														array('conditions'=>array('parent_id'=>$category_id),
															  'limit'=>100)
													);
		for($i=0;$i<sizeof($categories);$i++){
			$category_ids[] = $categories[$i]['MerchandiseCategory']['id'];
		}

		return $category_ids;
	}
	/*
	* API for showing the order history
	*/
	public function order_history($fb_id){
		$since_id = intval(@$this->request->query['since_id']);
		$this->loadModel('MerchandiseOrder');
		$this->loadModel('MerchandiseItem');
		$this->loadModel('MerchandiseCategory');

		$rs_order = $this->MerchandiseOrder->query("(SELECT * FROM 
										".Configure::read('FRONTEND_SCHEMA').".merchandise_orders
										WHERE payment_method='coins' AND fb_id = '{$fb_id}' AND 
										id > '{$since_id}' LIMIT 20) 
										UNION 
										(SELECT * FROM 
										".Configure::read('FRONTNED_SCHEMA').".merchandise_orders
										WHERE payment_method!='coins' AND fb_id = '{$fb_id}' AND 
										id > '{$since_id}' AND n_status != 0 LIMIT 20) ORDER BY
										order_date DESC");

		$rs = array();
		foreach ($rs_order as $key => $value)
		{
			$rs[]['MerchandiseOrder'] = $value[0];
		}

		$result = array();
		$since_id = 0;

		while(sizeof($rs)>0){
			$p = array_shift($rs);
			$since_id = intval($p['MerchandiseOrder']['id']);
			$p['MerchandiseOrder']['data'] = unserialize($p['MerchandiseOrder']['data']);
			$result[] = $p['MerchandiseOrder'];
		}

		$this->layout="ajax";
		$this->set('response',array('status'=>1,'data'=>$result,'since_id'=>$since_id));
		$this->render('default');
	}
	public function view_order($order_id){
		$order_id = intval($order_id);
		$this->loadModel('MerchandiseItem');
		$this->loadModel('MerchandiseOrder');
		$this->loadModel('Ongkir');
		//attach order detail
		$rs = $this->MerchandiseOrder->findById($order_id);

		$rs['MerchandiseOrder']['data'] = unserialize($rs['MerchandiseOrder']['data']);
			

		$ongkir = $this->Ongkir->find('all');
		foreach($ongkir as $o){
			if($o['Ongkir']['id'] == $rs['MerchandiseOrder']['ongkir_id']){
				$deliverTo = $o['Ongkir'];
			}
		}

		if($rs['MerchandiseOrder']['ongkir_value'] == 0)
		{
			$deliverTo['cost'] = 0;
		}

		//voucher if any

		$sql = "SELECT a.id AS voucher_id,a.voucher_code,c.name,
				b.game_team_id,
				b.po_number,
				a.merchandise_item_id,
				b.email,
				b.first_name,
				b.last_name,
				b.fb_id,
				b.id as order_id,
				b.ktp,
				b.phone,
				b.po_number,
				c.data,
				c.parent_id as item_parent_id
				FROM ".Configure::read('FRONTEND_SCHEMA').".merchandise_vouchers a
				INNER JOIN ".Configure::read('FRONTEND_SCHEMA').".merchandise_orders b
				ON a.merchandise_order_id = b.id 
				INNER JOIN ".Configure::read('FRONTEND_SCHEMA').".merchandise_items c
				ON a.merchandise_item_id = c.id
				WHERE a.merchandise_order_id = {$order_id} LIMIT 100";
		$voucher = $this->Game->query($sql);
		$vouchers = array();
		while(sizeof($voucher) > 0){
			$v = array_shift($voucher);
			$voucher_item = $v['b'];
			$voucher_item['item_name'] = $v['c']['name'];
			$voucher_item['voucher_id'] = $v['a']['voucher_id'];
			$voucher_item['voucher_code'] = $v['a']['voucher_code'];
			$voucher_item['item_data'] = json_decode($v['c']['data']);
			$voucher_item['item_id'] = $v['a']['merchandise_item_id'];
			$voucher_item['item_parent_id'] = intval(@$v['c']['item_parent_id']);
			//check parent, if parent exists, then we concat the parent's name into item's name.
			$parent_item = $this->MerchandiseItem->findById(intval($voucher_item['item_parent_id']));
			if(isset($parent_item['MerchandiseItem'])){
				$voucher_item['item_name'] = $parent_item['MerchandiseItem']['name'].' '.
																	$voucher_item['item_name'];	
			}
			$vouchers[] = $voucher_item;
		}
		$this->layout="ajax";
		$this->set('response',array('status'=>1,
									'data'=>$rs,
									'deliveryTo'=>$deliverTo,
									'vouchers'=>$vouchers));
		$this->render('default');

	}

	/*
	* returns data required for delivery fee payment page with ecash
	*/
	public function ecash_ongkir_payment($order_id){
		$this->loadModel('MerchandiseOrder');
		$this->loadModel('Ongkir');

		//fb_id
		$fb_profile = @unserialize(@decrypt_param(@$this->request->query['req']));
		
		//attach order detail
		$rs = $this->MerchandiseOrder->find('first', array(
				    	'conditions' => array(
				    			'id' => $order_id,
				    			'payment_method' => 'coins'
				    	)));

		$this->layout="ajax";

		if(isset($fb_profile) && $rs['MerchandiseOrder']['fb_id'] == $fb_profile['id']){
			
			//add suffix -1 to define that its the payment for shipping for these po number.
			$transaction_id =  $rs['MerchandiseOrder']['po_number'].'-1';

			$total_ongkir = $rs['MerchandiseOrder']['total_ongkir'];
			$total_admin_fee = $rs['MerchandiseOrder']['total_admin_fee'];
			
			$amount = $total_ongkir + $total_admin_fee;
			//ecash url
			$ecash_url = $this->Game->getEcashUrl(array(
				'transaction_id'=>$transaction_id,
				'amount'=>$amount,
				'clientIpAddress'=>$this->request->clientIp(),
				'description'=>'Shipping Fee #'.$transaction_id,
				'source'=>'SSPAY'
			));
			
			$this->set('response',array('status'=>1,
						'data'=>$rs['MerchandiseOrder'],
						'ecash_url'=>$ecash_url['data'],
						'transaction_id'=>$transaction_id,'deliveryTo'=>array('city' => $rs['MerchandiseOrder']['city'])));
		}else{
			$this->set('response',array('status'=>0));
		}
		
		$this->render('default');
	}
	public function ecash_ongkir_payment_complete(){
		$this->loadModel('MerchandiseOrder');
		$this->loadModel('MerchandiseItem');
		$this->loadModel('MerchandiseCategory');

		
		$id = $this->request->query['returnId'];
		CakeLog::write('debug','ecash_ongkir_payment_complete'.$id);
		//this is the secret data we sent, 
		//an object that consist the transaction_id and it's related order_id
		$ecash_data = unserialize(decrypt_param($this->request->query['ecash_data']));

		//these the data sent-back by ecash after user complete the payment by entering OTP.
		//$sendData = unserialize(decrypt_param($this->request->query['sendData']));

		//now validate the ecash returnId
		$rs  = $this->Game->EcashValidate($id);
		
		//CakeLog::write('debug','ecash_ongkir_payment_complete'.@$id.' - '.@$rs);
		list($id,$trace_number,$nohp,$transaction_id,$status) = explode(',',$rs['data']);

		//di comment dulu, gak working kayak gini, sessionnya beda soalnya
		//if(Configure::read('debug')!=0){
		$sendData = array('id'=>trim($id),
								'trace_number'=>trim($trace_number),
								'nohp'=>trim($nohp),
								'transaction_id'=>trim($transaction_id),
								'status'=>trim($status));
		//}
		
		if($transaction_id==$ecash_data['transaction_id']
					&& strtoupper(trim($status)) =='SUCCESS'){
			CakeLog::write('debug','ecash_ongkir_payment_complete'.$id.' - SUCCESS');
			//transaction complete, we update the order status
			$data['n_status'] = 1;
			$this->MerchandiseOrder->id = intval($ecash_data['order_id']);
			$updateResult = $this->MerchandiseOrder->save($data);
			if(isset($updateResult)){
				CakeLog::write('debug','ecash_ongkir_payment_complete'.$id.' - DBSUCCESS');
				$response_status = 1;
			}else{
				CakeLog::write('debug','ecash_ongkir_payment_complete'.$id.' - DBERROR');
				$response_status = 0;
			}
		}else{
			CakeLog::write('debug','ecash_ongkir_payment_complete'.$id.' - FAILED');
			//transaction incomplete , return error
			$response_status = 0;
		}
		$this->layout="ajax";

		CakeLog::write('debug','ecash_ongkir_payment_complete'.$id.' - '.$response_status.' - '.
						json_encode(array('status'=>$response_status,'data'=>$sendData)));

		$this->set('response',array('status'=>$response_status,'data'=>$sendData));
		$this->render('default');
	}
	public function get_ongkir(){
		$this->loadModel('Ongkir');
		$rs = $this->Ongkir->find('all',array('limit'=>10000,'order'=>array('Ongkir.kecamatan')));
		$ongkir = array();
		while(sizeof($rs)>0){
			$p = array_shift($rs);
			$ongkir[] = $p['Ongkir'];
		}	
		$this->layout="ajax";
		$this->set('response',array('status'=>1,'data'=>$ongkir));
		$this->render('default');
	}
	public function get_fm_profile(){
		$data = unserialize(decrypt_param($this->request->query['req']));
		$fb_id = $data['fb_id'];
		$team = array();
		Cakelog::write('debug', 'Api.get_fm_profile '.json_encode($data));
		
		$team = $this->Game->getTeam($fb_id);

		if(isset($team['id'])){
			$cash = $this->Game->getCash($fb_id);
		}else{
			$cash = 0;
		}
		
		$this->layout="ajax";
		$this->set('response',array('status'=>1,'data'=>array('team'=>$team,'coins'=>$cash)));
		$this->render('default');
	}
	/*
	public function test_fm_profile(){
		$data['fb_id']='622088280';
		print encrypt_param(serialize($data));
		die();

	}*/

	public function test_shopping_cart(){
		$shopping_cart[] = array('item_id'=>58,'qty'=>1);
		$shopping_cart[] = array('item_id'=>59,'qty'=>1);
		print encrypt_param(serialize($shopping_cart));
		die();

	}
	/*
	*	method for checking the items availability
	*  if the item is available, we flag the item_id with 1, else we flag it with 0
	*/
	public function catalog_checkItems(){
		$this->loadModel('MerchandiseItem');
		$this->loadModel('MerchandiseCategory');
		$this->loadModel('MerchandiseOrder');
		
		$game_team_id = intval(Sanitize::clean($this->request->query('game_team_id')));
		$fb_id = Sanitize::clean($this->request->query('fb_id'));

		$itemIds = Sanitize::clean($this->request->query('itemId'));
		$arr = explode(',',$itemIds);
		$items = array();

		for($i=0;$i<sizeof($arr);$i++){
			$item = $this->MerchandiseItem->findById(intval($arr[$i]));
			$total_claimed_qty = $this->getClaimedStock($game_team_id,$fb_id,$arr[$i]);

			if((($item['MerchandiseItem']['stock'] - $total_claimed_qty)) > 0){
				$items[intval($arr[$i])] = intval($item['MerchandiseItem']['stock']) - $total_claimed_qty;
			}else{
				
				$items[intval($arr[$i])] = 0;
			}
		}

		$this->layout="ajax";
		$this->set('response',array('status'=>1,'data'=>$items));
		$this->render('default');
	}
	private function getClaimedStock($game_team_id,$fb_id,$item_id){
		$pattern = 'claim_stock_'.$item_id.'_*';
		$claimed = $this->Game->getTmpKeys($game_team_id,
								$pattern);
		
		$total_claimed_qty = 0;
		if($claimed['status']==1){
			for($k=0;$k<sizeof($claimed['data']);$k++){
				//pastikan yg kita cek itu bukan punya si user
				$arr = explode("_",$claimed['data'][$k]);
				$owner_id = $arr[3];
				$owner_fb_id = $arr[4];
				
				if($owner_id !=0){
					if($owner_id != $game_team_id){
						$claimed_qty = $this->Game->getFromTmp($game_team_id,$claimed['data'][$k]);
						$total_claimed_qty += intval($claimed_qty['data']);

					}	
				}else if(strlen($owner_fb_id)>0){
					if($owner_fb_id != $fb_id){
						$claimed_qty = $this->Game->getFromTmp($game_team_id,$claimed['data'][$k]);
						$total_claimed_qty += intval($claimed_qty['data']);	
					}
				}else{
					//do nothing

				}
				
			}
		}
		
		return $total_claimed_qty;
	}
	
	//dummy for selling player
	public function test_buy(){
		$game_team = $this->Game->getTeam($fb_id);

		$player_id = Sanitize::clean($this->request->data['player_id']);
		$call_status = $this->request->data['status'];
		
		if(strlen($player_id)<2){
			
			$rs = array('status'=>'0','error'=>'no data available');

		}else{
			switch($call_status){
				case 1:
					$rs = array('status'=>1,'data'=>array(
									'name'=>'Michael Carrick',
									'transfer_value'=>10111573,
								),
								'message'=>"the player has been successfully bought.");

				break;
				case 2:
					$rs = array('status'=>2,'message'=>'no money');

				break;
				case -1:
					$rs = array('status'=>-1,'message'=>'you cannot buy a player who already bought from the same transfer window');

				break;
				case 3:
					$rs = array('status'=>3,'message'=>'Transfer window is closed');

				break;
				case 0:
					$rs = array('status'=>0,'message'=>'Oops, cannot buy the player.');

				break;
				default:
					$rs = array('status'=>'0','error'=>'no data available');
				break;
			}
			
		}

		

		$this->set('response',$rs);
		$this->render('default');
	}

	//dummy for buying player
	public function test_sale(){
		$game_team = $this->Game->getTeam($fb_id);

		$player_id = Sanitize::clean($this->request->data['player_id']);
		$call_status = $this->request->data['status'];
		
		if(strlen($player_id)<2){
			
			$rs = array('status'=>'0','error'=>'no data available');

		}else{
			switch($call_status){
				case 1:
					$rs = array('status'=>1,'data'=>array(
									'name'=>'Michael Carrick',
									'transfer_value'=>10111573,
								),
								'message'=>"the player has been successfully sold.");

				break;
				
				case -1:
					$rs = array('status'=>-1,'message'=>'you cannot sale a player who already bought from the same transfer window');

				break;
				case 3:
					$rs = array('status'=>3,'message'=>'Transfer window is closed');

				break;
				case 0:
					$rs = array('status'=>0,'message'=>'Oops, cannot sale the player.');

				break;
				default:
					$rs = array('status'=>0,'error'=>'no data available');
				break;
			}
			
		}


		$this->set('response',$rs);
		$this->render('default');
	}

	public function bet_match(){
		$this->layout="ajax";
		
		

		$rs = $this->Game->getMatches();
		$matches = $rs['matches'];
		unset($rs);
		$bet_matches = array();
		$n=0;
		//we display the previous match and upcoming match
		//so we got 20 matches displayed.
		$matchday = 0;
		for($i=0;$i<sizeof($matches);$i++){
			if($matches[$i]['period']!='FullTime'){
				$matchday = $matches[$i]['matchday'];
				break;
			}
		}
		//now retrieve the matches
		for($i=0;$i<sizeof($matches);$i++){
			if($matches[$i]['matchday'] == $matchday || 
					$matches[$i]['matchday'] == ($matchday - 1)){
				$bet_matches[] = array(
				'game_id'=>$matches[$i]['game_id'],
				'home_id'=>$matches[$i]['home_id'],
				'away_id'=>$matches[$i]['away_id'],
				'period'=>$matches[$i]['period'],
				'home_name'=>$matches[$i]['home_name'],
				'away_name'=>$matches[$i]['away_name'],
				'home_logo'=>'http://widgets-images.s3.amazonaws.com/football/team/badges_65/'.
									str_replace('t','',$matches[$i]['home_id']).'.png',
				'away_logo'=>'http://widgets-images.s3.amazonaws.com/football/team/badges_65/'.
									str_replace('t','',$matches[$i]['away_id']).'.png'
				);
			}
			
		}
		$this->set('response',array('status'=>1,'data'=>$bet_matches));
		$this->render('default');
	}
	//below is the list of `tebak-skor` minigame APIs
	public function submit_bet($game_id){

		$game_id = Sanitize::clean($game_id);
		CakeLog::write('debug',date("Y-m-d H:i:s - ").'submit_bet - '.$game_id);

		CakeLog::write('debug',date("Y-m-d H:i:s - ").'submit_bet - '.json_encode($this->request->query['req']));
		
		
		if(isset($this->request->query['req'])){
			$req = unserialize(decrypt_param(@$this->request->query['req']));
			$fb_id = $req['fb_id'];
			$bet_data = $req['data'];
			
			CakeLog::write("debug",json_encode($req));
			/*
			$fb_id = "100001023465395";
			$data = array(
				'SCORE_GUESS'=>array('home'=>1,'away'=>0,'coin'=>50),
				'CORNERS_GUESS'=>array('home'=>1,'away'=>0,'coin'=>10),
				'SHOT_ON_TARGET_GUESS'=>array('home'=>1,'away'=>0,'coin'=>0),
				'CROSSING_GUESS'=>array('home'=>1,'away'=>0,'coin'=>10),
				'INTERCEPTION_GUESS'=>array('home'=>1,'away'=>0,'coin'=>10),
				'YELLOWCARD_GUESS'=>array('home'=>1,'away'=>0,'coin'=>0)
			);
			$req = encrypt_param(serialize(array('fb_id'=>$fb_id,'data'=>$data)));
			$bet_data = $data;
			*/
			$game_team = $this->Game->getTeam($fb_id);
			$game_team_id = $game_team['id'];

			CakeLog::write("debug","1");

			$matches = $this->Game->getMatches();
			$the_match = array();
			CakeLog::write("debug","2");			
			foreach($matches['matches'] as $match){
				if($match['game_id'] == $game_id){
					$the_match = $match;
					break;
				}
				
			}
			CakeLog::write("debug","3");
			unset($matches);
			$coin_ok = false;

			//make sure that the coin is sufficient
			$cash = $this->Game->getCash($fb_id);
			CakeLog::write("debug",json_encode($cash));

			$total_bets = 0;

			CakeLog::write('debug',json_encode($bet_data));
			foreach($bet_data as $name=>$val){
				$total_bets += intval($val['coin']);
			}

			CakeLog::write("debug",json_encode($cash));

			CakeLog::write('debug',date("Y-m-d H:i:s").
									' - submit_bet - '.
									$game_id.' - fb_id:'.$fb_id.' - game_team_id : '.
									$game_team_id.' - cash : '.$cash.' - bet : '.$total_bets. 
									' - '.json_encode($bet_data));
			if($total_bets <= 100 
				&& $total_bets < intval($cash)){
				$coin_ok = true;
			}else{
				CakeLog::write('debug',date("Y-m-d H:i:s").
									' - submit_bet - '.'coin not ok -> total_bets : '.$total_bets);
			}

			if($the_match['period']=='PreMatch' && $coin_ok){
				
				
				foreach($bet_data as $name=>$val){
					//all negative coins will be invalid
					if($val['coin']<0){
						$val['coint'] = 0;
					}

					$sql = "INSERT INTO ".$this->ffgamedb.".game_bets
							(game_id,game_team_id,bet_name,home,away,coins,submit_dt)
							VALUES
							('{$game_id}',
								'{$game_team_id}',
								'{$name}',
								'{$val['home']}',
								'{$val['away']}',
								'{$val['coin']}',
								NOW())
							ON DUPLICATE KEY UPDATE
							home = VALUES(home),
							away = VALUES(away),
							coins = VALUES(coins)";
					$this->Game->query($sql,false);
					CakeLog::write('debug',date("Y-m-d H:i:s - ").
											'submit_bet - '.$game_id.
											' - fb_id:'.$fb_id.' - game_team_id : '.
									$game_team_id.' - cash : '.$cash.' - bet : '.$total_bets. 
									' - '.$sql);

				}
				$transaction_name = 'PLACE_BET_'.$game_id;
				$bet_cost = abs(intval($total_bets)) * -1;
				$sql = "INSERT INTO ".Configure::read('FRONTEND_SCHEMA').".game_transactions
						(fb_id,transaction_dt,transaction_name,amount,details)
						VALUES
						('{$fb_id}',NOW(),'{$transaction_name}',{$bet_cost},'deduction')
						ON DUPLICATE KEY UPDATE
						amount = VALUES(amount);";
				$this->Game->query($sql,false);
				CakeLog::write('error',$sql);
				$sql = "INSERT INTO ".Configure::read('FRONTEND_SCHEMA').".game_team_cash
						(fb_id,cash)
						SELECT fb_id,SUM(amount) AS cash 
						FROM ".Configure::read('FRONTEND_SCHEMA').".game_transactions
						WHERE fb_id = {$fb_id}
						GROUP BY fb_id
						ON DUPLICATE KEY UPDATE
						cash = VALUES(cash);";
				$this->Game->query($sql,false);


				$this->set('response',array('status'=>1,'game_id'=>$game_id,'fb_id'=>$fb_id));
			}else{
				$this->set('response',array('status'=>0,'game_id'=>$game_id,'fb_id'=>$fb_id));
			}
			
		}else{
			CakeLog::write('debug',date("Y-m-d H:i:s - ").'submit_bet - '.$game_id.' - no request');
			$this->set('response',array('status'=>0,'game_id'=>$game_id,'fb_id'=>0,'error'=>'no request specified'));
		}
		
		$this->layout="ajax";
	
		$this->render('default');
	}

	public function bet_info($game_id){
		$this->layout="ajax";


		
		$fb_id = $this->request->query['fb_id'];

		//get the game_team_id
		$game_team = $this->Game->getTeam($fb_id);
		$game_team_id = $game_team['id'];


		$matches = $this->Game->getMatches();
		$the_match = array();
		
		foreach($matches['matches'] as $match){
			if($match['game_id'] == $game_id){
				$the_match = $match;
				break;
			}
			
		}
		unset($matches);
		

		$the_match['home_logo'] = 'http://widgets-images.s3.amazonaws.com/football/team/badges_65/'.
									str_replace('t','',$the_match['home_id']).'.png';
		$the_match['away_logo'] = 'http://widgets-images.s3.amazonaws.com/football/team/badges_65/'.
									str_replace('t','',$the_match['away_id']).'.png';

		if($the_match['period'] == 'PreMatch'){
			$can_place_bet = true;
		}else{
			$can_place_bet = false;
		}
		//check if the user can place the bet
		$sql = "SELECT * FROM ".$this->ffgamedb.".game_bets a
				WHERE game_id='{$game_id}' AND game_team_id='{$game_team_id}' LIMIT 10;";

		
		CakeLog::write('debug',date("Y-m-d H:i:s - ").'bet_info - '.$game_id.' - '.$fb_id.' - '.$game_team_id.' - '.$sql);
		$check = $this->Game->query($sql,false);
		CakeLog::write('debug',date("Y-m-d H:i:s - ").'bet_info - '.$game_id.' - '.$fb_id.' - '.$game_team_id.' - '.json_encode($the_match). ' - '.json_encode(@$check[0]['a']));
		
		if(isset($check[0]['a']) 
				&& $check[0]['a']['game_team_id'] == $game_team_id){
			
			$n = 0;
			CakeLog::write('debug',date("Y-m-d H:i:s - ").'bet_info - '.$game_id.' - '.$fb_id.' - '.$game_team_id.' - has place bet');	
		}else if($the_match['period'] != 'PreMatch'){
			$can_place_bet = false;
			$n = 0;
			CakeLog::write('debug',date("Y-m-d H:i:s - ").'bet_info - '.$game_id.' - '.$fb_id.' - '.$game_team_id.' - cannot place bet :(');	
		}else{
			
			$n=1;
			CakeLog::write('debug',date("Y-m-d H:i:s - ").'bet_info - '.$game_id.' - '.$fb_id.' - '.$game_team_id.' - can place bet');	
		}

		$my_bet = array();
		
		$my_bet[] = $this->getBetValue('SCORE_GUESS',$check);
		$my_bet[] = $this->getBetValue('CORNERS_GUESS',$check);
		$my_bet[] = $this->getBetValue('SHOT_ON_TARGET_GUESS',$check);
		$my_bet[] = $this->getBetValue('CROSSING_GUESS',$check);
		$my_bet[] = $this->getBetValue('INTERCEPTION_GUESS',$check);
		$my_bet[] = $this->getBetValue('YELLOWCARD_GUESS',$check);

		if($n==1){
			$items = array(
				array('bet_name'=>'SCORE_GUESS'),
				array('bet_name'=>'CORNERS_GUESS'),
				array('bet_name'=>'SHOT_ON_TARGET_GUESS'),
				array('bet_name'=>'CROSSING_GUESS'),
				array('bet_name'=>'INTERCEPTION_GUESS'),
				array('bet_name'=>'YELLOWCARD_GUESS')
			);

			$this->set('response',array('status'=>1,
								'game_id'=>$game_id,
								'data'=>$items,
								'match'=>$the_match,
								'fb_id'=>$fb_id,
								'can_place_bet'=>$can_place_bet)
			);

		}else{

			$rs = $this->Game->getBetInfo($game_id);
			

			$items = array(
				array('bet_name'=>'SCORE_GUESS',
												'home'=>intval($rs['data']['SCORE_GUESS']['home']),
												'away'=>intval($rs['data']['SCORE_GUESS']['away'])),
				array('bet_name'=>'CORNERS_GUESS',
												'home'=>intval($rs['data']['CORNERS_GUESS']['home']),
												'away'=>intval($rs['data']['CORNERS_GUESS']['away'])),
				array('bet_name'=>'SHOT_ON_TARGET_GUESS',
												'home'=>intval($rs['data']['SHOT_ON_TARGET_GUESS']['home']),
												'away'=>intval($rs['data']['SHOT_ON_TARGET_GUESS']['away'])),
				array('bet_name'=>'CROSSING_GUESS',
												'home'=>intval($rs['data']['CROSSING_GUESS']['home']),
												'away'=>intval($rs['data']['CROSSING_GUESS']['away'])),
				array('bet_name'=>'INTERCEPTION_GUESS',
												'home'=>intval($rs['data']['INTERCEPTION_GUESS']['home']),
												'away'=>intval($rs['data']['INTERCEPTION_GUESS']['away'])),
				array('bet_name'=>'YELLOWCARD_GUESS',
												'home'=>intval($rs['data']['YELLOWCARD_GUESS']['home']),
												'away'=>intval($rs['data']['YELLOWCARD_GUESS']['away'])),


			);
			$winners = array();
			if(isset($rs['data']['winners'])){
				$winners = $rs['data']['winners'];
				
				if(sizeof($winners)>0){
					foreach($winners as $n=>$v){
						$game_user = $this->Game->query("
									SELECT fb_id FROM ".$this->ffgamedb.".game_users a
									INNER JOIN ".$this->ffgamedb.".game_teams b
									ON a.id = b.user_id WHERE b.id = {$v['game_team_id']}
									LIMIT 1;");
						$winners[$n]['game_team_id'] = null;
						$winners[$n]['fb_id'] = $game_user[0]['a']['fb_id'];
					}
				}
			}

			//dummy
			/*
			$winners = array(array('fb_id'=>'100000807572975','score'=>100),
							array('fb_id'=>'100000213094071','score'=>90),
							array('fb_id'=>'100001023465395','score'=>80));*/
			//->
			$this->set('response',array('status'=>1,
								'game_id'=>$game_id,
								'data'=>$items,
								'match'=>$the_match,
								'winners'=>$winners,
								'fb_id'=>$fb_id,
								'my_bet'=>$my_bet,
								'can_place_bet'=>$can_place_bet)
			);
		}
		
		$this->render('default');
	}

	/*
	*	Private League API for Mobile APPs
	*/
	public function private_leagues()
	{
		$this->loadModel('League');
		$fb_id = trim($this->request->query('fb_id'));
		Cakelog::write('debug', 'param '.json_encode($this->request->query));

		$rs_user = $this->User->findByFb_id($fb_id);
		if(count($rs_user) > 0)
		{
			$rs_league = $this->League->checkUser($rs_user['User']['email'], 
											$rs_user['Team']['id'], $this->league);

			$rs = $this->League->getLeague($rs_user['Team']['id'], $this->league);

			$response = array();
			foreach ($rs as $key => $value)
			{
				$is_admin = false;
				if($value['b']['user_id'] == $rs_user['User']['id']){
					$is_admin = true;
				}
				$response[$key] = array(
									'id' => $value['a']['league_id'],
									'name' => $value['b']['name'],
									'is_admin' => $is_admin,
									'players_joined' => $value['a']['total_joined'],
									'players_max' => $value['b']['max_player'],
									'players_invited' => $value['a']['total_invited']
								);
			}

			$this->set('response',array('status'=>1, 'data' => $response));

			$this->render('default');
		}
		else
		{
			$this->set('response',array('status'=>0, 'data' => null));

			$this->render('default');
		}

		
	}

	public function private_league_detail()
	{
		$this->loadModel('League');

		$private_league_id = trim($this->request->query('private_league_id'));

		$name = $this->League->query("SELECT name FROM league WHERE id='{$private_league_id}'");
		if(count($name) > 0)
		{
			$rs_weekly = $this->League->query("SELECT c.id,c.team_name,d.name,b.total_points,b.matchday 
											FROM league_member a 
											INNER JOIN league_table b ON a.league_id = b.league_id
											INNER JOIN teams c ON b.team_id = c.id
											INNER JOIN users d ON c.user_id = d.id
											WHERE b.league_id='{$private_league_id}' 
											AND b.league='{$this->league}'
											GROUP BY b.team_id,b.matchday
											ORDER BY b.total_points DESC
											LIMIT 100000");

			$rs_overall = $this->League->query("SELECT c.id,c.team_name,d.name,b.total_points,b.matchday
												FROM league_member a 
												INNER JOIN league_table b ON a.league_id = b.league_id
												INNER JOIN teams c ON b.team_id = c.id
												INNER JOIN users d ON c.user_id = d.id
												WHERE b.league_id='{$private_league_id}' 
												AND b.league='{$this->league}'
												GROUP BY b.team_id,b.matchday
												ORDER BY b.total_points DESC
												LIMIT 100000");

			$response = array();
			$weekly = array();
			$overall = array();
			$points = array();
			
			foreach ($rs_weekly as $key => $value)
			{
				$matchday = $value['b']['matchday'];
				$weekly[$matchday]['week'] = $matchday;
				$weekly[$matchday]['standing'][] = array(
										'club_name' => $value['c']['team_name'],
										'manager_name' => $value['d']['name'],
										'points' => $value['b']['total_points']
									);
			}
			$weekly = array_values($weekly);

			foreach($rs_overall as $key => $value)
			{
				$team_id = $value['c']['id'];
				if(!isset($points[$team_id])){
					$points[$team_id] = 0;
				}
				$points[$team_id] += $value['b']['total_points'];
				$overall[$team_id] = array(
										'club_name' => $value['c']['team_name'],
										'manager_name' => $value['d']['name'],
										'points' => $points[$team_id]
									);
			}

			arsort($points);
			$overall_fix = array();
			
			foreach ($points as $key => $value)
			{
				$overall_fix[$key] = $overall[$key];
			}

			$overall_fix = array_values($overall_fix);
			
			$this->set('response',array('status'=>1, 'data' => array(
																'name' => $name[0]['league']['name'],
																'weekly_standing' => $weekly,
																'overall_standing' => $overall_fix
																)));
			
		}
		else
		{
			$this->set('response',array('status'=>0, 'data' => array(
																'name' => null,
																'weekly_standing' => null,
																'overall_standing' => null
																)));
		}

		$this->render('default');
	}

	public function manage_private_league()
	{
		$this->loadModel('leagueInvitation');
		$private_league_id = trim($this->request->query('private_league_id'));

		$rs_invited = $this->leagueInvitation->find('all', array(
	    	'limit' => 1000,
	        'conditions' => array(
	        						'leagueInvitation.league_id' => $private_league_id,
	        						'league' => $this->league,
	        						'n_status' => 0
	        					))
	    );

	    $email_invited = array();
	    foreach ($rs_invited as $key => $value)
	    {
	    	$email_invited[] = array('email' => $value['leagueInvitation']['email']);
	    }
	    $email_invited = array_values($email_invited);

		$rs_joined = $this->leagueInvitation->find('all', array(
	    	'limit' => 1000,
	        'conditions' => array(
	        						'leagueInvitation.league_id' => $private_league_id,
	        						'league' => $this->league,
	        						'n_status' => 1
	        					))
	    );

	    $email_joined = array();
	    foreach ($rs_joined as $key => $value)
	    {
	    	$email_joined[] = array('email' => $value['leagueInvitation']['email']);
	    }
	    $email_joined = array_values($email_joined);

		$this->set('response',array('status'=>1, 'data' => array(
															'joined' => $email_joined,
															'waiting_confirmation' => $email_invited
															)));
		$this->render('default');
	}

	public function private_league_invites()
	{
		$this->loadModel('League');
		$fb_id = trim($this->request->query('fb_id'));

		$rs_user = $this->User->findByFb_id($fb_id);

		$rs_invited = $this->League->query("SELECT * FROM league_invitations a 
											INNER JOIN league b ON a.league_id = b.id 
											INNER JOIN users c ON b.user_id = c.id 
											WHERE a.email = '{$rs_user['User']['email']}' AND a.n_status = 0
											AND a.league='{$this->league}'
											LIMIT 100000");
		Cakelog::write('debug', json_encode($rs_invited));
		$response = array();
		foreach ($rs_invited as $key => $value)
		{
			$response[] = array(
												'private_league_id' => $value['b']['id'],
												'private_league_name' => $value['b']['name'],
												'inviter' => $value['c']['name']
												);
		}
		$this->set('response',array('status'=>1, 'data' => array('invitations' => $response)));
		$this->render('default');
	}

	public function private_league_config()
	{
		$max_player = Configure::read('PRIVATE_LEAGUE_PLAYER_MAX');
		$this->set('response',array('status'=>1, 'data' => array('max_player' => $max_player)));
		$this->render('default');
	}

	public function create_private_league()
	{
		$this->loadModel('League');
		if($this->request->is("post"))
		{
			$upload_dir	= Configure::read('privateleague_web_dir');
			$max_player = Configure::read('PRIVATE_LEAGUE_PLAYER_MAX');

			$fb_id = Sanitize::clean($this->request->data['fb_id']);
			$league_name = Sanitize::clean($this->request->data['private_league_name']);
			$rs_user = $this->User->findByFb_id($fb_id);

			/*$league_logo = $_FILES['private_league_logo'];
			if(is_array($league_logo))
			{
				$allow_ext = array('jpeg', 'jpg', 'gif');
				$aFile = explode('.', $league_logo['name']);
				$file_ext = array_pop($aFile);
				$filename = 'privateleague_default.jpg';
				if(in_array($file_ext, $allow_ext))
				{
					$filename = date("ymdhis").'-'.rand(0, 99999).'.'.$file_ext;
					if(move_uploaded_file($league_logo['tmp_name'], $upload_dir.$filename))
					{
						$this->Thumbnail->create($upload_dir.$filename,
														$upload_dir.'thumb_'.$filename,
														150,150);
					}
				}	
			}*/
			$filename = 'privateleague_default.jpg';
			$data = array('name' => $league_name,
									'logo' => $filename,
									'type' => 'private_league',
									'user_id' => $rs_user['User']['id'],
									'max_player' => $max_player,
									'date_created' => date("Y-m-d H:i:s"),
									'n_status' => 1,
									'league' => $this->league
									);

			try{
				$dataSource = $this->League->getDataSource();
				$dataSource->begin();

				$this->League->create();
				$result = $this->League->save($data);

				$league_id = $this->League->id;

				$this->League->query("INSERT INTO 
									league_invitations(league_id, email, n_status, league) 
									VALUES('{$league_id}','{$rs_user['User']['email']}',1,'{$this->league}')");

				$this->League->query("INSERT INTO league_member
											(league_id,team_id,join_date,n_status,league) 
											VALUES('{$league_id}','{$rs_user['Team']['id']}',
												now(),1,'{$this->league}')");

				$dataSource->commit();
				$response = array('id' => $result['League']['id'],
								   'name' => $result['League']['name'],
								   'max_player' => $result['League']['max_player']
									);

				$this->set('response',array('status'=>1, 'data' => $response));
				

			}catch(Exception $e){
				$dataSource->rollback();
				$this->set('response',array('status'=>0, 
										'data' => null, 
										'message' => 'Only One Private League Allowed Per User')
				);
			}

		}
		else
		{
			$this->set('response',array('status'=>0, 'data' => null));
		}
		$this->render('default');
	}

	public function invite_to_private_league()
	{
		if($this->request->is("post"))
		{
			$this->loadModel('League');
			$this->loadModel('leagueInvitation');
			$private_league_id = $this->request->data['private_league_id'];
			$email = $this->request->data['email'];

			try{

				$rs_league = $this->League->findById($private_league_id);
				$max_player = $rs_league['League']['max_player'];

				$rs_invited = $this->leagueInvitation->find('count', array(
		        'conditions' => array(
		        						'leagueInvitation.league_id' => $private_league_id,
		        						'league' => $this->league,
		        						'n_status' => 0
		        					))
			    );

			    $rs_joined = $this->leagueInvitation->find('count', array(
			        'conditions' => array(
			        						'leagueInvitation.league_id' => $private_league_id,
			        						'league' => $this->league,
			        						'n_status' => 1
			        					))
			    );

			    $limit = $max_player - ($rs_invited + $rs_joined);

			    $dataSource = $this->leagueInvitation->getDataSource();
				$dataSource->begin();

			    $i=2;
				foreach ($email as $value)
				{
					$this->leagueInvitation->query("INSERT INTO fantasy.league_invitations
													(league_id,email,n_status,league) 
													VALUES
													('{$private_league_id}','{$value}',
														0,'{$this->league}')");

					if($i == $limit){
						break;
					}
					$i++;
				}

				$dataSource->commit();
				
				$this->set('response',array('status'=>1));
			}catch(Exception $e){
				$dataSource->rollback();
				$this->set('response',array('status'=>0, 
											'message' => $value.' has joined in other private league'));
			}
		}
		else
		{
			$this->set('response',array('status'=>0, 'message' => 'Post Method Only'));
		}
		$this->render('default');
	}

	public function private_league_invite_action()
	{
		if($this->request->is("post"))
		{
			$this->loadModel('League');
			$this->loadModel('leagueInvitation');

			$fb_id = $this->request->data['fb_id'];
			$private_league_id = $this->request->data['private_league_id'];
			$action = $this->request->data['action'];
			$rs_user = $this->User->findByFb_id($fb_id);
			try{
				$email = $rs_user['User']['email'];
				$team_id = $rs_user['Team']['id'];
				$dataSource = $this->League->getDataSource();
				$dataSource->begin();
				if($action == 'accept')
				{
					$this->leagueInvitation->updateAll(
									array('n_status' => 1),
									array(
											'league_id' => $private_league_id,
											'email' => $email, 
											'n_status' => 0, 
											'league' => $this->league
										)
									);
				}
				else if($action == 'reject')
				{
					$this->leagueInvitation->updateAll(
									array('n_status' => 2),
									array(
											'league_id' => $private_league_id,
											'email' => $email, 
											'n_status' => 0, 
											'league' => $this->league
										)
									);
				}
				else
				{
					throw new Exception("Action not defined");
				}

				$this->League->query("INSERT INTO league_member(league_id,team_id,join_date,n_status,league)
										VALUES('{$private_league_id}','{$team_id}',now(),1,'{$this->league}')");
				$dataSource->commit();
				$this->set('response',array('status'=>1));
			}catch(Exception $e){
				$dataSource->rollback();

				Cakelog::write('error', 
				'Api.private_league_invite_action '.$e->getMessage().
				' data:'.json_encode($rs_user));

				$this->set('response',array('status'=>0,'message'=>'something wrong'));
			}
		}
		else
		{
			$this->set('response',array('status'=>0, 'message' => 'Post Method Only'));
		}
		$this->render('default');
	}

	/*
	*	End Of Private League API	
	*/

	private function getBetValue($bet_name,$bets){
		for($i=0;$i<sizeof($bets);$i++){
			if($bets[$i]['a']['bet_name']==$bet_name){
				return $bets[$i]['a'];
			}
		}
	}
	//--> and of `tebak-skor` minigame APIs

	//all the stuffs related to ticket agent / retailers
	private function agent_dummy(){
		$email = 'dev@supersoccer.co.id';
		$password = '12345678';
		$secret = 'qawsedrftg';
		$hash = sha1($email.$password.$secret);
		$this->layout="ajax";
		$this->set('response',array('hash'=>$hash));
		$this->render('default');
	}

	public function agent_login(){
		$this->layout="ajax";
		$email = Sanitize::clean($this->request->data['email']);
		$password = Sanitize::clean($this->request->data['password']);

		if(strlen($email) > 0 && strlen($password) > 0){
			$this->loadModel('Agent');
			$agent = $this->Agent->findByEmail($email);
			$secret = @$agent['Agent']['secret'];
			$hash = sha1($email.$password.$secret);

			if(@$agent['Agent']['password'] == $hash){
				$status = 1;
				$token = encrypt_param(serialize(array(
							'name'=>$agent['Agent']['name'],
							'email'=>$agent['Agent']['email'],
							'agent_id'=>$agent['Agent']['id'],
							'login_dt'=>date("Y-m-d H:i:s"),
							'ts'=>time()
						 )));
				$data = array(
							'name'=>$agent['Agent']['name'],
							'email'=>$agent['Agent']['email'],
							'agent_id'=>$agent['Agent']['id']
						 );
			}else{
				$status = 0;
				$token = '';
				$data = '';
			}
			$this->set('response',array('status'=>$status,'token'=>$token,'data'=>$data));
		}else{
			$this->set('response',array('status'=>0));
		}

		
		$this->render('default');
	}
	/*
	* /api/agent_catalog?agent_id=[n]&token=[s]
	* Response :JSON
	*/
	public function agent_catalog(){
		$this->layout="ajax";

		$agent_id = intval(@$this->request->query['agent_id']);
		$token = @$this->request->query['token'];

		if($agent_id > 0){
			if($this->agent_validate($agent_id,$token)){
				//do something
				$catalog = $this->getAgentCatalog($agent_id);
				$this->set('response',array('status'=>1,'data'=>$catalog));
			}else{
				$this->set('response',array('status'=>0,'error'=>'invalid token'));
			}
		}else{
			$this->set('response',array('status'=>0,'error'=>'invalid agent'));
		}
		$this->render('default');
	}
	/*
	* api for requesting item quota
	* /api/agent_request_quota?agent_id=[n]&item_id=[n]&qty=[n]
	* Response :JSON
	*/
	public function agent_request_quota(){
		$this->layout="ajax";

		$agent_id = intval(@$this->request->query['agent_id']);
		$item_id = intval(@$this->request->query['item_id']);
		$qty = intval(@$this->request->query['qty']);

		$token = @$this->request->query['token'];

		if($agent_id > 0){
			if($this->agent_validate($agent_id,$token)){
				//do something
				$rs = $this->setAgentRequestQuota($agent_id,$item_id,$qty);
				$this->set('response',array('status'=>1,'data'=>$rs));
			}else{
				$this->set('response',array('status'=>0,'error'=>'invalid token'));
			}
		}else{
			$this->set('response',array('status'=>0,'error'=>'invalid agent'));
		}
		$this->render('default');
	}
	/*
	* api for returning item quotas
	* /api/agent_return_quota?agent_id=[n]&item_id=[n]&qty=[n]
	* Response :JSON
	*/
	public function agent_return_quota(){
		$this->layout="ajax";

		$agent_id = intval(@$this->request->query['agent_id']);
		$item_id = intval(@$this->request->query['item_id']);
		$qty = intval(@$this->request->query['qty']);

		$token = @$this->request->query['token'];

		if($agent_id > 0){
			if($this->agent_validate($agent_id,$token)){
				//do something
				$rs = $this->setAgentReturnedQuota($agent_id,$item_id,$qty);
				$this->set('response',array('status'=>1,'data'=>$rs));
			}else{
				$this->set('response',array('status'=>0,'error'=>'invalid token'));
			}
		}else{
			$this->set('response',array('status'=>0,'error'=>'invalid agent'));
		}
		$this->render('default');
	}
	/*
	* API for vieweing the request quota history
	* /api/agent_request_quota?agent_id=[n]&start=0&total=10
	* Response :JSON
	*/
	public function agent_request_history(){
		$this->layout="ajax";

		$agent_id = intval(@$this->request->query['agent_id']);
		$start = intval(@$this->request->query['start']);
		$total = intval(@$this->request->query['total']);
		
		if($total > 20){
			$total = 20;
		}else if($total==0){
			$total = 10;
		}else{}

		$token = @$this->request->query['token'];

		if($agent_id > 0){
			if($this->agent_validate($agent_id,$token)){
				//do something
				$rs = $this->getAgentRequestHistory($agent_id,$start,$total);
				$this->set('response',array('status'=>1,'data'=>$rs));
			}else{
				$this->set('response',array('status'=>0,'error'=>'invalid token'));
			}
		}else{
			$this->set('response',array('status'=>0,'error'=>'invalid agent'));
		}
		$this->render('default');
	}
	/*
	* API for vieweing the request quota history
	* /api/agent_orders?agent_id=[n]&start=0&total=10
	* Response :JSON
	*/
	public function agent_orders(){
		$this->layout="ajax";

		$agent_id = intval(@$this->request->query['agent_id']);
		$start = intval(@$this->request->query['start']);
		$total = intval(@$this->request->query['total']);
		
		if($total > 20){
			$total = 20;
		}else if($total==0){
			$total = 10;
		}else{}

		$token = @$this->request->query['token'];

		if($agent_id > 0){
			if($this->agent_validate($agent_id,$token)){
				//do something
				$rs = $this->getAgentOrders($agent_id,$start,$total);
				$this->set('response',array('status'=>1,'data'=>$rs));
			}else{
				$this->set('response',array('status'=>0,'error'=>'invalid token'));
			}
		}else{
			$this->set('response',array('status'=>0,'error'=>'invalid agent'));
		}
		$this->render('default');
	}
	/*
	* API for vieweing the request quota history
	* POST :  /api/agent_checkout/
	* PostData : ?agent_id=[n]&d=[hashed_data]
	* Response :JSON
	*/
	public function agent_checkout(){
		$this->layout="ajax";

		$agent_id = intval(@$this->request->data['agent_id']);
		
		$order_data = unserialize(decrypt_param(@$this->request->data['d']));

		$token = @$this->request->data['token'];

		$po_number = '1001'.$agent_id.'-'.date("YmdHis").'-'.rand(1000,9999);

		if($agent_id > 0){
			if($this->agent_validate($agent_id,$token)){
				//save order
				$order_id = $this->saveAgentOrder($agent_id,$po_number,$order_data);
				if($order_id > 0){
					//generate vouchers
					$this->generateAgentVouchers($agent_id,$order_id,$po_number,
													$order_data['item_id'],$order_data['qty']);
					$this->set('response',array('status'=>1,
												'data'=>array('po_number'=>$po_number,
															'order_id'=>$order_id,
															'item_id'=>$order_data['item_id'],
															'qty'=>$order_data['qty'])));	
				}else{
					$this->set('response',array('status'=>0,'error'=>'cannot complete the purchase'));
				}
				
				
			}else{
				$this->set('response',array('status'=>0,'error'=>'invalid token'));
			}
		}else{
			$this->set('response',array('status'=>0,'error'=>'invalid agent'));
		}
		$this->render('default');
	}

	/*
	* API for login user from supersoccer
	* POST :  /api/login_supersoccer/
	* PostData : ?email=[s]&password=[s]
	* Response :JSON
	*/
	public function login_supersoccer()
	{
		$this->layout="ajax";
		$email = Sanitize::clean($this->request->data['email']);
		$password = Sanitize::clean($this->request->data['password']);

		if(strlen($email) > 0 && strlen($password) > 0){
			$this->loadModel('User');
			$rs_user = $this->User->findByEmail($email);
			$passHasher 	= new PasswordHash(8, true);
			$check_password = $passHasher->CheckPassword($password.$rs_user['User']['secret'] ,
									$rs_user['User']['password']);
			if($check_password){
				$this->loadModel("Game");
				$status = 1;
				$team = $this->Game->getTeam($rs_user['User']['fb_id']);
				CakeLog::write('debug', 'Teams :'.json_encode($team));

				$cash = 0;
				if(isset($team['id'])){
					$cash = $this->Game->getCash($rs_user['User']['fb_id']);
					CakeLog::write('debug', 'Cash :'.json_encode($cash));
				}

				$need_password = ($rs_user['User']['password'] == "") ? 1 : 0;
				$api_key = $this->Apikey->find('first');
				$access_token = encrypt_param(serialize(array('fb_id'=>$rs_user['User']['fb_id'],
															'api_key'=>$api_key['Apikey']['api_key'],
															  'valid_until'=>time()+24*60*60)));
				$this->redisClient->set($access_token,serialize(array('api_key'=>$api_key['Apikey']['api_key'],
																		  'fb_id'=>$rs_user['User']['fb_id'])));
				$this->redisClient->expire($access_token,24*60*60);//expires in 1 day

				$profile = array(
								'fb_id' => $rs_user['User']['fb_id'],
								'name'	=> $rs_user['User']['name'],
								'email'	=> $rs_user['User']['email'],
								'n_status' => $rs_user['User']['n_status'],
								'register_completed' => $rs_user['User']['register_completed'],
								'need_password' => $need_password
							);

				$this->set('response',array('status'=>1, 
											'data'=>array('profile'=>$profile,
															'team'=>$team,
															'coins'=>$cash,
															'access_token'=>$access_token)
											));
				$this->ActivityLog->writeLog($rs_user['User']['id'],'LOGIN');
			}
			else
			{
				$this->set('response',array('status'=>0, 'message' => 'Wrong Username or Password'));
			}

			
		}else{
			$this->set('response',array('status'=>0, 'message' => 'Terjadi Kesalahan'));
		}

		$this->render('default');

	}

	public function login_supersoccer_facebook()
	{
		$user_id = Sanitize::clean($this->request->query('user_id'));
		$rs_user = $this->User->findById($user_id);

		if(count($rs_user) > 0)
		{
			$this->set('response',array('status'=>1));
			$this->ActivityLog->writeLog($rs_user['User']['id'],'LOGIN');
			Cakelog::write('debug', 'api.login_supersoccer_facebook '.json_encode($rs_user));
		}
		else
		{
			$this->set('response',array('status'=>0));
			Cakelog::write('debug', 'api.login_supersoccer_facebook error '.json_encode($rs_user));
		}
		$this->render('default');
	}


	/*
	* API for login user from register_supersoccer
	* POST :  /api/register_supersoccer/
	* PostData : seee variable $data_save
	* Response :JSON
	*/
	public function register_supersoccer()
	{
		$this->layout="ajax";
		$data = $this->request->data;

		CakeLog::write('debug', 'api.register_supersoccer datanya :'.json_encode($data));

		$fb_id = $data['fb_id'];
		$fb_id_ori = $data['fb_id'];

		$secret		= '';
		$user_pass 	= '';

		if($fb_id == "")
		{
			$fb_id = date("Ymdhis").rand(1000, 9999);
			$fb_id_ori = NULL;

			$secret		= md5(date("Ymdhis"));
			$passHasher = new PasswordHash(8, true);
			$user_pass 	= $passHasher->HashPassword($data['password'].$secret);

		}

		$data_save = array('fb_id_ori'=>$fb_id_ori,
					  'fb_id'=>$fb_id,
					  'name'=>$data['name'],
					  'email'=>$data['email'],
					  'password'=>$user_pass,
					  'secret'=>$secret,
					  'location'=>$data['city'],
					  'phone_number'=>$data['phone_number'],
					  'register_date'=>date("Y-m-d H:i:s"),
					  'survey_about'=>$data['hearffl'],
					  'survey_daily_email'=>0,
					  'survey_daily_sms'=>0,
					  'survey_has_play'=>$data['firstime'],
					  'faveclub'=>Sanitize::clean(@$data['faveclub']),
					  'birthdate'=>$data['birthdate'],
					  'n_status'=>0,
					  'register_completed'=>0,
					  'activation_code' => date("Ymdhis").rand(100, 999),
					  'ref_code' => $data['ref_code']
					  );

		//make sure that the fb_id is unregistered
		$check = $this->User->findByFb_id($fb_id);
		//make sure that the email is not registered yet.
		$check2 = $this->User->findByEmail($data['email']);

		if(isset($check['User']) && @$check2['User']['register_completed'] != 0)
		{
			$this->set('response',array('status'=>0, 
					'message' => 'Mohon maaf, akun kamu sudah terdaftar sebelumnya. !'));
		}
		else if(isset($check2['User']) 
					|| @$check2['User']['email'] == $data['email']
					|| @$check2['User']['register_completed'] != 0)
		{

			$this->set('response',array('status'=>0, 
					'message' => 'Mohon maaf, akun email ini `'.Sanitize::html($data['email']).'` 
				sudah terdaftar sebelumnya. Silahkan menggunakan alamat email yang lain !'));
		}
		else
		{
			if(!isset($check2['User'])){
				try{
					$dataSource = $this->User->getDataSource();
					$dataSource->begin();
					$this->User->create();

					$rs = $this->User->save($data_save);
					$user_data = $rs['User'];
					$this->ActivityLog->writeLog($user_data['id'], 'REGISTER', $data['ref_code']);

					$this->User->query("INSERT INTO ".Configure::read('FRONTEND_SCHEMA').".game_transactions
										(fb_id,transaction_dt,transaction_name,amount,details)
										VALUES
										('{$fb_id}',NOW(),'START',0,'START')
										ON DUPLICATE KEY UPDATE
										amount = VALUES(amount)");

					$this->User->query("INSERT INTO ".Configure::read('FRONTEND_SCHEMA').".game_team_cash
										(fb_id,cash)
										SELECT fb_id,SUM(amount) AS cash 
										FROM ".Configure::read('FRONTEND_SCHEMA').".game_transactions
										WHERE fb_id = '{$fb_id}'
										GROUP BY fb_id
										ON DUPLICATE KEY UPDATE
										cash = VALUES(cash)");
					$dataSource->commit();
				}catch(Exception $e){
					$dataSource->rollback();
					Cakelog::write('error', 'api.register_supersoccer insert error msg: '.$e->getMessage());
					$this->set('response',
									array('status'=>0, 
										'message' => 'Akun Anda Sudah Terdaftar Sebelumnya'
									));
				}

			}
			
			if(isset($rs['User']) || isset($check2['User'])){
				//register user into gameAPI.
				//$this->loadModel('ProfileModel');
				$response = $this->api_post('/user/register',$data_save);

				//CakeLog::write('error', 
				//	'api.register_supersoccer /user/register data'.json_encode($response));

				if($response['status']==1 || @$check2['User']['register_completed'] == 0)
				{
					if(@$rs['User']['n_status'] == 0 || $user_data['n_status'] == 0)
					{
						unset($rs['User']['password']);
						unset($rs['User']['secret']);
						$this->set('response',
									array('status'=>1, 
										'profile' => $rs['User']
									));
					}
					else
					{
						unset($check2['User']['password']);
						unset($check2['User']['secret']);
						$this->set('response',
									array('status'=>1, 
										'profile' => $check2['User']
									));
					}
				}
				else
				{
					CakeLog::write('error', 
					'api.register_supersoccer /user/register data'.json_encode($response));
					$this->set('response',
									array('status'=>0, 
										'message' => 'Terjadi Kesalahan, Silahkan coba beberapa saat lagi'
									));
				}
			}
		}

		$this->render('default');
	}

	public function check_email()
	{
		$email = trim($this->request->query['email']);

		$rs_user = $this->User->findByEmail($email);
		$data = array();
		if(count($rs_user) > 0)
		{
			$data['id'] = $rs_user['User']['id'];
			$data['fb_id'] = $rs_user['User']['fb_id'];
			$data['name'] = $rs_user['User']['name'];
			$data['email'] = $rs_user['User']['email'];

			$this->set('response',array('status'=>1, 'data' => $data));
		}
		else
		{
			$this->set('response',array('status'=>1, 'data' => null));
		}
		$this->render('default');
	}

	public function check_fbid()
	{
		$fb_id = trim($this->request->query['fb_id']);

		$rs_user = $this->User->findByFb_id($fb_id);
		$data = array();
		if(count($rs_user) > 0)
		{
			$data['id'] = $rs_user['User']['id'];
			$data['fb_id'] = $rs_user['User']['fb_id'];
			$data['name'] = $rs_user['User']['name'];
			$data['email'] = $rs_user['User']['email'];

			$this->set('response',array('status'=>1, 'data' => $data));
		}
		else
		{
			$this->set('response',array('status'=>1, 'data' => null));
		}
		$this->render('default');
	}

	/*
	* API for login user from send_activation
	* POST :  /api/send_activation/
	* PostData : ?fb_id=[s]&email=[s]
	* Response :JSON
	*/
	public function send_activation()
	{
		if($this->request->is("post"))
		{
			$this->loadModel('User');
			$fb_id = Sanitize::clean($this->request->data['fb_id']);
			$email = Sanitize::clean($this->request->data['email']);

			$this->User->query("UPDATE users SET email='{$email}' WHERE fb_id='{$fb_id}'");
			$rs_user = $this->User->findByFb_id($fb_id);
			if(count($rs_user) != 0)
			{
				$data_request['email'] = $rs_user['User']['email'];
				$data_request['activation_code'] = $rs_user['User']['activation_code'];

				$send_mail = $this->requestAction(
													array(
														'controller' => 'profile',
														'action' => 'send_mail'
													),
													array('data_request' => $data_request)
												);
				if($send_mail)
				{
					$this->set('response', array('status'=>1));
				}
				else
				{
					$this->set('response', array('status'=>0));
				}
			}
			else
			{
				$this->set('response', array('status'=>0));
			}
		}
		else
		{
			$this->set('response', array('status'=>0));
		}
		$this->render('default');
	}


	/*
	* API for login user from activation_user
	* POST :  /api/activation_user/
	* PostData : ?email=[s]&activation_code=[n]
	* Response :JSON
	*/
	public function activation_user()
	{
		if($this->request->is("post"))
		{
			$this->loadModel('User');
			$email = trim(Sanitize::clean($this->request->data['email']));
			$activation_code = trim(Sanitize::clean($this->request->data['activation_code']));

			$rs_user = $this->User->findByEmail($email);
			if(count($rs_user) != 0)
			{
				if($rs_user['User']['activation_code'] == $activation_code)
				{
					$this->User->query("UPDATE users SET n_status = 1 WHERE email='{$email}'");
					$this->set('response', array('status'=>1));
				}
				else
				{
					$this->set('response', array('status'=>0));
				}
			}
			else
			{
				$this->set('response', array('status'=>0));
			}
		}
		else
		{
			$this->set('response', array('status'=>0));
		}
		$this->render('default');
	}

		/*
	* API for login user from send_activation_mobile
	* POST :  /api/send_activation_mobile/
	* PostData : ?fb_id=[s]&email=[s]
	* Response :JSON
	*/
	public function send_activation_mobile()
	{
		if($this->request->is("post"))
		{
			$this->loadModel('User');
			$fb_id = Sanitize::clean($this->request->data['fb_id']);
			$email = Sanitize::clean($this->request->data['email']);

			$this->User->query("UPDATE users SET email='{$email}' WHERE fb_id='{$fb_id}'");
			$rs_user = $this->User->findByFb_id($fb_id);
			if(count($rs_user) != 0)
			{
				$data_request['email'] = $rs_user['User']['email'];
				$data_request['activation_code'] = $rs_user['User']['activation_code'];

				$encrypt_param = encrypt_param(serialize($data_request));
				$ss_web = Configure::read('SUPERSOCCER_WEB');
				$data_request['url'] = $ss_web.'activation/?param='.$encrypt_param;

				$send_mail = $this->requestAction(
													array(
														'controller' => 'profile',
														'action' => 'send_link_activation'
													),
													array('data_request' => $data_request)
												);
				if($send_mail)
				{
					$this->set('response', array('status'=>1));
				}
				else
				{
					$this->set('response', array('status'=>0));
				}
			}
			else
			{
				$this->set('response', array('status'=>0));
			}
		}
		else
		{
			$this->set('response', array('status'=>0));
		}
		$this->render('default');
	}


	/*
	* API for login user from activation_user_mobile
	* POST :  /api/activation_user_mobile/
	* PostData : ?email=[s]&activation_code=[n]
	* Response :JSON
	*/
	public function activation_user_mobile()
	{
		$this->loadModel('User');
		$email = trim(Sanitize::clean($this->request->query['email']));
		$activation_code = trim(Sanitize::clean($this->request->query['activation_code']));

		$rs_user = $this->User->findByEmail($email);
		if(count($rs_user) != 0)
		{
			if($rs_user['User']['activation_code'] == $activation_code)
			{
				$this->User->query("UPDATE users SET n_status = 1 WHERE email='{$email}'");
				$this->set('response', array('status'=>1));
			}
			else
			{
				$this->set('response', array('status'=>0));
			}
		}
		else
		{
			$this->set('response', array('status'=>0));
		}
		$this->render('default');
	}

	public function get_quiz()
	{
		try{
			$this->loadModel('Game');
			$rs_quiz = $this->Game->query("SELECT 
											    question, question_id, is_answer, answer
											FROM
											    game_quiz_questions a
											INNER JOIN
											    game_quiz_answers b ON a.id = b.question_id
											WHERE
											    a.status = 1");

			$data = array();
			$i=0;
			foreach ($rs_quiz as $keys => $values)
			{
				$data[$values['a']['question']][] = $values['b'];
			}
			$this->set('response', array('status'=>1, 'data' => json_encode($data)));

		}catch(Exception $e){
			$this->set('response', array('status'=>0, 'message' => $e->getMessage()));
		}

		$this->render('default');
	}

	public function check_banned()
	{
		try{
			$this->loadModel('User');
			$this->loadModel('MerchandiseItem');

			$fb_id = Sanitize::clean($this->request->query('fb_id'));
			$users = $this->User->findByFb_id($fb_id);
			$rs_banned = $this->MerchandiseItem->query("SELECT * FROM banned_users
														WHERE user_id = '{$users['User']['id']}' LIMIT 100");
			Cakelog::write('debug', json_encode($rs_banned));
			$data = array();
			foreach ($rs_banned as $key => $value){
				$data[] = $value['banned_users'];
			}
			$this->set('response', array('status'=>1,'data'=>$data));

		}catch(Exception $e){
			Cakelog::write('error', $e->getMessage());
			$this->set('response', array('status'=>0,'msg'=>$e->getMessage()));
		}

		$this->render('default');
	}

	private function saveAgentOrder($agent_id,$po_number,$order_data){
		$this->loadModel('MerchandiseItem');
		$this->loadModel('AgentItem');
		//sanitize
		$order_data['item_id'] = intval($order_data['item_id']);
		$order_data['qty'] = intval($order_data['qty']);

		//item detail
		$item = $this->MerchandiseItem->findById(intval($order_data['item_id']));

		//agent's stock
		$agent_stock = $this->AgentItem->find('first',array(
			'conditions'=>array('agent_id'=>$agent_id,
								'merchandise_item_id'=>$order_data['item_id'])
		));

		//stock before purchased
		$current_stock = intval(@$agent_stock['AgentItem']['qty']);
		//stock after purchased
		$new_stock = $current_stock - intval($order_data['qty']);

		if(isset($item['MerchandiseItem']) && $new_stock >= 0 && $current_stock > 0){
			$total_price = intval($item['MerchandiseItem']['price_money']) * intval($order_data['qty']);
			
			$data = $order_data;

			$shopping_cart = array(
				array('item_id'=>$order_data['item_id'],
					'qty'=>$order_data['qty'],
					'data'=>$item)
			);
			$data['merchandise_item_id'] = $order_data['item_id'];
			$data['user_id'] = 0;
			$data['order_type'] = 1;
			$data['game_team_id'] = 0;
			$data['agent_id'] = $agent_id;
			$data['n_status'] = 3;
			$data['order_date'] = date("Y-m-d H:i:s");
			$data['data'] = serialize($shopping_cart);
			$data['po_number'] = $po_number;
			$data['total_sale'] = intval($total_price);
			$data['payment_method'] = 'direct';
			$data['trace_code'] = 0;
			$data['ongkir_id'] = intval(@$data['city_id']);
			$data['ongkir_value'] = 0;
			
			$this->loadModel('AgentOrder');
			$this->AgentOrder->create();
			$rs = $this->AgentOrder->save($data);
			$order_id = $this->AgentOrder->getInsertID();
			if(intval($order_id) > 0){
				//reduce the stock
				$this->reduceAgentStock($agent_id,$order_data['item_id'],$order_data['qty']);
				return $order_id;
			}else{
				return 0;
			}
		}else{
			return 0;
		}
	}
	private function reduceAgentStock($agent_id,$item_id,$qty){
		return $this->Game->query("UPDATE ".Configure::read('FRONTEND_SCHEMA').".agent_items SET qty = qty - {$qty} 
							WHERE agent_id={$agent_id} AND merchandise_item_id={$item_id}");
	}
	/*
	* generate ticket voucher, and saves it in ".Configure::read('FRONTEND_SCHEMA').".merchandise_vouchers
	*/
	private function generateAgentVouchers($agent_id,$order_id,$po_number,$item_id,$qty){
		
		$n_vouchers = 0;
		$no = 1;
		$qty = intval($qty);
		
			for($j=0;$j < $qty;$j++){
				$voucher_code = $po_number.$no;
				CakeLog::write('generateAgentVoucher',date("Y-m-d H:i:s")." - ".$po_number.' - '.$voucher_code);
				
				$sql = "
				INSERT IGNORE INTO ".Configure::read('FRONTEND_SCHEMA').".agent_vouchers
				(agent_id,agent_order_id,merchandise_item_id,voucher_code,created_dt,n_status)
				VALUES
				({$agent_id},
				{$order_id},
				 {$item_id},
				 '{$voucher_code}',
				 NOW(),
				 0)";
				CakeLog::write('generateAgentVoucher',' - >'.$sql);
				$rs = $this->Game->query($sql);
				
				$no++;
				$n_vouchers++;
				
			}
			
		return $n_vouchers;
	}
	private function getAgentRequestHistory($agent_id,$start,$total){
		$this->loadModel('AgentRequest');
		$rs = $this->AgentRequest->query("SELECT * FROM 
											".Configure::read('FRONTEND_SCHEMA').".agent_requests a
											INNER JOIN ".Configure::read('FRONTEND_SCHEMA').".merchandise_items b
											ON a.merchandise_item_id = b.id
											INNER JOIN ".Configure::read('FRONTEND_SCHEMA').".merchandise_items c
											ON b.parent_id = c.id
											WHERE a.agent_id = {$agent_id}
											LIMIT {$start},{$total};");
		$items = array();
		for($i=0;$i<sizeof($rs);$i++){
			$item = $rs[$i]['a'];
			$item['item_name'] = $rs[$i]['c']['name'].' - '.$rs[$i]['b']['name'];
			$item['price'] = $rs[$i]['b']['price_money'];
			$item['data'] = json_decode($rs[$i]['b']['data']);
			$items[] = $item;
		}
		return $items;
	}

	/**
	* basically it returns all the vouchers
	*/
	private function getAgentOrders($agent_id,$start,$total){
		$this->loadModel('AgentRequest');
		$this->loadModel('MerchandiseItem');
		$rs = $this->AgentRequest->query("SELECT * FROM ".Configure::read('FRONTEND_SCHEMA').".agent_vouchers a
											INNER JOIN ".Configure::read('FRONTEND_SCHEMA').".agent_orders b
											ON a.agent_order_id = b.id
											WHERE a.agent_id = {$agent_id} 
											ORDER BY a.id DESC LIMIT {$start},{$total};");
		$items = array();
		for($i=0;$i<sizeof($rs);$i++){
			$item = $rs[$i]['b'];
			$item['voucher_code'] = $rs[$i]['a']['voucher_code'];
			$item_data = unserialize($rs[$i]['b']['data']);
			
			$item_parent = $this->MerchandiseItem->findById($item_data[0]['data']['MerchandiseItem']['parent_id']);
			
			$item['data'] = json_decode($item_data[0]['data']['MerchandiseItem']['data'] );
			$item['item_name'] = $item_parent['MerchandiseItem']['name'].' '.$item_data[0]['data']['MerchandiseItem']['name'];
			$item['voucher_id'] = $rs[$i]['a']['voucher_code'];
			$items[] = $item;
		}
		return $items;
	}
	private function setAgentRequestQuota($agent_id,$item_id,$qty){
		$this->loadModel('AgentRequest');
		$this->AgentRequest->create();
		$data = array(
			'agent_id'=>$agent_id,
			'merchandise_item_id'=>$item_id,
			'request_quota'=>$qty,
			'request_date'=>date("Y-m-d H:i:s"),
			'n_status'=>0
		);
		$rs = $this->AgentRequest->save($data);
		if(isset($rs['AgentRequest'])){
			return $rs['AgentRequest'];	
		}else{
			return 0;
		}
		
	}
	private function setAgentReturnedQuota($agent_id,$item_id,$qty){
		$this->loadModel('AgentReturnedStock');
		$this->AgentReturnedStock->create();
		$data = array(
			'agent_id'=>$agent_id,
			'merchandise_item_id'=>$item_id,
			'returned_quota'=>$qty,
			'request_date'=>date("Y-m-d H:i:s"),
			'n_status'=>0
		);
		$rs = $this->AgentReturnedStock->save($data);
		if(isset($rs['AgentReturnedStock'])){
			
			//reduce agent stock
			$this->reduceAgentStock($agent_id,$item_id,$qty);
			//increase ss stock
			$this->Game->query("UPDATE ".Configure::read('FRONTEND_SCHEMA').".merchandise_items SET stock = stock + {$qty} 
								WHERE id={$item_id}");
			return $rs['AgentReturnedStock'];	
		}else{
			return 0;
		}
		
	}
	//use for validating the token
	private function agent_validate($agent_id,$token){
		
		$tokeninfo = @unserialize(decrypt_param($token));
	
		$this->loadModel('Agent');
		$agent = $this->Agent->findById($agent_id);
		if($agent['Agent']['id'] == $tokeninfo['agent_id'] &&
				$agent['Agent']['email'] == $tokeninfo['email']){
			return true;
		}
	}
	private function getAgentCatalog($agent_id){
		$rs = $this->Game->query("SELECT a.id,a.parent_id,a.name,a.description,a.price_money as price,
							b.qty,b.n_status,c.name AS parent_name,
							c.description AS parent_description,a.data,1 AS agent_id
							FROM ".Configure::read('FRONTEND_SCHEMA').".merchandise_items a
							LEFT JOIN ".Configure::read('FRONTEND_SCHEMA').".agent_items b
							ON a.id = b.merchandise_item_id AND b.agent_id = {$agent_id}
							INNER JOIN ".Configure::read('FRONTEND_SCHEMA').".merchandise_items c
							ON a.parent_id = c.id
							WHERE a.merchandise_category_id= ".Configure::read('ticket_category_id')." 
							AND a.parent_id NOT IN (0,1300,1301) AND a.n_status = 1 LIMIT 1000;");
		$items = array();
		for($i=0;$i<sizeof($rs);$i++){
			$item = $rs[$i]['a'];
			$item['agent_id'] = intval($rs[$i][0]['agent_id']);
			$item['qty'] = intval($rs[$i]['b']['qty']);
			$item['n_status'] = intval($rs[$i]['b']['n_status']);
			$item['parent'] = $rs[$i]['c'];
			$item['data'] = json_decode($item['data']);
			$items[] = $item;
		}
		return $items;
	}
	//--> end of ticketing stuffs


	//Tactics API
	public function tactics(){
		$this->loadModel('Team');
		$this->loadModel('User');
		$this->loadModel('Info');



		$api_session = $this->readAccessToken();
		$fb_id = $api_session['fb_id'];
		$user = $this->User->findByFb_id($fb_id);
		$game_team = $this->Game->getTeam($fb_id);


		//can updte formation
		$can_update_formation = true;

		$club = $this->Team->findByUser_id($user['User']['id']);
		
		$next_match = $this->Game->getNextMatch($game_team['team_id']);
		$next_match['match']['home_original_name'] = $next_match['match']['home_name'];
		$next_match['match']['away_original_name'] = $next_match['match']['away_name'];

		if($next_match['match']['home_id']==$game_team['team_id']){
			$next_match['match']['home_name'] = $club['Team']['team_name'];
		}else{
			$next_match['match']['away_name'] = $club['Team']['team_name'];
		}
		$next_match['match']['match_date_ts'] = strtotime($next_match['match']['match_date']);
		$this->getCloseTime($next_match);


		
		if(time() > $this->closeTime['ts'] && Configure::read('debug') == 0){
		    
		    $can_update_formation = false;
		    if(time() > $this->openTime){
		       
		        $can_update_formation = true;
		    }
		}else{
		    if(time() < $this->openTime){
		       
		        $can_update_formation = false;
		    }
		}

		$lineup = $this->Game->getLineup($game_team['id']);
		$instruction_points = $this->getInstructionPoints($user['Team']['id']);
		$upcoming_matchday = $this->getUpcomingMatchday();
		$current_tactics = $this->getCurrentTactics($upcoming_matchday,$game_team['id']);
		
		if($this->request->is('post')){
			if($can_update_formation){
				$save_success = $this->saveTactics($next_match['match'],$instruction_points,$upcoming_matchday,$game_team['id']);
				if($save_success){
					$this->set('response',array('status'=>1));
				}else{
					$this->set('response',array('status'=>0,'error'=>'Tactics tidak dapat disimpan'));
				}
			}else{
				$this->set('response',array('status'=>0,'error'=>'you cannot update tactics at these moment, please wait until the matches is over.'));		
			}
		}else{
			$this->set('response',array('status'=>1,'data'=>array('tactics'=>$current_tactics,
																'instruction_points'=>$instruction_points)));
		}
		
		$this->render('default');
	}
	private function saveTactics($next_match,$instruction_points,$upcoming_matchday,$game_team_id){

		$is_ok = false;

		
		
		

		$sql = "INSERT INTO ".$this->ffgamedb.".game_team_instructions
				(game_team_id,
				matchday,
				player_id,
				instruction_id,
				amount) VALUES ";
		//CakeLog::write('tactics',$_POST['instruction']);
		$total_spend = 0;
		for($i=0;$i<sizeof($this->request->data['instruction']);$i++){

			//maksimal instruction poin yang bisa dibagikan hanya 5pts
			$this->request->data['points'][$i] = intval($this->request->data['points'][$i]);
			if($this->request->data['points'][$i] > 5){
				$this->request->data['points'][$i] = 5;
			}else if($this->request->data['points'][$i] < 0){
				$this->request->data['points'][$i] = 0;
			}else{
				//do nothing
			}

			$total_spend += $this->request->data['points'][$i];
			
			if($i>0){
				$sql.=",";
			}
			$sql.= "({$game_team_id},
					{$upcoming_matchday},
					'{$this->request->data['player'][$i]}',
					{$this->request->data['instruction'][$i]},
					{$this->request->data['points'][$i]})";

		}
		$sql.="
				ON DUPLICATE KEY UPDATE
				amount = VALUES(amount);";

		//CakeLog::write('tactics',$sql);

		if($total_spend <= $instruction_points && $total_spend > 0 && intval($upcoming_matchday) > 0){
			$is_ok = true;
		}
		if($is_ok){
			$this->Game->query("DELETE FROM ".$this->ffgamedb.".game_team_instructions 
							WHERE game_team_id = {$game_team_id} AND matchday = {$upcoming_matchday}");

			$this->Game->query($sql);
		}
		return $is_ok;
	}
	//get upcoming matchday
	//these is useful for saving lineup / formations and tactics.
	private function getUpcomingMatchday(){
		$check = $this->Game->query("SELECT * FROM (SELECT matchday,MAX(is_processed) AS match_status
											FROM ".$this->ffgamedb.".game_fixtures GROUP BY matchday) a 
											WHERE match_status = 0 ORDER BY matchday ASC LIMIT 1;");
		
		try{
			$upcoming_matchday = @$check[0]['a']['matchday'];
		}catch(Exception $e){
			$upcoming_matchday = 0;
		}
		
		return $upcoming_matchday;
	}
	// instruction points is 1% of the average weekly points + minimum instruction points (10)
	// for example, if average weekly points is 1000, then the instruction points is 10
	private function getInstructionPoints($team_id){

		
		$minimum_ip = Configure::read('MINIMUM_INSTRUCTION_POINTS');

		$rs = $this->Game->query("SELECT (points+extra_points) as total_points 
											FROM ".Configure::read('FRONTEND_SCHEMA').".points a
											WHERE team_id={$team_id} 
											AND league = '{$_SESSION['league']}' 
											LIMIT 1");

		
		
		$total_points = intval(@$rs[0][0]['total_points']);

		
		$total_matches =  $this->Game->query("SELECT matchday FROM ".$this->ffgamedb.".game_fixtures a
											 WHERE period='FullTime' AND is_processed = 1 
											 ORDER BY matchday DESC LIMIT 1;");

		$total_matchday = intval(@$total_matches[0]['a']['matchday']);
		

		if($total_matchday > 0){
			$average_points = round($total_points / $total_matchday);	
		}else{
			$average_points = 0;
		}
		
		
		$instruction_points = round($average_points * 0.01) + $minimum_ip;
		
		/*if($instruction_points < $minimum_ip){
			$instruction_points = $minimum_ip;
		}*/

		return $instruction_points;
	}

	private function getCurrentTactics($upcoming_matchday,$game_team_id){
		if($upcoming_matchday==null){
			$upcoming_matchday = 0;
		}
	
		$sql = "SELECT * FROM ".$this->ffgamedb.".game_team_instructions a
				WHERE game_team_id = {$game_team_id} 
				AND matchday={$upcoming_matchday} 
				LIMIT 16";
		$rs = $this->Game->query($sql);

		$tactics = array();
		for($i=0;$i<sizeof($rs);$i++){
			$tactics[] = $rs[$i]['a'];
		}
		return $tactics;
	}
	public function tactic_list(){
		$opts = array('Tidak Ada','More Shoots','More Crosses','Focus on Through Ball','Create Chances','More Tackles','Dribbling','More Blocks');
		$tactics = array();
		for($i=0;$i<sizeof($opts);$i++){
			$tactics[] = array('id'=>$i,'name'=>$opts[$i]);
		}
		$this->set('response',array('status'=>1,'data'=>$tactics));
		$this->render('default');
	}
	/*
	* API for vieweing the request quota history
	* 
	* Response :JSON
	*/
	public function fixtures(){
		$this->layout="ajax";
		$competition_id = 8;
		$session_id = Configure::read('FM_SESSION_ID');
		if(@$_REQUEST['league']=='ita'){
			$competition_id = 21;
		}
		$rs  = $this->Game->query("SELECT a.*,b.name as home_name,c.name as away_name
										FROM ".$this->ffgamedb.".game_fixtures a
										INNER JOIN ".$this->ffgamedb.".master_team b
										ON a.home_id = b.uid
										INNER JOIN ".$this->ffgamedb.".master_team c
										ON a.away_id = c.uid
										WHERE a.competition_id={$competition_id} AND session_id={$session_id}
										ORDER BY a.matchday
										LIMIT 380");
		$fixture = array();
		for($i=0;$i<sizeof($rs);$i++){
			$p = $rs[$i]['a'];
			$p['home'] = $rs[$i]['b']['home_name'];
			$p['away'] = $rs[$i]['c']['away_name'];
			$fixture[] = $p;

		}	
		$this->set('response',array('status'=>1,'data'=>$fixture,'s'=>""));
		$this->render('default');
	}
	//--> end of tactics API

	//add cash - remove cash
	public function cash_transaction(){

		$status = 0;
		$message = '';
		if($this->request->is("post"))
		{
			Cakelog::write('error', 'api.cash_transaction data'.json_encode($this->request->data));

			$fb_id = $this->request->data['fb_id'];
			$transaction_name = $this->request->data['transaction_name'];
			$amount = intval($this->request->data['amount']);
			$details = $this->request->data['details'];

			try{
				$dataSource = $this->Game->getDataSource();
				$dataSource->begin();
				//cek is fb_id has registered
				$rs_user = $this->User->findByFb_id($fb_id);

				if(count($rs_user) == 0)
				{
					throw new Exception("User Not Found");
				}

				$sql = "INSERT INTO ".Configure::read('FRONTEND_SCHEMA').".game_transactions
						(fb_id,transaction_dt,transaction_name,amount,details)
						VALUES
						('{$fb_id}',NOW(),'{$transaction_name}',{$amount},'{$details}')
						ON DUPLICATE KEY UPDATE
						amount = VALUES(amount)";
				$this->Game->query($sql,false);
				$rs = $this->update_cash_summary($fb_id);
				if(!$rs){
					throw new Exception("coin is insufficient");
				}
				$status = 1;
				$dataSource->commit();
			}catch(Exception $e){
				$dataSource->rollback();
				$message = $e->getMessage();
				Cakelog::write('error', 'api.cash_transaction message'.$e->getMessage());
				$status = 0;
			}
			
			$this->set('response',array('status'=>$status,
										'data'=>array('fb_id'=>$fb_id,
															'transaction_name'=>$transaction_name,
															'amount'=>$amount,
															'details'=>$details),
										'message'=>$message
										));
		}
		else
		{
			Cakelog::write('error', 'api.cash_transaction message: not post request');
			$this->set('response',array('status'=>$status,'data'=> 'null','message'=>'not post request'));
		}

		$this->render('default');
	}

	public function get_coin($fb_id = "")
	{
		$message = '';
		$rs_user = $this->User->findByFb_id($fb_id);
		if(count($rs_user) == 0)
		{
			$message = 'User Not Found';
			$this->set('response',array('status'=>0,'data'=> 'null','message'=>$message));
		}
		else
		{
			$coin = intval($this->Game->getCash($fb_id));
			$this->set('response',array('status'=>1,'data'=> array('amount' => $coin),'message'=>$message));
		}
		$this->render('default');
	}

	

	//updating the team's cash wallet by summing all cash amounts
	private function update_cash_summary($fb_id){

		$rs_amount = $this->Game->query("SELECT fb_id, SUM(amount) AS cash 
					FROM ".Configure::read('FRONTEND_SCHEMA').".game_transactions
					WHERE fb_id = '{$fb_id}'
					GROUP BY fb_id");
		if($rs_amount[0][0]['cash'] < 0)
		{
			return false;
		}

		$sql = "INSERT INTO ".Configure::read('FRONTEND_SCHEMA').".game_team_cash
					(fb_id,cash)
					SELECT fb_id,SUM(amount) AS cash 
					FROM ".Configure::read('FRONTEND_SCHEMA').".game_transactions
					WHERE fb_id = '{$fb_id}'
					GROUP BY fb_id
					ON DUPLICATE KEY UPDATE
					cash = VALUES(cash);";
		$rs = $this->Game->query($sql,false);

		return true;
	}

	//temporary function
	public function remove_user($fb_id = "")
	{
		if(isset($this->request->query['email']))
		{
			$email = $this->request->query['email'];
			$rs_user = $this->User->findByEmail($email);

			$fb_id = $rs_user['User']['fb_id'];
		}
		$rs_user = $this->User->findByFb_id($fb_id);
		$rs_game_user = $this->User->query("SELECT * FROM 
			ffgame.game_users WHERE fb_id='{$fb_id}' LIMIT 1");

		$rs_game_user_ita = $this->User->query("SELECT * FROM 
			ffgame_ita.game_users WHERE fb_id='{$fb_id}' LIMIT 1");


		$this->layout="ajax";
		$this->User->query("DELETE FROM fantasy.users WHERE fb_id='{$fb_id}'");
		
		$this->User->query("DELETE FROM fantasy.teams WHERE user_id='{$rs_user['User']['id']}'");

		$this->User->query("DELETE FROM fantasy.game_transactions WHERE fb_id='{$fb_id}'");
		$this->User->query("DELETE FROM fantasy.game_team_cash WHERE fb_id='{$fb_id}'");

		$this->User->query("DELETE FROM ffgame.game_users WHERE fb_id='{$fb_id}'");
		$this->User->query("DELETE FROM ffgame_ita.game_users WHERE fb_id='{$fb_id}'");
		$this->User->query("DELETE FROM ffgame.game_teams WHERE user_id='{$rs_game_user[0]['game_users']['id']}'");
		$this->User->query("DELETE FROM ffgame_ita.game_teams WHERE user_id='{$rs_game_user_ita[0]['game_users']['id']}'");
		

		$this->set('response',array('status'=>1));
		$this->render('default');
	}
	public function check_team_name(){
	
		$team_name = Sanitize::clean($this->request->query['team_name']);

		$this->loadModel('Team');
		
		$club = $this->Team->findByTeam_name($team_name);
		if(isset($club['Team'])){
			$response = array("status"=>1);
		}else{
			$response = array("status"=>0);
		}
		$this->set('response',$response);
		$this->render('default');
	}
	public function create_team(){
		$api_session = $this->readAccessToken();
		//$fb_id = $api_session['fb_id'];
		$fb_id = $this->request->data['fb_id'];
		$team_id = $this->request->data['team_id'];

		$user = $this->User->findByFb_id($fb_id);
		$user['Team'] = $this->Game->getTeam($fb_id);
		
		$userData = $user['User'];
		Cakelog::write('api', 'api.create_team '.json_encode($user).' '.json_encode($userData));
		
		if(@$userData['register_completed']!=1 || $user['Team']==null){
			
			$data = array(
				'team_id'=>Sanitize::paranoid($team_id),
				'fb_id'=>Sanitize::paranoid($userData['fb_id'])
			);
			
			$players_selected = $this->Game->getMasterTeam($team_id);
			$players = array();
			foreach($players_selected as $p){
				$players[] = $p['uid'];
			}

			$data['players'] = json_encode($players);
			
			$result = $this->Game->create_team($data);

			CakeLog::write('create_team',json_encode($data).' - result : '.json_encode($result));
			$this->loadModel('User');			
			$user = $this->User->findByFb_id($fb_id);
			
			$step1_ok = false;
			$step2_ok = false;

			$all_ok = false;
			
			if(isset($result['error'])){
				CakeLog::write('create_team',$data['fb_id'].'-failed creating game_user and game_team');
				$results = array('status'=>0,'error'=>'failed creating the team');
			}else{
				CakeLog::write('create_team',$data['fb_id'].'-success creating game_user and game_team');
				$step1_ok = true;
				$userData['team'] = $this->Game->getTeam(Sanitize::paranoid($fb_id));
				$this->loadModel('Team');
				$this->Team->create();
				$InsertTeam = $this->Team->save(array(
					'user_id'=>$user['User']['id'],
					'team_id'=>Sanitize::paranoid($team_id),
					'team_name'=>Sanitize::clean($this->request->data['team_name']),
					'league'=>$this->league
				));

				if($InsertTeam){
					$check_team = $this->Team->findByUser_id($user['User']['id']);
					if($check_team['Team']['user_id']==$user['User']['id']){
						CakeLog::write('create_team',$data['fb_id'].'-success creating fantasy.teams '.json_encode($InsertTeam));
						$step2_ok = true;
					}else{
						CakeLog::write('create_team',$data['fb_id'].'- data not exists in fantasy.teams '.json_encode($check_team));
					}
					
				}
				
			}
			
			if($step1_ok == true && $step2_ok==true){
				$all_ok = true;
			}

			if($all_ok){
				$this->User->id = $user['User']['id'];
				$this->User->set('register_completed',1);
				$rs = $this->User->save();
				
				$results = array('status'=>1,'data'=>array('team_id'=>$data['team_id'],
															'fb_id'=>$data['fb_id'],
															'team_name'=>$this->request->data['team_name']));
			}else{
				
				CakeLog::write('create_team',$data['fb_id'].'- failed to save data to all tables ');
				$results = array('status'=>0,'error'=>'cannot save all team data');
			}
			
		}else{
			$results = array('status'=>0,'error'=>'the user already has a team');
			
		}
		$this->set('response',$results);
		$this->render('default');	
		
	}

	public function get_mobile_charge()
	{
		$charge = Configure::read('MOBILE_CHARGE');
		
		if($this->request->query('trx_type') != NULL)
		{
			$trx_type = strtoupper(trim($this->request->query('trx_type')));
			if(array_key_exists($trx_type, $charge))
			{
				$amount[$trx_type] = $charge[$trx_type];
				$results = array('status'=>1,'data'=>$amount);
			}
			else
			{
				$results = array('status'=>0,'data'=>NULL,'message'=>'Terjadi Kesalahan !');
			}
		}
		else
		{
			$results = array('status'=>1,'data'=>$charge);
		}

		$this->set('response',$results);
		$this->render('default');	
	}

	public function check_point_calculation()
	{
		try{
			$current_matchday = Sanitize::clean($this->request->query('gameweek'));
			$session_id = Configure::read('FM_SESSION_ID');

			if($this->request->query('gameweek') == NULL)
			{
				//current matchday
				$matchday = $this->Game->query("SELECT matchday FROM ".$this->ffgamedb.".game_fixtures a
													 WHERE period='FullTime' AND is_processed = 1 
													 AND session_id = '{$session_id}'
													 ORDER BY matchday DESC LIMIT 1");
				$current_matchday = $matchday[0]['a']['matchday'];
			}

			$rs_game_id = $this->Game->query("SELECT game_id FROM
											".$this->ffgamedb.".game_fixtures
											WHERE matchday = '{$current_matchday}' 
											ORDER BY id ASC LIMIT 40");

			$game_id = "";
			foreach ($rs_game_id as $key => $value)
			{
				$game_id .= "'".$value['game_fixtures']['game_id']."',";
			}
			$game_id = rtrim($game_id, ',');


			$rs_count = $this->Game->query("(SELECT 
												COUNT(id) as total 
											FROM 
												".$this->ffgamestatsdb.".job_queue 
											WHERE 
												game_id IN (".$game_id.") 
													AND n_status = 2) 
											UNION 
											(SELECT 
												COUNT(id) as total 
											FROM 
												".$this->ffgamestatsdb.".job_queue_rank 
											WHERE 
												game_id IN (".$game_id.") 
													AND n_status = 2)");

			if(count($rs_count) == 1 && $rs_count[0][0]['total'] != 0)
			{
				$results = array('status'=>1,'data'=>array('status' => 1, 'gameweek' => $current_matchday));
			}
			else
			{
				$results = array('status'=>1,'data'=>array('status' => 0, 'gameweek' => $current_matchday));
			}
		}catch(Exception $e){
			Cakelog::write('error', 'api.get_mobile_charge message:'.$e->getMessage());
			$results = array('status'=>1,'data'=>array(null),'message' => $e->getMessage());
		}
		
		$this->set('response',$results);
		$this->render('default');
	}

}
