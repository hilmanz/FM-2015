<?php
App::uses('AppModel', 'Model');
class BestAndWorstStats extends Stats {
	public $useTable = false;
	public $useDbConfig = 'opta';
	public function getReports(){
		$game_ids = $this->getGameIds(Configure::read('competition_id'),Configure::read('season_id'));
		$rs = array(
			'best_attacking_teams'=>$this->best_attacking_teams($game_ids),
			'best_defensive_teams'=>$this->best_defensive_teams($game_ids),
			'most_aggresive_teams'=>$this->most_aggresive_teams($game_ids),
			'most_error_prone_teams'=>$this->most_error_prone_teams($game_ids),
			'best_counter_attacking_teams'=>$this->best_counter_attacking_teams($game_ids),
			'strongest_teams_in_the_air'=>$this->strongest_teams_in_the_air($game_ids)
			);
		
		return $rs;
	}
	private function best_attacking_teams($game_ids){
		$sql ="SELECT 
				a.team_id,b.name,
				SUM(goals) AS goals,
				SUM(chances_created) AS chances,
				COUNT(a.id) AS total,
				(SUM(attack_effeciency)/COUNT(a.id)) AS attacking_effeciency
				FROM master_team_summary a
				INNER JOIN master_team b
				ON a.team_id = b.uid
				WHERE game_id IN (".$this->arrayToSql($game_ids).")
				GROUP BY team_id ORDER BY goals DESC LIMIT 20";
		$rs = $this->query($sql);
		$teams = array();
		while(sizeof($rs)>0){
			$p = array_shift($rs);
			$team = $p[0];
			$team['team_id'] = $p['a']['team_id'];
			$team['name'] = $p['b']['name'];
			$teams[] = $team;
		}
		return $teams;
	}
	private function best_defensive_teams($game_ids){
		$sql ="SELECT 
				a.team_id,b.name,
				SUM(a.goals_conceded) AS goals_conceded,
				SUM(a.chances_conceded) AS chances_conceded,
				(SUM(a.defense_effeciency) / COUNT(a.id)) AS def_effeciency
				FROM master_team_summary a
				INNER JOIN master_team b
				ON a.team_id = b.uid
				WHERE game_id IN (".$this->arrayToSql($game_ids).")
				GROUP BY team_id ORDER BY goals_conceded ASC LIMIT 20";
		$rs = $this->query($sql);
		$teams = array();
		while(sizeof($rs)>0){
			$p = array_shift($rs);
			$team = $p[0];
			$team['team_id'] = $p['a']['team_id'];
			$team['name'] = $p['b']['name'];
			$teams[] = $team;
		}
		return $teams;
	}
	private function most_aggresive_teams($game_ids){
		$sql ="SELECT 
				a.team_id,b.name,
				SUM(a.ball_recovery) AS ball_recovery,
				SUM(a.duels_won) AS duels_won,
				(SUM(a.challenge_won_ratio) / COUNT(a.id)) AS challenge_won_ratio,
				(SUM(a.fouling) / COUNT(a.id)) AS fouling_ratio
				FROM master_team_summary a
				INNER JOIN master_team b
				ON a.team_id = b.uid
				WHERE game_id IN (".$this->arrayToSql($game_ids).")
				GROUP BY team_id ORDER BY duels_won DESC LIMIT 20";
		$rs = $this->query($sql);
		$teams = array();
		while(sizeof($rs)>0){
			$p = array_shift($rs);
			$team = $p[0];
			$team['team_id'] = $p['a']['team_id'];
			$team['name'] = $p['b']['name'];
			$teams[] = $team;
		}
		return $teams;
	}
	private function most_error_prone_teams($game_ids){
		$sql ="SELECT 
				a.team_id,b.name,
				SUM(a.error_led_to_goals) AS error_lead_to_goals,
				SUM(a.error_led_to_shots) AS error_lead_to_shots,
				SUM(a.poor_control) AS poor_controls,
				SUM(a.error_led_to_goals+a.error_led_to_shots+a.poor_control) AS total_errors
				FROM master_team_summary a
				INNER JOIN master_team b
				ON a.team_id = b.uid
				WHERE game_id IN (".$this->arrayToSql($game_ids).")
				GROUP BY team_id ORDER BY total_errors DESC LIMIT 20;";
		$rs = $this->query($sql);
		$teams = array();
		while(sizeof($rs)>0){
			$p = array_shift($rs);
			$team = $p[0];
			$team['team_id'] = $p['a']['team_id'];
			$team['name'] = $p['b']['name'];
			$teams[] = $team;
		}
		return $teams;
	}
	private function best_counter_attacking_teams($game_ids){
		$sql ="SELECT 
				a.team_id,b.name,
				SUM(a.counter_attack_goals) AS counter_attack_goals,
				SUM(a.counter_attack_shots) AS counter_attack_shots,
				SUM(a.counter_attacks) AS counter_attacks,
				(SUM(a.counter_attack_effeciency)/COUNT(a.id)) AS effeciency,
				SUM(a.counter_attack_goals+a.counter_attack_shots) AS total_attempts
				FROM master_team_summary a
				INNER JOIN master_team b
				ON a.team_id = b.uid
				WHERE game_id IN (".$this->arrayToSql($game_ids).")
				GROUP BY a.team_id ORDER BY total_attempts DESC LIMIT 20;";
		$rs = $this->query($sql);
		$teams = array();
		while(sizeof($rs)>0){
			$p = array_shift($rs);
			$team = $p[0];
			$team['team_id'] = $p['a']['team_id'];
			$team['name'] = $p['b']['name'];
			$teams[] = $team;
		}
		return $teams;
	}
	private function strongest_teams_in_the_air($game_ids){
		$sql ="SELECT 
				a.team_id,b.name,
				SUM(a.aerial_duels_won) AS aerial_duels_won,
				SUM(a.headers_on_goal) AS headers_on_goal,
				SUM(a.headed_clearance) AS headed_clearance,
				SUM(a.crosses_dealt) AS crosses_dealt,
				(SUM(a.aerial_effenciency)/COUNT(a.id)) AS effeciency,
				SUM(a.aerial_duels_won+a.headers_on_goal+a.headed_clearance+a.crosses_dealt) AS total_attempts
				FROM master_team_summary a
				INNER JOIN master_team b
				ON a.team_id = b.uid
				WHERE game_id IN (".$this->arrayToSql($game_ids).")
				GROUP BY a.team_id ORDER BY total_attempts DESC LIMIT 20;";
		$rs = $this->query($sql);
		$teams = array();
		while(sizeof($rs)>0){
			$p = array_shift($rs);
			$team = $p[0];
			$team['team_id'] = $p['a']['team_id'];
			$team['name'] = $p['b']['name'];
			$teams[] = $team;
		}
		return $teams;
	}
}