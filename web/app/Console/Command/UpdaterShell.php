<?php
class UpdaterShell extends AppShell{
	 var $uses = array('Game','Team','User');
   private $recent_matchday = 0;
	 public function main() {
    	 	$limit = 10;
    	 	$start = 0;

        //if($this->week_finished()){
            $this->out('the game is completed');
            $this->out('getting points');
        
            $this->beforeFilter();
          
            do{
              $user = $this->User->find('all',array(
                'offset'=>$start,
                'limit'=>$limit
              ));
              $this->get_points($user);
              $start += $limit;
            }while(sizeof($user)>0);
         
           $this->out('recalculate ranks');
           CakeLog::write('updater', 'recalculate ranks');
           $this->recalculate_ranks();
           
           $this->flagJobAftermatch();
           $this->out('done');

           CakeLog::write('updater', 'done');
       // }else{
       //    $this->out('the games has not finished yet');
        //   CakeLog::write('updater', 'current matchday is still ongoing');
       // }
    }
    private function week_finished(){
      $rs = $this->Game->query("SELECT MAX(matchday) AS last_matchday 
                                FROM ffgame.game_fixtures 
                                WHERE period='FullTime' AND is_processed = 1;");
      $recent_matchday = intval($rs[0][0]['last_matchday']);

      $this->out('recent matchday : '.$rs[0][0]['last_matchday']);
      
      $rs = $this->Game->query("SELECT COUNT(*) AS total FROM ffgame.game_fixtures 
                                WHERE matchday={$recent_matchday} 
                                AND period='FullTime' AND is_processed = 1;");

      if($rs[0][0]['total']==10){
        //check if the matchday is already processed ?
        //we do these to prevent race condition
        $job = $this->Game->query("SELECT * FROM job_aftermatch WHERE matchday={$recent_matchday} LIMIT 1;");
        if(sizeof($job)==0){
          $this->recent_matchday = $recent_matchday;
          return true;
        }else{
          $this->out('matchday : '.$recent_matchday.' Already processed');
        }
      }
    }
    private function flagJobAftermatch(){
      $this->Game->query("INSERT IGNORE INTO job_aftermatch(matchday,update_dt)
                          VALUES({$this->recent_matchday},NOW())");
    }
    private function get_points($users){
     $modifiers = $this->Game->query("SELECT * FROM ffgame.game_matchstats_modifier Modifier LIMIT 100");
     $modifier = array();
     while(sizeof($modifiers)>0){
        $p = array_shift($modifiers);
        $modifier[$p['Modifier']['name']] = array(
                                              'goalkeeper'=>$p['Modifier']['g'],
                                              'defender'=>$p['Modifier']['d'],
                                              'midfielder'=>$p['Modifier']['m'],
                                              'forward'=>$p['Modifier']['f']
                                              );
     }


    	foreach($users as $user){
    		$response = $this->Game->getTeamPoints($user['User']['fb_id']);
        
    		$response['points'] = floatval($response['points']);
        $response['extra_points'] = floatval($response['extra_points']);
      

        if($user['Team']['id']>0){
          $sql = "
            INSERT INTO points
          (team_id,points,extra_points)
          VALUES
          ({$user['Team']['id']},{$response['points']},{$response['extra_points']})
          ON DUPLICATE KEY UPDATE
          points = VALUES(points),
          extra_points = VALUES(extra_points);
          ";
          $this->out($sql);
          try{
      		  $this->Team->query($sql,false);
          }catch(Exception $e){
            $this->out('Error : '.$e->getMessage());
          }
      		$this->out("Updating #".$user['Team']['id']." -> ".$response['points']);
          CakeLog::write('updater',"Updating #".$user['Team']['id']." -> ".$response['points']);


          if(is_array($response['game_points'])){

            $this->updating_weekly_stats($user['Team']['id'],
                                      $response['game_points']);
            
            $this->generate_summary($user,$modifier);
          }
        }
    	}
      unset($modifier);
      unset($modifiers);
    }
    private function generate_summary($user,$modifier){
        $this->out("Generate Summary #".$user['Team']['id']);
        $gameData = $this->Game->query("SELECT * FROM ffgame.game_users GameUser
                              INNER JOIN ffgame.game_teams GameTeam
                              ON GameTeam.user_id = GameUser.id
                              WHERE GameUser.fb_id = '{$user['User']['fb_id']}' LIMIT 1",false);
        $game_team_id = $gameData[0]['GameTeam']['id'];

        unset($gameData);
        //get money
        $r = $this->Game->query("SELECT SUM(start_budget+transactions) AS balance FROM 
                                (SELECT budget AS start_budget,0 AS transactions
                                FROM ffgame.game_team_purse WHERE game_team_id={$game_team_id} LIMIT 1
                                UNION ALL
                                SELECT 0,SUM(amount) AS transactions
                                FROM ffgame.game_team_expenditures
                                WHERE game_team_id={$game_team_id}
                                ) Finance;",false);
        $money = $r[0][0]['balance'];
     

        //get import player counts
        $r = $this->Game->query("SELECT game_team_id,COUNT(id) AS total 
                                  FROM ffgame.game_transfer_history 
                                  WHERE game_team_id = {$game_team_id} 
                                  AND transfer_type=1 LIMIT 10;",false);
        $import_player_counts = $r[0][0]['total'];

        unset($r);
        
        /*
        $games = $this->getStats($game_team_id,'games',$modifier);
        $passing_and_attacking = $this->getStats($game_team_id,'passing_and_attacking',$modifier);
        $defending = $this->getStats($game_team_id,'defending',$modifier);
        $goalkeeping = $this->getStats($game_team_id,'goalkeeping',$modifier);
        $mistakes_and_errors = $this->getStats($game_team_id,'mistakes_and_errors',$modifier);
        */

        $stats_group = $this->getStatsGroupValues($game_team_id,$modifier);

        $games = $stats_group['games'];
        $passing_and_attacking = $stats_group['passing_and_attacking'];
        $defending = $stats_group['defending'];
        $goalkeeping = $stats_group['goalkeeping'];
        $mistakes_and_errors = $stats_group['mistakes_and_errors'];

        $sql = "INSERT INTO team_summary
                      (game_team_id,money,import_player_counts,games,passing_and_attacking,defending,goalkeeping,mistakes_and_errors,last_update)
                      VALUES
                      ({$game_team_id},
                        {$money},
                        {$import_player_counts},
                        {$games},{$passing_and_attacking},
                        {$defending},{$goalkeeping},{$mistakes_and_errors},NOW())
                      ON DUPLICATE KEY UPDATE
                      money = VALUES(money),
                      import_player_counts = VALUES(import_player_counts),
                      games = VALUES(games),
                      passing_and_attacking = VALUES(passing_and_attacking),
                      defending = VALUES(defending),
                      goalkeeping = VALUES(goalkeeping),
                      mistakes_and_errors = VALUES(mistakes_and_errors),
                      last_update = VALUES(last_update);";

       // $this->out($sql);
      
        $rs = $this->Game->query($sql);
       if($rs){
           $this->out("Generate Summary #".$user['Team']['id'].' DONE');
       }
        
    }
    private function getStatsGroupValues($game_team_id,$modifier){
        $map = $this->getStatsCategories();
        $games = 0;
        $passing_and_attacking = 0;
        $defending = 0;
        $goalkeeping = 0;
        $mistakes_and_errors = 0;

        $game_ids = $this->Game->query("SELECT game_id FROM ffgame_stats.game_match_player_points a
                                        WHERE game_team_id={$game_team_id} GROUP BY game_id;");

        $game_id = array();
        while(sizeof($game_ids)>0){
          $g = array_pop($game_ids);
          $game_id[] = "'".$g['a']['game_id']."'";
        }

        $players = $this->Game->query("SELECT player_id FROM ffgame_stats.game_match_player_points bb
                                          WHERE bb.game_team_id={$game_team_id} GROUP BY player_id;",false);
        $pp = array();
        while(sizeof($players)>0){
          $p = array_pop($players);
          $pp[] = "'".$p['bb']['player_id']."'";
        }
        if(sizeof($pp)>0 && sizeof($game_id)>0){
          $is_finished = false;
          $start = 0;
          while(!$is_finished){
                $sql = "SELECT a.stats_name,SUM(a.stats_value) AS frequency,b.position 
                              FROM ffgame_stats.master_player_stats a
                              INNER JOIN ffgame.master_player b
                              ON a.player_id = b.uid
                              WHERE a.game_id IN (".implode(',',$game_id).") AND a.player_id IN (".implode(",",$pp).")
                              GROUP BY a.stats_name,a.player_id
                              LIMIT {$start},100;";
               $rs = $this->Game->query($sql,false);

               // $this->out($sql);
               if(sizeof($rs)>0){
                  foreach($rs as $r){
                    $stats_name = $r['a']['stats_name'];
                    $pos = strtolower($r['b']['position']);
                    $poin = ($modifier[$stats_name][$pos] * $r[0]['frequency']);
                    if($this->is_in_category($map,'games',$stats_name)){
                      $games += $poin;
                    }
                    if($this->is_in_category($map,'passing_and_attacking',$stats_name)){
                      $passing_and_attacking += $poin;
                    }
                    if($this->is_in_category($map,'defending',$stats_name)){
                      $defending += $poin;
                    }
                    if($this->is_in_category($map,'goalkeeping',$stats_name)){
                      $goalkeeping += $poin;
                    }
                    if($this->is_in_category($map,'mistakes_and_errors',$stats_name)){
                      $mistakes_and_errors += $poin;
                    }
                  }
                  $rs = null;
                  unset($rs);
                  $start+=100;
               }else{
                  $is_finished = true;
               }
          }
      
        }    
        return array('games'=>$games,
                      'passing_and_attacking'=>$passing_and_attacking,
                      'defending'=>$defending,
                      'goalkeeping'=>$goalkeeping,
                      'mistakes_and_errors'=>$mistakes_and_errors);
    }
    private function is_in_category($map,$category,$stats_name){
        foreach($map[$category] as $n=>$v){
            if($v==$stats_name){
              return true;
            }
        }
    }
    private function getStats($game_team_id,$category,$modifier){
      $map = $this->getStatsCategories();
      $stats = array();

      foreach($map[$category] as $n=>$v){
        $stats[] = "'".trim($v)."'";
      }
      unset($map);
      $sqlStr = implode(',',$stats);
      unset($stats);

     // $this->out('getting stats : #'.$game_team_id.'-'.$category);
      $is_finished = false;
      $start = 0;
      $total = 0;
      $players = $this->Game->query("SELECT player_id FROM ffgame_stats.game_match_player_points bb
                                          WHERE bb.game_team_id={$game_team_id} GROUP BY player_id;",false);
      $pp = array();
      while(sizeof($players)>0){
        $p = array_pop($players);
        $pp[] = "'".$p['bb']['player_id']."'";
      }
      if(sizeof($pp)>0){
        while(!$is_finished){
           // $this->out('start :'.$start);
           // $this->out('total :'.$total);

             $rs = $this->Game->query("SELECT a.stats_name,a.stats_value AS frequency,b.position 
                            FROM ffgame_stats.master_player_stats a
                            INNER JOIN ffgame.master_player b
                            ON a.player_id = b.uid
                            WHERE player_id IN (".implode(",",$pp).") 
                            AND stats_name IN ({$sqlStr})
                            LIMIT {$start},20;",false);

            
             if(sizeof($rs)>0){
                foreach($rs as $r){
                  $stats_name = $r['a']['stats_name'];
                  $pos = strtolower($r['b']['position']);
                  $total += ($modifier[$stats_name][$pos] * $r['a']['frequency']);
                }
                $rs = null;
                unset($rs);
                $start+=20;
             }else{
              $is_finished = true;
             }
        }
  
      }     
      
      
      return $total;
    }
    private function getStatsCategories(){
     // $this->out('get map');
      $games = array(
          'game_started'=>'game_started',
          'sub_on'=>'total_sub_on'
      );

      $passing_and_attacking = array(
              'Freekick Goal'=>'att_freekick_goal',
              'Goal inside the box'=>'att_ibox_goal',
              'Goal Outside the Box'=>'att_obox_goal',
              'Penalty Goal'=>'att_pen_goal',
              'Freekick Shots'=>'att_freekick_post',
              'On Target Scoring Attempt'=>'ontarget_scoring_att',
              'Shot From Outside the Box'=>'att_obox_target',
              'big_chance_created'=>'big_chance_created',
              'big_chance_scored'=>'big_chance_scored',
              'goal_assist'=>'goal_assist',
              'total_assist_attempt'=>'total_att_assist',
              'Second Goal Assist'=>'second_goal_assist',
              'final_third_entries'=>'final_third_entries',
              'fouled_final_third'=>'fouled_final_third',
              'pen_area_entries'=>'pen_area_entries',
              'won_contest'=>'won_contest',
              'won_corners'=>'won_corners',
              'penalty_won'=>'penalty_won',
              'last_man_contest'=>'last_man_contest',
              'accurate_corners_intobox'=>'accurate_corners_intobox',
              'accurate_cross_nocorner'=>'accurate_cross_nocorner',
              'accurate_freekick_cross'=>'accurate_freekick_cross',
              'accurate_launches'=>'accurate_launches',
              'long_pass_own_to_opp_success'=>'long_pass_own_to_opp_success',
              'successful_final_third_passes'=>'successful_final_third_passes',
              'accurate_flick_on'=>'accurate_flick_on'
          );


      $defending = array(
              'aerial_won'=>'aerial_won',
              'ball_recovery'=>'ball_recovery',
              'duel_won'=>'duel_won',
              'effective_blocked_cross'=>'effective_blocked_cross',
              'effective_clearance'=>'effective_clearance',
              'effective_head_clearance'=>'effective_head_clearance',
              'interceptions_in_box'=>'interceptions_in_box',
              'interception_won' => 'interception_won',
              'possession_won_def_3rd' => 'poss_won_def_3rd',
              'possession_won_mid_3rd' => 'poss_won_mid_3rd',
              'possession_won_att_3rd' => 'poss_won_att_3rd',
              'won_tackle' => 'won_tackle',
              'offside_provoked' => 'offside_provoked',
              'last_man_tackle' => 'last_man_tackle',
              'outfielder_block' => 'outfielder_block'
          );

      $goalkeeper = array(
                      'dive_catch'=> 'dive_catch',
                      'dive_save'=> 'dive_save',
                      'stand_catch'=> 'stand_catch',
                      'stand_save'=> 'stand_save',
                      'cross_not_claimed'=> 'cross_not_claimed',
                      'good_high_claim'=> 'good_high_claim',
                      'punches'=> 'punches',
                      'good_one_on_one'=> 'good_one_on_one',
                      'accurate_keeper_sweeper'=> 'accurate_keeper_sweeper',
                      'gk_smother'=> 'gk_smother',
                      'saves'=> 'saves',
                      'goals_conceded'=>'goals_conceded'
                          );


      $mistakes_and_errors = array(
                  'penalty_conceded'=>'penalty_conceded',
                  'red_card'=>'red_card',
                  'yellow_card'=>'yellow_card',
                  'challenge_lost'=>'challenge_lost',
                  'dispossessed'=>'dispossessed',
                  'fouls'=>'fouls',
                  'overrun'=>'overrun',
                  'total_offside'=>'total_offside',
                  'unsuccessful_touch'=>'unsuccessful_touch',
                  'error_lead_to_shot'=>'error_lead_to_shot',
                  'error_lead_to_goal'=>'error_lead_to_goal'
                  );
      $map = array('games'=>$games,
                    'passing_and_attacking'=>$passing_and_attacking,
                    'defending'=>$defending,
                    'goalkeeping'=>$goalkeeper,
                    'mistakes_and_errors'=>$mistakes_and_errors
                   );

      unset($games);
      unset($passing_and_attacking);
      unset($defending);
      unset($goalkeeper);
      unset($mistakes_and_errors);
      return $map;
    }
    private function updating_weekly_stats($team_id,$game_points){
      foreach($game_points as $weekly){
        $sql = "INSERT INTO weekly_points
                (team_id,game_id,matchday,matchdate,points,extra_points)
                VALUES
                ({$team_id},'{$weekly['game_id']}',
                  '{$weekly['matchday']}',
                  '{$weekly['match_date']}',{$weekly['total_points']},{$weekly['extra_points']})
                ON DUPLICATE KEY UPDATE
                points = VALUES(points),
                extra_points = VALUES(extra_points);";

                try{
                   $this->Team->query($sql);
                }catch(Exception $e){
                  $this->out('Error : '.$e->getMessage());
                }
                $this->out("Updating #".$team_id." week #".$weekly['matchday'].
                                        "-> ".$weekly['total_points']);

                CakeLog::write('updater',"Updating #".$team_id." week #".$weekly['matchday'].
                                        "-> ".$weekly['total_points']);
       
      }
    }
    private function recalculate_ranks(){
        $sql = "CALL recalculate_rank;";
        $this->Team->query($sql);
        $rs = $this->Team->query("SELECT matchday FROM weekly_points 
                            GROUP BY matchday ORDER BY matchday 
                            LIMIT 1000");
        if(is_array($rs)){
          foreach($rs as $r){
            $this->out('recalculating matchday #'.$r['weekly_points']['matchday'].' ranks');
            $sql = "CALL recalculate_weekly_rank('{$r['weekly_points']['matchday']}');";
            $this->Team->query($sql);
          }
        }

        $this->out('recalculating monthly ranks');
        $months = $this->Team->query("SELECT YEAR(matchdate) AS thn,MONTH(matchdate) AS bln
                            FROM weekly_points GROUP BY thn,bln;");
        foreach($months as $m){
          $mth = $m[0]['bln'];
          $yr = $m[0]['thn'];
          $sql = "CALL recalculate_monthly_rank({$mth},{$yr});";
          $this->Team->query($sql);  
        }
    }
}