<?php
/**
 * Static content controller.
 *
 * This file will render views from views/pages/
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
App::uses('AppController', 'Controller');
App::uses('Sanitize', 'Utility');


/**
 *GAME API Controller
 *
 * 
 *
 * @package       app.Controller
 * @link http://book.cakephp.org/2.0/en/controllers/pages-controller.html
 */
class GameController extends AppController {

/**
 * Controller name
 *
 * @var string
 */
	public $name = 'Game';
	public $components = array('RequestHandler','ActivityLog');
/**
 * This controller does not use a model
 *
 * @var array
 */
	public $uses = array();
	public $layout = null;
	
	/**
	* master data daftar pemain
	*/
	public function players($team_id){
		$team_id = Sanitize::paranoid($team_id);
		header('Content-type: application/json');
		print json_encode($this->Game->getMasterTeam($team_id));
		die();
	}
	/**
	*	daftar lineup saat ini.
	*/
	public function lineup(){
		$this->loadModel('Team');
		$this->loadModel('User');
		$userData = $this->getUserData();
		$lineup = $this->Game->getLineup($userData['team']['id']);
		header('Content-type: application/json');
		print json_encode($lineup);
		die();
	}
	/**
	* @todo
	* harus pastikan cuman bisa save lineup sebelum pertandingan dimulai.
	*/
	public function save_lineup(){
		$this->loadModel('Team');
		$this->loadModel('User');
		//if(date_default_timezone_get()=='Asia/Jakarta'){
		 //   $this->nextMatch['match']['match_date_ts'] += 6*60*60;
		//}
		//$time_limit = $this->nextMatch['match']['match_date_ts']-(4*60*60);
		
		//time limit for setting up formation
		$time_limit = $this->closeTime['ts'];
		
		//or is it new user ?
		if(time()<strtotime($this->userDetail['User']['register_date'])+(24*60*60)){
			$is_new_user = true;
		}else{
			$is_new_user = false;
		}
		if(Configure::read('DEBUG_CAN_UPDATE_FORMATION')){
			$is_new_user = true;
		}
		if(time() < $time_limit || Configure::read('debug') > 0 || $is_new_user){
			if(time() > $this->openTime || $is_new_user){
				$userData = $this->getUserData();
				$formation = $this->request->data['formation'];
				$players = array();
				foreach($this->request->data as $n=>$v){
					if(eregi('player-',$n)&&$v!=0){
						$players[] = array('player_id'=>str_replace('player-','',$n),'no'=>intval($v));
					}
				}
				$lineup = $this->Game->setLineup($userData['team']['id'],$formation,$players);

				header('Content-type: application/json');

				if(@$lineup['status']==1){
					$msg = "@p1_".$this->userDetail['User']['id']." telah menentukan formasinya.";

					$this->ActivityLog->writeLog($this->userDetail['User']['id'],'SAVE_FORMATION');
					$this->Info->write('set formation',$msg);
				}
			}else{
				$lineup['status'] = 0;
			}
			
		}else{
			$lineup['status'] = 0;
		}
		print json_encode($lineup);
		die();
	}
	public function get_notification(){
		$this->loadModel('Team');
		$this->loadModel('User');
		$this->loadModel('Notification');
		
		$game_team_id = $this->userData['team']['id'];

		$notifications = $this->Notification->find('all',array(
											  'conditions'=>array('Notification.game_team_id'=>array(0, $game_team_id),
											  						'league'=>$_SESSION['league']),
											  'order'=>array("Notification.id" => "DESC"),
											  'limit'=>25));

		$messages = array();

		$new_messages = 0;
		if(sizeof($notifications)>0){
			foreach($notifications as $notif){
				if((time() - strtotime($notif['Notification']['dt']))<=(24*60*60)){
					$new_messages++;
				}
				//print("sebelum : ".$notif['Notification']['content']."<br/>");
				$notif['Notification']['content'] = utf8_encode($notif['Notification']['content']);
				//print("sesudah : ".$notif['Notification']['content']."<br/>");
				$notif['Notification']['meta'] = json_decode($notif['Notification']['meta'],true);
				$messages[] = $notif['Notification'];
			}
		}

		if($new_messages > intval($this->Session->read("current_new_message"))){
			$this->Session->write('current_new_message',$new_messages);
			$this->Session->write('has_read_notification',0);
		}
		
		header('Content-type: application/json');
		print json_encode(array('status'=>'1',
								'data'=>array('messages'=>$messages,'total_new'=>$new_messages,'current_messages'=>$this->Session->read("current_new_message"))));
		die();
	}
	public function read_notification(){
		$this->Session->write('has_read_notification',1);
		header('Content-type: application/json');
		print json_encode(array('status'=>'1'));
		die();
	}
	public function hire_staff(){
		$this->loadModel('Team');
		$this->loadModel('User');
		$userData = $this->getUserData();
		$staff_id = intval($this->request->data['id']);
		
		$rs = $this->Game->hire_staff($userData['team']['id'],$staff_id);
		if($rs['status']==1){
			$msg = "@p1_".$this->userDetail['User']['id']." telah merekrut {$rs['officials']['name']} baru.";
			$this->Info->write('set formation',$msg);
		}
		header('Content-type: application/json');
		print json_encode($rs);
		die();
	}
	/**
	* sale a player
	*/
	public function sale(){
		$this->loadModel('Team');
		$this->loadModel('User');
		$userData = $this->getUserData();
		$player_id = Sanitize::clean(@$this->request->data['player_id']);

		$window = $this->Game->transfer_window();

		//check if the transfer window is opened, or the player is just registered within 24 hours
		$is_new_user = false;
		$can_transfer = false;
		
		if(time()<strtotime($this->userDetail['User']['register_date'])+(24*60*60)){
			$is_new_user = true;
		}
		if(!$is_new_user){
			if(strtotime(@$window['tw_open']) <= time() && strtotime(@$window['tw_close'])>=time()){
				$can_transfer = true;
				
			}
		}else{
			$can_transfer = true;
		}
		if(@$window['is_pro'] && $this->userDetail['User']['paid_member'] <= 0){
			$can_transfer = false;
		}
		
		if($can_transfer){
			$window_id = $window['id'];
			$rs = $this->Game->sale_player($window_id,$userData['team']['id'],$player_id);

			//reset financial statement
			$this->Session->write('FinancialStatement',null);
			
			if(@$rs['status']==1){
				$msg = "@p1_".$this->userDetail['User']['id']." telah melepas {$rs['data']['name']} seharga SS$".number_format($rs['data']['transfer_value']);
				$this->ActivityLog->writeLog($this->userDetail['User']['id'],'SOLD_PLAYER');
				$this->Info->write('sale player',$msg);
			}
		}else{
			$rs = array('status'=>3,'message'=>'Transfer window is closed','open'=>strtotime(@$window['tw_open']),
						'close'=> strtotime(@$window['tw_close']), 'now'=>time());
		}
		
		header('Content-type: application/json');
		print json_encode($rs);
		die();
	}
	/**
	* buy a player
	* @obselete
	*/
	public function buy(){
		$this->loadModel('Team');
		$this->loadModel('User');
		$userData = $this->getUserData();
		$player_id = Sanitize::clean($this->request->data['player_id']);

		$window = $this->Game->transfer_window();
		$window_id = intval(@$window['id']);
		
		//check if the transfer window is opened, or the player is just registered within 24 hours
		$is_new_user = false;
		$can_transfer = false;
		if(time()<strtotime($this->userDetail['User']['register_date'])+(24*60*60)){
			$is_new_user = true;
		}

		if(!$is_new_user){
			if(strtotime(@$window['tw_open']) <= time() && strtotime(@$window['tw_close'])>=time()){
				$can_transfer = true;
				
			}
		}else{
			$can_transfer = true;
		}
		if(@$window['is_pro'] && $this->userDetail['User']['paid_member'] <= 0){
			$can_transfer = false;
		}
		if($can_transfer){
			$rs = $this->Game->buy_player($window_id,$userData['team']['id'],$player_id);
		
			//reset financial statement
			$this->Session->write('FinancialStatement',null);
			

			if(@$rs['status']==1){
				$msg = "@p1_".$this->userDetail['User']['id']." telah membeli {$rs['data']['name']} seharga SS$".number_format($rs['data']['transfer_value']);
				$this->ActivityLog->writeLog($this->userDetail['User']['id'],'PURCHASED_PLAYER');
				$this->Info->write('buy player',$msg);
			}
		}else{
			$rs = array('status'=>3,'message'=>'Transfer window is closed');
		}

		
		header('Content-type: application/json');
		print json_encode($rs);
		die();
	}

	/*
	* api for negotiate salary window
	*/
	public function nego($nego_id){
		$this->loadModel('Team');
		$this->loadModel('User');
		$userData = $this->getUserData();
		$nego_id = intval(Sanitize::clean($nego_id));

		$window = $this->Game->transfer_window();
		$window_id = intval(@$window['id']);
		
		//check if the transfer window is opened, or the player is just registered within 24 hours
		$is_new_user = false;
		$can_transfer = false;
		if(time()<strtotime($this->userDetail['User']['register_date'])+(24*60*60)){
			$is_new_user = true;
		}

		if(!$is_new_user){
			if(strtotime(@$window['tw_open']) <= time() && strtotime(@$window['tw_close'])>=time()){
				$can_transfer = true;
				
			}
		}else{
			$can_transfer = true;
		}
		if(@$window['is_pro'] && $this->userDetail['User']['paid_member'] <= 0){
			$can_transfer = false;
		}
		if($can_transfer){
			$rs = $this->Game->nego_salary_window($window_id,$userData['team']['id'],$nego_id);
		
		}else{
			$rs = array('status'=>3,'message'=>'Transfer window is closed');
		}

		
		header('Content-type: application/json');
		print json_encode($rs);
		die();
	}
	/*
	* api for sending a salary offer
	*/
	public function offer($nego_id){
		$this->loadModel('Team');
		$this->loadModel('User');
		$userData = $this->getUserData();
		$nego_id = intval(Sanitize::clean($nego_id));

		$window = $this->Game->transfer_window();
		$window_id = intval(@$window['id']);
		
		//check if the transfer window is opened, or the player is just registered within 24 hours
		$is_new_user = false;
		$can_transfer = false;
		if(time()<strtotime($this->userDetail['User']['register_date'])+(24*60*60)){
			$is_new_user = true;
		}

		if(!$is_new_user){
			if(strtotime(@$window['tw_open']) <= time() && strtotime(@$window['tw_close'])>=time()){
				$can_transfer = true;
				
			}
		}else{
			$can_transfer = true;
		}
		if(@$window['is_pro'] && $this->userDetail['User']['paid_member'] <= 0){
			$can_transfer = false;
		}
		if($can_transfer){
			$rs = $this->Game->offer_salary($window_id,$userData['team']['id'],$nego_id,
											intval($this->request->data['player_id']),
											intval($this->request->data['offer_price']),
											intval(@$this->request->data['goal_bonus']),
											intval(@$this->request->data['cleansheet_bonus']));
			
		}else{
			$rs = array('status'=>3,'message'=>'Transfer window is closed');
		}

		
		header('Content-type: application/json');
		print json_encode($rs);
		die();
	}
	/*
	* now to buy a player, you need to negotiate transfer value to the club
	* there's several things to factor before the club agreed to sell the player
	* 1. bargain poin / pricing sweet spots
	* 2. normally the club can 
	*/
	public function buy_player(){
		$this->loadModel('Team');
		$this->loadModel('User');
		$userData = $this->getUserData();
		$player_id = Sanitize::clean($this->request->data['player_id']);
		$offer_price = Sanitize::clean(intval(@$this->request->data['offer_price']));
		$window = $this->Game->transfer_window();
		$window_id = intval(@$window['id']);

		//check if the transfer window is opened, or the player is just registered within 24 hours
		$is_new_user = false;
		$can_transfer = false;
		if(time()<strtotime($this->userDetail['User']['register_date'])+(24*60*60)){
			$is_new_user = true;
		}

		if(!$is_new_user){
			if(strtotime(@$window['tw_open']) <= time() && strtotime(@$window['tw_close'])>=time()){
				$can_transfer = true;
				
			}
		}else{
			$can_transfer = true;
		}
		if(@$window['is_pro'] && $this->userDetail['User']['paid_member'] <= 0){
			$can_transfer = false;
		}
		if($can_transfer){
			$rs = $this->Game->buy_player($window_id,$userData['team']['id'],$player_id,$offer_price);
		
			//reset financial statement
			$this->Session->write('FinancialStatement',null);
			

			if(@$rs['status']==1){
				$msg = "@p1_".$this->userDetail['User']['id']." ingin membeli {$player_id} seharga SS$".number_format($offer_price);
				$this->ActivityLog->writeLog($this->userDetail['User']['id'],'PURCHASED_PLAYER');
				$this->Info->write('buy player',$msg);
			}

		}else{
			$rs = array('status'=>3,'message'=>'Transfer window is closed');
		}

		
		header('Content-type: application/json');
		print json_encode($rs);
		die();
	}
	public function dismiss_staff(){
		$this->loadModel('Team');
		$this->loadModel('User');
		$userData = $this->getUserData();
		$staff_id = intval($this->request->data['id']);
		
		$rs = $this->Game->dismiss_staff($userData['team']['id'],$staff_id);
		header('Content-type: application/json');
		print json_encode($rs);
		die();
	}

	public function accept_offer($offer_id){
		require_once APP . 'Vendor' . DS. 'lib/Predis/Autoloader.php';
		$this->loadModel('Team');
		$this->loadModel('User');
		$userData = $this->getUserData();
		$offer_id = intval(Sanitize::clean($offer_id));

		
		
		
		$rs = $this->Game->accept_offer($userData['team']['id'],$offer_id,$this->nextMatch['match']);
		$league = $_SESSION['league'];
		$game_team_id = $userData['team']['id'];

		if(isset($rs['data']['uid'])){
			$player_id = $rs['data']['uid'];

			Predis\Autoloader::register();
			$this->redisClient = new Predis\Client(array(
												    'host'     => Configure::read('REDIS.Host'),
												    'port'     => Configure::read('REDIS.Port'),
												    
												));
			$this->redisClient->del('game_team_lineup_'.$league.'_'.$game_team_id);
			$this->redisClient->del('getPlayers_'.$league.'_'.$game_team_id);
			$this->redisClient->del('getPlayerTeamStats_'.$league.'_'.$game_team_id.'_'.$player_id);
			$this->redisClient->del('getPlayerDailyTeamStats_'.$league.'_'.$game_team_id.'_'.$player_id);
			
		}
		
		header('Content-type: application/json');
		print json_encode($rs);
		die();
	}
	public function decline_offer($offer_id){
		require_once APP . 'Vendor' . DS. 'lib/Predis/Autoloader.php';
		$this->loadModel('Team');
		$this->loadModel('User');
		$userData = $this->getUserData();
		$offer_id = intval(Sanitize::clean($offer_id));

		
		
		
		$rs = $this->Game->decline_offer($userData['team']['id'],$offer_id,$this->nextMatch['match']);
		$league = $_SESSION['league'];
		$game_team_id = $userData['team']['id'];

		if(isset($rs['data']['uid'])){
			$player_id = $rs['data']['uid'];

			Predis\Autoloader::register();
			$this->redisClient = new Predis\Client(array(
												    'host'     => Configure::read('REDIS.Host'),
												    'port'     => Configure::read('REDIS.Port'),
												    
												));
			$this->redisClient->del('game_team_lineup_'.$league.'_'.$game_team_id);
			$this->redisClient->del('getPlayers_'.$league.'_'.$game_team_id);
			$this->redisClient->del('getPlayerTeamStats_'.$league.'_'.$game_team_id.'_'.$player_id);
			$this->redisClient->del('getPlayerDailyTeamStats_'.$league.'_'.$game_team_id.'_'.$player_id);
			
		}
		
		header('Content-type: application/json');
		print json_encode($rs);
		die();
	}
	public function next_match(){
		$this->loadModel('Team');
		$this->loadModel('User');
		$userData = $this->getUserData();
		$result = $this->Game->getNextMatch($userData['team']['team_id']);
		if($result['status']==1){
			$result['match']['match_date_ts'] = strtotime($result['match']['match_date']);
			$result['match']['match_date'] = date("Y-m-d",$result['match']['match_date_ts']);
			$result['match']['match_time'] = date("H:i",$result['match']['match_date_ts']);
		}
		print json_encode($result);
		die();
	}

	public function venue($team_id){
		$result = $this->Game->getVenue($team_id);
		print json_encode($result);
		die();
	}


	public function check_team_name(){
	
		$team_name = Sanitize::clean($this->request->query['name']);

		$this->loadModel('Team');
		
		$club = $this->Team->findByTeam_name($team_name);
		if(isset($club['Team'])){
			print json_encode(array("status"=>1));
		}else{
			print json_encode(array("status"=>0));
		}
		die();
	}
}
