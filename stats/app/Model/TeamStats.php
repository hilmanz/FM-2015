<?php
App::uses('AppModel', 'Model');
error_reporting(0);
class TeamStats extends Stats {
	public $useTable = false;
	public $useDbConfig = 'opta';

	public function all_team_match_report($game_id){
		$match = $this->match_results_per_game($game_id);

		$rs = array(
				'results'=>$match,
				'home'=>$this->individualMatchReport($game_id,$match['home_team']),
				'away'=>$this->individualMatchReport($game_id,$match['away_team'])
			);
		return $rs;
	}
	public function all_team_match_report_raw($game_id){
		$match = $this->match_results_per_game($game_id);

		$rs = array(
				'results'=>$match,
				'home'=>$this->individualMatchReportRaw($game_id,$match['home_team']),
				'away'=>$this->individualMatchReportRaw($game_id,$match['away_team'])
			);
		return $rs;
	}
	public function get_lineups($team_id,$game_ids){
		$sql = "SELECT 
				b.uid AS player_id,b.name,b.position,b.first_name,b.last_name,b.known_name,
				b.birth_date,b.country,b.jersey_num,SUM(a.stats_value) AS score
				FROM player_stats a
				INNER JOIN master_player b
				ON a.player_id = b.uid
				WHERE a.team_id='{$team_id}'
				AND game_id IN (".$this->arrayToSql($game_ids).")
				AND a.stats_name IN (
					'att_freekick_target',
					'goals',
					'goal_assist',
					'big_chance_created',
					'penalty_won',
					'total_att_assist',
					'ontarget_scoring_att',
					'att_obox_target',
					'accurate_flick_on',
					'accurate_pass',
					'accurate_chipped_pass',
					'accurate_launches',
					'accurate_layoffs',
					'accurate_long_balls',
					'accurate_through_ball',
					'long_pass_own_to_opp_success',
					'accurate_cross',
					'accurate_fwd_zone_pass',
					'accurate_freekick_cross',
					'accurate_cross_nocorner',
					'total_attacking_pass',
					'ball_recovery',
					'duel_won',
					'aerial_won',
					'won_tackle',
					'last_man_contest',
					'interceptions_in_box',
					'effective_clearance',
					'effective_blocked_cross',
					'blocked_scoring_att',
					'six_yard_block',
					'offside_provoked',
					'interception_won',
					'won_contest',
					'last_man_tackle',
					'last_man_contest',
					'penalty_save',
					'dive_save',
					'dive_catch',
					'stand_save',
					'stand_catch',
					'good_claim',
					'good_high_claim',
					'punches',
					'good_one_on_one',
					'gk_smother',
					'accurate_keeper_sweeper',
					'penalty_conceded',
					'dispossessed',
					'error_lead_to_goal',
					'error_lead_to_shot',
					'poss_lost_ctrl',
					'unsuccessful_touch',
					'challenge_lost',
					'cross_not_claimed',
					'total_offside',
					'yellow_card',
					'red_card'
					)
				GROUP BY player_id;";
		$rs = $this->query($sql,false);
		

		$players = array();
		while(sizeof($rs)>0){
			$p = array_shift($rs);
			$player = $p['b'];
			$player['score'] = $p[0]['score'];
			$players[] = $player;
		}

		return $players;
	}
	/**
	* Raw Cumulative Stats
	*/
	public function getRawTeamStats($team_id){
		$game_ids = $this->getGameIds(Configure::read('competition_id'),
												Configure::read('season_id'));
		$stats = $this->getStats($team_id,$game_ids);
		$teamB = $this->getTeamBStats($team_id,$game_ids);
		return array('stats'=>$stats,'teamB'=>$teamB);
	}
	public function individualMatchReportRaw($game_id,$team_id){
		$game_ids = array($game_id);
		$stats = $this->getStats($team_id,$game_ids);
		$teamB = $this->getTeamBStats($team_id,$game_ids);
		return array('stats'=>$stats,'teamB'=>$teamB);
	}
	/*
	* individual match report
	*/
	public function individualMatchReport($game_id,$team_id){
		$game_ids = array($game_id);
		$allgame_ids = $this->getGameIds(Configure::read('competition_id'),
												Configure::read('season_id'));

		$stats = $this->getStats($team_id,$game_ids);
		if(sizeof($stats)>0){
			$teamBStats = $this->getTeamBStats($team_id,$game_ids);
			$rs = array('team'=>$this->getTeam($team_id),
					'match_results'=>$this->match_results_per_game($game_id),
					'most_influential_player'=>$this->getMostInfluencePlayer($team_id,$game_ids),
					'top_assist'=>$this->top_assist($team_id,$game_ids),
					'top_scorer'=>$this->top_scorer($team_id,$game_ids),
					'dangerous_passer'=>$this->dangerous_passer($team_id,$game_ids),
					'greatest_liability'=>$this->greatest_liability($team_id,$game_ids),

					'attacking_play'=>$this->attacking_play($team_id,$game_ids,$stats),
					'attacking_style'=>$this->attacking_style($team_id,$game_ids,$stats,$teamBStats),
					'goals'=>$this->goals($team_id,$game_ids,$stats,$teamBStats),
					'shooting'=>$this->shooting($team_id,$game_ids,$stats,$teamBStats),
					'ball_movement'=>$this->ball_movement($team_id,$game_ids,$stats,$teamBStats),
					'dribbling'=>$this->dribbling($team_id,$game_ids,$stats,$teamBStats),
					'passing_style'=>$this->passing_style($team_id,$game_ids,$stats,$teamBStats),
					'defending_style'=>$this->defending_style($team_id,$game_ids,$stats,$teamBStats),
					'goalkeeping'=>$this->goalkeeping($team_id,$game_ids,$stats,$teamBStats),
					'defending_strength_and_weakness'=>$this->defending_strength_and_weakness($team_id,$game_ids,$stats,$teamBStats),
					'aerial_strength'=>$this->aerial_strength($team_id,$game_ids,$stats,$teamBStats),
					'setplays'=>$this->setplays($team_id,$game_ids,$stats,$teamBStats),
					'total_games'=>$this->team_total_games($team_id,$allgame_ids),
					'lineups'=>$this->get_lineups($team_id,$game_ids)
					);

		}else{
			$rs = array('team'=>$this->getTeam($team_id));
		}
		return $rs;
	}
	/**
	* team stats cumulative report
	*/
	public function getReports($team_id){
		$game_ids = $this->getGameIds(Configure::read('competition_id'),
												Configure::read('season_id'));

		$stats = $this->getStats($team_id,$game_ids);
		$teamBStats = $this->getTeamBStats($team_id,$game_ids);

		$rs = array('team'=>$this->getTeam($team_id),
					'most_influential_player'=>$this->getMostInfluencePlayer($team_id,$game_ids),
					'top_assist'=>$this->top_assist($team_id,$game_ids),
					'top_scorer'=>$this->top_scorer($team_id,$game_ids),
					'dangerous_passer'=>$this->dangerous_passer($team_id,$game_ids),
					'greatest_liability'=>$this->greatest_liability($team_id,$game_ids),
					'goals'=>$this->goals($team_id,$game_ids,$stats,$teamBStats),
					'shooting'=>$this->shooting($team_id,$game_ids,$stats,$teamBStats),
					'ball_movement'=>$this->ball_movement($team_id,$game_ids,$stats,$teamBStats),
					'attacking_play'=>$this->attacking_play($team_id,$game_ids,$stats),
					'attacking_style'=>$this->attacking_style($team_id,$game_ids,$stats,$teamBStats),
					'dribbling'=>$this->dribbling($team_id,$game_ids,$stats,$teamBStats),
					'passing_style'=>$this->passing_style($team_id,$game_ids,$stats,$teamBStats),
					'defending_style'=>$this->defending_style($team_id,$game_ids,$stats,$teamBStats),
					'goalkeeping'=>$this->goalkeeping($team_id,$game_ids,$stats,$teamBStats),
					'defending_strength_and_weakness'=>$this->defending_strength_and_weakness($team_id,$game_ids,$stats,$teamBStats),
					'aerial_strength'=>$this->aerial_strength($team_id,$game_ids,$stats,$teamBStats),
					'setplays'=>$this->setplays($team_id,$game_ids,$stats,$teamBStats),
					'total_games'=>$this->team_total_games($team_id,$game_ids),
					'lineups'=>$this->get_lineups($team_id,$game_ids)
					);

		
		return $rs;
	}
	/**
	*	collection of match results
	*/
	public function match_results($team_id){
		$game_ids = $this->getGameIds(Configure::read('competition_id'),
												Configure::read('season_id'));

		$sql = "SELECT game_id,matchday,matchdate,
				period,home_team,home_score,away_team,away_score,
				b.name AS home_name,c.name AS away_name
				FROM matchinfo a
				INNER JOIN master_team b
				ON a.home_team = b.uid
				INNER JOIN master_team c
				ON a.away_team = c.uid
				WHERE 
				game_id IN (".$this->arrayToSql($game_ids).")
				AND (home_team = '{$team_id}' OR away_team = '{$team_id}')
				LIMIT 380;";
		$rs = $this->query($sql,false);
		$matches = array();
		while(sizeof($rs)>0){
			$r = array_shift($rs);
			$match = $r['a'];
			$match['home_name'] = $r['b']['home_name'];
			$match['away_name'] = $r['c']['away_name'];
			$matches[] = $match;
		}
		
		return $matches;
	}
	public function match_results_per_game($game_id){
		$game_ids = $this->getGameIds(Configure::read('competition_id'),
												Configure::read('season_id'));

		$sql = "SELECT game_id,matchday,matchdate,
				period,home_team,home_score,away_team,away_score,
				b.name AS home_name,c.name AS away_name
				FROM matchinfo a
				INNER JOIN master_team b
				ON a.home_team = b.uid
				INNER JOIN master_team c
				ON a.away_team = c.uid
				WHERE 
				game_id = '{$game_id}'
				
				LIMIT 380;";
		$rs = $this->query($sql,false);
		$matches = array();
		while(sizeof($rs)>0){
			$r = array_shift($rs);
			$match = $r['a'];
			$match['home_name'] = $r['b']['home_name'];
			$match['away_name'] = $r['c']['away_name'];
			$matches[] = $match;
		}
		
		return $matches[0];
	}
	
	private function team_total_games($team_id,$game_ids){
		$sql = "SELECT COUNT(game_id) AS total FROM matchinfo 
				WHERE game_id IN (".$this->arrayToSql($game_ids).") 
				AND (home_team = '{$team_id}' OR away_team = '{$team_id}');";
		$rs = $this->query($sql,false);
		return $rs[0][0]['total'];
	}
	private function getTeam($team_id){
		$rs = $this->query("SELECT * FROM master_team WHERE uid='{$team_id}' LIMIT 1",false);
		return $rs[0]['master_team'];
	}
	private function getMostInfluencePlayer($team_id,$game_ids){
		$sql = "SELECT player_id,SUM(most_influence) AS total,b.name,b.position,b.jersey_num,b.team_id,
				c.name AS team_name
				FROM master_player_summary a
				INNER JOIN master_player b
				ON a.player_id = b.uid
				INNER JOIN master_team c
				ON b.team_id = c.uid
				WHERE game_id IN (".$this->arrayToSql($game_ids).")
				AND most_influence > 0
				AND a.team_id='{$team_id}'
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
	private function top_assist($team_id,$game_ids){
		$sql = "SELECT a.player_id,SUM(stats_value) AS total,b.name,b.position,b.jersey_num 
				FROM player_stats a
				INNER JOIN master_player b
				ON a.player_id = b.uid
				WHERE a.team_id='{$team_id}' AND stats_name='goal_assist' 
				AND game_id IN (".$this->arrayToSql($game_ids).")
				GROUP BY player_id ORDER BY total DESC LIMIT 5";

		$rs = $this->query($sql,false);
		
		$players  = array();
		while(sizeof($rs)>0){
			$a = array_shift($rs);
			$player = $a['b'];
			
			$player['total'] = $a[0]['total'];
			$player['player_id'] = $a['a']['player_id'];
			$players[] = $player;
		}
		return $players;
	}
	private function top_scorer($team_id,$game_ids){
		$sql = "SELECT a.player_id,SUM(stats_value) AS total,b.name,b.position,b.jersey_num 
				FROM player_stats a
				INNER JOIN master_player b
				ON a.player_id = b.uid
				WHERE a.team_id='{$team_id}' AND stats_name='goals' 
				AND game_id IN (".$this->arrayToSql($game_ids).")
				GROUP BY player_id ORDER BY total DESC LIMIT 5";
		$rs = $this->query($sql,false);
		
		$players  = array();
		while(sizeof($rs)>0){
			$a = array_shift($rs);
			$player = $a['b'];
			
			$player['total'] = $a[0]['total'];
			$player['player_id'] = $a['a']['player_id'];
			$players[] = $player;
		}
		return $players;
	}
	private function dangerous_passer($team_id,$game_ids){
		$sql = "SELECT player_id,SUM(dangerous_pass) AS total,b.name,
				b.position,b.jersey_num,b.team_id,
				c.name AS team_name
				FROM master_player_summary a
				INNER JOIN master_player b
				ON a.player_id = b.uid
				INNER JOIN master_team c
				ON b.team_id = c.uid
				WHERE game_id IN (".$this->arrayToSql($game_ids).")
				AND dangerous_pass > 0
				AND a.team_id='{$team_id}'
				GROUP BY player_id 
				ORDER BY total DESC LIMIT 5;";
		$rs = $this->query($sql,false);
		
		$players  = array();
		while(sizeof($rs)>0){
			$a = array_shift($rs);
			$player = $a['b'];
			$player['total'] = $a[0]['total'];
			$player['player_id'] = $a['a']['player_id'];
			$players[] = $player;
		}
		return $players;
	}
	private function greatest_liability($team_id,$game_ids){
		$sql = "SELECT player_id,SUM(liable) AS total,b.name,
				b.position,b.jersey_num,b.team_id,
				c.name AS team_name
				FROM master_player_summary a
				INNER JOIN master_player b
				ON a.player_id = b.uid
				INNER JOIN master_team c
				ON b.team_id = c.uid
				WHERE game_id IN (".$this->arrayToSql($game_ids).")
				AND liable > 0
				AND a.team_id='{$team_id}'
				GROUP BY player_id 
				ORDER BY total DESC LIMIT 5;";
		$rs = $this->query($sql,false);
		
		$players  = array();
		while(sizeof($rs)>0){
			$a = array_shift($rs);
			$player = $a['b'];
			
			$player['total'] = $a[0]['total'];
			$player['player_id'] = $a['a']['player_id'];
			$players[] = $player;
		}
		return $players;
	}
	private function goals($team_id,$game_ids,$stats,$teamB){
		$first_half = $this->fromFormula('first_half_goals',$stats);
		$second_half = $this->fromFormula('goals - first_half_goals',
									 		$stats);

		
		return array('first_half'=>array('total'=>$first_half,
										 'average'=>0),
					  'second_half'=>array('total'=>$second_half,
					  							'average'=>0),
					);
	}
	private function ball_movement($team_id,$game_ids,$stats,$teamB){

		$final_3rd_entries = $this->fromFormula('final_third_entries',$stats);
		$penalty_area_entries = $this->fromFormula('pen_area_entries',$stats);
		$chances_created = $this->fromFormula('big_chance_created',$stats);
		$chances_missed = $this->fromFormula('big_chance_missed',$stats);
		$chances_scored = $this->fromFormula('big_chance_scored',$stats);
		$chances_conversion = floatval($this->fromFormula('(big_chance_scored/big_chance_created)',$stats)) * 100;

		
		return array('final_3rd_entries'=>array('total'=>$final_3rd_entries),
						'penalty_area_entries'=>array('total'=>$penalty_area_entries),
						'chances_created'=>array('total'=>$chances_created),
						'chances_missed'=>array('total'=>$chances_missed),
						'chances_scored'=>array('total'=>$chances_scored),
						'chances_conversion'=>array('total'=>$chances_conversion),
					);
	}
	private function shooting($team_id,$game_ids,$stats,$teamB){
		$shots_from_inside_the_box = $this->fromFormula('(att_ibox_blocked + att_ibox_goal + att_ibox_miss + att_ibox_target)',
									 $stats);

		$ibox_accuracy = $this->fromFormula('(att_ibox_goal + att_ibox_target) / (att_ibox_blocked + att_ibox_goal + att_ibox_miss + att_ibox_target + att_ibox_post)',
									 $stats);
		$ibox_goal = $this->fromFormula('att_ibox_goal',$stats);


		$shots_from_outside_the_box = $this->fromFormula('(att_obox_blocked + att_obox_goal + att_obox_miss + att_obox_target)',$stats);
		$obox_accuracy = $this->fromFormula('(att_obox_goal + att_obox_target) / (att_obox_blocked + att_obox_goal + att_obox_miss + att_obox_target + att_obox_post)',$stats);
		$obox_goal = $this->fromFormula('att_obox_goal',$stats);

		return array('shots_from_inside_the_box'=>array('total'=>$shots_from_inside_the_box,
										 				'accuracy'=>$ibox_accuracy,
										 				'goals'=>$ibox_goal),

					  'shots_from_outside_the_box'=>array('total'=>$shots_from_outside_the_box,
										 				'accuracy'=>$obox_accuracy,
										 				'goals'=>$obox_goal),
					);
	}
	private function attacking_style($team_id,$game_ids,$stats,$teamB){
		$deep_cross = intval(@$stats['crosses_18yardplus']);
		$deep_cross_avg = intval(@$stats['crosses_18yardplus']) / (intval(@$stats['crosses_18yardplus']) + intval(@$stats['crosses_18yard']) +intval(@$stats['total_pull_back']) + intval(@$stats['total_through_ball']));
		$cross_from_18yd = intval(@$stats['crosses_18yard']);
		$cross_from_18yd_avg = intval(@$stats['crosses_18yard']) / (intval(@$stats['crosses_18yardplus']) + intval(@$stats['crosses_18yard']) +intval(@$stats['total_pull_back']) + intval(@$stats['total_through_ball']));

		$cutbacks = intval(@$stats['total_pull_back']);
		$cutbacks_avg = intval(@$stats['total_pull_back']) / (intval(@$stats['crosses_18yardplus']) + intval(@$stats['crosses_18yard']) +intval(@$stats['total_pull_back']) + intval(@$stats['total_through_ball']));
		
		$through_ball = intval(@$stats['total_through_ball']);
		$through_ball_avg = intval(@$stats['total_through_ball']) / (intval(@$stats['crosses_18yardplus']) + intval(@$stats['crosses_18yard']) +intval(@$stats['total_pull_back']) + intval(@$stats['total_through_ball']));
		

		$through_ball = intval(@$stats['total_through_ball']);
		$through_ball_avg = intval(@$stats['total_through_ball']) / (intval(@$stats['crosses_18yardplus']) + intval(@$stats['crosses_18yard']) +intval(@$stats['total_pull_back']) + intval(@$stats['total_through_ball']));
		
		$accurate_cutbacks = intval(@$stats['accurate_pull_back']);
		$accurate_cutbacks_avg = floatval(@(intval(@$stats['accurate_pull_back']) / intval(@$stats['total_pull_back'])));

		$accurate_through_ball = intval(@$stats['accurate_through_ball']);
		$accurate_through_ball_avg = floatval(@(intval(@$stats['accurate_through_ball']) / intval(@$stats['total_through_ball'])));

		$accurate_through_ball = intval(@$stats['accurate_through_ball']);
		$accurate_through_ball_avg = floatval(@(intval(@$stats['accurate_through_ball']) / intval(@$stats['total_through_ball'])));

		$chances_from_crosses = intval(@$stats['att_hd_total']);
						//att_bx_centre + att_ibox_blocked + att_ibox_miss + att_ibox_post + att_ibox_target
		$shots_from_ibox = intval(@$stats['att_ibox_blocked']) + intval(@$stats['att_ibox_goal']) + intval(@$stats['att_ibox_miss']) + intval(@$stats['att_ibox_target']);
		
		$shots_from_obox = intval(@$stats['att_obox_blocked']) + intval(@$stats['att_obox_goal']) + intval(@$stats['att_obox_miss']) + intval(@$stats['att_obox_target']);
		
		$shots_from_ibox_avg = $shots_from_ibox / ($shots_from_ibox + $shots_from_obox);
		$shots_from_obox_avg = $shots_from_obox / ($shots_from_ibox + $shots_from_obox);
		$chances_from_crosses_avg = ($chances_from_crosses / intval(@$stats['total_scoring_att']));

		$goals_from_shot_ibox = intval(@$stats['att_ibox_goal']);
		$goals_from_shot_obox = intval(@$stats['att_obox_goal']);
		$goals_from_crosses = intval(@$stats['att_hd_goal']);
		$goals_from_shot_ibox_avg = $goals_from_shot_ibox / ($goals_from_shot_ibox+$goals_from_shot_obox+$goals_from_crosses);
		$goals_from_shot_obox_avg = $goals_from_shot_obox / ($goals_from_shot_ibox+$goals_from_shot_obox+$goals_from_crosses);
		$goals_from_crosses_avg = $goals_from_crosses / ($goals_from_shot_ibox+$goals_from_shot_obox+$goals_from_crosses);

		
		return array('deep_cross'=>array('total'=>$deep_cross,
										 'average'=>$deep_cross_avg),
					  'cross_from_18yd'=>array('total'=>$cross_from_18yd,
					  							'average'=>$cross_from_18yd_avg),

					  'cutbacks'=>array('total'=>$cutbacks,
					  							'average'=>$cutbacks_avg),

					  'through_ball'=>array('total'=>$through_ball,
					  							'average'=>$through_ball_avg),

					  'accurate_cutbacks'=>array('total'=>$accurate_cutbacks,
					  							'average'=>$accurate_cutbacks_avg),

					  'accurate_through_ball'=>array('total'=>$accurate_through_ball,
					  							'average'=>$accurate_through_ball_avg),

					  'chances_from_crosses'=>array("total"=>$chances_from_crosses,
					  								"average"=>$chances_from_crosses_avg),

					  'shots_from_inside_the_box'=>array("total"=>$shots_from_ibox,
					  								"average"=>$shots_from_ibox_avg),
					  'shots_from_outside_the_box'=>array("total"=>$shots_from_obox,
					  								"average"=>$shots_from_obox_avg),

					   'goals_from_shot_inside_the_box'=>array("total"=>$goals_from_shot_ibox,
					  								"average"=>$goals_from_shot_ibox_avg),

					   'goals_from_shot_outside_the_box'=>array("total"=>$goals_from_shot_obox,
					  								"average"=>$goals_from_shot_obox_avg),


					);
	}

	private function dribbling($team_id,$game_ids,$stats,$teamB){
		return array('beating_an_opponent'=>intval(@$stats['won_contest']),
					  'beating_last_defender'=>intval(@$stats['last_man_contest']),
					  'fouled_in_attacking_3rd'=>intval(@$stats['fouled_final_third']),
					);
	}
	private function passing_style($team_id,$game_ids,$stats,$teamB){
		$long_ball = intval(@$stats['total_long_balls']);
		$short_passes = intval(@$stats['total_pass']) - intval(@$stats['total_long_balls']) - intval(@$stats['total_launches']);
		$launches = intval(@$stats['total_launches']);
		$through_balls = intval(@$stats['total_through_ball']);
		$chipped_passes = intval(@$stats['total_chipped_pass']);
		$forward_passes = intval(@$stats['total_final_third_passes']);
		//$total_pass = $long_ball + $short_passes + $launches + $through_balls + $chipped_passes ;
		$total_pass = intval(@$stats['total_pass']);
		$leftside_pass = intval(@$stats['leftside_pass']);
		$rightside_pass = intval(@$stats['rightside_pass']);
		$accurate_pass = intval(@$stats['accurate_pass']);

		//average
		$long_ball_avg = $long_ball / $total_pass;
		$short_passes_avg = $short_passes / $total_pass;
		$launches_avg = $launches / $total_pass;
		$through_balls_avg = $through_balls / $total_pass;
		$chipped_passes_avg = $chipped_passes / $total_pass;
		$forward_passes_avg = $forward_passes / intval(@$stats['total_pass']);
		
		$leftside_pass_avg = $leftside_pass / ($leftside_pass+$rightside_pass);
		$rightside_pass_avg = $rightside_pass / ($leftside_pass+$rightside_pass);
		$accurate_pass_avg = $accurate_pass / intval(@$stats['total_pass']);
		//accuracy

		$long_ball_acc = ($long_ball > 0) ? intval(@$stats['accurate_long_balls']) / $long_ball : 0;
		$short_passes_acc = 0;
		$launches_acc = intval(@$stats['accurate_launches']) / $launches;
		$through_balls_acc =  intval(@$stats['accurate_through_ball']) / $through_balls;
		$chipped_passes_acc =  intval(@$stats['accurate_chipped_pass']) / $chipped_passes;
		$forward_passes_acc =  intval(@$stats['successful_final_third_passes']) / $forward_passes;

		//accurates
		$accurate_long_balls = intval(@$stats['accurate_long_balls']);
		$accurate_short_pass = intval(0);
		$accurate_launches = intval(@$stats['accurate_launches']);
		$accurate_through_ball = intval(@$stats['accurate_through_ball']);
		$accurate_chipped_pass = intval(@$stats['accurate_chipped_pass']);
		$accurate_fwd_zone_pass = intval(@$stats['successful_final_third_passes']);


		
		//$accurate_pass_acc =  $accurate_pass / intval(@$stats['total_pass']);


		return array('long_ball'=>array('total'=>$long_ball,
										 'accurate'=>$accurate_long_balls,
										 'average'=>$long_ball_avg,
										 'accuracy'=>$long_ball_acc),

					'short_passes'=>array('total'=>$short_passes,
										'accurate'=>$accurate_short_pass,
										 'average'=>$short_passes_avg,
										 'accuracy'=>$short_passes_acc),


					'launches'=>array('total'=>$launches,
											'accurate'=>$accurate_launches,
										 'average'=>$launches_avg,
										 'accuracy'=>$launches_acc),

					'through_balls'=>array('total'=>$through_balls,
										 'accurate'=>$accurate_through_ball,
										 'average'=>$through_balls_avg,
										 'accuracy'=>$through_balls_acc),

					'chipped_passes'=>array('total'=>$chipped_passes,
										 'accurate'=>$accurate_chipped_pass,
										 'average'=>$chipped_passes_avg,
										 'accuracy'=>$chipped_passes_acc),

					'forward_passes'=>array('total'=>$forward_passes,
										'accurate'=>$accurate_fwd_zone_pass,
										 'average'=>$forward_passes_avg,
										 'accuracy'=>$forward_passes_acc),


					'leftside_pass'=>array('total'=>$leftside_pass,
										 'average'=>$leftside_pass_avg,
										 'accuracy'=>0),

					'rightside_pass'=>array('total'=>$rightside_pass,

										 'average'=>$rightside_pass_avg,
										 'accuracy'=>0),

					'accurate_passes'=>array('total'=>$total_pass,
										  'accurate'=>$accurate_pass,
										 'average'=>$accurate_pass_avg,
										 'accuracy'=>$accurate_pass_avg),
					'total_pass'=>array('total'=>intval(@$total_pass),'average'=>0,accuracy=>0)
				);



	}
	private function setplays($team_id,$game_ids,$stats,$teamB){
		$corners_won = intval(@$stats['won_corners']);
		$freekicks_won = intval(@$stats['fk_foul_won']);
		$corner_delivery = intval(@$stats['accurate_corners_intobox']);
		$freekick_delivery = intval(@$stats['accurate_freekick_cross']);
		$direct_freekicks = intval(@$stats['att_freekick_goal']) + intval(@$stats['att_freekick_target']);
		
		//accuracy
		$corners_won_acc = 0;
		$freekicks_won_acc = 0;
		$corner_delivery_acc = (intval(@$stats['accurate_cross']) - intval(@$stats['accurate_cross_nocorner'])) / intval(@$stats['total_corners_intobox']);
		$freekick_delivery_acc = intval(@$stats['accurate_freekick_cross']) / intval(@$stats['freekick_cross']);
		$direct_freekicks_acc = intval(@$stats['att_freekick_target']) / intval(@$stats['att_freekick_total']);
		
		//chance ratio
		$corners_won_ratio = 0;
		$freekicks_won_ratio = 0;
		$corner_delivery_ratio = intval(@$stats['att_corner']) / (intval(@$stats['total_cross']) - intval(@$stats['total_cross_nocorner']));
		$freekick_delivery_ratio = (intval(@$stats['att_setpiece']) - intval(@$stats['att_corner']))/ intval(@$stats['freekick_cross']);
		$direct_freekicks_ratio = intval(@$stats['att_freekick_goal']) / intval(@$stats['att_freekick_total']);

		return array('corners_won'=>array('total'=>$corners_won,
										 'accuracy'=>$corners_won_acc,
										  'chance_ratio'=>$corners_won_ratio),


						'freekicks_won'=>array('total'=>$freekicks_won,
										 'accuracy'=>$freekicks_won_acc,
										  'chance_ratio'=>$freekicks_won_ratio),

						'corner_delivery'=>array('total'=>$corner_delivery,
										 'accuracy'=>$corner_delivery_acc,
										  'chance_ratio'=>$corner_delivery_ratio,
										  'total2'=>intval(@$stats['corner_taken'])),

						'freekick_delivery'=>array('total'=>$freekick_delivery,
										 'accuracy'=>$freekick_delivery_acc,
										  'chance_ratio'=>$freekick_delivery_ratio,
										  'total2'=>intval(@$stats['freekick_cross'])),

						'direct_freekicks'=>array('total'=>$direct_freekicks,
										 'accuracy'=>$direct_freekicks_acc,
										  'chance_ratio'=>$direct_freekicks_ratio,
										  'total2'=>intval(@$stats['att_freekick_total'])),

					
					
				);
	}
	private function aerial_strength($team_id,$game_ids,$stats,$teamB){
		$aerial_duels_won = intval(@$stats['aerial_won']);
		$header_at_goals = intval(@$stats['att_hd_target']);
		$effective_clearance = intval(@$stats['effective_head_clearance']);
		$flick_ons = intval(@$stats['accurate_flick_on']);
		$gk_highclaims = intval(@$stats['good_high_claim']);
		$gk_crosses_not_claimed = intval(@$stats['cross_not_claimed']);
		

		$aerial_duels_won_avg = intval(@$stats['aerial_won']) / (intval(@$stats['aerial_won']) + intval(@$stats['aerial_lost']));

		$header_at_goals_avg = intval(@$stats['att_hd_target']) / intval(@$stats['att_hd_total']);
		$effective_clearance_avg = intval(@$stats['effective_head_clearance']) / intval(@$stats['head_clearance']);
		$flick_ons_avg = intval(@$stats['accurate_flick_on']) / intval(@$stats['total_flick_on']);
		$gk_highclaims_avg = intval(@$stats['good_high_claim']) / ($gk_highclaims + intval(@$stats['cross_not_claimed']));
		$gk_crosses_not_claimed_avg = intval(@$stats['cross_not_claimed']) / ($gk_highclaims + intval(@$stats['cross_not_claimed']));


		return array('aerial_duels_won'=>array('total'=>$aerial_duels_won,
										 'average'=>$aerial_duels_won_avg,
										 'total2'=>(intval(@$stats['aerial_won']) + intval(@$stats['aerial_lost']))),

					'header_at_goals'=>array('total'=>$header_at_goals,
										 'average'=>$header_at_goals_avg,
										 'total2'=>intval(@$stats['att_hd_total'])),

					'effective_clearance'=>array('total'=>$effective_clearance,
										 'average'=>$effective_clearance_avg,
										 'total2'=>intval(@$stats['head_clearance'])),

					'flick_ons'=>array('total'=>$flick_ons,
										 'average'=>$flick_ons_avg,
										 'total2'=>intval(@$stats['total_flick_on'])),

					'gk_highclaims'=>array('total'=>$gk_highclaims,
										 'average'=>$gk_highclaims_avg,
										 'total2'=>($gk_highclaims + intval(@$stats['cross_not_claimed']))),

					'gk_crosses_not_claimed'=>array('total'=>$gk_crosses_not_claimed,
										 'average'=>$gk_crosses_not_claimed_avg,
										 'total2'=>($gk_highclaims + intval(@$stats['cross_not_claimed']))),
					
				);
	}
	private function defending_style($team_id,$game_ids,$stats,$teamB){
		$recover_in_attacking_3rd = intval(@$stats['poss_won_att_3rd']);
		$recover_in_midfield = intval(@$stats['poss_won_mid_3rd']);
		$recover_in_defending_3rd = intval(@$stats['poss_won_def_3rd']);
		$recover_in_attacking_3rd_avg = $recover_in_attacking_3rd / ($recover_in_attacking_3rd + $recover_in_midfield + $recover_in_defending_3rd);
		$recover_in_midfield_avg = $recover_in_midfield / ($recover_in_attacking_3rd + $recover_in_midfield + $recover_in_defending_3rd);
		$recover_in_defending_3rd_avg = $recover_in_defending_3rd / ($recover_in_attacking_3rd + $recover_in_midfield + $recover_in_defending_3rd);

		

		return array('recover_in_attacking_3rd'=>array('total'=>$recover_in_attacking_3rd,
										 'average'=>$recover_in_attacking_3rd_avg),
					 'recover_in_midfield'=>array('total'=>$recover_in_midfield,
										 'average'=>$recover_in_midfield_avg),
					 'recove_rin_defending_3rd'=>array('total'=>$recover_in_defending_3rd,
										 'average'=>$recover_in_defending_3rd_avg)
				);



	}
	private function goalkeeping($team_id,$game_ids,$stats,$teamB){
		$shot_saved = intval(@$stats['saves']);
		$one_on_one = intval(@$stats['good_one_on_one']);
		$keeper_sweeper = intval(@$stats['accurate_keeper_sweeper']);
		$gk_smother = intval(@$stats['gk_smother']);
		$gk_highclaims = intval(@$stats['good_high_claim']);
		$gk_crosses_not_claimed = intval(@$stats['cross_not_claimed']);
		

		$shot_saved_avg = intval(@$stats['saves']) /  intval(@$teamB['ontarget_scoring_att']);
		$one_on_one_avg = (intval(@$stats['total_one_on_one']) > 0 ) ? intval(@$stats['good_one_on_one']) /  intval(@$stats['total_one_on_one']) : 0;
		$keeper_sweeper_avg = intval(@$stats['accurate_keeper_sweeper']) /  intval(@$stats['total_keeper_sweeper']);
		$gk_smother_avg = 0;
		$gk_highclaims_avg = intval(@$stats['good_high_claim']) /  ($gk_highclaims + intval(@$stats['cross_not_claimed']));
		$gk_crosses_not_claimed_avg = intval(@$stats['cross_not_claimed']) /  ($gk_highclaims + intval(@$stats['cross_not_claimed']));

		$penalty_saved = intval(@$stats['penalty_save']);
		$penalty_saved_avg = intval(@$stats['penalty_save']) / intval(@$stats['penalty_conceded']);

		return array('shot_saved'=>array('total'=>$shot_saved,
										 'average'=>$shot_saved_avg,
										 'total2'=>intval(@$teamB['ontarget_scoring_att'])),

					  'one_on_one'=>array('total'=>$one_on_one,
										 'average'=>$one_on_one_avg,
										 'total2'=>intval(@$stats['total_one_on_one'])),

					  'keeper_sweeper'=>array('total'=>$keeper_sweeper,
										 'average'=>$keeper_sweeper_avg,
										 'total2'=>intval(@$stats['total_keeper_sweeper'])),

					  'gk_smother'=>array('total'=>$gk_smother,
										 'average'=>$gk_smother_avg),

					  'gk_highclaims'=>array('total'=>$gk_highclaims,
										 'average'=>$gk_highclaims_avg,
										 'total2'=>($gk_highclaims + intval(@$stats['cross_not_claimed']))),

					  'gk_crosses_not_claimed'=>array('total'=>$gk_crosses_not_claimed,
										 'average'=>$gk_crosses_not_claimed_avg,
										 'total2'=>$gk_highclaims + intval(@$stats['cross_not_claimed'])),

					  'penalty_saved'=>array('total'=>$penalty_saved,
										 'average'=>$penalty_saved_avg,
										 'total2'=> intval(@$stats['penalty_conceded'])),
					 
				);
	}
	private function defending_strength_and_weakness($team_id,$game_ids,$stats,$teamB){
		$duels_won = intval(@$stats['duel_won']);
		$tackling_won = intval(@$stats['won_tackle']);
		$challenge_lost = intval(@$stats['challenge_lost']);
		$head_clearance = intval(@$stats['effective_head_clearance']);
		$attempts_conceded_inbox = intval(@$stats['attempts_conceded_ibox']);
		$attempts_conceded_outside_box = intval(@$stats['attempts_conceded_obox']);
		$attempts_conceded_from_fastbreak = intval(@$teamB['shot_fastbreak']) + intval(@$teamB['att_fastbreak']);
		$attempts_conceded_from_setpieces = intval(@$teamB['att_assits_setplay']);
		
		$duels_won_avg = intval(@$stats['duel_won']) / (intval(@$stats['duel_won'])+intval(@$stats['duel_lost']));
		$tackling_won_avg = intval(@$stats['won_tackle']) / intval(@$stats['total_tackle']);
		$challenge_lost_avg = 0;
		$head_clearance_avg = intval(@$stats['effective_head_clearance']) / intval(@$stats['head_clearance']);


		$attempts_conceded_inbox_avg = $attempts_conceded_inbox / ($attempts_conceded_inbox + $attempts_conceded_outside_box);
		$attempts_conceded_outside_box_avg = $attempts_conceded_outside_box / ($attempts_conceded_inbox + $attempts_conceded_outside_box);


		$attempts_conceded_from_fastbreak_avg = (intval(@$teamB['shot_fastbreak']) + intval(@$stats['att_fastbreak'])) / intval(@$teamB['total_scoring_att']);
		$attempts_conceded_from_setpieces_avg = intval(@$teamB['att_assits_setplay']) / intval(@$teamB['total_scoring_att']);

		$error_lead_to_shot = intval(@$stats['error_lead_to_shot']);
		$error_lead_to_goal = intval(@$stats['error_lead_to_goal']);
		$total_errors = intval(@$stats['error_lead_to_shot']) + intval(@$stats['error_lead_to_goal']) + intval(@$stats['unsuccessful_touch']) + intval(@$stats['dispossessed']);
		$penalty_conceded = intval(@$stats['penalty_conceded']);
		$fouls_conceded_in_attacking_3rd = intval(@$teamB['fouled_final_third']);
		
		return array('duels_won'=>array('total'=>$duels_won,
										 'average'=>$duels_won_avg,
										 'total2'=>(intval(@$stats['duel_won'])+intval(@$stats['duel_lost']))),

						'tackling_won'=>array('total'=>$tackling_won,
										 'average'=>$tackling_won_avg,
										 'total2'=>intval(@$stats['total_tackle'])),

						'challenge_lost'=>array('total'=>$challenge_lost,
										 'average'=>$challenge_lost_avg),

						'head_clearance'=>array('total'=>$head_clearance,
										 'average'=>$head_clearance_avg,
										 'total2'=>intval(@$stats['head_clearance'])),

						'attempts_conceded_inbox'=>array('total'=>$attempts_conceded_inbox,
										 'average'=>$attempts_conceded_inbox_avg,
										 ),

						'attempts_conceded_outside_box'=>array('total'=>$attempts_conceded_outside_box,
										 'average'=>$attempts_conceded_outside_box_avg,
										 ),

						'attempts_conceded_from_fastbreak'=>array('total'=>$attempts_conceded_from_fastbreak,
										 'average'=>$attempts_conceded_from_fastbreak_avg,
										 ),

						'attempts_conceded_from_setpieces'=>array('total'=>$attempts_conceded_from_setpieces,
										 'average'=>$attempts_conceded_from_setpieces_avg,
										 ),

						'error_lead_to_shot'=>array('total'=>$error_lead_to_shot,
										 'average'=>0,
										 ),

						'error_lead_to_goal'=>array('total'=>$error_lead_to_goal,
										 'average'=>0),

						'total_errors'=>array('total'=>$total_errors,
										 'average'=>0),

						'penalty_conceded'=>array('total'=>$penalty_conceded,
										 'average'=>0),

						'fouls_conceded_in_attacking_3rd'=>array('total'=>$fouls_conceded_in_attacking_3rd,
										 'average'=>0),

					  
					 
				);

	}
	/*
	* Frequency | Efficiency | Chances | Goals | Conversion Rate | Average/Game
	* Open Play, Set Pieces, Counter Attack
	*/
	private function attacking_play($team_id,$game_ids,$stats){
		$rs = $this->query("SELECT COUNT(*) AS total FROM (SELECT game_id 
							FROM team_stats 
							WHERE game_id IN (".$this->arrayToSql($game_ids).")
							AND team_id='{$team_id}' GROUP BY game_id) a;",false);
		$total_games = $rs[0][0]['total'];
		//openplay
		$openplay['frequency'] = $this->openplay_frequency($stats);
		$openplay['efficiency'] = $this->openplay_efficiency($stats);
		$openplay['chances'] = $this->openplay_chances($stats);
		$openplay['goals'] = $this->openplay_goals($stats);
		$openplay['conversion_rate'] = $this->openplay_conversion($stats);
		$openplay['average'] = $this->openplay_average_per_game($stats,$total_games);

		$setpieces['frequency'] = $this->setpieces_frequency($stats);
		$setpieces['efficiency'] = $this->setpieces_efficiency($stats);
		$setpieces['chances'] = $this->setpieces_chances($stats);
		$setpieces['goals'] = $this->setpieces_goals($stats);
		$setpieces['conversion_rate'] = $this->setpieces_conversion($stats);
		$setpieces['average'] = $this->setpieces_average_per_game($stats,$total_games);

		$counter['frequency'] = $this->counter_frequency($stats);
		$counter['efficiency'] = $this->counter_efficiency($stats);
		$counter['chances'] = $this->counter_chances($stats);
		$counter['goals'] = intval(@$stats['goal_fastbreak']);
		$counter['conversion_rate'] = @floatval($this->counter_conversion($stats));
		$counter['average'] = $this->counter_average_per_game($stats,$total_games);

		$openplay['efficiency'] = $openplay['chances'] / ($openplay['chances']+$setpieces['chances']+$counter['chances']);
		$setpieces['efficiency'] = $setpieces['chances'] / ($openplay['chances']+$setpieces['chances']+$counter['chances']);
		$counter['efficiency'] = $counter['chances'] / ($openplay['chances']+$setpieces['chances']+$counter['chances']);


		return array('openplay'=>$openplay,
					 'setpieces'=>$setpieces,
					 'counter_attack'=>$counter);
	}
	private function counter_average_per_game($stats,$total_games){
		$chances = intval(@$stats['total_fastbreak']);
		return $chances/$total_games;

	}
	private function counter_conversion($stats){
		return intval(@$stats['goal_fastbreak'])/intval(@$stats['total_fastbreak']);
	}
	private function counter_chances($stats){
		return intval(@$stats['total_fastbreak']);
		
	}
	private function counter_efficiency($stats){
		
		$s = (intval(@$stats['total_fastbreak'])/intval(@$stats['att_openplay']));
		return $s;
		//return (intval(@$stats['goal_fastbreak']) + intval(@$stats['shot_fastbreak']) + intval(@$stats['att_fastbreak'])) / (intval(@$stats['goals']) + intval(@$stats['total_scoring_att']));

	}
	private function counter_frequency($stats){
		return intval(@$stats['total_fastbreak']);
		//$s = (intval(@$stats['att_fastbreak'])/intval(@$stats['att_openplay']));
		//return $s;
		//return (intval(@$stats['goal_fastbreak']) + intval(@$stats['shot_fastbreak']) +intval(@$stats['att_fastbreak']))/ ((intval(@$stats['goals_openplay']) + intval(@$stats['att_openplay'])) + ((intval(@$stats['goals']) - intval(@$stats['goals_openplay'])) +(intval(@$stats['total_scoring_att']) - intval(@$stats['att_openplay'])) )+(intval(@$stats['goal_fastbreak']) + intval(@$stats['shot_fastbreak']) +intval(@$stats['att_fastbreak'])));
		//return (intval(@$stats['goal_fastbreak']) + intval(@$stats['shot_fastbreak']) +intval(@$stats['att_fastbreak']));
	}
	private function setpieces_average_per_game($stats,$total_games){
		$chances = $this->setpieces_chances($stats);
		return $chances/$total_games;

	}
	private function setpieces_conversion($stats){
		//pr($stats['goals'].'-'.$stats['goals_openplay'].'-'.$stats['goal_fastbreak']);
		//$s = ($stats['goals'] - ($stats['goals_openplay'])) / ($stats['total_scoring_att'] - $stats['att_openplay']);

		$s = $s = ($stats['goals'] - ($stats['goals_openplay'])) / ($stats['freekick_cross'] + $stats['total_corners_intobox']);
		//sementara jika angkanya minus, kita set jadi 0 aja. sampai ada rumus baru.
		if($s<0){ $s = 0;}
		return $s;
		
		//return ($stats['goals'] - ($stats['goals_openplay']+$stats['goal_fastbreak']))/ intval(@$stats['att_assist_setplay']);
	}
	private function setpieces_goals($stats){
		$s = ($stats['goals'] - ($stats['goals_openplay']));
		//sementara jika angkanya minus, kita set jadi 0 aja. sampai ada rumus baru.
		if($s<0){ $s = 0;}
		return $s;

	}
	private function setpieces_frequency($stats){
		//((goals - goals_openplay) +(total_scoring_att - att_openplay) / ((goals_openplay + att_openplay) + ((goals - goals_openplay) +(total_scoring_att - att_openplay) +(goal_fastbreak + shot_fastbreak +att_fastbreak))
		
		$a = $this->getStatsValue('goals',$stats) - $this->getStatsValue('goals_openplay',$stats);
		$b = $this->getStatsValue('total_scoring_att',$stats) - $this->getStatsValue('att_openplay',$stats);
		/*
		$c = $this->getTotalValuesFromAttributes('goals_openplay,att_openplay',$stats);
		$d = $this->getStatsValue('goals',$stats) - $this->getStatsValue('goals_openplay',$stats);
		$e = $this->getStatsValue('total_scoring_att',$stats) - $this->getStatsValue('att_openplay',$stats);
		$f = $this->getTotalValuesFromAttributes('goal_fastbreak,shot_fastbreak,att_fastbreak',$stats);
		$n = ($c + $d + $e +$f);
		if($n>0){
			return ($a+$b) / $n;
		}
		return 0;
		*/
		return ($a+$b);
		
	}
	private function setpieces_efficiency($stats){
		//((goals - goals_openplay) +(total_scoring_att - att_openplay)) / (goals + total_scoring_att)
		return (($stats['goals'] - $stats['goals_openplay']) +($stats['total_scoring_att'] - $stats['att_openplay'])) / ($stats['goals'] + $stats['total_scoring_att']);
		
	}
	private function setpieces_chances($stats){
		return intval(@$stats['att_assist_setplay']);
	}
	private function openplay_average_per_game($stats,$total_games){
		$goals = $this->getTotalValuesFromAttributes('goals_openplay',$stats);
		
		if($total_games>0){
			return $goals / $total_games;
		}
		return 0;
	}
	private function openplay_goals($stats){
		return $this->getTotalValuesFromAttributes('goals_openplay',$stats);
	}
	
	private function openplay_conversion($stats){
		$s1 = $this->getTotalValuesFromAttributes('goals_openplay',$stats);
		$s2 = $this->getTotalValuesFromAttributes('att_openplay',$stats);

		if($s2>0){
			return ($s1/$s2);
		}
		return 0;
	}
	private function openplay_frequency($stats){
		/*
		$s1 = $this->getTotalValuesFromAttributes('goals_openplay,att_openplay',$stats);
		$goals = $this->getTotalValuesFromAttributes('goals',$stats);
		$goals_openplay = $this->getTotalValuesFromAttributes('goals_openplay',$stats);
		$total_scoring_att = $this->getTotalValuesFromAttributes('total_scoring_att',$stats);
		$att_openplay = $this->getTotalValuesFromAttributes('att_openplay',$stats);
		$s2 = $this->getTotalValuesFromAttributes('goal_fastbreak,shot_fastbreak,att_fastbreak',$stats);
		$total = ($s1) / ($s1+ ($goals - $goals_openplay) + ($total_scoring_att - $att_openplay) + $s2);
		return $total;
		*/
		return intval(@$stats['att_openplay']);
	}
	private function openplay_efficiency($stats){
		$s1 = $this->getTotalValuesFromAttributes('goals_openplay,att_openplay',$stats);
		$s2 = $this->getTotalValuesFromAttributes('goals,total_scoring_att',$stats);
		
		if($s2>0){
			return ($s1/$s2);
		}
		return 0;
	}
	private function openplay_chances($stats){
		$s1 = $this->getTotalValuesFromAttributes('att_openplay',$stats);
		return $s1;
	}
	function getTotalValuesFromAttributes($str,$stats){
	    $arr = explode(",",$str);
	    $score = 0;
	    foreach($arr as $a){
	      if(isset($stats[strtolower(trim($a))])){
	        $score += $stats[strtolower(trim($a))];
	      }
	    }
	    $arr = null;
	    $str = null;
	    unset($arr);
	    unset($str);
	    return $score;
	  }
	private function getStatsValue($statsName,$stats){
		return $stats[$statsName];
	}
	private function getStats($team_id,$game_ids){
		$sql = "SELECT stats_name,SUM(stats_value) AS total 
				FROM team_stats 
				WHERE game_id IN (".$this->arrayToSql($game_ids).")
				AND team_id='{$team_id}'
				GROUP BY stats_name;";


		$rs = $this->query($sql,false);
		$stats = array();
		while(sizeof($rs)>0){
			$st = array_shift($rs);
			$stats[$st['team_stats']['stats_name']] = $st[0]['total'];
		}

		return $stats;
	}
	private function getTeamBStats($team_id,$game_ids){

		//1 get the related game_ids
		$sql = "SELECT game_id FROM matchinfo 
				WHERE (home_team = '{$team_id}' OR away_team='{$team_id}') 
				AND game_id IN (".$this->arrayToSql($game_ids).")
				";
		$rs = $this->query($sql,false);
		
		$teamB_game_ids = array();
		while(sizeof($rs)>0){
			$a = array_shift($rs);
			$teamB_game_ids[] = $a['matchinfo']['game_id'];
		}
		//2 get team B lists
		$sql = "SELECT team_id FROM team_stats 
				WHERE game_id IN (".$this->arrayToSql($teamB_game_ids).") 
				AND team_id <> '{$team_id}' GROUP BY team_id;";
		$rs = $this->query($sql,false);
		
		$teamBIds = array();
		while(sizeof($rs)>0){
			$a = array_shift($rs);
			$teamBIds[] = $a['team_stats']['team_id'];
		}

		//get the accumulated stats
		$sql = "SELECT stats_name,SUM(stats_value) AS total 
				FROM team_stats 
				WHERE game_id IN (".$this->arrayToSql($teamB_game_ids).") 
				AND team_id IN (".$this->arrayToSql($teamBIds).")
				GROUP BY stats_name;";

		$rs = $this->query($sql,false);
		$stats = array();
		while(sizeof($rs)>0){
			$st = array_shift($rs);
			$stats[$st['team_stats']['stats_name']] = $st[0]['total'];
		}

		return $stats;
	}
}