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
		$rs = $this->query($sql,false);
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
		$rs = $this->query($sql,false);
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
		$rs = $this->query($sql,false);
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
		$rs = $this->query($sql,false);
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
		$rs = $this->query($sql,false);
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
		$rs = $this->query($sql,false);
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
		$rs = $this->query($sql,false);
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
		$rs = $this->query($sql,false);
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
		$sql = "SELECT player_id,SUM(dangerous_pass) AS total,b.name,b.position,b.jersey_num,b.team_id,
				c.name AS team_name
				FROM master_player_summary a
				INNER JOIN master_player b
				ON a.player_id = b.uid
				INNER JOIN master_team c
				ON b.team_id = c.uid
				WHERE game_id IN (".$this->arrayToSql($game_ids).")
				AND dangerous_pass > 0
				GROUP BY player_id 
				ORDER BY total DESC LIMIT 5;";
		
		$rs = $this->query($sql,false);
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
		$sql = "SELECT player_id,SUM(accurate_cross) AS total,SUM(total_cross) as overall,
				b.name,b.position,b.jersey_num,b.team_id,
				c.name AS team_name
				FROM master_player_summary a
				INNER JOIN master_player b
				ON a.player_id = b.uid
				INNER JOIN master_team c
				ON b.team_id = c.uid
				WHERE game_id IN (".$this->arrayToSql($game_ids).")
				AND accurate_cross > 0
				GROUP BY player_id 
				ORDER BY total DESC LIMIT 5;";

		$rs = $this->query($sql,false);
		
		$players  = array();
		while(sizeof($rs)>0){
			$a = array_shift($rs);
			$player = $a['b'];
			$player['team_name'] = $a['c']['team_name'];
			$player['total'] = $a[0]['total'];
			$player['overall'] = $a[0]['overall'];
			$player['percent'] = round(($a[0]['total'] / $a[0]['overall'])*100,1);
			$player['player_id'] = $a['a']['player_id'];
			$players[] = $player;
		}
		$players = $this->SortEqualValuesDESC($players,'total','percent');
		return $players;
	}
	public function SharpestShooters($game_ids){
		$sql = "SELECT player_id,SUM(ontarget_scoring_att) AS total,
				SUM(total_scoring_att) AS overall,
				b.name,b.position,b.jersey_num,b.team_id,
				c.name AS team_name
				FROM master_player_summary a
				INNER JOIN master_player b
				ON a.player_id = b.uid
				INNER JOIN master_team c
				ON b.team_id = c.uid
				WHERE game_id IN (".$this->arrayToSql($game_ids).")
				AND ontarget_scoring_att > 0
				GROUP BY player_id 
				ORDER BY total DESC LIMIT 5;";
		$rs = $this->query($sql,false);
		$players  = array();
		while(sizeof($rs)>0){
			$a = array_shift($rs);
			$player = $a['b'];
			$player['team_name'] = $a['c']['team_name'];
			$player['total'] = $a[0]['total'];
			$player['overall'] = $a[0]['overall'];
			$player['percent'] = round(($a[0]['total'] / $a[0]['overall'])*100,1);
			$player['player_id'] = $a['a']['player_id'];
			$players[] = $player;
		}
		$players = $this->SortEqualValuesDESC($players,'total','percent');

		return $players;
	}
	private function SortEqualValuesDESC($data,$check_field,$sort_field){
		$changes = false;
		for($i=1;$i<sizeof($data);$i++){
			$swap = false;
			$a = $data[$i-1];
			$b = $data[$i];
			$p = $a[$check_field];
			$q = $b[$check_field];
			$p1 = $a[$sort_field];
			$q1 = $b[$sort_field];

			if($p==$q){
				if($p1<$q1){
					$swap = true;
				}
			}
			if($swap){
				$data[$i-1] = $b;
				$data[$i] = $a;
				$changes = true;
			}
		}

		if($changes){
			return $this->SortEqualValuesDESC($data,$check_field,$sort_field);
		}else{
			return $data;
		}
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
		$rs = $this->query($sql,false);
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
		$rs = $this->query($sql,false);
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
		$rs = $this->query($sql,false);
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
		$rs = $this->query($sql,false);
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
	public function individual_report($player_id,$team_id){
		$game_ids = $this->getPlayerOnlyGameIds($player_id,Configure::read('competition_id'),
												Configure::read('season_id'));

		return $this->getPlayerStatsPerCategory($player_id,$game_ids,$team_id);
	}
	public function individual_report_raw($player_id){
		$game_ids = $this->getPlayerOnlyGameIds($player_id,Configure::read('competition_id'),
												Configure::read('season_id'));
		$player_stats = $this->getPlayerStats($player_id,$game_ids);

		$player_info = $this->getPlayerInfo($player_id);
		$teamB_stats = $this->teamBPlayerStats($player_info['team_id'],$game_ids);

		$stats = array('stats'=>$player_stats,
						'teamB'=>$teamB_stats);

		return $stats;
	}
	public function individual_report_per_match($game_id,$player_id,$team_id){
		
		
		return $this->getPlayerStatsPerCategory($player_id,array($game_id),$team_id);
	}
	public function individual_report_per_match_raw($game_id,$player_id){
		$game_ids = array($game_id);
		$player_stats = $this->getPlayerStats($player_id,$game_ids);

		$player_info = $this->getPlayerInfo($player_id);
		$teamB_stats = $this->teamBPlayerStats($player_info['team_id'],$game_ids);

		$stats = array('stats'=>$player_stats,
						'teamB'=>$teamB_stats);

		return $stats;
	}
	private function getPlayerStatsPerCategory($player_id,$game_ids,$team_id){
		$player_stats = $this->getPlayerStats($player_id,$game_ids);
		$player_info = $this->getPlayerInfo($player_id,$team_id);
		$teamB_stats = $this->teamBPlayerStats($player_info['team_id'],$game_ids);
		//the maps
		$map = array(
			    'goals_and_assists'=>array(
			                            'goals'=>'goals',
			                            'goal_assists'=>'goal_assist',
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
			                'accurate_pass'=>'accurate_pass',
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
		//$shooting = $this->getStats('shooting',$map,$player_stats);
		//$passing = $this->getStats('passing',$map,$player_stats);
		//$defending = $this->getStats('defending',$map,$player_stats);
		$dribbling = $this->getStats('dribbling',$map,$player_stats);
		$goalkeeping = $this->getStats('goalkeeping',$map,$player_stats);
		$mistakes_and_errors = $this->getStats('mistakes_and_errors',$map,$player_stats);

		$shooting = array(
			 'shoot_on_target'=>array('total'=>intval(@$player_stats['ontarget_scoring_att']),
			 						   'overall'=>intval(@$player_stats['total_scoring_att'])),
			 'on_target_shot_from_outside_the_box'=>array(
			 							'total'=>intval(@$player_stats['att_obox_target']),
			 							'overall'=>(intval(@$player_stats['att_obox_target'])+intval(@$player_stats['att_obox_miss']))
			 							)
		);

		$passing = array(
			                'accurate_flick_on'=>array('total'=>intval(@$player_stats['accurate_flick_on']),
			                							'overall'=>intval(@$player_stats['total_flick_on'])),
			                'accurate_pass'=>array('total'=>intval(@$player_stats['accurate_pass']),
			                						'overall'=>intval(@$player_stats['total_pass'])),
			                'accurate_chipped_pass'=>array('total'=>intval(@$player_stats['accurate_chipped_pass']),
			                							'overall'=>intval(@$player_stats['total_chipped_pass'])),
			                'accurate_launches'=>array('total'=>intval(@$player_stats['accurate_launches']),
			                							'overall'=>intval(@$player_stats['total_launches'])),
			                'accurate_layoffs'=>array('total'=>intval(@$player_stats['accurate_layoffs']),
			                							'overall'=>intval(@$player_stats['total_layoffs'])),
			                'accurate_long_balls'=>array('total'=>intval(@$player_stats['accurate_long_balls']),
			                							'overall'=>intval(@$player_stats['total_long_balls'])),
			                'accurate_through_balls'=>array('total'=>intval(@$player_stats['accurate_through_ball']),
			                							'overall'=>intval(@$player_stats['total_through_ball'])),
			                'long_pass_to_opponents_half'=>array('total'=>intval(@$player_stats['long_pass_own_to_opp_success'])),
			                'accurate_crossing'=>array('total'=>intval(@$player_stats['accurate_cross']),
			                							'overall'=>intval(@$player_stats['total_cross'])),
			                'accurate_attacking_pass'=>array('total'=>intval(@$player_stats['accurate_fwd_zone_pass']),
			                							'overall'=>intval(@$player_stats['total_fwd_zone_pass'])),
			                'accurate_free_kick_delivery'=>array('total'=>intval(@$player_stats['att_freekick_target']),
			                							'overall'=>intval(@$player_stats['att_freekick_total']))
			    		);
		 $defending = array(
			                'Ball_recovery'=>array('total'=>intval(@$player_stats['ball_recovery'])),
			                'Duel_Won'=>array('total'=>intval(@$player_stats['duel_won']),
			                				 'overall'=>intval(@$player_stats['duel_won']) + intval(@$player_stats['duel_lost'])),
			                'Aerial_Duel_Won'=>array('total'=>intval(@$player_stats['aerial_won']),
			                							'overall'=>intval(@$player_stats['aerial_won']) + intval(@$player_stats['aerial_lost'])),
			                'Tackle_won'=>array('total'=>intval(@$player_stats['won_tackle']),
			                							'overall'=>intval(@$player_stats['total_tackle'])),
			                'Tackle_won_as_a_last_man'=>array('total'=>intval(@$player_stats['last_man_contest'])),
			                'Intercepted_a_pass_inside_the_box'=>array('total'=>intval(@$player_stats['interceptions_in_box'])),
			                'Effective_clearance'=>array('total'=>intval(@$player_stats['effective_clearance']),
			                							'overall'=>intval(@$player_stats['total_clearance'])),
			                'Blocked_a_cross'=>array('total'=>intval(@$player_stats['blocked_cross'])),
			                'Blocked_a_shot'=>array('total'=>intval(@$player_stats['blocked_scoring_att'])),
			                'Blocked_a_shot_from_within_6_yards_box'=>array('total'=>intval(@$player_stats['six_yard_block'])),
			                'offsides_provoked'=>array('total'=>intval(@$player_stats['offside_provoked'])),
			                'Intercepted_Passes'=>array('total'=>intval(@$player_stats['interception_won'])),
			                

			        );
		$total_points = 0;
		foreach($goals_and_assists as $n=>$v){
			$total_points+=intval($v);
		}
		foreach($shooting as $n=>$v){
			$total_points+=intval($v['total']);
		}
		foreach($passing as $n=>$v){
			$total_points+=intval($v['total']);
		}
		foreach($defending as $n=>$v){
			$total_points+=intval($v['total']);
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
		
		//games played
		$player_info['games_played'] = intval(@$player_stats['game_started']) + intval(@$player_stats['total_sub_on']);
		$player_info['mins_played'] = intval(@$player_stats['mins_played']);
		//minutes played
		return array('player'=>$player_info,
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
				first_name,last_name,known_name,birth_date,a.team_id,
				weight,height,jersey_num,country,b.name AS team_name
				FROM master_player a
				INNER JOIN master_team b
				ON a.team_id = b.uid
				WHERE a.uid = '{$player_id}';";

		$rs = $this->query($sql,false);
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

		$rs = $this->query($sql,false);
		$stats = array();
		while(sizeof($rs)>0){
			$st = array_shift($rs);
			$stats[$st['player_stats']['stats_name']] = $st[0]['total'];
		}
		return $stats;
	}
	private function teamBPlayerStats($team_id,$game_ids){
		$sql = "SELECT stats_name,SUM(stats_value) AS total 
				FROM player_stats 
				WHERE game_id IN (".$this->arrayToSql($game_ids).")
				AND team_id NOT IN ('{$team_id}')
				GROUP BY stats_name
				LIMIT 1000;";


		$rs = $this->query($sql,false);
		$stats = array();
		while(sizeof($rs)>0){
			$st = array_shift($rs);
			$stats[$st['player_stats']['stats_name']] = $st[0]['total'];
		}
		return $stats;
	}
}