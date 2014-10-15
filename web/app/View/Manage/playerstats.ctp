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
function getStatsList($position,$str,$stats,$modifier){

     $arr = explode(",",$str);
     $s = array();

    foreach($arr as $a){
        $stats_name = trim($a);
        if(isset($stats[$stats_name])){
	        $s[trim($stats_name)]['frequency'] = intval(@$stats[$stats_name]['total']);
	        $s[trim($stats_name)]['point'] = $stats[$stats_name]['points'];

    	}else{
    		$s[trim($stats_name)] = array('frequency'=>0,'point'=>0);
    	}
    }
    return $s;
}
function getTotalPoints($str,$stats){
    $arr = explode(",",$str);
    $total = 0;
    foreach($arr as $a){
        $total += intval(@$stats[$a]);
    }
    return $total;
}


$games = getStatsList($data['position'],'game_started,total_sub_on',$data['ori_stats'],$modifier);
              
$attacking_and_passing = getStatsList($data['position'],'goals,att_freekick_goal,att_pen_goal,att_ibox_target,att_obox_target,goal_assist_openplay,goal_assist_setplay,att_assist_openplay,att_assist_setplay,second_goal_assist,big_chance_created,accurate_through_ball,accurate_cross_nocorner,accurate_pull_back,won_contest,long_pass_own_to_opp_success,accurate_long_balls,accurate_flick_on,accurate_layoffs,penalty_won,won_corners,fk_foul_won,ontarget_scoring_att,att_ibox_goal,att_obox_goal',
                        $data['ori_stats'],$modifier);


$defending = getStatsList($data['position'],'duel_won,aerial_won,ball_recovery,won_tackle,interception_won,interceptions_in_box,offside_provoked,outfielder_block,effective_blocked_cross,effective_head_clearance,effective_clearance,clearance_off_line  ',$data['ori_stats'],$modifier);

$goalkeeping = getStatsList($data['position'],'good_high_claim,saves,penalty_save',$data['ori_stats'],$modifier);
$mistakes_and_errors = getStatsList($data['position'],
    'penalty_conceded,fk_foul_lost,poss_lost_all,challenge_lost,error_lead_to_shot,error_lead_to_goal,total_offside,yellow_card,red_card',$data['ori_stats'],$modifier);

$games_total = 0;
foreach($games as $v){
    $games_total+=$v['point'];
}
$attacking_and_passing_total = 0;
foreach($attacking_and_passing as $v){
    $attacking_and_passing_total+=$v['point'];
}
$defending_total = 0;
foreach($defending as $v){
    $defending_total+=$v['point'];
}
$goalkeeping_total = 0;
foreach($goalkeeping as $v){
    $goalkeeping_total+=$v['point'];
}
$mistakes_and_errors_total = 0;
foreach($mistakes_and_errors as $v){
    $mistakes_and_errors_total+=$v['point'];
}

?>

<script>
function profileLoaded(widget, data, id){
    $('.player-detail .opta-widget-container h2 span').html('Player Profile');
    $(".opta-widget-container div.profile-container div.profile dl").find('dt').each(
        function(k,item){
            if($(item).html()=='Name'){
                $(item).next().remove();
                $(item).remove();
            }
        });
}
_optaParams.callbacks = [profileLoaded];


</script>
<div id="myClubPage">
    <?php echo $this->element('infobar'); ?>
    <div class="headbar tr">
        <div class="match-info fl">
            <h4 class="playerName"><?=h($data['name'])?></h4>
        </div>
        <div class="match-info fl">
            <h4><span class="matchClub"><?=h($home)?></span> <span class="matchScore"><?=intval($match['home_score'])?></span>  vs  
			<span class="matchScore"><?=intval($match['away_score'])?></span> <span class="matchClub"><?=h($away)?></span></h4>
        </div>
       
        <div class="fr">
      		  <a href="<?=$this->Html->url('/manage/matchinfo/?game_id='.$match['game_id'].'&r='.$r)?>" class="button">Kembali</a>
        </div>
    </div><!-- end .headbar -->
	
    <div id="thecontent" class="playerStatsPage">
      <div id="tabs-Info">
			<div class="player-detail">
				<opta widget="playerprofile" sport="football" competition="8" season="2013" 
					team="<?=str_replace('t','',$data['original_team_id'])?>" player="<?=str_replace("p","",$player_id)?>" show_image="true" show_nationality="true" opta_logo="false" narrow_limit="400"></opta>
			</div>
			<div class="profileStats-container" style="display: block;">
			  <h2><span>Performance Summary</span></h2>
			  <div class="profileStatsContainer">
				<div class="profileStats" style="overflow:hidden;">
					<a href="#" class="statsbox">
						<h4>Games</h4>
						<p><?=($games_total)?></p>
					</a>
					<a href="" class="statsbox">
						<h4>Passing and Attacking</h4>
						<p><?=($attacking_and_passing_total)?></p>
					</a>
					<a href="#" class="statsbox">
						<h4>Defending</h4>
						<p><?=($defending_total)?></p>
					</a>
				   
					<a href="#/stats_detail/3" class="statsbox">
						<h4>Goalkeeping</h4>
						<p><?=($goalkeeping_total)?></p>
					</a>
				   
					<a href="#/stats_detail/4" class="statsbox">
						<h4>Mistakes and Errors</h4>
						<p><?=($mistakes_and_errors_total)?></p>
					</a>
				   
				</div><!-- end .profileStats -->
			  </div><!-- end .profileStats-container -->
			</div><!-- end .profileStats-container -->  
		</div>
        <div class="row">
              <div class="col2">
				  <div  class="boxTab">
					<div class="titleTab"><span class="fl">Games</span><span class="fr yellow">Total Poin  <?=($games_total);?></span></div>
					<table width="100%" border="0" cellspacing="0" cellpadding="0">
						<thead>
							<th>Aksi</th><th>Frekuensi</th><th>Poin</th>
						</thead>
						<tbody>
						<?php foreach($games as $statsName=>$val):?>
						<tr>
							<td>
								<?=ucfirst(str_replace("_"," ",$statsName))?>
							</td>
							<td>
								<?=number_format($val['frequency'])?>
							</td>
							<td>
								<?=($val['point'])?>
							</td>
						</tr>
					   <?php
						endforeach;
						?>
						</tbody>
					</table>
				  </div><!-- end .boxTab -->
				  <div  class="boxTab">
						<div class="titleTab"><span class="fl">Attacking and Passing</span><span class="fr yellow">Total Poin  <?=($attacking_and_passing_total);?></span></div>
						<table width="100%" border="0" cellspacing="0" cellpadding="0">
							<thead>
								<th>Aksi</th><th>Frekuensi</th><th>Poin</th>
							</thead>
							<tbody>
							<?php foreach($attacking_and_passing as $statsName=>$val):?>
							<tr>
								<td>
									<?=ucfirst(str_replace("_"," ",$statsName))?>
								</td>
								<td>
									<?=number_format($val['frequency'])?>
								</td>
								<td>
									<?=($val['point'])?>
								</td>
							</tr>
						   <?php
							endforeach;
							?>
						</tbody>
					</table>
				  </div><!-- end .boxTab -->
				</div><!-- end .col2 -->
              <div class="col2 col2Right">
				  <div  class="boxTab">
					    <div class="titleTab"><span class="fl">Defending</span><span class="fr yellow">Total Poin  <?=($defending_total);?></span></div>
						<table width="100%" border="0" cellspacing="0" cellpadding="0">
							<thead>
								<th>Aksi</th><th>Frekuensi</th><th>Poin</th>
							</thead>
							<tbody>
							<?php foreach($defending as $statsName=>$val):?>
							<tr>
								<td>
									<?=ucfirst(str_replace("_"," ",$statsName))?>
								</td>
								<td>
									<?=number_format($val['frequency'])?>
								</td>
								<td>
									<?=($val['point'])?>
								</td>
							</tr>
						   <?php
							endforeach;
							?>
						</tbody>
					</table>
				  </div><!-- end .boxTab -->
				  <div  class="boxTab">
					    <div class="titleTab"><span class="fl">Goalkeeping</span><span class="fr yellow">Total Poin  <?=($goalkeeping_total);?></span></div>
						<table width="100%" border="0" cellspacing="0" cellpadding="0">
							<thead>
								<th>Aksi</th><th>Frekuensi</th><th>Poin</th>
							</thead>
							<tbody>
							<?php foreach($goalkeeping as $statsName=>$val):?>
							<tr>
								<td>
									<?=ucfirst(str_replace("_"," ",$statsName))?>
								</td>
								<td>
									<?=number_format($val['frequency'])?>
								</td>
								<td>
									<?=($val['point'])?>
								</td>
							</tr>
						   <?php
							endforeach;
							?>
						</tbody>
					</table>
				  </div><!-- end .boxTab -->
				  <div  class="boxTab">
					    <div class="titleTab"><span class="fl">Mistakes and Errors</span><span class="fr yellow">Total Poin  <?=($mistakes_and_errors_total);?></span></div>
						<table width="100%" border="0" cellspacing="0" cellpadding="0">
							<thead>
								<th>Aksi</th><th>Frekuensi</th><th>Poin</th>
							</thead>
							<tbody>
							<?php foreach($mistakes_and_errors as $statsName=>$val):?>
							<tr>
								<td>
									<?=ucfirst(str_replace("_"," ",$statsName))?>
								</td>
								<td>
									<?=number_format($val['frequency'])?>
								</td>
								<td>
									<?=($val['point'])?>
								</td>
							</tr>
						   <?php
							endforeach;
							?>
						</tbody>
					</table>
				  </div><!-- end .boxTab -->
			</div><!-- end .col2 -->
        </div>
    </div>
</div>
