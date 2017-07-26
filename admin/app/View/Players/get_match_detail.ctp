<?php
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
<div class="row">
	<a href="<?=$this->Html->url('/players/view/'.$user['User']['id'])?>" class="button">Back</a>
</div>

<h3 class="titles">Player Info</h3>
<table width="100%" border="0" cellspacing="0" cellpadding="0" class="dataTable">
	<tbody>
		<tr>
			<td><strong>Name</strong></td><td><?=h($user['User']['name'])?></td>
			<td><strong>Team</strong></td><td><?=h($user['Team']['team_name'])?> (<?=$team_data['c']['name']?>)</td>
			<td><strong>Budget</strong></td><td>SS$ <?=number_format($budget)?></td>
		</tr>
		<tr>
			<td><strong>FBID</strong></td><td><?=h($user['User']['fb_id'])?></td><td><strong>Joined</strong></td>
			<td><?=date("d-m-Y H:i:s",strtotime($user['User']['register_date']))?></td>
			<td><strong>Points</strong></td><td><?=@number_format($point['points'])?></td>
		</tr>
		<tr>
			<td><strong>Email</strong></td><td><?=h($user['User']['email'])?></td>
			<td><strong>Reg.Status</strong></td>
			<td>
				<?php if($user['User']['register_completed']==1):?>
					Completed
				<?php else:?>
					In Progress
				<?php endif;?>
			</td>
			<td><strong>Rank</strong></td><td><?=@number_format($point['rank'])?></td>
		</tr>
		<tr>
			<td><strong>Mobile</strong></td><td><?=h($user['User']['phone_number'])?></td>
			<td></td><td></td>
			<td><strong>Total Matches</strong></td><td><?=number_format($total_matches)?></td>
		</tr>
		<tr>
			<td><strong>Location</strong></td><td><?=h($user['User']['location'])?></td>
			<td></td><td></td><td><strong>Coin Gained</strong></td>
            <td><?=number_format($cash[0]['game_transactions']['amount'])?></td>
		</tr>
	</tbody>
</table>
<div id="thecontent">
	<h3>Squad</h3>
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
                                        fk_foul_won',
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

                $goalkeeping = getTotalPoints('good_high_claim,saves',$detail['ori_stats']);
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
                <a class="thumbPlayersSmall" href="<?=$this->Html->url('/manage/player/'.$player_id)?>"> <img width="60px" src="http://omo.akamai.opta.net/image.php?custID=c8bb60c8f6d0184c33a87e6f3041b9cc&sport=football&entity=player&description=<?=str_replace('t','',$detail['original_team_id'])?>&dimensions=103x155&id=<?=str_replace('p','',$player_id)?>"/></a>
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
        <tr>
            <td colspan="2">&nbsp;</td>
            <td>&nbsp;</td>
            <td>&nbsp;</td>
            <td>&nbsp;</td>
            <td>&nbsp;</td>
            <td>&nbsp;</td>
            <td>&nbsp;</td>
            <td><?=number_format($overall_points)?></td>
        </tr>
		</tbody>
    </table>
</div>

<div id="thecontent">
	<h3>Extra Point</h3>
	<table width="100%" border="0" cellspacing="0" cellpadding="0">
	<thead>
        <tr>
            <th>Match Day</th>
            <th class="aligncenter">Booster</th>
            <th class="aligncenter">Points</th>
        </tr>
	 </thead>
	 <tbody>
        <?php
        	$extra_points_all = 0;
        	foreach($extra_points as $key => $value):
        ?>
	        <tr>
	            <td class="aligncenter"><?=$value['game_team_extra_points']['matchday']?></td>
	            <td class="aligncenter"><?=$value['game_team_extra_points']['modifier_name']?></td>
	            <td class="aligncenter"><?=$value['game_team_extra_points']['extra_points']?></td>
	        </tr>
	    <?php $extra_points_all += $value['game_team_extra_points']['extra_points']; ?>
        <?php endforeach;?>
        <tr>
        	<td></td>
        	<td></td>
        	<td><?=number_format($extra_points_all)?></td>
        </tr>
		</tbody>
    </table>
</div>