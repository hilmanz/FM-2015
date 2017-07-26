<?php

App::uses('AppController', 'Controller');
/*
* Manage Digital Coupon
* Pad
*/

class SimulationController extends AppController {

/**
 * Controller name
 *
 * @var string
 */
	public $name = 'Simulation';
	private $season = 2014;
	//display the available coupons, 20 items each
	public function index(){
		$this->loadModel('Admin');
		
		if(!isset($this->request->query['league'])){
			$league = 'epl';	
		}else{
			$league = $this->request->query['league'];
		}
		$this->set('league',$league);

		if($league=='epl'){
			$teams = $this->getEPLTeams();
		}else{
			$teams = $this->getItaTeams();
		}
		$this->set('teams',$teams);

		if($this->request->is('post')){
			
			$stats = $this->showStats($league);
			$this->set('stats',$stats);
		}
	}
	private function showStats($league){
		if($league=='epl'){
			$fixture = $this->getEPLFixture($this->request->data['team_id'],
										$this->request->data['matchday']);
			$stats = $this->getEPLStats($fixture['game_id'],
							   $this->request->data['team_id']);
		}else{
			$fixture = $this->getItaFixture($this->request->data['team_id'],
										$this->request->data['matchday']);
			$stats = $this->getItaStats($fixture['game_id'],
							   $this->request->data['team_id']);

		}
		
		return $stats;


		//return $teams;
	}
	private function getEPLTeams(){
		$teams = $this->Admin->query("SELECT * FROM ffgame.master_team LIMIT 20;");
		$rs = array();
		for($i=0;$i<sizeof($teams);$i++){
			$rs[] = $teams[$i]['master_team'];
		}
		return $rs;
	}
	private function getItaTeams(){
		$teams = $this->Admin->query("SELECT * FROM ffgame_ita.master_team LIMIT 20;");
		$rs = array();
		for($i=0;$i<sizeof($teams);$i++){
			$rs[] = $teams[$i]['master_team'];
		}
		return $rs;
	}

	private function getEPLFixture($team_id,$matchday){
		$sql = "SELECT game_id FROM ffgame.game_fixtures 
				WHERE (home_id = '{$team_id}' OR away_id = '{$team_id}') 
				AND matchday={$matchday} AND competition_id=8 AND session_id={$this->season};";
		$q = $this->Admin->query($sql);

		return $q[0]['game_fixtures'];
	}
	private function getItaFixture($team_id,$matchday){
		$sql = "SELECT game_id FROM ffgame_ita.game_fixtures 
				WHERE (home_id = '{$team_id}' OR away_id = '{$team_id}') 
				AND matchday={$matchday} AND competition_id=21 AND session_id={$this->season};";
		$q = $this->Admin->query($sql);

		return $q[0]['game_fixtures'];
	}
	private function getEPLStats($game_id,$team_id){

		
		$sql = "SELECT *,SUM(total) AS points 
				FROM (SELECT player_id,b.name,b.position,stats_name,stats_value,(stats_value * f) AS total
				FROM optadb.player_stats a
				INNER JOIN ffgame.master_player b
				ON a.player_id = b.uid
				INNER JOIN ffgame.game_matchstats_modifier c
				ON c.name = a.stats_name
				WHERE game_id='{$game_id}' AND a.team_id='{$team_id}') aa
				GROUP BY player_id;";

		$stats = $this->Admin->query($sql);
		$rs = array();
		
		foreach($stats as $st){
			$rs[] = array('name'=>$st['aa']['name'],
							'position'=>$st['aa']['position'],
							'points'=>$st['0']['points']);
		}
		return $rs;
	}
	private function getItaStats($game_id,$team_id){
		

		$sql = "SELECT *,SUM(total) AS points 
				FROM (SELECT player_id,b.name,b.position,stats_name,stats_value,(stats_value * f) AS total
				FROM optadb.player_stats a
				INNER JOIN ffgame_ita.master_player b
				ON a.player_id = b.uid
				INNER JOIN ffgame_ita.game_matchstats_modifier c
				ON c.name = a.stats_name
				WHERE game_id='{$game_id}' AND a.team_id='{$team_id}') aa
				GROUP BY player_id;";

		$stats = $this->Admin->query($sql);
		$rs = array();
		
		foreach($stats as $st){
			$rs[] = array('name'=>$st['aa']['name'],
							'position'=>$st['aa']['position'],
							'points'=>$st['0']['points']);
		}
		return $rs;
	}

}
