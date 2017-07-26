<?php
class StatsUpdateShell extends AppShell{
	var $uses = array('Matchinfo');

	public function main() {
       //$this->dummy(); //disable these on production
       $this->process_queue();
  }
  private function process_queue(){
    $queue = $this->getNewQueue();
    CakeLog::write('stats_update', 'processing queue : '.sizeof($queue));

    if(sizeof($queue)>0){
      for($i=0;$i<sizeof($queue);$i++){
        CakeLog::write('stats_update','updating stats #'.$queue[$i]['a']['game_id']);
        $this->addToQueue($queue[$i]['a']);
        $this->generateStats($queue[$i]['a']);
        if($queue[$i]['a']['period']=='FullTime'){
          CakeLog::write('stats_update','Flag stats #'.$queue[$i]['a']['game_id']);
          $this->flagDone($queue[$i]['a']['game_id']);
        }else{
          CakeLog::write('stats_update','this is ongoing stats #'.$queue[$i]['a']['game_id']);
        }
      }
      //$this->process_queue();
    }
  }
  private function getNewQueue(){
    $sql = "SELECT game_id,home_team,away_team,period 
            FROM ".Configure::read('optadb').".matchinfo a 
            WHERE competition_id = '".Configure::read('competition_id')."' 
            AND season_id = '".Configure::read('season_id')."' 
            AND NOT EXISTS
            (SELECT 1 FROM statsjob_queue b 
            WHERE b.game_id = a.game_id AND n_status = 1 LIMIT 1) LIMIT 20";
    print $sql;
    
   
    $rs = $this->Matchinfo->query($sql,false);

    
    return $rs;
  }
  private function addToQueue($queue){
    $sql = "INSERT IGNORE INTO ".Configure::read('optadb').".statsjob_queue
            (game_id,update_time,n_status)
            VALUES
            ('{$queue['game_id']}',NOW(),0)";
    
    $this->Matchinfo->query($sql);
  }
  private function flagDone($game_id){
    $sql = "UPDATE ".Configure::read('optadb').".statsjob_queue
            SET n_status=1
            WHERE game_id = '{$game_id}'";
    
    $this->Matchinfo->query($sql);
  }

  private function generateStats($game){
    $this->out('processing stats for game #'.$game['game_id']);
    $home_id = $game['home_team'];
    $away_id = $game['away_team'];
    
    //home stats
    $this->calculateTeamStats($game['game_id'],$home_id,$away_id);

    //away stats
    $this->calculateTeamStats($game['game_id'],$away_id,$home_id);
  }

  private function calculateTeamStats($game_id,$teamA,$teamB){
    $this->out('generating stats for team#'.$teamA);
    //get players
    $players = $this->getPlayers($game_id,$teamA);
    foreach($players as $player){
      $this->processPlayer($game_id,$player['player_stats']['player_id'],$teamA,$teamB);
    }
    //merge the accumulative stats from player into team stats
    $this->mergePlayerStatsToTeamStats($game_id,$teamA);
    //add def_goals,mid_goals, and fw_goals
    $this->mergeGoalGroups($game_id,$teamA);
    //calculate team summaries
    $this->processTeamStats($game_id,$teamA,$teamB);
  }
  private function mergeGoalGroups($game_id,$team_id){
    $this->out('merge goals');
    $sql = "
            INSERT INTO team_stats
            (game_id,team_id,stats_name,stats_value)
            SELECT game_id,a.team_id,'forward_goals' AS stats_name,SUM(stats_value) AS stats_value 
            FROM player_stats a 
            INNER JOIN master_player b 
            ON a.player_id = b.uid
            WHERE game_id='{$game_id}' 
            AND a.team_id='{$team_id}' 
            AND stats_name='goals' 
            AND b.position = 'Forward'
            GROUP BY stats_name
            ON DUPLICATE KEY UPDATE
            stats_value = VALUES(stats_value)";
    $this->Matchinfo->query($sql,false);

    $sql = "
            INSERT INTO team_stats
            (game_id,team_id,stats_name,stats_value)
            SELECT game_id,a.team_id,'midfielder_goals' AS stats_name,SUM(stats_value) AS stats_value 
            FROM player_stats a 
            INNER JOIN master_player b 
            ON a.player_id = b.uid
            WHERE game_id='{$game_id}' 
            AND a.team_id='{$team_id}' 
            AND stats_name='goals' 
            AND b.position = 'Midfielder'
            GROUP BY stats_name
            ON DUPLICATE KEY UPDATE
            stats_value = VALUES(stats_value)";
    $this->Matchinfo->query($sql);


    $sql = "
            INSERT INTO team_stats
            (game_id,team_id,stats_name,stats_value)
            SELECT game_id,a.team_id,'defender_goals' AS stats_name,SUM(stats_value) AS stats_value 
            FROM player_stats a 
            INNER JOIN master_player b 
            ON a.player_id = b.uid
            WHERE game_id='{$game_id}' 
            AND a.team_id='{$team_id}' 
            AND stats_name='goals' 
            AND b.position = 'Defender'
            GROUP BY stats_name
            ON DUPLICATE KEY UPDATE
            stats_value = VALUES(stats_value)";

    $this->Matchinfo->query($sql);

     $sql = "
            INSERT INTO team_stats
            (game_id,team_id,stats_name,stats_value)
            SELECT game_id,a.team_id,'gk_goals' AS stats_name,SUM(stats_value) AS stats_value 
            FROM player_stats a 
            INNER JOIN master_player b 
            ON a.player_id = b.uid
            WHERE game_id='{$game_id}' 
            AND a.team_id='{$team_id}' 
            AND stats_name='goals' 
            AND b.position = 'Goalkeeper'
            GROUP BY stats_name
            ON DUPLICATE KEY UPDATE
            stats_value = VALUES(stats_value)";
            
    $this->Matchinfo->query($sql);
  }
  private function mergePlayerStatsToTeamStats($game_id,$team_id){
    $this->out('merge player stats into team stats');
    $sql = "INSERT IGNORE INTO team_stats
            (game_id,team_id,stats_name,stats_value)
            SELECT game_id,team_id,stats_name,SUM(stats_value) AS stats_value
            FROM player_stats WHERE game_id='{$game_id}' AND team_id='{$team_id}'
            AND stats_name IN (
              'accurate_chipped_pass',
              'accurate_cross',
              'accurate_cross_nocorner',
              'accurate_flick_on',
              'accurate_freekick_cross',
              'accurate_fwd_zone_pass',
              'accurate_keeper_sweeper',
              'accurate_launches',
              'accurate_layoffs',
              'accurate_long_balls',
              'accurate_pass',
              'accurate_through_ball',
              'aerial_lost',
              'aerial_won',
              'att_assist_openplay',
              'att_assist_setplay',
              'att_corner',
              'att_fastbreak',
              'att_freekick_goal',
              'att_freekick_target',
              'att_freekick_total',
              'att_hd_goal',
              'att_hd_miss',
              'att_hd_target',
              'att_hd_total',
              'att_ibox_blocked',
              'att_ibox_goal',
              'att_ibox_miss',
              'att_ibox_target',
              'att_obox_blocked',
              'att_obox_miss',
              'att_obox_target',
              'att_one_on_one',
              'att_openplay',
              'att_pen_goal',
              'att_pen_miss',
              'att_pen_target',
              'att_setpiece',
              'ball_recovery',
              'blocked_cross',
              'blocked_scoring_att',
              'challenge_lost',
              'clean_sheet',
              'cross_not_claimed',
              'crosses_18yard',
              'crosses_18yardplus',
              'dangerous_play',
              'defender_goals',
              'dispossessed',
              'dive_catch',
              'dive_save',
              'diving_save',
              'duel_lost',
              'duel_won',
              'effective_blocked_cross',
              'effective_clearance',
              'effective_head_clearance',
              'error_lead_to_goal',
              'error_lead_to_shot',
              'fk_foul_lost',
              'fk_foul_won',
              'fouled_final_third',
              'freekick_cross',
              'gk_smother',
              'goal_assist',
              'goal_assist_deadball',
              'goal_assist_openplay',
              'goal_assist_setplay',
              'goals',
              'good_claim',
              'good_high_claim',
              'good_one_on_one',
              'head_clearance',
              'head_pass',
              'hit_woodwork',
              'interception',
              'interception_won',
              'interceptions_in_box',
              'last_man_contest',
              'last_man_tackle',
              'long_pass_own_to_opp_success',
              'long_pass_own_to_opp',
              'offside_provoked',
              'offtarget_att_assist',
              'ontarget_att_assist',
              'ontarget_scoring_att',
              'outfielder_block',
              'penalty_save',
              'poss_lost_ctrl',
              'post_scoring_att',
              'punches',
              'red_card',
              'saves',
              'second_yellow',
              'shot_fastbreak',
              'shot_off_target',
              'six_yard_block',
              'stand_catch',
              'stand_save',
              'successful_final_third_passes',
              'touches',
              'unsuccessful_touch',
              'won_contest',
              'won_corners',
              'won_tackle',
              'yellow_card',
              'big_chance_created',
              'big_chance_missed',
              'big_chance_scored',
              'final_third_entries',
              'final_third_entry',
              'goal_fastbreak',
              'goals_conceded',
              'goals_conceded_ibox',
              'goals_conceded_obox',
              'goals_openplay',
              'leftside_pass',
              'passes_left',
              'passes_right',
              'pen_area_entries',
              'pen_goals_conceded',
              'penalty_conceded',
              'penalty_faced',
              'penalty_won',
              'poss_lost_all',
              'poss_won_att_3rd',
              'poss_won_def_3rd',
              'poss_won_mid_3rd',
              'rightside_pass',
              'forward_goals',
              'midfielder_goals',
              'possession_percentage',
              'total_attacking_pass',
              'total_att_assist',
              'total_chipped_pass',
              'total_claim',
              'total_clearance',
              'total_contest',
              'total_corners_intobox',
              'total_cross',
              'total_cross_nocorner',
              'total_fastbreak',
              'total_flick_on',
              'total_fwd_zone_pass',
              'total_high_claim',
              'total_keeper_sweeper',
              'total_launches',
              'total_layoffs',
              'total_long_balls',
              'total_offside',
              'total_one_on_one',
              'total_pass',
              'total_pull_back',
              'total_red_card',
              'total_scoring_att',
              'total_tackle',
              'total_through_ball',
              'total_yel_card'
            )
            GROUP BY stats_name";
    $this->out($sql);
    $rs = $this->Matchinfo->query($sql,false);
    $this->out($rs);
  }
  private function processTeamStats($game_id,$team_id,$teamB){
    $this->out('generating summaries');
    $stats = $this->Matchinfo->query("SELECT stats_name,stats_value FROM team_stats 
                                      WHERE game_id='{$game_id}' AND team_id='{$team_id}';",false);
    $statsB = $this->Matchinfo->query("SELECT stats_name,stats_value FROM team_stats 
                                      WHERE game_id='{$game_id}' AND team_id='{$teamB}';",false);
    $team_stats = array();
    while(sizeof($stats)>0){
      $st = array_shift($stats);
      $team_stats[$st['team_stats']['stats_name']] = $st['team_stats']['stats_value'];
    }
    $team_statsB = array();
    while(sizeof($statsB)>0){
      $st = array_shift($statsB);
      $team_statsB[$st['team_stats']['stats_name']] = $st['team_stats']['stats_value'];
    }
    //let's get the stats one by one
    $team['chances_created'] = $this->team_chances_created($team_stats);
    $team['goals'] = $this->team_goals($team_stats);
    $team['goals_conceded'] = $this->team_goals_conceded($team_stats);
    $team['chances_conceded'] = $this->team_chances_conceded($team_stats,$team_statsB);
    $team['attack_effeciency'] = $this->team_attack_effeciency($team_stats);
    $team['defense_effeciency'] = $this->team_defense_effeciency($team_stats);
    $team['ball_recovery'] = $this->getTotalValuesFromAttributes('ball_recovery',$team_stats);
    $team['duels_won'] = $this->getTotalValuesFromAttributes('duel_won',$team_stats);
    $team['duels_lost'] = $this->getTotalValuesFromAttributes('duel_lost',$team_stats);
    $team['challenge_won_ratio'] = $this->team_challenge_won_ratio($team_stats);
    $team['error_led_to_goals'] = $this->getTotalValuesFromAttributes('error_lead_to_goal',$team_stats);
    $team['error_led_to_shots'] = $this->getTotalValuesFromAttributes('error_lead_to_shot',$team_stats);
    $team['poor_control'] = $this->getTotalValuesFromAttributes('unsuccessful_touch',$team_stats);

    $team['counter_attack_goals'] = $this->getTotalValuesFromAttributes('goal_fastbreak',$team_stats);
    $team['counter_attack_shots'] = $this->getTotalValuesFromAttributes('att_fastbreak',$team_stats);
    $team['counter_attacks'] = $this->getTotalValuesFromAttributes('total_fastbreak',$team_stats);

    $team['counter_attack_effeciency'] = $this->team_counter_attack_effeciency($team_stats);

    $team['aerial_duels_won'] = $this->getTotalValuesFromAttributes('aerial_won',$team_stats);
    $team['headers_on_goal'] = $this->getTotalValuesFromAttributes('att_hd_goal',$team_stats);
    $team['headed_clearance'] = $this->getTotalValuesFromAttributes('effective_head_clearance',$team_stats);
    $team['crosses_dealt'] = $this->getTotalValuesFromAttributes('good_high_claim',$team_stats);
    $team['aerial_effeciency'] = $this->team_aerial_effeciency($team_stats);
    $team['fouling'] = $this->team_fouling($team_stats);

    $sql = "
    INSERT INTO master_team_summary
    (game_id,
    team_id,
    chances_created,
    goals,
    goals_conceded,
    chances_conceded,
    attack_effeciency,
    defense_effeciency,
    ball_recovery,
    duels_won,
    challenge_won_ratio,
    error_led_to_goals,
    error_led_to_shots,
    poor_control,
    counter_attack_goals,
    counter_attack_shots,
    counter_attacks,
    counter_attack_effeciency,
    aerial_duels_won,
    headers_on_goal,
    headed_clearance,
    crosses_dealt,
    aerial_effenciency,
    fouling,
    last_update)
    VALUES
    ('{$game_id}','{$team_id}',
     '{$team['chances_created']}',
     '{$team['goals']}',
     '{$team['goals_conceded']}',
     '{$team['chances_conceded']}',
     '{$team['attack_effeciency']}',
     '{$team['defense_effeciency']}',
     '{$team['ball_recovery']}',
     '{$team['duels_won']}',
     '{$team['challenge_won_ratio']}',
     '{$team['error_led_to_goals']}',
     '{$team['error_led_to_shots']}',
     '{$team['poor_control']}',
     '{$team['counter_attack_goals']}',
     '{$team['counter_attack_shots']}',
     '{$team['counter_attacks']}',
     '{$team['counter_attack_effeciency']}',
     '{$team['aerial_duels_won']}',
     '{$team['headers_on_goal']}',
     '{$team['headed_clearance']}',
     '{$team['crosses_dealt']}',
     '{$team['aerial_effeciency']}',
     '{$team['fouling']}',
      NOW()
     )
    ON DUPLICATE KEY UPDATE
    chances_created = VALUES(chances_created),
    goals = VALUES(goals),
    goals_conceded = VALUES(goals_conceded),
    chances_conceded = VALUES(chances_conceded),
    attack_effeciency = VALUES(attack_effeciency),
    defense_effeciency = VALUES(defense_effeciency),
    ball_recovery = VALUES(ball_recovery),
    duels_won = VALUES(duels_won),
    challenge_won_ratio = VALUES(challenge_won_ratio),
    error_led_to_goals = VALUES(error_led_to_goals),
    error_led_to_shots = VALUES(error_led_to_shots),
    poor_control = VALUES(poor_control),
    counter_attack_goals = VALUES(counter_attack_goals),
    counter_attack_shots = VALUES(counter_attack_shots),
    counter_attacks = VALUES(counter_attacks),
    counter_attack_effeciency = VALUES(counter_attack_effeciency),
    aerial_duels_won = VALUES(aerial_duels_won),
    headers_on_goal = VALUES(headers_on_goal),
    headed_clearance = VALUES(headed_clearance),
    crosses_dealt = VALUES(crosses_dealt),
    fouling = VALUES(fouling),
    aerial_effenciency = VALUES(aerial_effenciency);

    ";

    $this->Matchinfo->query($sql);

  }
  private function team_fouling($stats){
    $p1 = "fk_foul_lost,dangerous_play";
    $score1 = $this->getTotalValuesFromAttributes($p1,$stats);

    $p2 = "total_tackle";
    $score2 = $this->getTotalValuesFromAttributes($p2,$stats);

    if($score2>0){
      return $score1 / $score2;
    }
    return 0;
  }
  private function team_aerial_effeciency($stats){
    $p1 = "aerial_won,att_hd_goal,att_hd_target,effective_head_clearance,good_high_claim";
    $score1 = $this->getTotalValuesFromAttributes($p1,$stats);

    $p2 = "aerial_won,aerial_lost,att_hd_total,head_clearance,total_high_claim";
    $score2 = $this->getTotalValuesFromAttributes($p2,$stats);

    if($score2>0){
      return $score1 / $score2;
    }
    return 0;
  }
  private function team_counter_attack_effeciency($stats){
    $p1 = "goal_fastbreak";
    $score1 = $this->getTotalValuesFromAttributes($p1,$stats);

    $p2 = "att_fastbreak";
    $score2 = $this->getTotalValuesFromAttributes($p2,$stats);

    $p3 = "total_fastbreak";
    $score3 = $this->getTotalValuesFromAttributes($p3,$stats);

    if($score3 > 0){
      return (($score1+$score2) / $score3);
    }
    return 0;
  }
  private function team_challenge_won_ratio($stats){
    $p1 = "duel_won";
    $score1 = $this->getTotalValuesFromAttributes($p1,$stats);

    $p2 = "duel_lost";
    $score2 = $this->getTotalValuesFromAttributes($p2,$stats);

    $total = $score1+$score2;
    
    if($total>0){
      return ($score1 / $total);
    }else{
      return 0;
    }
   
  }
  private function team_attack_effeciency($stats){
    $p1 = "accurate_fwd_zone_pass,goal_assist,goals,ontarget_scoring_att,big_chance_created,big_chance_scored,big_chance_missed,accurate_cross,accurate_through_ball,aerial_won,second_goal_assist,total_att_assist,total_attacking_pass,won_contest,penalty_won,fouled_final_third,last_man_contest,accurate_pull_back";
    $score1 = $this->getTotalValuesFromAttributes($p1,$stats);
    return $score1;
  }
  private function team_defense_effeciency($stats){
    $p1 = "interception_won,won_tackle,outfielder_block,effective_clearance,last_man_tackle,interceptions_in_box,offside_provoked,aerial_won,accurate_launches,ball_recovery,clearance_off_line";
    $score1 = $this->getTotalValuesFromAttributes($p1,$stats);
    return $score1;
  }
  private function team_chances_conceded($stats,$statsB){
    /*Chances Conceded -->
    +Attempts Conceded inbox
    +Attempts conceded outside the box 
    +Attempts conceded from fastbreak 
    +Attempts conceded from Set Pieces
    */

    $p1 = "attempts_conceded_ibox,attempts_conceded_obox";
    $p2 = "shot_fastbreak";
    $p3 = "att_setpiece";
    $score1 = $this->getTotalValuesFromAttributes($p1,$stats);
    $score2 = $this->getTotalValuesFromAttributes($p2,$statsB);
    $score3 = $this->getTotalValuesFromAttributes($p3,$statsB);
    
    return ($score1+$score2+$score3);
  }
  private function team_chances_created($stats){
    $p1 = "total_scoring_att";
    $score1 = $this->getTotalValuesFromAttributes($p1,$stats);
    return $score1;
  }
  private function team_goals($stats){
    $p1 = "goals";
    $score1 = $this->getTotalValuesFromAttributes($p1,$stats);
    return $score1;
  }
  private function team_goals_conceded($stats){
    $p1 = "goals_conceded";
    $score1 = $this->getTotalValuesFromAttributes($p1,$stats);
    return $score1;
  }
  private function processPlayer($game_id,$player_id,$teamA,$teamB){
     $this->out('processing player #'.$player_id);

     $stats = $this->Matchinfo->query("SELECT stats_name,stats_value 
                                      FROM player_stats 
                                      WHERE game_id='{$game_id}' AND 
                                      team_id='{$teamA}' AND 
                                      player_id = '{$player_id}' 
                                      LIMIT 1000;",
                                      false);
     $player_stats = array();
     while(sizeof($stats)>0){
        $st = array_shift($stats);
        $player_stats[$st['player_stats']['stats_name']] = $st['player_stats']['stats_value'];
     }
     $player['most_influence'] = $this->most_influence($game_id,$teamA,$player_id,$player_stats);
     $player['def_influence'] = $this->def_influence($game_id,$teamA,$player_id,$player_stats);
     $player['mid_influence'] = $this->mid_influence($game_id,$teamA,$player_id,$player_stats);
     $player['fw_influence'] = $this->fw_influence($game_id,$teamA,$player_id,$player_stats);
     $player['def_score'] = $this->def_score($game_id,$teamA,$player_id,$player_stats);
     $player['atk_score'] = $this->atk_score($game_id,$teamA,$player_id,$player_stats);
     $player['creativity'] = $this->creativity($game_id,$teamA,$player_id,$player_stats);
     $player['most_accurate_pass'] = $this->most_accurate_pass($game_id,$teamA,$player_id,$player_stats);
     $player['least_accurate_pass'] = $this->least_accurate_pass($game_id,$teamA,$player_id,$player_stats);
     $player['shoot_accuracy'] = $this->shoot_accuracy($game_id,$teamA,$player_id,$player_stats);
     $player['chance_created'] = $this->chance_created($game_id,$teamA,$player_id,$player_stats);
     $player['dangerous_pass'] = $this->dangerous_pass($game_id,$teamA,$player_id,$player_stats);
     $player['assist'] = $this->assist($game_id,$teamA,$player_id,$player_stats);
     $player['best_cross_percentage'] = $this->best_cross_percentage($game_id,$teamA,$player_id,$player_stats);
     $player['worst_cross_percentage'] = $this->worst_cross_percentage($game_id,$teamA,$player_id,$player_stats);
     $player['ball_wins'] = $this->ball_wins($game_id,$teamA,$player_id,$player_stats);
     $player['def_fails'] = $this->def_fails($game_id,$teamA,$player_id,$player_stats);
     $player['liable'] = $this->liable($game_id,$teamA,$player_id,$player_stats);
     $player['gk_score'] = $this->gk_score($game_id,$teamA,$player_id,$player_stats,$teamB);
     $player['shot_stopping_percentage'] = $this->shot_stopping_percentage($game_id,$teamA,$player_id,$player_stats,$teamB);
     $player['best_at_crosses'] = $this->best_at_crosses($game_id,$teamA,$player_id,$player_stats);
     $player['one_v_one'] = $this->one_v_one($game_id,$teamA,$player_id,$player_stats);
     $player['deadkick'] = $this->deadkick($game_id,$teamA,$player_id,$player_stats);
     $player['accurate_cross'] = $this->accurate_cross($game_id,$teamA,$player_id,$player_stats);
     $player['total_cross'] = $this->total_cross($game_id,$teamA,$player_id,$player_stats);
     $player['ontarget_scoring_att'] = $this->getTotalValuesFromAttributes('ontarget_scoring_att',$player_stats);
     $player['total_scoring_att'] = $this->getTotalValuesFromAttributes('total_scoring_att',$player_stats);
     
     $this->Matchinfo->query(
      "INSERT INTO master_player_summary
       (game_id,team_id,player_id,
        most_influence,
        def_influence,
        mid_influence,
        fw_influence,
        def_score,
        atk_score,
        creativity,
        most_accurate_pass,
        least_accurate_pass,
        shoot_accuracy,
        chance_created,
        dangerous_pass,
        assist,
        best_cross_percentage,
        worst_cross_percentage,
        ball_wins,
        def_fails,
        liable,
        gk_score,
        shot_stopping_percentage,
        best_at_crosses,
        one_v_one,
        deadkick,
        accurate_cross,
        total_cross,
        ontarget_scoring_att,
        total_scoring_att
        )
       VALUES
       ('{$game_id}','{$teamA}','{$player_id}',
        '{$player['most_influence']}',
        '{$player['def_influence']}',
        '{$player['mid_influence']}',
        '{$player['fw_influence']}',
        '{$player['def_score']}',
        '{$player['atk_score']}',
        '{$player['creativity']}',
        '{$player['most_accurate_pass']}',
        '{$player['least_accurate_pass']}',
        '{$player['shoot_accuracy']}',
        '{$player['chance_created']}',
        '{$player['dangerous_pass']}',
        '{$player['assist']}',
        '{$player['best_cross_percentage']}',
        '{$player['worst_cross_percentage']}',
        '{$player['ball_wins']}',
        '{$player['def_fails']}',
        '{$player['liable']}',
        '{$player['gk_score']}',
        '{$player['shot_stopping_percentage']}',
        '{$player['best_at_crosses']}',
        '{$player['one_v_one']}',
        '{$player['deadkick']}',
        '{$player['accurate_cross']}',
        '{$player['total_cross']}',
        '{$player['ontarget_scoring_att']}',
        '{$player['total_scoring_att']}')
        ON DUPLICATE KEY UPDATE
        most_influence = VALUES(most_influence),
        def_influence = VALUES(def_influence),
        mid_influence = VALUES(mid_influence),
        fw_influence = VALUES(fw_influence),
        def_score = VALUES(def_score),
        atk_score = VALUES(atk_score),
        creativity = VALUES(creativity),
        most_accurate_pass = VALUES(most_accurate_pass),
        least_accurate_pass = VALUES(least_accurate_pass),
        shoot_accuracy = VALUES(shoot_accuracy),
        chance_created = VALUES(chance_created),
        dangerous_pass = VALUES(dangerous_pass),
        assist = VALUES(assist),
        best_cross_percentage = VALUES(best_cross_percentage),
        worst_cross_percentage = VALUES(worst_cross_percentage),
        ball_wins = VALUES(ball_wins),
        def_fails = VALUES(def_fails),
        liable = VALUES(liable),
        gk_score = VALUES(gk_score),
        shot_stopping_percentage = VALUES(shot_stopping_percentage),
        best_at_crosses = VALUES(best_at_crosses),
        one_v_one = VALUES(one_v_one),
        deadkick = VALUES(deadkick),
        accurate_cross = VALUES(accurate_cross),
        total_cross = VALUES(total_cross),
        ontarget_scoring_att = VALUES(ontarget_scoring_att),
        total_scoring_att = VALUES(total_scoring_att)
        ;"
     );  
  }
  private function getPlayers($game_id,$team_id){
    $sql = "SELECT player_id FROM player_stats 
            WHERE game_id='{$game_id}' AND team_id='{$team_id}' 
            GROUP BY player_id LIMIT 100;";
    return $this->Matchinfo->query($sql,false); 
  }
  private function most_influence($game_id,$team_id,$player_id,$stats){
    return $this->getTotalValuesFromAttributes("accurate_fwd_zone_pass,ontarget_scoring_att,effective_clearance,won_tackle,goal_assist, goals, aerial_won, interception_won, big_chance_created,big_chance_scored,big_chance_missed,accurate_cross,outfielder_block,penalty_won,fouled_final_third,last_man_contest,last_man_tackle,offside_provoked,ontarget_scoring_att,accurate_cross,accurate_through_ball,ball_recovery,clearance_off_line,saves,gk_smother,good_high_claim,good_one_on_one,interceptions_in_box,penalty_won,second_goal_assist,total_att_assist,won_contest,accurate_keeper_sweeper,total_attacking_pass",
                                                $stats);
  }
  function def_influence($game_id,$team_id,$player_id,$stats){
    $str = "accurate_fwd_zone_pass, ontarget_scoring_att, effective_clearance,won_tackle,goal_assist, goals, aerial_won, interception_won, big_chance_created,big_chance_scored,big_chance_missed,accurate_cross,outfielder_block,penalty_won,fouled_final_third,last_man_contest,last_man_tackle,offside_provoked,ontarget_scoring_att,acurate_cross,accurate_through_ball,ball_recovery,clearance_off_line,saves,interceptions_in_box,penalty_won,second_goal_assist,total_att_assist,won_contest,total_attacking_pass,accurate_pull_back";
    return $this->getTotalValuesFromAttributes($str,$stats);
  }
  function mid_influence($game_id,$team_id,$player_id,$stats){
    $str = "accurate_fwd_zone_pass, ontarget_scoring_att, effective_clearance,won_tackle,goal_assist, goals, aerial_won, interception_won, big_chance_created,big_chance_scored,big_chance_missed,accurate_cross,outfielder_block,penalty_won,fouled_final_third,last_man_contest,last_man_tackle,offside_provoked,ontarget_scoring_att,accurate_cross,accurate_through_ball,ball_recovery,clearance_off_line,saves,interceptions_in_box,penalty_won,second_goal_assist,total_att_assist,won_contest,total_attacking_pass,accurate_pull_back";
    return $this->getTotalValuesFromAttributes($str,$stats);
  }
  function fw_influence($game_id,$team_id,$player_id,$stats){
    $str = "accurate_fwd_zone_pass, ontarget_scoring_att, effective_clearance,won_tackle,goal_assist, goals, aerial_won, interception_won, big_chance_created,big_chance_scored,big_chance_missed,accurate_cross,outfielder_block,penalty_won,fouled_final_third,last_man_contest,last_man_tackle,offside_provoked,ontarget_scoring_att,accurate_cross,accurate_through_ball,ball_recovery,clearance_off_line,saves,interceptions_in_box,penalty_won,second_goal_assist,total_att_assist,won_contest,accurate_layoffs,accurate_flick_on,total_attacking_pass,accurate_pull_back";
    return $this->getTotalValuesFromAttributes($str,$stats);
  }
  function def_score($game_id,$team_id,$player_id,$stats){
    $str = "interception_won,won_tackle,outfielder_block,effective_clearance,last_man_tackle,interceptions_in_box,offside_provoked,aerial_won,accurate_launches,ball_recovery,clearance_off_line";
    return $this->getTotalValuesFromAttributes($str,$stats);
  }
  function atk_score($game_id,$team_id,$player_id,$stats){
    $str = "accurate_fwd_zone_pass,goal_assist,goals,ontarget_scoring_att,big_chance_created,big_chance_scored,big_chance_missed,accurate_cross,accurate_through_ball,aerial_won,second_goal_assist,total_att_assist,total_attacking_pass,won_contest,penalty_won,fouled_final_third,last_man_contest,accurate_pull_back";
    return $this->getTotalValuesFromAttributes($str,$stats);
  }
  function creativity($game_id,$team_id,$player_id,$stats){
    $str = "accurate_fwd_zone_pass,big_chance_created,big_chance_scored,big_chance_missed,accurate_cross,accurate_through_ball,second_goal_assist,total_att_assist,goal_assist,total_attacking_pass,won_contest,accurate_pull_back";
    return $this->getTotalValuesFromAttributes($str,$stats);
  }
  function most_accurate_pass($game_id,$team_id,$player_id,$stats){
    $p1 = "accurate_fwd_zone_pass,accurate_launches,accurate_long_balls,accurate_through_ball,accurate_chipped_pass,accurate_pull_back";
    $p2 = "total_fwd_zone_pass,total_launches,total_long_balls,total_through_ball,total_chipped_pass,total_pull_back";
    $score1 = $this->getTotalValuesFromAttributes($p1,$stats);
    $score2 = $this->getTotalValuesFromAttributes($p2,$stats);
    if($score2==0){
      return 0;
    }else{
      return ($score1/$score2);  
    }
  }
  function least_accurate_pass($game_id,$team_id,$player_id,$stats){
    $p1 = "total_fwd_zone_pass,total_launches,total_long_balls,total_through_ball,total_chipped_pass";
    $p2 = "accurate_fwd_zone_pass,accurate_launches,accurate_long_balls,accurate_through_ball,accurate_chipped_pass";
    $p3 = "total_fwd_zone_pass,total_launches,total_long_balls,total_through_ball,total_chipped_pass";
    $score1 = $this->getTotalValuesFromAttributes($p1,$stats);
    $score2 = $this->getTotalValuesFromAttributes($p2,$stats);
    $score3 = $this->getTotalValuesFromAttributes($p3,$stats);
    if($score3==0){
      return 0;
    }else{
      return (($score1-$score2)/$score3);  
    }
  }
  function shoot_accuracy($game_id,$team_id,$player_id,$stats){
      //ontarget_scoring_att-total_scoring_att
      $p1 = "ontarget_scoring_att";
      $p2 = "total_scoring_att";
      $score1 = $this->getTotalValuesFromAttributes($p1,$stats);
      $score2 = $this->getTotalValuesFromAttributes($p2,$stats);
      if($score2>0){
        return ($score1/$score2);
      }
      return 0;
  }
  function chance_created($game_id,$team_id,$player_id,$stats){
    $str = "big_chance_created,big_chance_scored,big_chance_missed,att_assist_openplay,att_assist_setplay,second_goal_assist";
    return $this->getTotalValuesFromAttributes($str,$stats);
  }
  function dangerous_pass($game_id,$team_id,$player_id,$stats){
    $str = "accurate_fwd_zone_pass,accurate_through_ball,long_pass_own_to_opp_success,total_attacking_pass,successful_final_third_passes,big_chance_created,big_chance_scored,big_chance_missed,att_assist_openplay,att_assist_setplay,second_goal_assist";
    return $this->getTotalValuesFromAttributes($str,$stats);
  }
  function assist($game_id,$team_id,$player_id,$stats){
    $str = "goal_assist_openplay";
    return $this->getTotalValuesFromAttributes($str,$stats);
  }
  function best_cross_percentage($game_id,$team_id,$player_id,$stats){
    //accurate_cross_nocorner/total_cross_nocorner
    $p1 = "accurate_cross_nocorner";
    $p2 = "total_cross_nocorner";
    $score1 = $this->getTotalValuesFromAttributes($p1,$stats);
    $score2 = $this->getTotalValuesFromAttributes($p2,$stats);
    print "accurate_cross_no_corner/total_cross_nocorner = ".$score1."/".$score2.PHP_EOL;
    if($score2==0){
      return 0;
    }else{
      return ($score1/$score2);  
    }
  }
  function accurate_cross($game_id,$team_id,$player_id,$stats){
    //accurate_cross_nocorner/total_cross_nocorner
    $p1 = "accurate_cross_nocorner";
   
    $score1 = $this->getTotalValuesFromAttributes($p1,$stats);
    return $score1;
  }
  function total_cross($game_id,$team_id,$player_id,$stats){
    //accurate_cross_nocorner/total_cross_nocorner
    $p1 = "total_cross_nocorner";
   
    $score1 = $this->getTotalValuesFromAttributes($p1,$stats);
    return $score1;
  }
  function worst_cross_percentage($game_id,$team_id,$player_id,$stats){
    //(total_cross_nocorner-accurate_cross_nocorner)/total_cross_nocorner
    $p1 = "total_cross_nocorner";
    $p2 = "accurate_cross_nocorner";
    $p3 = "total_cross_nocorner";
    $score1 = $this->getTotalValuesFromAttributes($p1,$stats);
    $score2 = $this->getTotalValuesFromAttributes($p2,$stats);
    $score3 = $this->getTotalValuesFromAttributes($p3,$stats);

    print "total_cross_nocorner - accurate_cross_nocorner/total_cross_nocorner = (".$score1."-".$score2.")/".$score3.PHP_EOL;
    if($score3==0){
      return 0;
    }else{
      return (($score1-$score2)/$score3);  
    }
  }
  function ball_wins($game_id,$team_id,$player_id,$stats){
    $str = "won_tackle,interception_won,ball_recovery,duel_won,last_man_tackle";
    return $this->getTotalValuesFromAttributes($str,$stats);
  }
  function def_fails($game_id,$team_id,$player_id,$stats){
    $str="duel_lost,challenge_lost,fouls,dangerous_play,fk_foul_lost";
    return $this->getTotalValuesFromAttributes($str,$stats); 
  }
  function liable($game_id,$team_id,$player_id,$stats){
    $str = "dangerous_play,red_card,second_yellow,yellow_card,penalty_conceded,fk_foul_lost,error_lead_to_goal,error_lead_to_shot";
    return $this->getTotalValuesFromAttributes($str,$stats); 
  }
  function gk_score($game_id,$team_id,$player_id,$stats,$teamB){
    //p1 / (p3 + p2)
    /*
    $p1 = "good_high_claim,good_one_on_one,accurate_keeper_sweeper,saves";
    $p2 = "total_one_on_one";
    $score1 = $this->getTotalValuesFromAttributes($p1,$stats);
    $score2 = $this->getTotalValuesFromAttributes($p2,$stats);
    $score3 = $this->getOtherTeamStats($game_id,$teamB,"ontarget_scoring_att");
    $this->out('punches,good_high_claim,good_one_on_one,accurate_keeper_sweeper,saves,gk_smother');
    $this->out('score1 / (score2+score3) -> '.$score1.'/('.$score2.'+'.$score3.')');
    $sum = $score2 + $score3;
    */
    $p1 = "good_high_claim,good_one_on_one,saves,diving_save,dive_catch,gk_smother,punches";
    $p2 = "cross_not_claimed";
    $score1 = $this->getTotalValuesFromAttributes($p1,$stats);
    $score2 = $this->getTotalValuesFromAttributes($p2,$stats);
   
    return $score1 - $score2;
   
  }
  function shot_stopping_percentage($game_id,$team_id,$player_id,$stats,$teamB){
    $p1 = "saves";
    $score1 = $this->getTotalValuesFromAttributes($p1,$stats);
    $score2 = $this->getOtherTeamStats($game_id,$teamB,"ontarget_scoring_att");
    $this->out('#'.$game_id.'->'.$team_id.'#saves('.$score1.') vs '.$teamB.'#ontarget_scoring_att('.$score2.')');
    $this->out('score1 / (score2) -> '.$score1.'/('.$score2.')');
    if($score2>0){
      return $score1 / $score2;
    }else{
      return 0;
    }
  }
  function best_at_crosses($game_id,$team_id,$player_id,$stats){
    //(good_high_claim/total_high_claim)
    $p1 = "good_high_claim";
    $p2 = "total_high_claim";
   
    $score1 = $this->getTotalValuesFromAttributes($p1,$stats);
    $score2 = $this->getTotalValuesFromAttributes($p2,$stats);


    if($score2==0){
      return 0;
    }else{
      return ($score1/$score2);  
    }
  }
  function one_v_one($game_id,$team_id,$player_id,$stats){
    $p1 = "good_one_on_one,accurate_keeper_sweeper";
    $p2 = "total_one_on_one,total_keeper_sweeper";
   
    $score1 = $this->getTotalValuesFromAttributes($p1,$stats);
    $score2 = $this->getTotalValuesFromAttributes($p2,$stats);


    if($score2==0){
      return 0;
    }else{
      return ($score1/$score2);  
    }
  }
  function deadkick($game_id,$team_id,$player_id,$stats){
    //(att_freekick_goal)/(att_freekick_total)
    $p1 = "att_freekick_goal";
    $p2 = "att_freekick_total";
   
    $score1 = $this->getTotalValuesFromAttributes($p1,$stats);
    $score2 = $this->getTotalValuesFromAttributes($p2,$stats);


    if($score2==0){
      return 0;
    }else{
      return ($score1/$score2);  
    }
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
  function getOtherTeamStats($game_id,$team_id,$statsName){
    $sql = "SELECT stats_name,stats_value
            FROM 
            optadb.team_stats 
            WHERE 
            game_id = '{$game_id}' 
            AND team_id='{$team_id}' 
            AND stats_name = '{$statsName}' 
            LIMIT 1;";
    $rs = $this->Matchinfo->query($sql,false);
    if(sizeof($rs)>0){
      return $rs[0]['team_stats']['stats_value'];
    }else{
      return 0;
    }
  }
  private function dummy(){
    $sql = 'TRUNCATE TABLE statsjob_queue';
    $this->Matchinfo->query($sql);
  }
}