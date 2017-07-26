<?php

if($club['team_id'] == $match['home_id']){
    $home = $club['team_name'];
    $away = $match['away_name'];
}else{
    $away = $club['team_name'];
    $home = $match['home_name'];
}

function getPoin($position,$stats_name,$modifier){
   
    return intval(@$modifier[$stats_name][$position]);
}
function getTotalPoints($str,$stats){
    $arr = explode(",",$str);
    $total = 0;

    foreach($arr as $a){
        $a = trim($a);
        $total += floatval(@$stats[$a]['points']);
    }
    return $total;
}
?>
<div id="myClubPage">
    <?php echo $this->element('infobar'); ?>
    <div class="headbar tr">
        <div class="match-info fl">
            <h4><span class="matchClub"><?=h($home)?></span> <span class="matchScore"><?=intval($match['home_score'])?></span>  vs  
			<span class="matchScore"><?=intval($match['away_score'])?></span> <span class="matchClub"><?=h($away)?></span></h4>
        </div>
        <div class="match-info fl brLeft">
            <h4>
                <span class="matchClub">Total Poin</span> <span class="totalpoin yellow">0</span>
                <span class="bonusPoin" title="Bonus Points" alt="Bonus Points"></span>
            </h4>
        </div>
        <div class="fr">
      		  <a href="<?=$this->Html->url('/manage/club')?>" class="button">Kembali</a>
        </div>
    </div><!-- end .headbar -->
	
    <div id="thecontent">
		<table width="100%" border="0" cellspacing="0" cellpadding="0">
		<thead>
            <tr>
                <th colspan="2">Player</th>
                <th class="aligncenter">Posisi</th>
                <th class="aligncenter">Play</th>
                <th class="aligncenter">Attacking &amp; Passing</th>
                <th class="aligncenter">Defending</th>
                <th class="aligncenter">Goalkeeping</th>
                <th class="aligncenter">Mistakes and Errors</th>
                <th class="aligncenter">Poin</th>
            </tr>
		 </thead>
		 <tbody>
            <?php
                $overall_points = 0;

                foreach($players as $player_id=>$detail):
                    /*
                    foreach($detail['stats'] as $n=>$v){
                        //$detail['stats'][$n] = $detail['stats'][$n] * 
                                               // getPoin($detail['position'],
                                                       // $n,
                                                       // $modifier);
                      
                    }
                    */

                    $games = getTotalPoints('game_started,total_sub_on',$detail['ori_stats']);

                
                    $attacking_and_passing = getTotalPoints('goals,
                                            att_freekick_goal,
                                            att_pen_goal,
                                            att_ibox_target,
                                            att_obox_target,
                                            goal_assist_openplay,
                                            goal_assist_setplay,
                                            att_assist_openplay,
                                            att_assist_setplay,
                                            second_goal_assist,
                                            big_chance_created,
                                            accurate_through_ball,
                                            accurate_cross_nocorner,
                                            accurate_pull_back,
                                            won_contest,
                                            long_pass_own_to_opp_success,
                                            accurate_long_balls,
                                            accurate_flick_on,
                                            accurate_layoffs,
                                            penalty_won,
                                            won_corners,
                                            fk_foul_won,ontarget_scoring_att,
                                            att_ibox_goal,
                                            att_obox_goal',
                                            
                                            $detail['ori_stats']);
                    $defending = getTotalPoints('duel_won,
                                            aerial_won,
                                            ball_recovery,
                                            won_tackle,
                                            interception_won,
                                            interceptions_in_box,
                                            offside_provoked,
                                            outfielder_block,
                                            effective_blocked_cross,
                                            effective_head_clearance,
                                            effective_clearance,
                                            clearance_off_line',$detail['ori_stats']);

                    $goalkeeping = getTotalPoints('good_high_claim,saves,penalty_save',$detail['ori_stats']);
                    $mistakes_and_errors = getTotalPoints('penalty_conceded,
                                            fk_foul_lost,
                                            poss_lost_all,
                                            challenge_lost,
                                            error_lead_to_shot,
                                            error_lead_to_goal,
                                            total_offside,
                                            yellow_card,
                                            red_card',$detail['ori_stats']);

                    $total_poin = $games + $attacking_and_passing + $defending +
                                  $goalkeeping + $mistakes_and_errors;

                    $overall_points += $total_poin;
                    
            ?>
            <tr>
                <td>
                    <a class="thumbPlayersSmall" href="<?=$this->Html->url('/manage/player/'.$player_id)?>"> <img src="http://omo.akamai.opta.net/image.php?custID=c8bb60c8f6d0184c33a87e6f3041b9cc&sport=football&entity=player&description=<?=str_replace('t','',$detail['original_team_id'])?>&dimensions=103x155&id=<?=str_replace('p','',$player_id)?>"/></a>
                </td>
                <td class="aligncenter"><?=h($detail['name'])?></td>
                <td class="aligncenter"><?=h($detail['position'])?></td>
                <td class="aligncenter"><?=$games?></td>
                <td class="aligncenter"><?=$attacking_and_passing?></td>
                <td class="aligncenter"><?=$defending?></td>
                <td class="aligncenter"><?=$goalkeeping?></td>
                <td class="aligncenter"><?=$mistakes_and_errors?></td>
                <?php
                    $playerstats_url = $this->Html->url('/manage/playerstats/'.
                                        $player_id.'/?game_id='.$match['game_id'].'&r='.$r);
                ?>
                <td><a href="<?=$playerstats_url?>" class="yellow"><?=$total_poin?></a></td>
            </tr>
            <?php endforeach;?>
			</tbody>
        </table>
    </div>
</div>
<script>
var total_points = <?=floatval($match['points'])?>;
<?php
$bonus_points = floatval($match['points']) - floatval($overall_points);
if($bonus_points > 0):
?>
$(".bonusPoin").html('(<?=($bonus_points)?>)');
<?php endif;?>
$(".totalpoin").html(<?=($overall_points)?>);

</script>