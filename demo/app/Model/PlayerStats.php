<?php
App::uses('AppModel', 'Model');

class PlayerStats extends Stats {
	public $useTable = false;
	public $useDbConfig = 'opta';
	/**
	* get best players across the league
	*/
	public function last_week_report(){
		$last_match = $this->getLastWeekMatches(Configure::read('competition_id'),
												Configure::read('season_id'));

		$rs = array('best_players'=>$this->best_players($last_match),
					'worst_players'=>$this->worst_players($last_match),
					'dangerous_passers'=>$this->lastWeekMostDangerousPassers($last_match),
					'best_crossers'=>$this->lastWeekBestCrossers($last_match),
					'sharpest_shooters'=>$this->SharpestShooters($last_match),
					'best_ball_winners'=>$this->BestBallWinners($last_match),
					'best_goalkeeper'=>$this->BestGoalKeeper($last_match),
					'best_shotstoppers'=>$this->BestShotStoppers($last_match),
					'weakest_defenders'=>$this->WeakestDefenders($last_match),
					'most_liable'=>$this->MostLiable($last_match),
					);
		
		return $rs;
	}
	public function cumulative_reports(){
		$game_ids = $this->getGameIds(Configure::read('competition_id'),
												Configure::read('season_id'));
		
		$rs = array('best_players'=>$this->best_players($game_ids),
					'worst_players'=>$this->worst_players($game_ids),
					'dangerous_passers'=>$this->lastWeekMostDangerousPassers($game_ids),
					'best_crossers'=>$this->lastWeekBestCrossers($game_ids),
					'sharpest_shooters'=>$this->SharpestShooters($game_ids),
					'best_ball_winners'=>$this->BestBallWinners($game_ids),
					'best_goalkeeper'=>$this->BestGoalKeeper($game_ids),
					'best_shotstoppers'=>$this->BestShotStoppers($game_ids),
					'weakest_defenders'=>$this->WeakestDefenders($game_ids),
					'most_liable'=>$this->MostLiable($game_ids),
					);
		
		return $rs;
	}
	public function best_players($last_match){
		

		$rs = array('overall'=>$this->lastWeekBestPlayerInfluence($last_match),
					'defender'=>$this->lastWeekBestDefender($last_match),
					'midfielder'=>$this->lastWeekBestMidfielder($last_match),
					'forward'=>$this->lastWeekBestForward($last_match)
					);

		return $rs;
		
	}
	public function worst_players($last_match){
		$last_match = $this->getLastWeekMatches(Configure::read('competition_id'),
												Configure::read('season_id'));

		$rs = array('overall'=>$this->lastWeekWorstPlayerInfluence($last_match),
					'defender'=>$this->lastWeekWorstDefender($last_match),
					'midfielder'=>$this->lastWeekWorstMidfielder($last_match),
					'forward'=>$this->lastWeekWorstForward($last_match)
					);

		return $rs;
		
	}
	private function lastWeekBestPlayerInfluence($game_ids){
		$sql = "SELECT player_id,SUM(most_influence) AS total,b.name,b.position,
				b.jersey_num,b.team_id,
				c.name AS team_name
				FROM master_player_summary a
				INNER JOIN master_player b
				ON a.player_id = b.uid
				INNER JOIN master_team c
				ON b.team_id = c.uid
				WHERE game_id IN (".$this->arrayToSql($game_ids).")
				GROUP BY player_id 
				ORDER BY total DESC LIMIT 5;";
		$rs = $this->query($sql);
		$players  = array();
		while(sizeof($rs)>0){
			$a = array_shift($rs);
			$player = $a['b'];
			$player['team_name'] = $a['c']['team_name'];
			$player['total'] = $a[0]['total'];
			$player['player_id'] = $a['a']['player_id'];
			$players[] = $player;
		}
		return $players;
		
	}
	private function lastWeekBestDefender($game_ids){
		$sql = "SELECT player_id,SUM(def_influence) AS total,b.name,b.position,b.jersey_num,b.team_id,
				c.name AS team_name
				FROM master_player_summary a
				INNER JOIN master_player b
				ON a.player_id = b.uid AND b.position='Defender'
				INNER JOIN master_team c
				ON b.team_id = c.uid
				WHERE game_id IN (".$this->arrayToSql($game_ids).")
				AND def_influence > 0
				GROUP BY player_id 
				ORDER BY total DESC LIMIT 5;";
		$rs = $this->query($sql);
		$players  = array();
		while(sizeof($rs)>0){
			$a = array_shift($rs);
			$player = $a['b'];
			$player['team_name'] = $a['c']['team_name'];
			$player['total'] = $a[0]['total'];
			$player['player_id'] = $a['a']['player_id'];
			$players[] = $player;
		}
		return $players;
		
	}
	private function lastWeekBestMidfielder($game_ids){
		$sql = "SELECT player_id,SUM(mid_influence) AS total,b.name,b.position,b.jersey_num,b.team_id,
				c.name AS team_name
				FROM master_player_summary a
				INNER JOIN master_player b
				ON a.player_id = b.uid AND b.position='Midfielder'
				INNER JOIN master_team c
				ON b.team_id = c.uid
				WHERE game_id IN (".$this->arrayToSql($game_ids).")
				AND mid_influence > 0
				GROUP BY player_id 
				ORDER BY total DESC LIMIT 5;";
		$rs = $this->query($sql);
		$players  = array();
		while(sizeof($rs)>0){
			$a = array_shift($rs);
			$player = $a['b'];
			$player['team_name'] = $a['c']['team_name'];
			$player['total'] = $a[0]['total'];
			$player['player_id'] = $a['a']['player_id'];
			$players[] = $player;
		}
		return $players;
		
	}
	private function lastWeekBestForward($game_ids){
		$sql = "SELECT player_id,SUM(fw_influence) AS total,b.name,b.position,b.jersey_num,b.team_id,
				c.name AS team_name
				FROM master_player_summary a
				INNER JOIN master_player b
				ON a.player_id = b.uid AND b.position='Forward'
				INNER JOIN master_team c
				ON b.team_id = c.uid
				WHERE game_id IN (".$this->arrayToSql($game_ids).")
				AND fw_influence > 0
				GROUP BY player_id 
				ORDER BY total DESC LIMIT 5;";
		$rs = $this->query($sql);
		$players  = array();
		while(sizeof($rs)>0){
			$a = array_shift($rs);
			$player = $a['b'];
			$player['team_name'] = $a['c']['team_name'];
			$player['total'] = $a[0]['total'];
			$player['player_id'] = $a['a']['player_id'];
			$players[] = $player;
		}
		return $players;
		
	}
	private function lastWeekWorstDefender($game_ids){
		$sql = "SELECT player_id,SUM(def_influence) AS total,b.name,b.position,b.jersey_num,b.team_id,
				c.name AS team_name
				FROM master_player_summary a
				INNER JOIN master_player b
				ON a.player_id = b.uid AND b.position='Defender'
				INNER JOIN master_team c
				ON b.team_id = c.uid
				WHERE game_id IN (".$this->arrayToSql($game_ids).")
				AND def_influence > 0
				GROUP BY player_id 
				ORDER BY total ASC LIMIT 5;";
		$rs = $this->query($sql);
		$players  = array();
		while(sizeof($rs)>0){
			$a = array_shift($rs);
			$player = $a['b'];
			$player['team_name'] = $a['c']['team_name'];
			$player['total'] = $a[0]['total'];
			$player['player_id'] = $a['a']['player_id'];
			$players[] = $player;
		}
		return $players;
		
	}
	private function lastWeekWorstMidfielder($game_ids){
		$sql = "SELECT player_id,SUM(mid_influence) AS total,b.name,b.position,b.jersey_num,b.team_id,
				c.name AS team_name
				FROM master_player_summary a
				INNER JOIN master_player b
				ON a.player_id = b.uid AND b.position='Midfielder'
				INNER JOIN master_team c
				ON b.team_id = c.uid
				WHERE game_id IN (".$this->arrayToSql($game_ids).")
				AND mid_influence > 0
				GROUP BY player_id 
				ORDER BY total ASC LIMIT 5;";
		$rs = $this->query($sql);
		$players  = array();
		while(sizeof($rs)>0){
			$a = array_shift($rs);
			$player = $a['b'];
			$player['team_name'] = $a['c']['team_name'];
			$player['total'] = $a[0]['total'];
			$player['player_id'] = $a['a']['player_id'];
			$players[] = $player;
		}
		return $players;
		
	}
	private function lastWeekWorstForward($game_ids){
		$sql = "SELECT player_id,SUM(fw_influence) AS total,b.name,b.position,b.jersey_num,b.team_id,
				c.name AS team_name
				FROM master_player_summary a
				INNER JOIN master_player b
				ON a.player_id = b.uid AND b.position='Forward'
				INNER JOIN master_team c
				ON b.team_id = c.uid
				WHERE game_id IN (".$this->arrayToSql($game_ids).")
				AND fw_influence > 0
				GROUP BY player_id 
				ORDER BY total ASC LIMIT 5;";
		$rs = $this->query($sql);
		$players  = array();
		while(sizeof($rs)>0){
			$a = array_shift($rs);
			$player = $a['b'];
			$player['team_name'] = $a['c']['team_name'];
			$player['total'] = $a[0]['total'];
			$player['player_id'] = $a['a']['player_id'];
			$players[] = $player;
		}
		return $players;
		
	}
	private function lastWeekWorstPlayerInfluence($game_ids){
		$sql = "SELECT player_id,SUM(most_influence) AS total,b.name,b.position,b.jersey_num,b.team_id,
				c.name AS team_name
				FROM master_player_summary a
				INNER JOIN master_player b
				ON a.player_id = b.uid
				INNER JOIN master_team c
				ON b.team_id = c.uid
				WHERE game_id IN (".$this->arrayToSql($game_ids).")
				AND most_influence > 0
				GROUP BY player_id 
				ORDER BY total ASC LIMIT 5;";
		$rs = $this->query($sql);
		$players  = array();
		while(sizeof($rs)>0){
			$a = array_shift($rs);
			$player = $a['b'];
			$player['team_name'] = $a['c']['team_name'];
			$player['total'] = $a[0]['total'];
			$player['player_id'] = $a['a']['player_id'];
			$players[] = $player;
		}
		return $players;
	}
	public function lastWeekMostDangerousPassers($game_ids){
		$sql = "SELECT player_id,SUM(chance_created) AS total,b.name,b.position,b.jersey_num,b.team_id,
				c.name AS team_name
				FROM master_player_summary a
				INNER JOIN master_player b
				ON a.player_id = b.uid
				INNER JOIN master_team c
				ON b.team_id = c.uid
				WHERE game_id IN (".$this->arrayToSql($game_ids).")
				AND chance_created > 0
				GROUP BY player_id 
				ORDER BY total DESC LIMIT 5;";
		$rs = $this->query($sql);
		$players  = array();
		while(sizeof($rs)>0){
			$a = array_shift($rs);
			$player = $a['b'];
			$player['team_name'] = $a['c']['team_name'];
			$player['total'] = $a[0]['total'];
			$player['player_id'] = $a['a']['player_id'];
			$players[] = $player;
		}
		return $players;
	}
	public function lastWeekBestCrossers($game_ids){
		$sql = "SELECT player_id,((SUM(best_cross_percentage)/COUNT(a.id))*100) AS total,b.name,b.position,b.jersey_num,b.team_id,
				c.name AS team_name
				FROM master_player_summary a
				INNER JOIN master_player b
				ON a.player_id = b.uid
				INNER JOIN master_team c
				ON b.team_id = c.uid
				WHERE game_id IN (".$this->arrayToSql($game_ids).")
				AND best_cross_percentage > 0
				GROUP BY player_id 
				ORDER BY total DESC LIMIT 5;";
		$rs = $this->query($sql);
		$players  = array();
		while(sizeof($rs)>0){
			$a = array_shift($rs);
			$player = $a['b'];
			$player['team_name'] = $a['c']['team_name'];
			$player['total'] = $a[0]['total'];
			$player['player_id'] = $a['a']['player_id'];
			$players[] = $player;
		}
		return $players;
	}
	public function SharpestShooters($game_ids){
		$sql = "SELECT player_id,((SUM(shoot_accuracy)/COUNT(a.id))*100) AS total,b.name,b.position,b.jersey_num,b.team_id,
				c.name AS team_name
				FROM master_player_summary a
				INNER JOIN master_player b
				ON a.player_id = b.uid
				INNER JOIN master_team c
				ON b.team_id = c.uid
				WHERE game_id IN (".$this->arrayToSql($game_ids).")
				AND shoot_accuracy > 0
				GROUP BY player_id 
				ORDER BY total DESC LIMIT 5;";
		$rs = $this->query($sql);
		$players  = array();
		while(sizeof($rs)>0){
			$a = array_shift($rs);
			$player = $a['b'];
			$player['team_name'] = $a['c']['team_name'];
			$player['total'] = $a[0]['total'];
			$player['player_id'] = $a['a']['player_id'];
			$players[] = $player;
		}
		return $players;
	}
	public function BestBallWinners($game_ids){
		$sql = "SELECT player_id,SUM(ball_wins) AS total,b.name,b.position,b.jersey_num,b.team_id,
				c.name AS team_name
				FROM master_player_summary a
				INNER JOIN master_player b
				ON a.player_id = b.uid
				INNER JOIN master_team c
				ON b.team_id = c.uid
				WHERE game_id IN (".$this->arrayToSql($game_ids).")
				AND ball_wins > 0
				GROUP BY player_id 
				ORDER BY total DESC LIMIT 5;";
		$rs = $this->query($sql);
		$players  = array();
		while(sizeof($rs)>0){
			$a = array_shift($rs);
			$player = $a['b'];
			$player['team_name'] = $a['c']['team_name'];
			$player['total'] = $a[0]['total'];
			$player['player_id'] = $a['a']['player_id'];
			$players[] = $player;
		}
		return $players;
	}
	public function BestGoalKeeper($game_ids){
		$sql = "SELECT player_id,SUM(gk_score) AS total,b.name,b.position,b.jersey_num,b.team_id,
				c.name AS team_name
				FROM master_player_summary a
				INNER JOIN master_player b
				ON a.player_id = b.uid AND b.position = 'Goalkeeper'
				INNER JOIN master_team c
				ON b.team_id = c.uid
				WHERE game_id IN (".$this->arrayToSql($game_ids).")
				AND gk_score > 0
				GROUP BY player_id 
				ORDER BY total DESC LIMIT 5;
				";
		$rs = $this->query($sql);
		$players  = array();
		while(sizeof($rs)>0){
			$a = array_shift($rs);
			$player = $a['b'];
			$player['team_name'] = $a['c']['team_name'];
			$player['total'] = $a[0]['total'];
			$player['player_id'] = $a['a']['player_id'];
			$players[] = $player;
		}
		return $players;
	}
	public function BestShotStoppers($game_ids){
		$sql = "SELECT player_id,(SUM(shot_stopping_percentage)/COUNT(a.id)) AS total,b.name,b.position,b.jersey_num,b.team_id,
				c.name AS team_name
				FROM master_player_summary a
				INNER JOIN master_player b
				ON a.player_id = b.uid
				INNER JOIN master_team c
				ON b.team_id = c.uid
				WHERE game_id IN (".$this->arrayToSql($game_ids).")
				AND shot_stopping_percentage > 0
				GROUP BY player_id 
				ORDER BY total DESC LIMIT 5;";
		$rs = $this->query($sql);
		$players  = array();
		while(sizeof($rs)>0){
			$a = array_shift($rs);
			$player = $a['b'];
			$player['team_name'] = $a['c']['team_name'];
			$player['total'] = $a[0]['total'];
			$player['player_id'] = $a['a']['player_id'];
			$players[] = $player;
		}
		return $players;
	}
	public function WeakestDefenders($game_ids){
		$sql = "SELECT player_id,SUM(def_fails) AS total,b.name,b.position,b.jersey_num,b.team_id,
				c.name AS team_name
				FROM master_player_summary a
				INNER JOIN master_player b
				ON a.player_id = b.uid AND b.position='Defender'
				INNER JOIN master_team c
				ON b.team_id = c.uid
				WHERE game_id IN (".$this->arrayToSql($game_ids).")
				AND def_fails > 0
				GROUP BY player_id 
				ORDER BY total DESC LIMIT 5;";
		$rs = $this->query($sql);
		$players  = array();
		while(sizeof($rs)>0){
			$a = array_shift($rs);
			$player = $a['b'];
			$player['team_name'] = $a['c']['team_name'];
			$player['total'] = $a[0]['total'];
			$player['player_id'] = $a['a']['player_id'];
			$players[] = $player;
		}
		return $players;
	}
	public function MostLiable($game_ids){
		$sql = "SELECT player_id,SUM(liable) AS total,b.name,b.position,b.jersey_num,b.team_id,
				c.name AS team_name
				FROM master_player_summary a
				INNER JOIN master_player b
				ON a.player_id = b.uid
				INNER JOIN master_team c
				ON b.team_id = c.uid
				WHERE game_id IN (".$this->arrayToSql($game_ids).")
				AND liable > 0
				GROUP BY player_id 
				ORDER BY total DESC LIMIT 5;";
		$rs = $this->query($sql);
		$players  = array();
		while(sizeof($rs)>0){
			$a = array_shift($rs);
			$player = $a['b'];
			$player['team_name'] = $a['c']['team_name'];
			$player['total'] = $a[0]['total'];
			$player['player_id'] = $a['a']['player_id'];
			$players[] = $player;
		}
		return $players;
	}
	public function individual_report($player_id){
		$game_ids = $this->getGameIds(Configure::read('competition_id'),
												Configure::read('season_id'));

		return $this->getPlayerStatsPerCategory($player_id,$game_ids);
	}
	public function individual_report_per_match($game_id,$player_id){
		$game_ids = $this->getGameIds(Configure::read('competition_id'),
												Configure::read('season_id'));

		return $this->getPlayerStatsPerCategory($player_id,array($game_id));
	}
	private function getPlayerStatsPerCategory($player_id,$game_ids){
		$player_stats = $this->getPlayerStats($player_id,$game_ids);
		
		//the maps
		$map = array(
			    'goals_and_assists'=>array(
			                            'goals'=>'goals',
			                            'assist'=>'goal_assist',
			                            'clear_cut_chance_created'=>'big_chance_created',
			                            'penalty_won'=>'penalty_won',
			                            'Assisted_A_Shot'=>'total_att_assist'

			                        ),

			    'shooting'=>array(
			                    'shoot_on_target'=>'ontarget_scoring_att',
			                    'on_target_shot_from_outside_the_box'=>'att_obox_target'
			                ),

			    'passing'=>array(
			                'accurate_flick_on'=>'accurate_flick_on',
			                'accurate_pass'=>'accurate_passes',
			                'accurate_chipped_pass'=>'accurate_chipped_pass',
			                'accurate_launches'=>'accurate_launches',
			                'accurate_layoffs'=>'accurate_layoffs',
			                'accurate_long_balls'=>'accurate_long_balls',
			                'accurate_through_balls'=>'accurate_through_ball',
			                'long_pass_to_opponents_half'=>'long_pass_own_to_opp_success',
			                'accurate_crossing'=>'accurate_cross',
			                'accurate_attacking_pass'=>'accurate_fwd_zone_pass',
			                'accurate_free_kick_delivery'=>'accurate_freekick_cross',
			                'Accurate_Crossing'=>'accurate_cross_nocorner',
			                'Accurate_Attacking_pass'=>'total_attacking_pass',


			            ),
			    'defending'=>array(
			                'Ball_recovery'=>'ball_recovery',
			                'Duel_Won'=>'duel_won',
			                'Aerial_Duel_Won'=>'aerial_won',
			                'Tackle_won'=>'won_tackle',
			                'Tackle_won_as_a_last_man'=>'last_man_contest',
			                'Intercepted_a_pass_inside_the_box'=>'interceptions_in_box',
			                'Effective_clearance'=>'effective_clearance',
			                'Blocked_a_cross'=>'effective_blocked_cross',
			                'Blocked_a_shot'=>'blocked_scoring_att',
			                'Blocked_a_shot_from_within_6_yards_box'=>'six_yard_block',
			                'offsides_provoked'=>'offside_provoked',
			                'Intercepted_Passes'=>'interception_won',
			                

			        ),
			    'dribbling'=>array(
			        'contests_won'=>'won_contest',
			        'Tackles_Won_Against_Last_Man'=>'last_man_tackle',
			        'Last_man_contest'=>'last_man_contest'
			    ),

			    'goalkeeping'=>array(
			                'Penalty_Save'=>'penalty_save',
			                'Diving_Save'=>'dive_save',
			                'Diving_Save_and_Caught_the_ball'=>'dive_catch',
			                'Standing_save'=>'stand_save',
			                'Standing_save_and_Caught_the_ball'=>'stand_catch',
			                'Claimed_a_cross'=>'good_claim',
			                'Claimed_a_high_cross_into_the_box'=>'good_high_claim',
			                'Punched_the_ball_away'=>'punches',
			                'Won_a_1v1_challenge'=>'good_one_on_one',
			                'Smothered_an_attack'=>'gk_smother',
			                'Successfull_Sweeps'=>'accurate_keeper_sweeper'
			        ),

			    'mistakes_and_errors'=>array(
			        'Penalty_Conceded'=>'penalty_conceded',
			        'Dispossessed'=>'dispossessed',
			        'Error_that_led_to_a_goal'=>'error_lead_to_goal',
			        'Error_that_lead_to_a_shot'=>'error_lead_to_shot',
			        'Poor_pass'=>'poss_lost_ctrl',
			        'Poor_touch'=>'unsuccessful_touch',
			        'challenge_lost'=>'challenge_lost',
			        'Cross_not_claimed'=>'cross_not_claimed',
			        'Offsides'=>'total_offside',
			        'Yellow_Card'=>'yellow_card',
			        'Red_Card'=>'red_card'
			    )
			);
		$main_stats_vals = array('goals_and_assists'=>0,
                            'shooting'=>0,
                            'defending'=>0,
                            'passing'=>0,
                            'dribbling'=>0,
                            'goalkeeping'=>0,
                            'mistakes_and_errors'=>0,
                         );

		//distribute the points accordingly
		$goals_and_assists = $this->getStats('goals_and_assists',$map,$player_stats);
		$shooting = $this->getStats('shooting',$map,$player_stats);
		$passing = $this->getStats('passing',$map,$player_stats);
		$defending = $this->getStats('defending',$map,$player_stats);
		$dribbling = $this->getStats('dribbling',$map,$player_stats);
		$goalkeeping = $this->getStats('goalkeeping',$map,$player_stats);
		$mistakes_and_errors = $this->getStats('mistakes_and_errors',$map,$player_stats);

		$total_points = 0;
		foreach($goals_and_assists as $n=>$v){
			$total_points+=intval($v);
		}
		foreach($shooting as $n=>$v){
			$total_points+=intval($v);
		}
		foreach($passing as $n=>$v){
			$total_points+=intval($v);
		}
		foreach($defending as $n=>$v){
			$total_points+=intval($v);
		}
		foreach($dribbling as $n=>$v){
			$total_points+=intval($v);
		}
		foreach($goalkeeping as $n=>$v){
			$total_points+=intval($v);
		}
		foreach($mistakes_and_errors as $n=>$v){
			$total_points+=intval($v);
		}
		return array('player'=>$this->getPlayerInfo($player_id),
					 'goals_and_assists'=>$goals_and_assists,
					 'shooting'=>$shooting,
					 'passing'=>$passing,
					 'defending'=>$defending,
					 'dribbling'=>$dribbling,
					 'goalkeeping'=>$goalkeeping,
					 'mistakes_and_errors'=>$mistakes_and_errors,
					 'total_points'=>$total_points
					 );
	}
	private function getPlayerInfo($player_id){
		$sql = "SELECT a.uid AS player_id,a.name,a.position,
				first_name,last_name,known_name,birth_date,
				weight,height,jersey_num,country,b.name AS team_name
				FROM master_player a
				INNER JOIN master_team b
				ON a.team_id = b.uid
				WHERE a.uid = '{$player_id}';";

		$rs = $this->query($sql);
		$player = $rs[0]['a'];
		$player['team_name'] = $rs[0]['b']['team_name'];
		return $player;
	}
	private function getStats($category,$map,$stats){
	    $statTypes = $map[$category];
	    $collection = array();
	    foreach($stats as $s=>$total){
	        foreach($statTypes as $n=>$v){
	            if(!isset($collection[$n])){
	                $collection[$n] = 0;
	            }
	            if($s == $v){
	                $collection[$n] = $total;
	            }
	        }
	    }
	    return $collection;
	}
	private function getPlayerStats($player_id,$game_ids){
		$sql = "SELECT stats_name,SUM(stats_value) AS total 
				FROM player_stats 
				WHERE player_id = '{$player_id}'
				AND game_id IN (".$this->arrayToSql($game_ids).")
				GROUP BY stats_name
				LIMIT 1000;";
		$rs = $this->query($sql);
		$stats = array();
		while(sizeof($rs)>0){
			$st = array_shift($rs);
			$stats[$st['player_stats']['stats_name']] = $st[0]['total'];
		}
		return $stats;
	}
}