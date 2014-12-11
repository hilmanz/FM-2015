<?php
/**
 * Market Controller

 */
App::uses('AppController', 'Controller');
App::uses('Sanitize', 'Utility');

class MarketController extends AppController {

/**
 * Controller name
 *
 * @var string
 */
	public $name = 'Market';

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

		
		$this->set('my_team',$user['Team']);

		if(!$this->hasTeam()){
			$this->redirect('/login/expired');
		}

		$this->getFinanceSummary($this->userData['fb_id']);
		//banners
		$long_banner = $this->getBanners('TRANSFERWINDOW_TOP',2,true);
		$this->set('long_banner',$long_banner);
		$long_banner2 = $this->getBanners('TRANSFERWINDOW_BOTTOM',2,true);
		$this->set('long_banner2',$long_banner2);
	}
	public function hasTeam(){
		$userData = $this->getUserData();
		if(is_array($userData['team'])){
			return true;
		}
	}

	public function index(){
		$teams = $this->Game->getMatchResultStats();

		foreach($teams['data'] as $n=>$v){
			$teams['data'][$n]['stats']['points_earned'] = ($v['stats']['wins'] * 3) + 
															($v['stats']['draws']);
		}
		
		$this->set('teams',$this->sortTeamByPoints($teams['data']));
		//budget
		$userData = $this->getUserData();
		$budget = $this->Game->getBudget($userData['team']['id']);
		$this->set('team_bugdet',$budget);
	}
	private function sortTeamByPoints($teams){
		
		$changes = false;
		$n = sizeof($teams);
		for($i=1;$i<sizeof($teams);$i++){
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
	public function team($team_id){
		$userData = $this->getUserData();
		$club = $this->Game->getClub($team_id);
		$this->set('club',$club);

		$players = $this->Game->getMasterTeam($team_id);

		//list of players
		$my_players = $this->Game->get_team_players($userData['fb_id']);
		
		$player_list = array();
		while(sizeof($players)>0){
			$p = array_shift($players);
			if(!$this->isMyPlayer($p['uid'],$my_players)){
				$player_list[] = $p;
			}
		}
		
		$this->set('players',$player_list);
		$budget = $this->Game->getBudget($userData['team']['id']);
		$this->set('team_bugdet',$budget);
	}
	private function isMyPlayer($player_id,$my_players){
		foreach($my_players as $m){
				if($m['uid']==$player_id){
					return true;
				}
			}
	}
	public function player($player_id){

		$userData = $this->userData;
		$userDetail = $this->userDetail;
		//user data
		$this->set('user',$userDetail['User']);

		//budget
		$budget = $this->Game->getBudget($userData['team']['id']);

		$this->set('team_bugdet',$budget);

		//club
		$club = $userDetail;

		$this->set('club',$club['Team']);

		//player detail : 
		$rs = $this->Game->get_player_info($player_id);
		

		//can transfer ?
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
		
		//still can't transfer ?
		//we check if the user has transfer window perk in active.
		if(!$can_transfer){
			$can_transfer = $this->isTransferWindowPerkActive($rs);
		}

		$this->set('can_purchase',$can_transfer);
		//-->


		if($rs['status']==1){
			if(isset($rs['data']['daily_stats'])&&sizeof($rs['data']['daily_stats'])>0){
				foreach($rs['data']['daily_stats'] as $n=>$v){
					$fixture = $this->Team->query("SELECT matchday,match_date,
										UNIX_TIMESTAMP(match_date) as ts
										FROM ".$_SESSION['ffgamedb'].".game_fixtures 
										WHERE game_id='{$n}' 
										LIMIT 1");
					
					$rs['data']['daily_stats'][$n]['fixture'] = $fixture[0]['game_fixtures'];
					$rs['data']['daily_stats'][$n]['fixture']['ts'] = $fixture[0][0]['ts'];
				}
			}
			
			$this->set('data',$rs['data']);
		}
		
		//stats modifier
		$modifier = $this->Game->query("SELECT * FROM ".$_SESSION['ffgamedb'].".game_matchstats_modifier as Modifier");
		$this->set('modifiers',$modifier);

		//enable OPTA Widget
		$this->set('ENABLE_OPTA',true);
		$this->set('OPTA_CUSTOMER_ID',Configure::read('OPTA_CUSTOMER_ID'));
		//-->

	}
	/*
	* check if there's any transfer window perk active.
	* @returns boolean (only if the perk lifetime is still valid)
	*/
	private function isTransferWindowPerkActive(){
		
		$game_team_id = $this->userData['team']['id'];

		$activeTransferWindowPerk = $this->Session->read('active_transfer_window_perk');
		if($activeTransferWindowPerk==null){
			$activeTransferWindowPerk = $this->getActiveTransferWindowPerk($game_team_id);
		}
		
		if(isset($activeTransferWindowPerk['MasterPerk']) &&
				isset($activeTransferWindowPerk['DigitalPerk'])){
			$redeem_dt = strtotime($activeTransferWindowPerk['DigitalPerk']['redeem_dt']);
			$perk_expiry_ts = $redeem_dt + (intval($activeTransferWindowPerk['MasterPerk']['data']['hour']) * 60 * 60);
			if(time() < $perk_expiry_ts){
				return true;
			}else{
				//reset the session
				$this->Session->write('active_transfer_window_perk',null);
			}
		}
	}
	private function getActiveTransferWindowPerk(){
		$this->loadModel('DigitalPerk');
		$this->loadModel('MasterPerk');
		$this->MasterPerk->useDbConfig = $_SESSION['ffgamedb'];
		$this->DigitalPerk->useDbConfig = $_SESSION['ffgamedb'];
		$this->DigitalPerk->bindModel(array(
							'belongsTo'=>array('MasterPerk'=>array('type'=>'INNER'))));
		$perks =  $this->DigitalPerk->find('all',array(
				'conditions'=>array(
					'DigitalPerk.game_team_id' => $this->userData['team']['id'],
					'DigitalPerk.available > 0',
					'DigitalPerk.n_status' => 1
				),
				'limit'=>100
			));
		
		for($i=0;$i<sizeof($perks);$i++){
			$p = $perks[$i];
			if($p['MasterPerk']['perk_name']=='ACCESSORIES'){
				$data = unserialize($p['MasterPerk']['data']);
				if($data['type']=='transfer_window'){
					$p['MasterPerk']['data'] = $data;
					$this->Session->write('active_transfer_window_perk',$p);
					return $p;
				}
			}
		}
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
