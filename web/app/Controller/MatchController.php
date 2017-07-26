<?php
/**
 * Match Controller

 */
App::uses('AppController', 'Controller');
App::uses('Sanitize', 'Utility');

class MatchController extends AppController {

/**
 * Controller name
 *
 * @var string
 */
	public $name = 'Match';

/**
 * This controller does not use a model
 *
 * @var array
 */
	public $uses = array();
	protected $userData;
	protected $club;
	protected $user;
	public function beforeFilter(){
		parent::beforeFilter();
		$this->loadModel('User');
		$this->loadModel('Team');
		$this->userData = $this->getUserData();
		$this->user = $this->User->findByFb_id($this->userData['fb_id']);
		$this->club = $this->Team->findByUser_id($this->user['User']['id']);
		if(!$this->hasTeam()){
			$this->redirect('/login/expired');
		}
	}
	public function hasTeam(){
		$userData = $this->userData;
		if(is_array($userData['team'])){
			return true;
		}
	}

	public function index(){
		$rs = $this->Game->getMatches();
		
		if(sizeof($rs['matches'])>0){
			foreach($rs['matches'] as $n=>$v){
				if($v['home_id']==$this->userData['team']['team_id']){
					$rs['matches'][$n]['my_match'] = true;
					$rs['matches'][$n]['home_name'] = $this->club['Team']['team_name'];
				}else if($v['away_id']==$this->userData['team']['team_id']){
					$rs['matches'][$n]['my_match'] = true;
					$rs['matches'][$n]['away_name'] = $this->club['Team']['team_name'];
				}else{
					$rs['matches'][$n]['my_match'] = false;
				}
			}
		}
		$this->set('current_week',intval($this->nextMatch['match']['matchday']));
		$this->set('matches',$rs['matches']);
	}
	public function details($game_id){
		$game_id = Sanitize::paranoid($game_id);
		$rs = $this->Game->getMatchDetails($game_id);
		$modifier = $this->Team->query("SELECT name 
										FROM ".$_SESSION['ffgamedb'].".game_matchstats_modifier as stats;");
		$stats = array();
		foreach($modifier as $mod){
			$stats[] = $mod['stats']['name'];
		}
		$modifier = null;
		unset($modifier);
		$this->set('mods',$stats);
		$this->set('o',$rs);
	}
	public function view($game_id){
		$game_id = Sanitize::paranoid($game_id);
		
		$rs = $this->Game->getMatchDetails($game_id);
		$modifier = $this->Team->query("SELECT name 
										FROM ".$_SESSION['ffgamedb'].".game_matchstats_modifier as stats;");
		$stats = array();
		foreach($modifier as $mod){
			$stats[] = $mod['stats']['name'];
		}
		$modifier = null;
		unset($modifier);
		$this->set('mods',$stats);
		$this->set('o',$rs);

		$this->set('game_id',$game_id);
		$this->set('ENABLE_CHARTS',true);
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

	public function livestats($game_id){
		$game_id = Sanitize::escape($game_id);
		$this->layout="ajax";
		$response = $this->Game->livestats($game_id);
		
		$this->set('response',$response);
		$this->render('plaintext');
	}
	public function livegoals($game_id){
		$game_id = Sanitize::escape($game_id);
		$this->layout="ajax";
		$response = $this->Game->livegoals($game_id);
		
		$this->set('response',$response);
		$this->render('plaintext');
	}
}
