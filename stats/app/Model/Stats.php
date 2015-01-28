<?php
App::uses('AppModel', 'Model');
class Stats extends AppModel {
	public $useTable = false;
	public $useDbConfig = 'opta';

	//report 1 - BPL Leaders Table
	public function getLeaderboard(){
		$teams = $this->getTeams();
		foreach($teams as $n=>$team){
			$matchresult = $this->getMatchResults($team['team_id'],
													Configure::read('competition_id'),
													Configure::read('season_id'));
			$stats = $this->compileMatchResults($team,$matchresult);

			$game_ids = $this->getGameIds(Configure::read('competition_id'),
												Configure::read('season_id'));

			$stats['top_scorer'] = $this->getTopScorer($team['team_id'],$game_ids);
			$stats['top_assist'] = $this->getTopAssist($team['team_id'],$game_ids);
			$teams[$n]['stats'] = $stats;
		}
		return $this->sortTeamByPoints($teams);
	}
	private function getTopScorer($team_id,$game_ids){
		$sql = "SELECT player_id,b.name,b.known_name,b.last_name,b.first_name,b.position,b.jersey_num,
				SUM(stats_value) AS total
				FROM optadb.player_stats a 
				INNER JOIN 
				optadb.master_player b
				ON a.player_id = b.uid
				WHERE a.team_id='{$team_id}' AND stats_name='goals'
				AND a.game_id IN (".$this->arrayToSql($game_ids).")
				GROUP BY player_id ORDER BY total DESC LIMIT 1;";
		$rs = $this->query($sql,false);
		$player = @$rs[0]['b'];
		$player['player_id'] = @$rs[0]['a']['player_id'];
		$player['goals'] = @$rs[0][0]['goals'];
		
		return $player;
		
	}
	private function getTopAssist($team_id,$game_ids){
		$sql = "SELECT player_id,b.name,b.known_name,b.last_name,b.first_name,b.position,b.jersey_num,
				SUM(stats_value) AS total
				FROM optadb.player_stats a 
				INNER JOIN 
				optadb.master_player b
				ON a.player_id = b.uid
				WHERE a.team_id='{$team_id}' AND stats_name='goal_assist'
				AND a.game_id IN (".$this->arrayToSql($game_ids).")
				GROUP BY player_id ORDER BY total DESC LIMIT 1;";

		$rs = $this->query($sql,false);
		$player = @$rs[0]['b'];
		$player['player_id'] = @$rs[0]['a']['player_id'];
		$player['goals'] = @$rs[0][0]['goals'];
		
		return $player;
		
	}
	private function sortTeamByPoints($teams){
		
		$changes = false;
		$n = sizeof($teams);
		for($i=1;$i<sizeof($teams);$i++){
			$swap = false;
			$p = $teams[$i-1];
			$q = $teams[$i];
			
			if($q['stats']['points_earned'] > $p['stats']['points_earned']){
				$swap = true;
			}else if($q['stats']['points_earned'] == $p['stats']['points_earned']){
				//the most goals wins
				if(($q['stats']['goals'] - $q['stats']['goals_conceded']) > ($p['stats']['goals'] - $p['stats']['goals_conceded'])){
					$swap = true;
				}else if(($q['stats']['goals'] - $q['stats']['goals_conceded']) == ($p['stats']['goals'] - $p['stats']['goals_conceded'])){
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
	
	private function compileMatchResults($team,$matchresult){
		$stats = array('games_played'=>0,
							'games_won'=>0,
							'games_lost'=>0,
							'games_drawn'=>0,
							'goals'=>0,
							'goals_conceded'=>0,
							'points_earned'=>0,
							'top_scorer'=>array(),
							'top_assist'=>array());
		foreach($matchresult as $mrs){
			if($mrs['matchinfo']['home_team']==$team['team_id']){
				if($mrs['matchinfo']['home_score']>$mrs['matchinfo']['away_score']){
					$stats['games_won']+=1;
					$stats['points_earned']+=3;
				}else if($mrs['matchinfo']['home_score']<$mrs['matchinfo']['away_score']){
					$stats['games_lost']+=1;
				}else{
					$stats['games_drawn']+=1;
					$stats['points_earned']+=1;
				}
				$stats['goals'] += $mrs['matchinfo']['home_score'];
				$stats['goals_conceded'] += $mrs['matchinfo']['away_score'];

			}else{
				if($mrs['matchinfo']['home_score']<$mrs['matchinfo']['away_score']){
					$stats['games_won']+=1;
					$stats['points_earned']+=3;
				}else if($mrs['matchinfo']['home_score']>$mrs['matchinfo']['away_score']){
					$stats['games_lost']+=1;
				}else{
					$stats['games_drawn']+=1;
					$stats['points_earned']+=1;
				}
				$stats['goals'] += $mrs['matchinfo']['away_score'];
				$stats['goals_conceded'] += $mrs['matchinfo']['home_score'];
			}
			$stats['games_played']++;
		}
		return $stats;
	}
	private function getMatchResults($team_id,$competition_id,$season_id){
		$sql = "SELECT game_id,matchday,period,result_winner,home_team,home_score,away_team,away_score 
				FROM optadb.matchinfo 
				WHERE competition_id IN ('c8','c21') AND season_id={$season_id} 
				AND (home_team='{$team_id}' OR away_team='{$team_id}') 
				LIMIT 50";
		return $this->query($sql,false);
	}
	public function getTeams(){
		$teams = $this->query("SELECT uid as team_id,name FROM ".Configure::read('optadb').".master_team LIMIT 40");
		$rs = array();
		while(sizeof($teams)>0){
			$t = array_shift($teams);
			$rs[] = $t['master_team'];
		}
		return $rs;
	}
	public function getGameIds($competition_id,$season_id){
		$sql = "SELECT game_id
				FROM optadb.matchinfo 
				WHERE competition_id IN ('c8','c21') AND season_id={$season_id}
				LIMIT 400";
		$rs = $this->query($sql,false);
		$game_ids = array();
		while(sizeof($rs)>0){
			$a = array_shift($rs);
			$game_ids[] = $a['matchinfo']['game_id'];
		}
		return $game_ids;
	}
	public function getPlayerOnlyGameIds($player_id,$competition_id,$season_id){
		$sql = "SELECT game_id
				FROM optadb.matchinfo a
				WHERE competition_id IN ('c8','c21') AND season_id={$season_id}
				AND EXISTS (SELECT 1 FROM optadb.player_stats b 
							WHERE b.game_id = a.game_id AND player_id='{$player_id}')
				AND period='FullTime' LIMIT 400";

		$rs = $this->query($sql,false);

		$game_ids = array();

		while(sizeof($rs)>0){
			$a = array_shift($rs);
			$game_ids[] = $a['a']['game_id'];
		}
		
		return $game_ids;
	}
	public function getLastWeekMatches($competition_id,$season_id){
		$sql = "SELECT MAX(matchday) AS latest_week 
				FROM matchinfo 
				WHERE 
				competition_id='{$competition_id}'
				AND 
				season_id='{$season_id}'
				AND period = 'FullTime';";
		$matches = $this->query($sql);
		$matchday = $matches[0][0]['latest_week'];
		$sql = "SELECT game_id FROM matchinfo 
				WHERE 
				competition_id='{$competition_id}'
				AND 
				season_id='{$season_id}'
				AND period = 'FullTime'
				AND matchday = {$matchday};";

		$rs = $this->query($sql,false);
		while(sizeof($rs)>0){
			$a = array_shift($rs);
			$game_ids[] = $a['matchinfo']['game_id'];
		}
		return $game_ids;
	}
	public function arrayToSql($arr){
		$str = "";
		foreach($arr as $n=>$a){
			if($n>0){
				$str.=",";
			}
			$str.="'{$a}'";
		}
		return $str;
	}
	public function fromFormula($str,$stats){
		try{
		$out = preg_replace_callback('/([a-zA-Z0-9\_]+)/',
									function($matches) use($stats){
		                                return intval(@$stats[$matches[0]]); 
		                            },
									$str);
			
			$m = new EvalMath();
			$m->suppress_errors = false;
			$output =  $m->evaluate($out);
			
		}catch(Exception $e){
			
			$output = 0;
		}
		$m = null;
		unset($m);
		
		return $output;
	}
}