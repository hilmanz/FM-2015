<?php
App::uses('AppModel', 'Model');
class WeeklyMatchStats extends Stats {
	public $useTable = false;
	public $useDbConfig = 'opta';
	public function getReports($matchday){
		$game_ids = $this->getGameIds(Configure::read('competition_id'),Configure::read('season_id'));
		$rs = $this->query("SELECT game_id,period,home_team,away_team,home_score,away_score,
						referee,venue_name,matchday,matchdate,Home.name AS home_name,Home.uid,
						Away.name AS away_name,Away.uid,attendance
						FROM matchinfo MatchResult
						INNER JOIN master_team Home
						ON MatchResult.home_team = Home.uid
						INNER JOIN master_team Away
						ON MatchResult.away_team = Away.uid
						WHERE MatchResult.game_id IN (".$this->arrayToSql($game_ids).")
						AND period='FullTime' AND matchday={$matchday} LIMIT 20");
		
		return $rs;
	}
	
}