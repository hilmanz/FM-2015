<?php
/**
 * Application level Controller
 *
 * This file is application-wide controller file. You can put all
 * application-wide controller-related methods here.
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
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @package       app.Controller
 * @since         CakePHP(tm) v 0.2.9
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
App::uses('Controller', 'Controller');

/**
 * Application Controller
 *
 * Add your application-wide methods in the class below, your controllers
 * will inherit them.
 *
 * @package		app.Controller
 * @link		http://book.cakephp.org/2.0/en/controllers.html#the-app-controller
 */
class AppController extends Controller {

	protected $FB_APP_ID;
	protected $FB_SECRET;
	protected $userData;
	protected $userDetail;
	protected $userPoints;
	protected $userRank;
	protected $nextMatch;
	protected $redisClient;
	protected $gameApiAccessToken;
	protected $closeTime;
	
	
	public function beforeFilter(){
		
		if(isset($this->request->query['email'])
			&& isset($this->request->query['osign'])){
			$this->Session->write('pending_redirect',
					'/events/redeem?osign='.$this->request->query['osign']);
		
		}

		//filter the league.
		//the default league is epl
		
		if(isset($this->request->query['league'])){
			if(@$this->request->query['league']=='ita'){
				$_SESSION['league'] = 'ita';
				$_SESSION['ffgamedb'] = 'ffgame_ita';
				$_SESSION['ffgamestatsdb'] = 'ffgame_stats_ita';
			}else{
				$_SESSION['league'] = 'epl';
				$_SESSION['ffgamedb'] = 'ffgame';
				$_SESSION['ffgamestatsdb'] = 'ffgame_stats';
			}
			
			$endpoints = Configure::read('API_ENDPOINTS');
			Configure::write('API_URL',$endpoints[$_SESSION['league']]);
			$this->Session->write('API_URL',null);
			if($this->isUserLogin()){
				$this->renewUserData();
			}
		}else{
			//$_SESSION['ffgamedb'] = 'ffgame';
			//$_SESSION['ffgamestatsdb'] = 'ffgame_stats';
		}
		if(!isset($_SESSION['league'])){
			$_SESSION['league'] = 'epl';
			$_SESSION['ffgamedb'] = 'ffgame';
			$_SESSION['ffgamestatsdb'] = 'ffgame_stats';

		}

		/*
		if($this->request->is('mobile') &&
			$this->request->params['pass'][0]!='mobile'){
			$this->redirect('/pages/mobile');
			die();
		}
		*/
		$this->disableCache();
		$this->response->disableCache();
		
		$this->loadModel('Game');
		//set acces token
		$this->initAccessToken();
		$this->Game->setAccessToken($this->getAccessToken());

		if($this->params['controller']=='api'){
			$this->ApiInit();
			
		}else{

			
			$this->loadModel('Ticker');
			$last_session = intval($this->Session->read('LastSession'));
			
			if($last_session < time()){
				$this->Session->write('FinancialStatement',null);
				$this->Session->write('LastSession',time()+60*15);//set 15 menit saja.
				
			}
			

			$this->set('FB_APP_ID',Configure::read('FB.APP_ID'));
			$this->set('FB_SECRET',Configure::read('FB.SECRET'));
			$this->set('FB_AFTER_LOGIN_URL',Configure::read('FB.AFTER_LOGIN_REDIRECT_URL'));
			$this->set('debug',Configure::read('debug'));
			$this->set('DOMAIN',Configure::read('DOMAIN'));
			$this->FB_APP_ID = Configure::read('FB.APP_ID');
			$this->FB_SECRET = Configure::read('FB.SECRET');

			
		

		
			if($this->isUserLogin()){
				$this->userData = $this->getUserData();
				
				$this->initPerks();
				
				//prepare everything up.
				$this->set('USER_IS_LOGIN',true);
				$this->set('USER_DATA',$this->userData);
				$this->loadModel('User');
				$this->loadModel('Point');
				$this->loadModel('Info');
				$this->loadModel('Team');
				


				
				$this->User->bindModel(array(
						'hasOne'=>array(
							'Team'=>array(
								'conditions' => array(
									'Team.league' => $_SESSION['league'])
								)
						)
					)
				);

				$this->userDetail = $this->User->findByFb_id($this->userData['fb_id']);
				$this->set('register_is_completed',@$this->userDetail['User']['register_completed']);

				/*
				if($this->userDetail['User']['n_status']==1 && 
					$this->userDetail['User']['register_completed']==1 &&
					$this->userDetail['Team']['id']==null && $this->request->params['controller']!='profile'){
					$this->Session->setFlash("Mohon maaf, team loe tidak dapat diakses. Silahkan laporkan ke noreplysupersoccer@gmail.com");
					$this->redirect('/profile/error');
					die();
				}*/
				$point = $this->Point->find('first',
									array('conditions'=>array('Point.team_id'=>@$this->userDetail['Team']['id'],
															 'Point.league'=>$_SESSION['league'])));
				$this->userPoints = @$point['Point']['points'] + @$point['Point']['extra_points'];
				$this->userRank = @$point['Point']['rank'];

				//get recent cash
				$this->cash = floatval($this->Game->getCash(@$this->userDetail['User']['fb_id']));
				
				//temporary patch.  remove it after next match.
				if($this->userPoints==0){
					$this->userRank = 0;
				}
				//======>
				$this->set('USER_RANK',$this->userRank);
				$this->set('USER_POINTS',$this->userPoints);
				$this->set('USER_COINS',$this->cash);
				
				$this->nextMatch = $this->Game->getNextMatch(@$this->userData['team']['team_id']);

				

				@$this->nextMatch['match']['last_match_ts'] = strtotime(@$this->nextMatch['match']['last_match']);
				
				$this->nextMatch['match']['match_date_ts'] = strtotime(@$this->nextMatch['match']['match_date']);
				
				$this->set('match_date_ts',$this->nextMatch['match']['match_date_ts']);

				
				$next_match_ts = $this->nextMatch['match']['match_date_ts'];
				
				try{
					$last_matchday = @$this->nextMatch['match']['matchday'] - 1;
				
					$previous_match = @$this->nextMatch['match']['previous_setup'];
					
					$upcoming_match = @$this->nextMatch['match']['matchday_setup'];
					$future_match = @$this->nextMatch['match']['future_setup'];
				}catch(Exception $e){
					$last_matchday = 0;
					$previous_match = null;
					$upcoming_match = null;
					$future_match = null;
				}
				
				if($previous_match!=null && $upcoming_match !=null){
					//check the previous match backend proccess status
					$last_match_status = $this->Session->read('last_match_status');
					if(is_array($last_match_status)){
						
						if($last_match_status['ts'] + (60 * 1) < time()){
							$matchstatus = $this->Game->getMatchStatus($previous_match['matchday']);	

							$this->Session->write('last_match_status',array(
														'matchstatus'=>$matchstatus,
														'ts'=>time()
													));
						}else{
							
							$matchstatus = $last_match_status['matchstatus'];
						}
					}else{
						$matchstatus = $this->Game->getMatchStatus($previous_match['matchday']);		
						$this->Session->write('last_match_status',array(
													'matchstatus'=>$matchstatus,
													'ts'=>time()
												));
					}
					
					if($_SESSION['league']=='ita'){
						CakeLog::write('matchday','last_matchday:'.json_encode($last_matchday));
						CakeLog::write('matchday','previous_match:'.json_encode($previous_match));
						CakeLog::write('matchday','upcoming_match:'.json_encode($upcoming_match));
						CakeLog::write('matchday','matchstatus:'.json_encode($matchstatus));
					}
					//pr($matchstatus);
					//pr(date("Y-m-d H:i:s",time()));
					//pr( strtotime($previous_match['start_dt']));
					//pr(strtotime($previous_match['end_dt']));
					//pr(strtotime($upcoming_match['start_dt']));
					
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

						}else if(strtotime($previous_match['end_dt']) >= strtotime($upcoming_match['start_dt'])){
							//ini artinya match sebelumnya berakhir di hari yg sama atau mundur diatas jadwal pertandingan berikutnya
							//pr('foo');
							//pr($previous_match);
							if(time() < $upcoming_match['start_dt']){
								$open_time = time();
								//pr('yey');
							
							}else{
								$open_time = strtotime($previous_match['start_dt']);
								//pr('disini');
								//pr($open_time);
								
							}

							$close_time = array("datetime"=>$upcoming_match['start_dt'],
										"ts"=>strtotime($upcoming_match['start_dt']));
						}
						
					}else{

						if(time() < strtotime($upcoming_match['start_dt'])){
							//jika pertandingan belum di mulai.. maka open time itu diset berdasarkan
							//opentime minggu lalu
							$open_time = strtotime($previous_match['end_dt']);
							//pr('belum mulai');
						}else if(time() > strtotime($upcoming_match['start_dt'])
								 && time() <= strtotime($upcoming_match['end_dt'])){
							//pr('uda mulai');
							//jika tidak, menggunakan open time berikutnya
							$open_time = strtotime($upcoming_match['end_dt']);

						}else{
							//pr('entah lah');
							$open_time = strtotime($upcoming_match['end_dt']);
							$matchstatus = $this->Game->getMatchStatus($upcoming_match['matchday']);
							
							if($matchstatus['is_finished']==0){
								$open_time += (60*60*24*30);
							}
						}

						
						$close_time = array("datetime"=>$upcoming_match['start_dt'],
										"ts"=>strtotime($upcoming_match['start_dt']));

						
					}
					//pr(date('Y-m-d H:i:s',$open_time));
					//pr($close_time);
					$this->closeTime = $close_time;
					
					$this->set('close_time',$close_time);

					//formation open time
									
					$this->openTime = $open_time;
					$this->set('open_time',$open_time);
					
				}else{

					if(strtotime($upcoming_match['end_dt']) >= strtotime($future_match['start_dt'])){
						//ini artinya match sebelumnya berakhir di hari yg sama atau mundur diatas jadwal pertandingan berikutnya
						
						//pr($previous_match);
						if(time() < $future_match['start_dt']){
							$open_time = time();
							//pr($open_time);
						
						}else{
							$open_time = strtotime($upcoming_match['start_dt']);
							
							//pr($open_time);
							
						}

						$close_time = array("datetime"=>$future_match['start_dt'],
									"ts"=>strtotime($future_match['start_dt']));

					}else{
						$close_time = array("datetime"=>$upcoming_match['start_dt'],
										"ts"=>strtotime($upcoming_match['start_dt']));
						//formation open time
						$open_time = time() - (24*60*60);
					}
					
					

					$this->closeTime = $close_time;
					
					$this->set('close_time',$close_time);

					
					$this->openTime = $open_time;
					$this->set('open_time',$open_time);
					//pr(date("Y-m-d H:i:s",$open_time));
				}
				if($_SESSION['league']=='ita'){
					CakeLog::write('matchday','closeTime:'.json_encode($this->closeTime));
					CakeLog::write('matchday','openTime:'.json_encode(date("Y-m-d H:i:s",$this->openTime)));	
				}
				
				
				
				
				//notification stuffs
				
				
				$this->set('has_read_notification',$this->Session->read('has_read_notification'));

			}else{
				$this->set('USER_IS_LOGIN',false);
			}
			//news ticker
			$this->set('tickers',$this->Ticker->find('all',array('limit'=>5)));

		}
		
	}
	private function renewUserData(){
		$this->loadModel('Game');
		$user_session = $this->getUserData();
		//get team 
		$user_session['team'] = $this->Game->getTeam($user_session['fb_id']);
		$this->Session->write('Userlogin.info',$user_session);
	}
	//initializes user's perks
	//also we need to enable immediately any accessories perks like jersey, custom stadium, etc.
	protected function initPerks(){
		$this->userPerks = $this->getUserPerks();
		$this->Session->write('MyAppliedPerks',null);
		$applied_perks = $this->Session->read('MyAppliedPerks');

		//enable custom jersey
		if(!isset($applied_perks['custom_jersey']) || $applied_perks['custom_jersey'] == null){
			$applied_perks = $this->enableCustomJersey();

		}
		if(isset($applied_perks['custom_jersey']['jersey_id'])){
			$this->set('custom_jersey_css',$this->Game->getCustomJerseyStyle(
												$applied_perks['custom_jersey']['jersey_id']
											)
			);
		}
		//-->
	}
	private function enableCustomJersey(){
		$rs = $this->Game->getPerkByType($this->userPerks,'ACCESSORIES','jersey');
		$applied_perks = $this->Session->read('MyAppliedPerks');
		//make sure that we only enable 1 jersey.
		if(sizeof($rs)>0){
			$jersey = $rs[0];
			$applied_perks['custom_jersey'] = $rs[0];
		}else{
			$applied_perks['custom_jersey'] = array();
		}
		$this->Session->write('MyAppliedPerks',$applied_perks);
		return $applied_perks;
	}
	//get current active user perks
	//we only try to retrieve the perks once, and stored it to session for further use.
	protected function getUserPerks(){

		$perks = $this->Session->read('MyPerk');
		
		if(!is_array($perks)){
			$this->loadModel('DigitalPerk');
			$this->loadModel('MasterPerk');
			$this->MasterPerk->useDbConfig = $_SESSION['ffgamedb'];
			$this->DigitalPerk->useDbConfig = $_SESSION['ffgamedb'];
			$this->DigitalPerk->bindModel(
				array('belongsTo'=>
						array('MasterPerk'=>array('type'=>'INNER'))
					)
				
			);
			$perks =  $this->DigitalPerk->find('all',array(
				'conditions'=>array(
					'DigitalPerk.game_team_id' => @$this->userData['team']['id'],
					'DigitalPerk.available > 0',
					'DigitalPerk.n_status' => 1
				),
				'limit'=>100
			));
			$this->Session->write('MyPerk',$perks);
		}
		return $perks;
		
	}
	protected function logTime($activityLog=null){
		$initial_ts = intval($this->Session->read('track_time_initial_ts'));
        $last_ts = intval($this->Session->read('track_time_initial_ts'));
        $activityLog->logTime($this->userDetail['User']['id'],$this->Session,false);
	}
	private function ApiInit(){
		require_once APP . 'Vendor' . DS. 'lib/Predis/Autoloader.php';
		//tell the browser that we're outputing JSON data.
		if(Configure::read('debug') ==0 ){header('Content-type: application/json');}

		$this->loadModel('Apikey');
		$this->loadModel('User');
		$this->loadModel('Team');
		Predis\Autoloader::register();
		$this->redisClient = new Predis\Client(array(
											    'host'     => Configure::read('REDIS.Host'),
											    'port'     => Configure::read('REDIS.Port'),
											    'database' => Configure::read('REDIS.Database')
											));
		$this->layout = 'ajax';

		if($this->request->params['action']!='auth' && $this->request->params['action'] != 'login'){
			$access_token = @$_REQUEST['access_token'];

			
			if(!$this->validateAPIAccessToken($access_token) || strlen($access_token) < 2){
				print json_encode(array('status'=>401,'error'=>'invalid access token !'));
				die();
			}
		}
	}
	protected function readAccessToken(){
		$sessionContent = unserialize($this->redisClient->get($this->gameApiAccessToken));
		if(strlen($sessionContent['fb_id'])>0){
			return $sessionContent;
		}
		
	}
	protected function validateAPIAccessToken($access_token){

		//$this->redisClient->get($access_token);
		if(strlen($access_token)>0){
			if($this->redisClient->ttl($access_token)>0){
				$sessionContent = unserialize($this->redisClient->get($access_token));
				$rs = $this->Apikey->findByApi_key($sessionContent['api_key']);
				if(isset($rs['Apikey']) 
					&& strlen($rs['Apikey']['api_key']) > 0
					&& strlen($sessionContent['api_key']) > 0
					&& $rs['Apikey']['api_key'] == $sessionContent['api_key']){
					$this->gameApiAccessToken = $access_token;
					return true;
				}
			}else{
				$this->Session->write('API_CURRENT_ACCESS_TOKEN',null);
			}
		}else{
			$this->Session->write('API_CURRENT_ACCESS_TOKEN',null);
		}
		
	}
	public function isUserLogin(){
		
		if($this->Session->read('Userlogin.is_login')==true){
	  		return true;
	  	}
	}
	public function getUserData(){
		return $this->Session->read('Userlogin.info');
	}
	public function getAccessToken(){
		$access_token = $this->Session->read('access_token');
		
		return $access_token;
	}
	public function initAccessToken(){
			
		if($this->getAccessToken()!=null){
			
			$check = $this->api_call('/checkSession',array('access_token'=>$this->getAccessToken()));
			if($check['status']==0){
				$this->Session->write('access_token',null);
			}
		}
		if($this->Session->read('access_token')==null){
			$ckfile = tempnam ("/tmp", "CURLCOOKIE");
			$response = $this->api_post('/auth',array(),
									$ckfile);
			if(!isset($response['error'])){
				$challenge_code = $response['challenge_code'];
				$request_code = sha1($this->getAPIKey().'|'.$challenge_code.'|'.$this->salt());
				$response = $this->api_post('/auth',
											array('request_code'=>$request_code),
											$ckfile);

				unlink($ckfile);
				$this->Session->write('access_token',$response['access_token']);
				$access_token = $this->Session->read('access_token');
				return $access_token;
				
			}else{
				unlink($ckfile);
				if($this->request->params['controller']!='login'
					&& $this->request->params['action']!='service_unavailable' 
					&& $this->request->params['controller']!='api'){
					$this->redirect('/login/service_unavailable');
				}else{
					//die(json_encode(array('error'=>'service unavailable')));
				}
			}
			return 0;
		}else{

			return $this->Session->read('access_token');
		}
		
	}
	public function getAPIUrl(){
		$in_session = $this->Session->read('API_URL');

		if(isset($in_session)){
			return $in_session;
		}else{
			$API_URL = Configure::read('API_URL');
			if(is_array($API_URL)){
				$n = sizeof($API_URL);
				$API_URL = $API_URL[rand(0,($n-1))];
			}
			$this->Session->write('API_URL',$API_URL);
			$in_session = $this->Session->read('API_URL');
			return $API_URL;
		}
		
		
	}
	public function getAPIKey(){
		return Configure::read('API_KEY');
	}


	public function api_post($uri,$params,$cookie_file='',$timeout=15){
		App::import("Vendor","common");
		if($this->getAccessToken()!=null){
			$params['access_token'] = $this->getAccessToken();
		}
		$params['api_key'] = $this->getAPIKey();
		$response = json_decode(curlPost($this->getAPIUrl().$uri,$params,$cookie_file,$timeout),true);
		return $response;
	}
	public function api_call($uri,$params,$cookie_file='',$timeout=15){
		App::import("Vendor","common");

		if($this->getAccessToken()!=null){
			$params['access_token'] = $this->getAccessToken();
		}
		$params['api_key'] = $this->getAPIKey();
		
		$response = json_decode(curlGet($this->getAPIUrl().$uri,$params,$cookie_file,$timeout),true);

		return $response;
	}
	/*
	* like api_call but without converting the json string into php-array.
	*/
	public function api_call_raw($uri,$params,$cookie_file='',$timeout=15){
		App::import("Vendor","common");
		if($this->getAccessToken()!=null){
			$params['access_token'] = $this->getAccessToken();
		}
		$params['api_key'] = $this->getAPIKey();
		$response = curlGet($this->getAPIUrl().$uri,$params,$cookie_file,$timeout);

		return $response;
	}
	public function salt(){
		return Configure::read('API_SALT');
	}

	public function getBanners($slot,$total=10,$random=false){
		$this->loadModel('Banners');
		if($random){
			$banners = $this->Banners->find('all',array('conditions'=>array('slot'=>$slot),
													'limit'=>$total,
													'order' => 'rand()'));
		}else{
			$banners = $this->Banners->find('all',array('conditions'=>array('slot'=>$slot),
													'limit'=>$total));	
		}
		
		return $banners;
	}
	

	protected function getFinancialStatementSummary($fb_id){

		$finance = $this->Game->financial_statements($fb_id);
		
		$this->weekly_balances = @$finance['data']['weekly_balances'];
		$this->expenditures = @$finance['data']['expenditures'];
		$this->tickets_sold = @$finance['data']['tickets_sold'];
		$this->starting_budget = @intval($finance['data']['starting_budget']);

		if($finance['status']==1){

			$report = array('total_matches' => $finance['data']['total_matches'],
							'budget' => $finance['data']['budget']);
			$total_items = array();
			foreach($finance['data']['report'] as $n=>$v){
				$report[$v['item_name']] = $v['total'];
				$total_items[$v['item_name']] = $v['item_total'];
			}
			
			$report['total_earnings'] = intval(@$report['tickets_sold'])+
										intval(@$report['commercial_director_bonus'])+
										intval(@$report['marketing_manager_bonus'])+
										intval(@$report['public_relation_officer_bonus'])+
										intval(@$report['win_bonus'])+
										
										intval(@$report['player_sold']);

			foreach($report as $item_name=>$price){
				if($price > 0 && @eregi('other_',$item_name)){
					//$report['total_earnings'] += intval($price);
					
				}
				if($price > 0 && @eregi('perk-',$item_name)){
					//$report['total_earnings'] += intval($price);

				}
			}
			$this->finance_total_items_raw = $total_items;

			return $report;
		}
	}
	protected function getFinanceSummary($fb_id){
		$cached = $this->Session->read('cached_finance_summary');
		if($cached==null){


			$financial_statement = $this->getFinancialStatementSummary($fb_id);
			//last earnings
			$rs = $this->Game->getLastEarnings($this->userData['team']['id']);
			if($rs['status']==1){
				$financial_statement['last_earning'] = $rs['data']['total_earnings'];
			}else{
				$financial_statement['last_earning'] = 0;
			}

			//last expenses
			$rs = $this->Game->getLastExpenses($this->userData['team']['id']);
			if($rs['status']==1){
				$financial_statement['last_expenses'] = $rs['data']['total_expenses'];
			}else{
				$financial_statement['last_expenses'] = 0;
			}

			
			//weekly salaries

			//list of players
			$players = $this->Game->get_team_players($this->userData['fb_id']);

			$weekly_salaries = 0;
			foreach($players as $p){
				$weekly_salaries += intval(@$p['salary']);
			}
			
			//-->
			//list of staffs
			//get officials
			$officials = $this->Game->getAvailableOfficials($this->userData['team']['id']);
			$staffs = array();
			foreach($officials as $official){
				if(isset($official['hired'])){
					$staffs[] = $official;
				}
			}
			

			foreach($staffs as $p){
				$weekly_salaries += intval(@$p['salary']);
			}
			$financial_statement['weekly_salaries'] = $weekly_salaries;

			//end of weekly salaries
			



		    //budget
			$budget = $this->Game->getBudget($this->userData['team']['id']);

			$financial_statement['budget'] = $budget;
			$this->Session->write('cached_finance_summary',$financial_statement);


		}else{
			$financial_statement = $cached;
		}

		$this->set('team_bugdet',$financial_statement['budget']);
		$this->set('last_earning',$financial_statement['last_earning']);
		$this->set('last_expenses',$financial_statement['last_expenses']);
		$this->set('weekly_salaries',$financial_statement['weekly_salaries']);
	}

	public function can_update_formation(){
		$can_update_formation = true;
	
		if(time() > $this->closeTime['ts'] && Configure::read('debug') == 0){
		    $can_update_formation = false;
		    if(time() > $this->openTime){
		        $can_update_formation = true;
		    }
		}else{
		    if(time() < $this->openTime && Configure::read('debug') == 0){

		        $can_update_formation = false;
		    }
		}
		return $can_update_formation;
	}
	
}