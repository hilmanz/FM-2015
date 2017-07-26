<?php
/**
* Game Fixtures Monitoring.
*
*/
App::uses('AppController', 'Controller');


class MatchesController extends AppController {

/**
 * Controller name
 *
 * @var string
 */
	public $name = 'Matches';

	public function index(){
		$this->loadModel('Matches');
		$this->paginate = array('limit'=>10,
								'conditions'=>array('competition_id'=>'c8','season_id'=>'2013'),
								'order'=>array('Matches.matchdate'=>'asc'));
		$rs = $this->paginate('Matches');
		foreach($rs as $n=>$v){
			$home = $this->Matches->getTeam($v['Matches']['home_team']);
			$rs[$n]['home'] = $home;
			$away = $this->Matches->getTeam($v['Matches']['away_team']);
			$rs[$n]['away'] = $away;

		}

		$this->set('data',$rs);
	}
	public function player_stats($game_id,$team_id,$player_id){
		$this->loadModel('Matches');
		$this->loadModel('Player');

		$match = $this->Matches->findByGame_id($game_id);
		$match['home'] = $this->Matches->getTeam($match['Matches']['home_team']);
		$match['away'] = $this->Matches->getTeam($match['Matches']['away_team']);
		$this->set('match',$match);

		$player = $this->Player->findByUid($player_id);
		$player['Player']['team'] = $this->Matches->getTeam($player['Player']['team_id']);

		$this->set('player',$player['Player']);

		$this->set('stats',$this->getPlayerStats($game_id,$team_id,$player_id));
	}
	public function view($game_id){
		$this->loadModel('Matches');
		$this->loadModel('Player');
		$match = $this->Matches->findByGame_id($game_id);
		$match['home'] = $this->Matches->getTeam($match['Matches']['home_team']);
		$match['away'] = $this->Matches->getTeam($match['Matches']['away_team']);
		$this->set('match',$match);

		//home stats
		$home_stats = $this->team_stats($game_id,$match['home']['uid']);
		//away stats
		$away_stats = $this->team_stats($game_id,$match['away']['uid']);

		$this->set('home_stats',$home_stats);
		$this->set('away_stats',$away_stats);

		unset($home_stats);
		unset($away_stats);
		

		//goals
		$home_goals = $this->getGoals($game_id,$match['home']['uid']);
		$away_goals = $this->getGoals($game_id,$match['away']['uid']);
		
		
		$goals = array();
		foreach($home_goals as $n=>$v){
			if(!isset($goals[$n])){
				$goals[$n] = array('home'=>array(),'away'=>array());
			}
			$player = $this->Player->findByUid($v['Goal']['player_id']);
			$v['Goal']['player_name'] = $player['Player']['name'];
			$goals[$n]['home'] = $v['Goal'];
		}

		foreach($away_goals as $n=>$v){
			if(!isset($goals[$n])){
				$goals[$n] = array('home'=>array(),'away'=>array());
			}
			$player = $this->Player->findByUid($v['Goal']['player_id']);
			$v['Goal']['player_name'] = $player['Player']['name'];
			$goals[$n]['away'] = $v['Goal'];
		}
		
		unset($home_goals);
		unset($away_goals);
		$this->set('goals',$goals);


		//bookings
		$home_books = $this->getBookings($game_id,$match['home']['uid']);
		$away_books = $this->getBookings($game_id,$match['away']['uid']);
		
		
		$bookings = array();
		foreach($home_books as $n=>$v){
			if(!isset($bookings[$n])){
				$bookings[$n] = array('home'=>array(),'away'=>array());
			}
			$player = $this->Player->findByUid($v['Booking']['player_id']);
			$v['Booking']['player_name'] = $player['Player']['name'];
			$bookings[$n]['home'] = $v['Booking'];
		}

		foreach($away_books as $n=>$v){
			if(!isset($bookings[$n])){
				$bookings[$n] = array('home'=>array(),'away'=>array());
			}
			$player = $this->Player->findByUid($v['Booking']['player_id']);
			$v['Booking']['player_name'] = $player['Player']['name'];
			$bookings[$n]['away'] = $v['Booking'];
		}
		
		unset($home_books);
		unset($away_books);

		$this->set('bookings',$bookings);

		//lineup
		$home_lineup = $this->getLineup($game_id,$match['home']['uid']);
		$away_lineup = $this->getLineup($game_id,$match['away']['uid']);
		foreach($home_lineup as $n=>$v){
			$player =  $this->Player->findByUid($v['Lineup']['player_id']);
			$home_lineup[$n]['Player'] = $player['Player'];
		}
		foreach($away_lineup as $n=>$v){
			$player =  $this->Player->findByUid($v['Lineup']['player_id']);
			$away_lineup[$n]['Player'] = $player['Player'];
		}
		$this->set('home_lineup',$home_lineup);
		$this->set('away_lineup',$away_lineup);

		$this->set('game_id',$game_id);
	}
	private function team_stats($game_id,$team_id){
		$this->loadModel('Team_stat');
		$stats = $this->Team_stat->find('all',array(
				'conditions'=>array('game_id'=>$game_id,
									 'team_id'=>$team_id),
				'limit'=>1000,
				'order'=>array('Team_stat.stats_name'=>'ASC')
			));
		
		return $stats;

	}
	private function getPlayerStats($game_id,$team_id,$player_id){
		$this->loadModel('Player_stat');
		$stats = $this->Player_stat->find('all',array(
				'conditions'=>array('game_id'=>$game_id,
									 'team_id'=>$team_id,
									 'player_id'=>$player_id),
				'limit'=>1000,
				'order'=>array('Player_stat.stats_name'=>'ASC')
			));
		
		return $stats;

	}
	private function getGoals($game_id,$team_id){
		$this->loadModel('Goal');
		$stats = $this->Goal->find('all',array(
				'conditions'=>array('game_id'=>$game_id,
									 'team_id'=>$team_id),
				'limit'=>100
			));
		
		return $stats;
	}
	private function getBookings($game_id,$team_id){
		$this->loadModel('Booking');
		$stats = $this->Booking->find('all',array(
				'conditions'=>array('game_id'=>$game_id,
									 'team_id'=>$team_id),
				'limit'=>100
			));
		
		return $stats;
	}
	private function getLineup($game_id,$team_id){
		$this->loadModel('Lineup');
		$stats = $this->Lineup->find('all',array(
				'conditions'=>array('game_id'=>$game_id,
									 'team_id'=>$team_id),
				'order'=>array('status'=>'ASC'),
				'limit'=>100
			));
		
		return $stats;
	}
}
