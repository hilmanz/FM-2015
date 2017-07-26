<?php
App::uses('AppModel', 'Model');
error_reporting(0);
class TeamStats extends Stats {
	public $useTable = false;
	public $useDbConfig = 'opta';

	/*
	* individual match report
	*/
	public function individualMatchReport($game_id,$team_id){
		$game_ids = array($game_id);


		$stats = $this->getStats($team_id,$game_ids);
		if(sizeof($stats)>0){
			$teamBStats = $this->getTeamBStats($team_id,$game_ids);
			$rs = array('team'=>$this->getTeam($team_id),
					'most_influential_player'=>$this->getMostInfluencePlayer($team_id,$game_ids),
					'top_assist'=>$this->top_assist($team_id,$game_ids),
					'top_scorer'=>$this->top_scorer($team_id,$game_ids),
					'dangerous_passer'=>$this->dangerous_passer($team_id,$game_ids),
					'greatest_liability'=>$this->greatest_liability($team_id,$game_ids),
					'attacking_play'=>$this->attacking_play($team_id,$game_ids,$stats),
					'attacking_style'=>$this->attacking_style($team_id,$game_ids,$stats,$teamBStats),
					'dribbling'=>$this->dribbling($team_id,$game_ids,$stats,$teamBStats),
					'passing_style'=>$this->passing_style($team_id,$game_ids,$stats,$teamBStats),
					'defending_style'=>$this->defending_style($team_id,$game_ids,$stats,$teamBStats),
					'goalkeeping'=>$this->goalkeeping($team_id,$game_ids,$stats,$teamBStats),
					'defending_strength_and_weakness'=>$this->defending_strength_and_weakness($team_id,$game_ids,$stats,$teamBStats),
					'aerial_strength'=>$this->aerial_strength($team_id,$game_ids,$stats,$teamBStats),
					'setplays'=>$this->setplays($team_id,$game_ids,$stats,$teamBStats)
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
					'attacking_play'=>$this->attacking_play($team_id,$game_ids,$stats),
					'attacking_style'=>$this->attacking_style($team_id,$game_ids,$stats,$teamBStats),
					'dribbling'=>$this->dribbling($team_id,$game_ids,$stats,$teamBStats),
					'passing_style'=>$this->passing_style($team_id,$game_ids,$stats,$teamBStats),
					'defending_style'=>$this->defending_style($team_id,$game_ids,$stats,$teamBStats),
					'goalkeeping'=>$this->goalkeeping($team_id,$game_ids,$stats,$teamBStats),
					'defending_strength_and_weakness'=>$this->defending_strength_and_weakness($team_id,$game_ids,$stats,$teamBStats),
					'aerial_strength'=>$this->aerial_strength($team_id,$game_ids,$stats,$teamBStats),
					'setplays'=>$this->setplays($team_id,$game_ids,$stats,$teamBStats)
					);

		pr($rs);
		return $rs;
	}
	private function getTeam($team_id){
		$rs = $this->query("SELECT * FROM master_team WHERE uid='{$team_id}' LIMIT 1");
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
	private function top_assist($team_id,$game_ids){
		$sql = "SELECT a.player_id,SUM(stats_value) AS total,b.name,b.position,b.jersey_num 
				FROM player_stats a
				INNER JOIN master_player b
				ON a.player_id = b.uid
				WHERE a.team_id='{$team_id}' AND stats_name='goal_assist' 
				AND game_id IN (".$this->arrayToSql($game_ids).")
				GROUP BY player_id LIMIT 5";
		$rs = $this->query($sql);
		
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
				GROUP BY player_id LIMIT 5";
		$rs = $this->query($sql);
		
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
				AND a.team_id='t1'
				GROUP BY player_id 
				ORDER BY total DESC LIMIT 5;";
		$rs = $this->query($sql);
		
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
				AND a.team_id='t1'
				GROUP BY player_id 
				ORDER BY total DESC LIMIT 5;";
		$rs = $this->query($sql);
		
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
	private function attacking_style($team_id,$game_ids,$stats,$teamB){
		$deep_cross = intval(@$teamB['crosses_18yardplus']);
		$deep_cross_avg = intval(@$teamB['crosses_18yardplus']) / (intval(@$teamB['crosses_18yardplus']) + intval(@$teamB['crosses_18yard']) +intval(@$stats['total_pull_back']) + intval(@$stats['total_through_ball']));
		$cross_from_18yd = intval(@$teamB['crosses_18yard']);
		$cross_from_18yd_avg = intval(@$teamB['crosses_18yard']) / (intval(@$teamB['crosses_18yardplus']) + intval(@$teamB['crosses_18yard']) +intval(@$stats['total_pull_back']) + intval(@$stats['total_through_ball']));

		$cutbacks = intval(@$stats['total_pull_back']);
		$cutbacks_avg = intval(@$stats['total_pull_back']) / (intval(@$teamB['crosses_18yardplus']) + intval(@$teamB['crosses_18yard']) +intval(@$stats['total_pull_back']) + intval(@$stats['total_through_ball']));
		
		$through_ball = intval(@$stats['total_through_ball']);
		$through_ball_avg = intval(@$stats['total_through_ball']) / (intval(@$teamB['crosses_18yardplus']) + intval(@$teamB['crosses_18yard']) +intval(@$stats['total_pull_back']) + intval(@$stats['total_through_ball']));
		

		$through_ball = intval(@$stats['total_through_ball']);
		$through_ball_avg = intval(@$stats['total_through_ball']) / (intval(@$teamB['crosses_18yardplus']) + intval(@$teamB['crosses_18yard']) +intval(@$stats['total_pull_back']) + intval(@$stats['total_through_ball']));
		
		$accurate_cutbacks = intval(@$stats['accurate_pull_back']);
		$accurate_cutbacks_avg = floatval(@(intval(@$stats['accurate_pull_back']) / intval(@$stats['total_pull_back'])));

		$accurate_through_ball = intval(@$stats['accurate_through_ball']);
		$accurate_through_ball_avg = floatval(@(intval(@$stats['accurate_through_ball']) / intval(@$stats['total_through_ball'])));

		$accurate_through_ball = intval(@$stats['accurate_through_ball']);
		$accurate_through_ball_avg = floatval(@(intval(@$stats['accurate_through_ball']) / intval(@$stats['total_through_ball'])));

		$chances_from_crosses = intval(@$stats['att_hd_total']);

		$shots_from_ibox = intval(@$att_ibox_blocked) + intval(@$att_ibox_goal) + intval(@$att_ibox_miss) + intval(@$att_ibox_target);
		
		$shots_from_obox = intval(@$att_obox_blocked) + intval(@$att_obox_goal) + intval(@$att_obox_miss) + intval(@$att_obox_target);
		
		$shots_from_ibox_avg = $shots_from_ibox / ($chances_from_crosses + $shots_from_ibox + $shots_from_obox);
		$shots_from_obox_avg = $shots_from_obox / ($chances_from_crosses + $shots_from_ibox + $shots_from_obox);
		$chances_from_crosses_avg = $chances_from_crosses / ($chances_from_crosses + $shots_from_ibox + $shots_from_obox);

		$goals_from_shot_ibox = intval(@$stats['att_ibox_goal']);
		$goals_from_shot_obox = intval(@$stats['att_obox_goal']);
		$goals_from_crosses = intval(@$stats['att_obox_goal']);
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

					  'shots_from_ibox'=>array("total"=>$shots_from_ibox,
					  								"average"=>$shots_from_ibox_avg),
					  'shots_from_obox'=>array("total"=>$shots_from_obox,
					  								"average"=>$shots_from_obox_avg),

					   'goals_from_shot_ibox'=>array("total"=>$goals_from_shot_ibox,
					  								"average"=>$goals_from_shot_ibox_avg),

					   'goals_from_shot_obox'=>array("total"=>$goals_from_shot_obox,
					  								"average"=>$goals_from_shot_obox_avg),

					   'goals_from_crosses'=>array("total"=>$goals_from_crosses,
					  								"average"=>$goals_from_crosses_avg),

					);
	}

	private function dribbling($team_id,$game_ids,$stats,$teamB){
		return array('beating_an_opponent'=>intval(@$stats['won_contest']),
					  'beating_last_defender'=>intval(@$stats['last_man_contest']),
					  'fouled_in_attacking_3rd'=>intval(@$stats['fouled_final_third']),
					);
	}
	private function passing_style($team_id,$game_ids,$stats,$teamB){
		$long_ball = intval(@$stats['total_longballs']);
		$short_passes = intval(@$stats['total_pass']) - intval(@$stats['total_longballs']);
		$launches = intval(@$stats['total_launches']);
		$through_balls = intval(@$stats['total_through_ball']);
		$chipped_passes = intval(@$stats['total_chipped_pass']);
		$forward_passes = intval(@$stats['total_fwd_zone_pass']);
		$total_pass = $long_ball + $short_passes + $launches + $through_balls 
						+ $chipped_passes + $forward_passes;
		$leftside_pass = intval(@$stats['leftside_pass']);
		$rightside_pass = intval(@$stats['rightside_pass']);

		//average
		$long_ball_avg = $long_ball / $total_pass;
		$short_passes_avg = $short_passes / $total_pass;
		$launches_avg = $launches / $total_pass;
		$through_balls_avg = $through_balls / $total_pass;
		$chipped_passes_avg = $chipped_passes / $total_pass;
		$forward_passes_avg = $forward_passes / $total_pass;
		
		$leftside_pass_avg = $leftside_pass / ($leftside_pass+$rightside_pass);
		$rightside_pass_avg = $rightside_pass / ($leftside_pass+$rightside_pass);

		//accuracy

		$long_ball_acc = ($long_ball > 0) ? intval(@$stats['accurate_long_balls']) / $long_ball : 0;
		$short_passes_acc = 1;
		$launches_acc = intval(@$stats['accurate_launches']) / $launches;
		$through_balls_acc =  intval(@$stats['accurate_through_ball']) / $through_balls;
		$chipped_passes_acc =  intval(@$stats['accurate_chipped_pass']) / $chipped_passes;
		$forward_passes_acc =  intval(@$stats['accurate_fwd_zone_pass']) / $forward_passes;

		return array('long_ball'=>array('total'=>$long_ball,
										 'average'=>$long_ball_avg,
										 'accuracy'=>$long_ball_acc),

					'short_passes'=>array('total'=>$short_passes,
										 'average'=>$short_passes_avg,
										 'accuracy'=>$short_passes_acc),


					'launches'=>array('total'=>$launches,
										 'average'=>$launches_avg,
										 'accuracy'=>$launches_acc),

					'through_balls'=>array('total'=>$through_balls,
										 'average'=>$through_balls_avg,
										 'accuracy'=>$through_balls_acc),

					'chipped_passes'=>array('total'=>$chipped_passes,
										 'average'=>$chipped_passes_avg,
										 'accuracy'=>$chipped_passes_acc),

					'forward_passes'=>array('total'=>$forward_passes,
										 'average'=>$forward_passes_avg,
										 'accuracy'=>$chipped_passes_acc),


					'leftside_pass'=>array('total'=>$leftside_pass,
										 'average'=>$leftside_pass_avg,
										 'accuracy'=>0),

					'rightside_pass'=>array('total'=>$rightside_pass,
										 'average'=>$rightside_pass_avg,
										 'accuracy'=>0),


				);



	}
	private function setplays($team_id,$game_ids,$stats,$teamB){
		$corners_won = intval(@$stats['won_corners']);
		$freekicks_won = intval(@$stats['fk_foul_won']);
		$corner_delivery = intval(@$stats['accurate_cross']) - intval(@$stats['accurate_cross_nocorner']);
		$freekick_delivery = intval(@$stats['accurate_freekick_cross']);
		$direct_freekicks = intval(@$stats['att_freekick_target']);
		
		//accuracy
		$corners_won_acc = 0;
		$freekicks_won_acc = 0;
		$corner_delivery_acc = (intval(@$stats['accurate_cross']) - intval(@$stats['accurate_cross_nocorner'])) / intval(@$stats['total_corners_intobox']);
		$freekick_delivery_acc = intval(@$stats['accurate_freekick_cross']) / intval(@$stats['freekick_cross']);
		$direct_freekicks_acc = intval(@$stats['att_freekick_target']) / intval(@$stats['att_freekick_total']);
		
		//chance ratio
		$corners_won_ratio = 0;
		$freekicks_won_ratio = 0;
		$corner_delivery_ratio = intval(@$stats['att_corner']) / (intval(@$stats['accurate_cross']) - intval(@$stats['accurate_cross_nocorner']));
		$freekick_delivery_ratio = (intval(@$stats['att_setpiece']) - intval(@$stats['att_corner']))/ intval(@$stats['accurate_freekick_cross']);
		$direct_freekicks_ratio = intval(@$stats['att_freekick_goal']) / intval(@$stats['att_freekick_total']);

		return array('corners_won'=>array('total'=>$corners_won,
										 'accuracy'=>$corners_won_acc,
										  'chance_ratio'=>$corners_won_ratio),


						'freekicks_won'=>array('total'=>$freekicks_won,
										 'accuracy'=>$freekicks_won_acc,
										  'chance_ratio'=>$freekicks_won_ratio),

						'corner_delivery'=>array('total'=>$corner_delivery,
										 'accuracy'=>$corner_delivery_acc,
										  'chance_ratio'=>$corner_delivery_ratio),

						'freekick_delivery'=>array('total'=>$freekick_delivery,
										 'accuracy'=>$freekick_delivery_acc,
										  'chance_ratio'=>$freekick_delivery_ratio),

						'direct_freekicks'=>array('total'=>$direct_freekicks,
										 'accuracy'=>$direct_freekicks_acc,
										  'chance_ratio'=>$direct_freekicks_ratio),

					
					
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
		$gk_highclaims_avg = intval(@$stats['good_high_claim']) / intval(@$stats['total_high_claim']);
		$gk_crosses_not_claimed_avg = (intval(@$stats['total_claim']) > 0) ? intval(@$stats['cross_not_claimed']) / intval(@$stats['total_claim']) : 0;


		return array('aerial_duels_won'=>array('total'=>$aerial_duels_won,
										 'average'=>$aerial_duels_won_avg),

					'header_at_goals'=>array('total'=>$header_at_goals,
										 'average'=>$header_at_goals_avg),

					'effective_clearance'=>array('total'=>$effective_clearance,
										 'average'=>$effective_clearance_avg),

					'flick_ons'=>array('total'=>$flick_ons,
										 'average'=>$flick_ons_avg),

					'gk_highclaims'=>array('total'=>$gk_highclaims,
										 'average'=>$gk_highclaims_avg),

					'gk_crosses_not_claimed'=>array('total'=>$gk_crosses_not_claimed,
										 'average'=>$gk_crosses_not_claimed_avg),
					
				);
	}
	private function defending_style($team_id,$game_ids,$stats,$teamB){
		$recover_in_attacking_3rd = intval(@$stats['$poss_won_att_3rd']);
		$recover_in_midfield = intval(@$stats['poss_won_mid_3rd']);
		$recover_in_defending_3rd = intval(@$stats['poss_won_def_3rd']);
		$recover_in_attacking_3rd_avg = $recover_in_attacking_3rd / ($recover_in_attacking_3rd + $recover_in_midfield + $recover_in_defending_3rd);
		$recover_in_midfield_avg = $recover_in_midfield / ($recover_in_attacking_3rd + $recover_in_midfield + $recover_in_defending_3rd);
		$recover_in_defending_3rd_avg = $recover_in_defending_3rd / ($recover_in_attacking_3rd + $recover_in_midfield + $recover_in_defending_3rd);

		

		return array('recover_in_attacking_3rd'=>array('total'=>$recover_in_attacking_3rd,
										 'average'=>$recover_in_attacking_3rd_avg),
					 'recover_in_midfield'=>array('total'=>$recover_in_midfield,
										 'average'=>$recover_in_midfield_avg),
					 'recover_in_defending_3rd'=>array('total'=>$recover_in_defending_3rd,
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
		

		$shot_saved_avg = intval(@$stats['saves']) /  intval(@$stats['ontarget_scoring_att']);
		$one_on_one_avg = (intval(@$stats['total_one_on_one']) > 0 ) ? intval(@$stats['good_one_on_one']) /  intval(@$stats['total_one_on_one']) : 0;
		$keeper_sweeper_avg = intval(@$stats['accurate_keeper_sweeper']) /  intval(@$stats['total_keeper_sweeper']);
		$gk_smother_avg = 1;
		$gk_highclaims_avg = (intval(@$stats['total_high_claim']) > 0) ? intval(@$stats['good_high_claim']) /  intval(@$stats['total_high_claim']) : 0;
		$gk_crosses_not_claimed_avg = (intval(@$stats['total_claim']) > 0) ? intval(@$stats['cross_not_claimed']) /  intval(@$stats['total_claim']) : 0;


		return array('shot_saved'=>array('total'=>$shot_saved,
										 'average'=>$shot_saved_avg),

					  'one_on_one'=>array('total'=>$one_on_one,
										 'average'=>$one_on_one_avg),

					  'keeper_sweeper'=>array('total'=>$keeper_sweeper,
										 'average'=>$keeper_sweeper_avg),

					  'gk_smother'=>array('total'=>$gk_smother,
										 'average'=>$gk_smother_avg),

					  'gk_highclaims'=>array('total'=>$gk_highclaims,
										 'average'=>$gk_highclaims_avg),

					  'gk_crosses_not_claimed'=>array('total'=>$gk_crosses_not_claimed,
										 'average'=>$gk_crosses_not_claimed_avg),
					 
				);
	}
	private function defending_strength_and_weakness($team_id,$game_ids,$stats,$teamB){
		$duels_won = intval(@$stats['duel_won']);
		$tackling_won = intval(@$stats['won_tackle']);
		$challenge_lost = intval(@$stats['challenge_lost']);
		$head_clearance = intval(@$stats['effective_head_clearance']);
		$attempts_conceded_inbox = intval(@$stats['attempts_conceded_ibox']);
		$attempts_conceded_outside_box = intval(@$stats['attempts_conceded_obox']);
		$attempts_conceded_from_fastbreak = intval(@$teamB['shot_fastbreak']);
		$attempts_conceded_from_setpieces = intval(@$teamB['att_setpiece']);
		
		$duels_won_avg = intval(@$stats['duel_won']) / (intval(@$stats['duel_won'])+intval(@$stats['duel_lost']));
		$tackling_won_avg = intval(@$stats['won_tackle']) / intval(@$stats['total_tackle']);
		$challenge_lost_avg = 0;
		$head_clearance_avg = intval(@$stats['effective_head_clearance']) / intval(@$stats['head_clearance']);


		$attempts_conceded_inbox_avg = $attempts_conceded_inbox / ($attempts_conceded_inbox + $attempts_conceded_outside_box);
		$attempts_conceded_outside_box_avg = $attempts_conceded_outside_box / ($attempts_conceded_inbox + $attempts_conceded_outside_box);


		$attempts_conceded_from_fastbreak_avg = (intval(@$teamB['shot_fastbreak']) + intval(@$stats['att_fastbreak'])) / intval(@$teamB['total_scoring_att']);
		$attempts_conceded_from_setpieces_avg = intval(@$teamB['att_setpiece']) / intval(@$teamB['total_scoring_att']);

		$error_lead_to_shot = intval(@$stats['error_lead_to_shot']);
		$error_lead_to_goal = intval(@$stats['error_lead_to_goal']);
		$total_errors = intval(@$stats['error_lead_to_shot']) + intval(@$stats['error_lead_to_goal']) + intval(@$stats['unsuccessful_touch']) + intval(@$stats['dispossessed']);
		$penalty_conceded = intval(@$stats['penalty_conceded']);
		$fouls_conceded_in_attacking_3rd = intval(@$teamB['fouled_final_third']);
		
		return array('duels_won'=>array('total'=>$duels_won,
										 'average'=>$duels_won_avg),

						'tackling_won'=>array('total'=>$tackling_won,
										 'average'=>$tackling_won_avg),

						'challenge_lost'=>array('total'=>$challenge_lost,
										 'average'=>$challenge_lost_avg),

						'head_clearance'=>array('total'=>$head_clearance,
										 'average'=>$head_clearance_avg),

						'attempts_conceded_inbox'=>array('total'=>$attempts_conceded_inbox,
										 'average'=>$attempts_conceded_inbox_avg),

						'attempts_conceded_outside_box'=>array('total'=>$attempts_conceded_outside_box,
										 'average'=>$attempts_conceded_outside_box_avg),

						'attempts_conceded_from_fastbreak'=>array('total'=>$attempts_conceded_from_fastbreak,
										 'average'=>$attempts_conceded_from_fastbreak_avg),

						'attempts_conceded_from_setpieces'=>array('total'=>$attempts_conceded_from_setpieces,
										 'average'=>$attempts_conceded_from_setpieces_avg),

						'error_lead_to_shot'=>array('total'=>$error_lead_to_shot,
										 'average'=>0),

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
							AND team_id='{$team_id}' GROUP BY game_id) a;");
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

		return array('openplay'=>$openplay,
					 'setpieces'=>$setpieces,
					 'counter_attack'=>$counter);
	}
	private function counter_average_per_game($stats,$total_games){
		$chances = intval(@$stats['total_fastbreak']);
		return $chances/$total_games;

	}
	private function counter_conversion($stats){
		return intval(@$stats['goal_fastbreak'])/intval(@$stats['att_fastbreak']);
	}
	private function counter_chances($stats){
		return intval(@$stats['shot_fastbreak']);
	}
	private function counter_efficiency($stats){
		return (intval(@$stats['goal_fastbreak']) + intval(@$stats['shot_fastbreak']) + intval(@$stats['att_fastbreak'])) / (intval(@$stats['goals']) + intval(@$stats['total_scoring_att']));

	}
	private function counter_frequency($stats){
		return (intval(@$stats['goal_fastbreak']) + intval(@$stats['shot_fastbreak']) +intval(@$stats['att_fastbreak']))/ ((intval(@$stats['goals_openplay']) + intval(@$stats['att_openplay'])) + ((intval(@$stats['goals']) - intval(@$stats['goals_openplay'])) +(intval(@$stats['total_scoring_att']) - intval(@$stats['att_openplay'])) )+(intval(@$stats['goal_fastbreak']) + intval(@$stats['shot_fastbreak']) +intval(@$stats['att_fastbreak'])));

	}
	private function setpieces_average_per_game($stats,$total_games){
		$chances = $this->setpieces_chances($stats);
		return $chances/$total_games;

	}
	private function setpieces_conversion($stats){
		return ($stats['goals'] - $stats['goals_openplay']) / ($stats['total_scoring_att'] - $stats['att_openplay']);
	}
	private function setpieces_goals($stats){
		return ($stats['goals'] - $stats['goals_openplay']);

	}
	private function setpieces_frequency($stats){
		//((goals - goals_openplay) +(total_scoring_att - att_openplay) / ((goals_openplay + att_openplay) + ((goals - goals_openplay) +(total_scoring_att - att_openplay) +(goal_fastbreak + shot_fastbreak +att_fastbreak))
		$a = $this->getStatsValue('goals',$stats) - $this->getStatsValue('goals_openplay',$stats);
		$b = $this->getStatsValue('total_scoring_att',$stats) - $this->getStatsValue('att_openplay',$stats);
		$c = $this->getTotalValuesFromAttributes('goals_openplay,att_openplay',$stats);
		$d = $this->getStatsValue('goals',$stats) - $this->getStatsValue('goals_openplay',$stats);
		$e = $this->getStatsValue('total_scoring_att',$stats) - $this->getStatsValue('att_openplay',$stats);
		$f = $this->getTotalValuesFromAttributes('goal_fastbreak,shot_fastbreak,att_fastbreak',$stats);
		$n = ($c + $d + $e +$f);
		if($n>0){
			return ($a+$b) / $n;
		}
		return 0;
	}
	private function setpieces_efficiency($stats){
		//((goals - goals_openplay) +(total_scoring_att - att_openplay)) / (goals + total_scoring_att)
		return (($stats['goals'] - $stats['goals_openplay']) +($stats['total_scoring_att'] - $stats['att_openplay'])) / ($stats['goals'] + $stats['total_scoring_att']);
		
	}
	private function setpieces_chances($stats){
		return $stats['total_scoring_att'] - $stats['att_openplay'];
	}
	private function openplay_average_per_game($stats,$total_games){
		$goals = $this->getTotalValuesFromAttributes('goals',$stats);
		
		if($total_games>0){
			return $goals / $total_games;
		}
		return 0;
	}
	private function openplay_goals($stats){
		return $this->getTotalValuesFromAttributes('goals_openplay',$stats);
	}
	
	private function openplay_conversion($stats){
		$s1 = $this->getTotalValuesFromAttributes('goals',$stats);
		$s2 = $this->getTotalValuesFromAttributes('total_scoring_att',$stats);
		if($s2>0){
			return ($s1/$s2);
		}
		return 0;
	}
	private function openplay_frequency($stats){
		$s1 = $this->getTotalValuesFromAttributes('goals_openplay,att_openplay',$stats);
		$goals = $this->getTotalValuesFromAttributes('goals',$stats);
		$goals_openplay = $this->getTotalValuesFromAttributes('goals_openplay',$stats);
		$total_scoring_att = $this->getTotalValuesFromAttributes('total_scoring_att',$stats);
		$att_openplay = $this->getTotalValuesFromAttributes('att_openplay',$stats);
		$s2 = $this->getTotalValuesFromAttributes('goal_fastbreak,shot_fastbreak,att_fastbreak',$stats);
		$total = ($s1) / ($s1+ ($goals - $goals_openplay) + ($total_scoring_att - $att_openplay) + $s2);
		return $total;
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


		$rs = $this->query($sql);
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
		$rs = $this->query($sql);
		
		$teamB_game_ids = array();
		while(sizeof($rs)>0){
			$a = array_shift($rs);
			$teamB_game_ids[] = $a['matchinfo']['game_id'];
		}
		//2 get team B lists
		$sql = "SELECT team_id FROM team_stats 
				WHERE game_id IN (".$this->arrayToSql($teamB_game_ids).") 
				AND team_id <> '{$team_id}' GROUP BY team_id;";
		$rs = $this->query($sql);
		
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

		$rs = $this->query($sql);
		$stats = array();
		while(sizeof($rs)>0){
			$st = array_shift($rs);
			$stats[$st['team_stats']['stats_name']] = $st[0]['total'];
		}

		return $stats;
	}
}