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
	public function beforeFilter(){
		parent::beforeFilter();
		$userData = $this->getUserData();
		
	}
	public function hasTeam(){
		$userData = $this->getUserData();
		if(is_array($userData['team'])){
			return true;
		}
	}

	public function index(){
		$rs = $this->Game->getMatches();
		
		$this->set('matches',$rs['matches']);
	}
	public function details($game_id){
		$game_id = Sanitize::paranoid($game_id);
		$rs = $this->Game->getMatchDetails($game_id);
		$this->set('o',$rs);
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
