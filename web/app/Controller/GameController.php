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
											  'limit 25'));
		$messages = array();
		$new_messages = 0;
		if(sizeof($notifications)>0){
			foreach($notifications as $notif){
				if((time() - strtotime($notif['Notification']['dt']))<=(24*60*60)){
					$new_messages++;
				}
				$messages[] = $notif['Notification'];
			}
		}
		header('Content-type: application/json');
		print json_encode(array('status'=>'1',
								'data'=>array('messages'=>$messages,'total_new'=>$new_messages)));
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
		$player_id = Sanitize::clean($this->request->data['player_id']);

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
