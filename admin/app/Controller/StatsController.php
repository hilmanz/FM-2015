<?php
/**
* Game Fixtures Monitoring.
*
*/
App::uses('AppController', 'Controller');


class StatsController extends AppController {

/**
 * Controller name
 *
 * @var string
 */
	public $name = 'Stats';
	public function beforeFilter(){
		parent::beforeFilter();
		$this->loadModel('Service');
	}
	public function index(){
		
		$this->redirect('/stats/teams');
	}
	public function teams(){
		$rs = $this->Service->request('stats/report/1');
		if($rs['status']==1){
			$this->set('data',$rs['data']);	
		}
	}
	public function team(){
		$team_id = $this->request->query['team_id'];
		$rs = $this->Service->request('stats/report/5?team_id='.$team_id);

		if($rs['status']==1){
			$this->set('data',$rs['data']);	
		}

		//match report collection
		$rs = $this->Service->request('stats/report/13?team_id='.$team_id);
		if($rs['status']==1){
			$this->set('report',$rs['data']);	
		}		
		$this->set('team_id',$team_id);

		//raw statistics for team
		$rs = $this->Service->request('stats/report/55?team_id='.$team_id);

		if($rs['status']==1){
			$this->set('teamstats',$rs['data']['stats']);	
			$this->set('teamBstats',$rs['data']['teamB']);
		}		
	}
	public function matchdetails(){
		$game_id = $this->request->query['game_id'];
		$team_id = $this->request->query['team_id'];
		$rs = $this->Service->request('stats/report/8?game_id='.$game_id);
		
		
		//home overall stats
		$rs2 = $this->Service->request('stats/report/5?team_id='.$rs['data']['results']['home_team']);
		//away overall stats
		$rs3 = $this->Service->request('stats/report/5?team_id='.$rs['data']['results']['away_team']);
		
		if($rs['status']==1){
			$this->set('report',$rs['data']);
			$this->set('home_overall',$rs2['data']);
			$this->set('away_overall',$rs3['data']);
		}
		$this->set('team_id',$team_id);


		//raw statistics for team
		$rs = $this->Service->request('stats/report/58?game_id='.$game_id);

		if($rs['status']==1){
			$this->set('rawstats',$rs['data']);
		}		
	}
	public function matchstats(){
		$team_id = $this->request->query['team_id'];
		$game_id = $this->request->query['game_id'];
		
		$rs = $this->Service->request('stats/report/9?game_id='.$game_id.'&team_id='.$team_id);

		//overall stats
		$rs2 = $this->Service->request('stats/report/5?team_id='.$team_id);
		if($rs['status']==1){
			$this->set('data',$rs['data']);
			$this->set('overall_stats',$rs2['data']);
		}	
		$this->set('team_id',$team_id);
		//raw statistics for team
		$rs = $this->Service->request('stats/report/59?game_id='.$game_id.'&team_id='.$team_id);

		if($rs['status']==1){
			$this->set('teamstats',$rs['data']['stats']);	
			$this->set('teamBstats',$rs['data']['teamB']);
		}		
	}
	public function player(){
		$player_id = $this->request->query['player_id'];
		$team_id = $this->request->query['team_id'];
		$rs = $this->Service->request('stats/report/6?player_id='.$player_id.'&team_id='.$team_id);
		
		if($rs['status']==1){
			$this->set('data',$rs['data']);	
		}
		$this->set('player_id',$player_id);


		//raw statistics for player
		$rs = $this->Service->request('stats/report/56?player_id='.$player_id.'&team_id='.$team_id);

		if($rs['status']==1){
			$this->set('playerstats',$rs['data']['stats']);	
			$this->set('teamBstats',$rs['data']['teamB']);
		}	
	}
	public function playerstats(){
		$game_id = $this->request->query['game_id'];
		$player_id = $this->request->query['player_id'];
		$team_id = $this->request->query['team_id'];
		$rs = $this->Service->request('stats/report/10?game_id='.$game_id.'&player_id='.$player_id.'&team_id='.$team_id);

		if($rs['status']==1){
			$this->set('data',$rs['data']);	
		}
		$this->set('player_id',$player_id);
		$this->set('game_id',$game_id);


		//raw statistics for player
		$rs = $this->Service->request('stats/report/510?game_id='.$game_id.'&player_id='.$player_id.'&team_id='.$team_id);

		if($rs['status']==1){
			$this->set('playerstats',$rs['data']['stats']);	
			$this->set('teamBstats',$rs['data']['teamB']);
		}	
	}
	public function season_facts(){

	}
	public function latest_match_reports(){

	}
}
