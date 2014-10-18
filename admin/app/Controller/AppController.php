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
	
	public function beforeFilter(){
		$this->loadModel('AddCoinHistory');
		$this->loadModel('AddFundHistory');
		$this->loadModel('Coupon');
		$this->loadModel('CouponCode');
		$this->loadModel('Events');
		$this->loadModel('MasterMatchday');
		$this->loadModel('MasterPerk');
		$this->loadModel('Newsletter');
		$this->loadModel('Perk');
		$this->loadModel('SponsorBanner');
		$this->loadModel('Sponsorship');
		$this->loadModel('TriggeredEvents');
		
		//filter the league.
		//the default league is epl
		if($this->Session->read('league') == NULL){
			$this->Session->write('league', 'epl');
			$this->Session->write('ffgamedb', 'ffgame');
			$this->Session->write('ffgamestatsdb', 'ffgame_stats');
		}

		if(isset($this->request->query['league'])){
			if(@$this->request->query['league']=='ita'){
				$this->Session->write('league', 'ita');
				$this->Session->write('ffgamedb', 'ffgame_ita');
				$this->Session->write('ffgamestatsdb', 'ffgame_stats_ita');
			}else{
				$this->Session->write('league', 'epl');
				$this->Session->write('ffgamedb', 'ffgame');
				$this->Session->write('ffgamestatsdb', 'ffgame_stats');
			}

			$endpoints = Configure::read('API_ENDPOINTS');
			Configure::write('API_URL',$endpoints[$_SESSION['league']]);
			$this->Session->write('API_URL',null);
			
		}

		$this->layout = 'admin';
		$this->loadModel('Game');
		//set acces token
		$this->initAccessToken();
		$this->Game->setAccessToken($this->getAccessToken());

		$this->AddCoinHistory->useDbConfig = $_SESSION['ffgamedb'];
		$this->AddFundHistory->useDbConfig = $_SESSION['ffgamedb'];
		$this->Coupon->useDbConfig = $_SESSION['ffgamedb'];
		$this->CouponCode->useDbConfig = $_SESSION['ffgamedb'];
		$this->Events->useDbConfig = $_SESSION['ffgamedb'];
		$this->MasterMatchday->useDbConfig = $_SESSION['ffgamedb'];
		$this->MasterPerk->useDbConfig = $_SESSION['ffgamedb'];
		$this->Newsletter->useDbConfig = $_SESSION['ffgamedb'];
		$this->Perk->useDbConfig = $_SESSION['ffgamedb'];
		$this->SponsorBanner->useDbConfig = $_SESSION['ffgamedb'];
		$this->Sponsorship->useDbConfig = $_SESSION['ffgamedb'];
		$this->TriggeredEvents->useDbConfig = $_SESSION['ffgamedb'];

		if($this->Session->read('AdminLogin')==null && $this->request->params['controller']!='login'){
			
			$this->redirect('/login');
		}

		if($this->Session->read('AdminLogin')==null){
			$this->set('USER_IS_LOGIN',false);
		}else{
			$this->set('USER_IS_LOGIN',true);
		}
	}
	
	public function salt(){
		return Configure::read('API_SALT');
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
	
}

