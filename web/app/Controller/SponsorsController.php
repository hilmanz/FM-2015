<?php
/**
 * Market Controller

 */
App::uses('AppController', 'Controller');
App::uses('Sanitize', 'Utility');

class SponsorsController extends AppController {

/**
 * Controller name
 *
 * @var string
 */
	public $name = 'Sponsors';

/**
 * This controller does not use a model
 *
 * @var array
 */
	public $uses = array();
	public function beforeFilter(){
		parent::beforeFilter();
		$this->loadModel('Team');
		$this->loadModel('User');
		$userData = $this->getUserData();
		$user = $this->userDetail;
		$this->set('user',$user['User']);
		if(!$this->hasTeam()){
			$this->redirect('/login/expired');
		}
	}
	public function hasTeam(){
		$userData = $this->getUserData();
		if(is_array($userData['team'])){
			return true;
		}
	}
	public function index(){
		$this->redirect('/');
	}
	public function apply(){
		$this->loadModel('GameTeamSponsor');
		$c = Sanitize::clean($this->request->query['c']);
		
		if(strlen($c)>0){
			$data = unserialize(decrypt_param($c));
			$sponsor = $this->Game->query("SELECT * 
								FROM ffgame.game_sponsorships Sponsor
								WHERE id = ".intval($data['sponsor_id'])." 
								LIMIT 1");
			
			if($this->userData['team']['id'] == $data['game_team_id']){
				$rs = $this->Game->apply_sponsorship($this->nextMatch['match']['game_id'],
													$this->nextMatch['match']['matchday'],
													$data['game_team_id'],
													$data['sponsor_id']);
				if($rs['status']==1){
					//sponsorship accepted
					$this->set('sponsor_name',$sponsor[0]['Sponsor']['name']);
				}else if($rs['status']==2){
					//already has a sponsor
					$this->hasSponsorError();
				}else{
					//denied
					$this->refused();
				}
			}else{
				$this->errorCode();
			}
		}else{
			$this->set('title','Ups !');
			$this->set('message','Link yang lo tuju sepertinya salah nih !');
			$this->error();
		}
	}
	private function refused(){
		$this->set('title','Mohon Maaf');
		$this->set('message','Permohonan anda ditolak.');
		$this->error();
	}
	private function hasSponsorError(){
		$this->set('title','Hallo ? ');
		$this->set('message','Anda sudah memiliki sponsor, 1 tim hanya diperkenankan memiliki 1 sponsor.');
		$this->error();
	}
	private function errorCode(){
		$this->set('title','Hallo ? ');
		$this->set('message','Kami tidak menemukan sponsor yang anda maksud ! Silahkan cek kembali email anda !<br/> Terima Kasih !');
		$this->error();
	}
	public function error(){
		$this->render('error');
	}
	public function team_error(){
		$this->set('error_type','team');
		$this->render('error');
	}
	public function success(){
		$this->render('success');
	}
}
