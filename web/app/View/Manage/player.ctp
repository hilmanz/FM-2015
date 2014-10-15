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
var club_url = "<?=$this->Html->url('/manage/club/?rooster=1')?>";
<?php
if(isset($data['daily_stats'])):
?>
var daily_stats = JSON.parse('<?=json_encode($data['daily_stats'])?>');
<?php
else:
?>
var daily_stats = {};
<?php endif;?>

</script>
<?php
require_once APP . 'Vendor' . DS. 'stats.locale.php';
if($_SESSION['league']=='epl'){
    $transfer_date = '11';
}else{
    $transfer_date = '17';
}
//mapping statistics
$games = array(
        'game_started'=>'game_started',
        'sub_on'=>'total_sub_on'
    );

$passing_and_attacking = array(
            'goals'=>'goals',
            'att_freekick_goal'=>'att_freekick_goal',
            'att_pen_goal'=>'att_pen_goal',
            'att_ibox_target'=>'att_ibox_target',
            'att_obox_target'=>'att_obox_target',
            'goal_assist_openplay'=>'goal_assist_openplay',
            'goal_assist_setplay'=>'goal_assist_setplay',
            'att_assist_openplay'=>'att_assist_openplay',
            'att_assist_setplay'=>'att_assist_setplay',
            'second_goal_assist'=>'second_goal_assist',
            'big_chance_created'=>'big_chance_created',
            'accurate_through_ball'=>'accurate_through_ball',
            'accurate_cross_nocorner'=>'accurate_cross_nocorner',
            'accurate_pull_back'=>'accurate_pull_back',
            'won_contest'=>'won_contest',
            'long_pass_own_to_opp_success'=>'long_pass_own_to_opp_success',
            'accurate_long_balls'=>'accurate_long_balls',
            'accurate_flick_on'=>'accurate_flick_on',
            'accurate_layoffs'=>'accurate_layoffs',
            'penalty_won'=>'penalty_won',
            'won_corners'=>'won_corners',
            'fk_foul_won'=>'fk_foul_won',
            'ontarget_scoring_att'=>'ontarget_scoring_att',
            'att_ibox_goal'=>'att_ibox_goal',
            'att_obox_goal'=>'att_obox_goal'
          );


      $defending = array(
        'duel_won'  =>  'duel_won',
        'aerial_won'    =>  'aerial_won',
        'ball_recovery' =>  'ball_recovery',
        'won_tackle'    =>  'won_tackle',
        'interception_won'  =>  'interception_won',
        'interceptions_in_box'  =>  'interceptions_in_box',
        'offside_provoked'  =>  'offside_provoked',
        'outfielder_block'  =>  'outfielder_block',
        'effective_blocked_cross'   =>  'effective_blocked_cross',
        'effective_head_clearance'  =>  'effective_head_clearance',
        'effective_clearance'   =>  'effective_clearance',
        'clearance_off_line'    =>  'clearance_off_line'  


          );

      $goalkeeper = array(
                      'good_high_claim'=> 'good_high_claim',
                      'saves'=> 'saves',
                      'penalty_save'=>'penalty_save'
                          );


      $mistakes_and_errors = array(
                    'penalty_conceded'=>    'penalty_conceded',
                    'fk_foul_lost'=>    'fk_foul_lost',
                    'poss_lost_all'=>   'poss_lost_all',
                    'challenge_lost'=>  'challenge_lost',
                    'error_lead_to_shot'=>  'error_lead_to_shot',
                    'error_lead_to_goal'=>  'error_lead_to_goal',
                    'total_offside'=>   'total_offside',
                    'yellow_card'=>   'yellow_card',
                    'red_card'=>   'red_card'
                  );


$map = array('games'=>$games,
              'passing_and_attacking'=>$passing_and_attacking,
              'defending'=>$defending,
              'goalkeeper'=>$goalkeeper,
              'mistakes_and_errors'=>$mistakes_and_errors
             );


switch($data['player']['position']){
    case 'Forward':
        $pos = "f";
    break;
    case 'Midfielder':
        $pos = "m";
    break;
    case 'Defender':
        $pos = "d";
    break;
    default:
        $pos = 'g';
    break;
}
$total_points = 0;
$main_stats_vals = array('games'=>0,
                            'passing_and_attacking'=>0,
                            'defending'=>0,
                            'goalkeeper'=>0,
                            'mistakes_and_errors'=>0,
                         );

if(isset($data['overall_stats'])){
    foreach($data['overall_stats'] as $stats){
       
        $total_points += $stats['points'];
        
        $main_stats_vals[$stats['stats_category']]+= $stats['points'];
    }
    //foreach($main_stats_vals as $n){
    //    $total_points += $n;
    //}

}

/*
if(isset($data['overall_stats'])){
    foreach($data['overall_stats'] as $stats){
        $total_points += $stats['points'];
        foreach($map as $mainstats=>$substats){
            foreach($substats as $n=>$v){
                
                if($v==$stats['stats_name']){
                    if(!isset($main_stats_vals[$mainstats])){
                        $main_stats_vals[$mainstats] = 0;
                        $main_stats_ori[$mainstats] = 0;
                    }
                    $main_stats_vals[$mainstats] += ($stats['total'] *
                                                    getModifierValue($modifiers,
                                                                            $v,
                                                                            $pos));

                   
                }
            }
        }
    }
    //foreach($main_stats_vals as $n){
    //    $total_points += $n;
    //}

}*/

function getModifierValue($modifiers,$statsName,$pos){
    foreach($modifiers as $m){
        if($m['Modifier']['name']==$statsName){
            return ($m['Modifier'][$pos]);
        }
    }
    return 0;
}
function getStats($category,$pos,$modifiers,$map,$stats){
    $collection = array();
    $statTypes = $map[$category];
    foreach($stats as $st){
        if($st['stats_category']==$category){
            foreach($statTypes as $n=>$v){
                if(!isset($collection[$n])){
                    $collection[$n] = 0;
                }
                if($st['stats_name'] == $v){

                    $collection[$n] = array('total'=>$st['total'],
                                            'points'=>$st['points']);
                }
            }
        }
    }
    return $collection;
}
/*
function getStats($category,$pos,$modifiers,$map,$stats){
    
    
    $statTypes = $map[$category];
    //pr($statTypes);
    $collection = array();
    foreach($stats as $s){
        foreach($statTypes as $n=>$v){
            if(!isset($collection[$n])){
                $collection[$n] = 0;
            }
            if($s['stats_name'] == $v){
                $collection[$n] = array('total'=>$s['total'],
                                    'points'=>$s['total'] * getModifierValue($modifiers,$v,$pos));
            }
        }
    }
    
    return $collection;
}
*/
//get last performance value.
$weekly_performance = array();
foreach($data['stats'] as $n=>$v){
    if(!isset($weekly_performance[$v['matchday']])){
        $weekly_performance[$v['matchday']] = $v;
    }else{
        if( $weekly_performance[$v['matchday']]['performance'] == 0){
             $weekly_performance[$v['matchday']] = $v;
        }
    }
}
$data['stats'] = null;
while(sizeof($weekly_performance)>0){
    $data['stats'][] = array_shift($weekly_performance);
}

$weekly_performance = null;

?>
<div id="myClubPage">
     <?php echo $this->element('infobar'); ?>
     <?php if($data['player']!=null):?>
    <div class="headbar tr">
        <div class="club-info fl player-club">
            <div class="fl club-info-entry">
                <h3 class="clubname"><?=h($data['player']['name'])?></h3>
            </div>
        </div>
        <div class="club-info fl">
            <p>Gaji Mingguan</p>
            <h4>ss$ <?=number_format($data['player']['salary'])?></h4>
        </div>
        <div class="club-info fl" style="width:150px;">
            <p>Nilai Transfer</p>
            <h4>
                ss$
                <?php
                    $performance = 0;

                    if(sizeof($data['stats'])>0){
                        if(intval(@$data['stats'][sizeof($data['stats'])-1]['points'])!=0){

                        $performance = getTransferValueBonus(
                                            $data['stats'][sizeof($data['stats'])-1]['performance'],
                                            $data['player']['transfer_value']);
                        }  
                    }
                    
                    $transfer_value = $data['player']['transfer_value'] + $performance;
                    echo number_format($transfer_value);
                ?>
            </h4>
        </div>
        <div class="club-info fl">
            <p>Status</p>
            <h4>N/A</h4>
        </div>
        <div class="club-info fl">
            <h5>
                <?php
                    print ($total_points);
                ?>
                Poin
            </h5>
        </div>
        <div class="club-money fr">
            <a data-team-name="<?=h($club['team_name'])?>" data-price="<?=number_format($transfer_value)?>" data-player-name="<?=$data['player']['name']?>" data-team="<?=$data['player']['original_team_id']?>" data-player="<?=$data['player']['player_id']?>" id="btnSale" class="icon-cart buttons" href="#popup-messages"><span>JUAL</span></a>
            <a class="buttons" href="<?=$this->Html->url('/manage/club/?rooster=1')?>"><span>KEMBALI</span></a>
        </div>
    </div><!-- end .headbar -->
    <div id="thecontent">
        <div class="content">
            <div id="tabs-Info">
                <div class="player-detail">
                    <opta widget="playerprofile" sport="football" competition="8" season="2013" 
                        team="<?=str_replace('t','',$data['player']['original_team_id'])?>" player="<?=str_replace("p","",$data['player']['player_id'])?>" show_image="true" show_nationality="true" opta_logo="false" narrow_limit="400"></opta>
                </div>
                
            	<div class="profileStats-container" style="display: block;">
                  <h2><span>Overall Performance</span></h2>
                  <div class="profileStatsContainer">
                    <div class="profileStats" style="overflow:hidden;">
                        <a href="#" class="statsbox">
                            <h4>Games</h4>
                            <p><?=($main_stats_vals['games'])?></p>
                        </a>
                        <a href="#" class="statsbox">
                            <h4>Passing and Attacking</h4>
                            <p><?=($main_stats_vals['passing_and_attacking'])?></p>
                        </a>
                        <a href="#" class="statsbox">
                            <h4>Defending</h4>
                            <p><?=($main_stats_vals['defending'])?></p>
                        </a>
                       
                        <a href="#" class="statsbox">
                            <h4>Goalkeeping</h4>
                            <p><?=($main_stats_vals['goalkeeper'])?></p>
                        </a>
                       
                        <a href="#" class="statsbox">
                            <h4>Mistakes and Errors</h4>
                            <p><?=($main_stats_vals['mistakes_and_errors'])?></p>
                        </a>
                       
                    </div><!-- end .profileStats -->
                  </div><!-- end .profileStats-container -->
                </div><!-- end .profileStats-container -->     
            </div><!-- end #Info -->
            <?php if(sizeof($data['daily_stats'])>0):?>
            <div id="chartbox" class="row">
                <div class="stats"></div>
            </div>
            <div id="profiletabs">
              <div class="rowTItle">
             	 <h3 class="tabtitle"><span class="fl">PERFORMANCE DETAILS</span></h3>
              </div>
              <div class="tabContainer">
              <div class="col2">
				  <div id="tabs-Games" class="boxTab">
					<div class="profileStatsContainer">
						<div class="profileStats">
						<div class="titleTab">Games</div>
						<table width="100%" border="0" cellspacing="0" cellpadding="0" class="theTable">
							<thead>
								<tr>
									<th>Aksi</th>
									<th class="aligncenter" width="1">Frequency</th>
									<th class="aligncenter" width="1">Poin</th>
								</tr>
							</thead>
							<tbody>
							<?php 
								$profileStats = getStats('games',$pos,$modifiers,$map,$data['overall_stats']);
								if(isset($profileStats)):
									foreach($profileStats as $statsName=>$statsVal):
										//$statsName = ucfirst(str_replace('_',' ',$statsName));
                                        $statsName = stats_translated($statsName,'id');
							?>
							  <tr>
								<td><p class="s-title"><?=$statsName?></p></td>
								<td class="aligncenter">
									<?=number_format($statsVal['total'])?>
								</td>
								<td class="aligncenter">
								   <?=floatval($statsVal['points'])?>
								</td>
							  </tr>
							<?php
								endforeach;
								endif;
							?>
							</tbody>
						</table>
						</div><!-- end .profileStats -->
					</div><!-- end .profileStats-container -->
				  </div><!-- end #Info -->
				  <div id="tabs-Passing" class="boxTab">
					  <div class="profileStatsContainer">
						<div class="profileStats">
						<div class="titleTab">Passing &amp; Attacking</div>
						<table width="100%" border="0" cellspacing="0" cellpadding="0" class="theTable">
							<thead>
								<tr>
									<th>Aksi</th>
									<th class="aligncenter" width="1">Frequency</th>
									<th class="aligncenter" width="1">Poin</th>
								</tr>
							</thead>
							<tbody>
							<?php 
								$profileStats = getStats('passing_and_attacking',$pos,$modifiers,$map,$data['overall_stats']);
								if(isset($profileStats)):
									foreach($profileStats as $statsName=>$statsVal):
										$statsName = stats_translated($statsName,'id');
							?>
							  <tr>
								<td><p class="s-title"><?=$statsName?></p></td>
								<td class="aligncenter">
								   <?=number_format($statsVal['total'])?>
								</td>
								<td class="aligncenter">
									<?=floatval($statsVal['points'])?>
								</td>
							  </tr>
							<?php
								endforeach;
								endif;
							?>
							</tbody>
						</table>
						</div><!-- end .profileStats -->
					</div><!-- end .profileStats-container -->
				  </div><!-- end #tabs-Keuagan -->
			  </div>
              <div class="col2 col2Right">
              <div id="tabs-Defending" class="boxTab">
               <div class="profileStatsContainer">
                    <div class="profileStats">
					<div class="titleTab">Defending</div>
        			<table width="100%" border="0" cellspacing="0" cellpadding="0" class="theTable">
						<thead>
							<tr>
								<th>Aksi</th>
								<th class="aligncenter" width="1">Frequency</th>
								<th class="aligncenter" width="1">Poin</th>
							</tr>
						</thead>
						<tbody>
                        <?php 
                            $profileStats = getStats('defending',$pos,$modifiers,$map,$data['overall_stats']);
                            if(isset($profileStats)):
                                foreach($profileStats as $statsName=>$statsVal):
                                    $statsName = stats_translated($statsName,'id');
                        ?>
                          <tr>
                            <td><p class="s-title"><?=$statsName?></p></td>
                            <td class="aligncenter">
                                <?=number_format($statsVal['total'])?>
                            </td>
                            <td class="aligncenter">
                                <?=floatval($statsVal['points'])?>
                            </td>
                          </tr>
                        <?php
                            endforeach;
                            endif;
                        ?>
						</tbody>
					</table>
                    </div><!-- end .profileStats -->
                </div><!-- end .profileStats-container -->
              </div><!-- end #tabs-Squad -->
              <?php if($pos=='g'):?>
              <div id="tabs-Goalkeeping" class="boxTab">
                   <div class="profileStatsContainer">
                    <div class="profileStats">
					<div class="titleTab">Goalkeeping</div>
        			<table width="100%" border="0" cellspacing="0" cellpadding="0" class="theTable">
						<thead>
							<tr>
								<th>Aksi</th>
								<th class="aligncenter" width="1">Frequency</th>
								<th class="aligncenter" width="1">Poin</th>
							</tr>
						</thead>
						<tbody>
                        <?php 
                            $profileStats = getStats('goalkeeper',$pos,$modifiers,$map,$data['overall_stats']);
                            if(isset($profileStats)):
                                foreach($profileStats as $statsName=>$statsVal):
                                     $statsName = stats_translated($statsName,'id');
                        ?>
                          <tr>
                            <td><p class="s-title"><?=$statsName?></p></td>
                            <td class="aligncenter">
                                <?=number_format($statsVal['total'])?>
                            </td>
                            <td class="aligncenter">
                                <?=floatval($statsVal['points'])?>
                            </td>
                          </tr>
                        <?php
                            endforeach;
                            endif;
                        ?>
						</tbody>
					</table>
                    </div><!-- end .profileStats -->
                </div><!-- end .profileStats-container -->
              </div><!-- end #tabs-Staff -->
             <?php endif;?>
              <div id="tabs-Mistakes" class="boxTab">
                   <div class="profileStatsContainer">
                    <div class="profileStats">
					<div class="titleTab">Mistakes &amp; Errors</div>
        			<table width="100%" border="0" cellspacing="0" cellpadding="0" class="theTable">
						<thead>
							<tr>
								<th>Aksi</th>
								<th class="aligncenter" width="1">Frequency</th>
								<th class="aligncenter" width="1">Poin</th>
							</tr>
						</thead>
						<tbody>
                        <?php 
                            $profileStats = getStats('mistakes_and_errors',$pos,$modifiers,$map,$data['overall_stats']);
                            if(isset($profileStats)):
                                foreach($profileStats as $statsName=>$statsVal):
                                    $statsName = stats_translated($statsName,'id');
                        ?>
                          <tr>
                            <td><p class="s-title"><?=$statsName?></p></td>
                            <td class="aligncenter">
                                <?=number_format($statsVal['total'])?>
                            </td>
                            <td class="aligncenter">
                                <?=floatval($statsVal['points'])?>
                            </td>
                          </tr>
                        <?php
                            endforeach;
                            endif;
                        ?>
						</tbody>
					</table>
                    </div><!-- end .profileStats -->
                </div><!-- end .profileStats-container -->
              </div><!-- end #tabs-Staff -->
              </div><!-- end #tabcontainer -->
			  </div>
            </div><!-- end #clubtabs -->
            <?php endif;?>
        </div><!-- end .content -->



    </div><!-- end #thecontent -->
    <?php else:?>
    <div id="thecontent">
        <div class="content">
            <div>
                <h1 class="yellow">Pemain ini bukan anggota Klab.</h1>
               
               
            </div><!-- end #logoutpage -->
        </div>
    </div>
    <?php endif;?>
</div><!-- end #myClubPage -->
<!--popups-->
<div class="popup">
    <div class="popupContainer popup-small" id="popup-messages">
        <div class="popupHeader">
        </div><!-- END .popupHeader -->
        <div class="popupContent">
            <div class="entry-popup">
                yellow
            </div><!--END .entry-popup-->
        </div><!-- END .popupContent -->
    </div><!-- END .popupContainer -->
</div><!-- END .popup --> 




<?=$this->Html->script(array('highcharts'))?>
<script>
var stats  = [];
for(var i in daily_stats){
    stats.push({
        ts:daily_stats[i].fixture.ts*1000,
        games:daily_stats[i].games,
        passing_and_attacking:daily_stats[i].passing_and_attacking,
       
        defending:daily_stats[i].defending,
        goalkeeping:daily_stats[i].goalkeeper,
        mistakes:daily_stats[i].mistakes_and_errors
    });
}
var categories = [];
var games = [];
var passing_and_attacking = [];
var defending = [];
var goalkeeping = [];
var mistakes = [];

$.each(stats,function(k,v){
  categories.push(v.ts);
  games.push(parseFloat(v.games));
  passing_and_attacking.push(parseFloat(v.passing_and_attacking));
  defending.push(parseFloat(v.defending));
  goalkeeping.push(parseFloat(v.goalkeeping));
  mistakes.push(parseFloat(v.mistakes) * -1);
});

$('.stats').highcharts({
    chart: {
        type: 'area',
        backgroundColor:'#ccc',
        style: {
            color: "#000"
        },
    },
    title: {
        text: 'Weekly Performance',
        style: {
          color: '#000'
        }
    },
   
    xAxis: {
        type: 'datetime',
        dateTimeLabelFormats: { // don't display the dummy year
            day: '%e. %b',
            month: '%e. %b',
            year: '%b'
        },
        categories: categories,
        labels: {
            align:'left',
            rotation:60,
            formatter: function() {
                
                return Highcharts.dateFormat('%d/%m', this.value);
            }
        }
    },
    yAxis: {
        title: {
            text: 'Total',
            style:{
              color:'#000'
            }
        },

    },
    tooltip: {
        enabled: true,
        formatter: function() {
            console.log(this);
            return '<strong>'+Highcharts.dateFormat('%d/%m/%Y', this.x)+'</strong><br/>'+
                    this.series.name+': '+ this.y +'';
        }
    },
    plotOptions: {
        area: {
            stacking: 'normal',
            lineColor: '#666666',
            lineWidth: 1,
            marker: {
                lineWidth: 1,
                lineColor: '#666666'
            }
        }
    },
    credits:false,
    series: [
        {
            name: 'Games',
            data: games
        },
        {
            name: 'Passing & Attacking',
            data: passing_and_attacking
        },
        {
            name: 'Defending',
            data: defending
        },
       
        {
            name: 'Goalkeeping',
            data: goalkeeping
        },
       
        {
            name: 'Mistakes and Errors',
            data: mistakes
        },

    ]
});
</script>
<script>
$( "#profiletabs" ).tabs({
    create:function(event,ui){
        var thisTab = $(ui.tab).find('a').html();
        $("#profiletabs h3").html(thisTab);
        //console.log('create');
    },
    activate:function(event,ui){
        var thisTab = $(ui.newTab).find('a').html();
        $("#profiletabs h3").html(thisTab);
       // console.log('activate');
    }
});


</script>

<script>
$("#btnSale").fancybox({
    beforeLoad : function(){
      $("#popup-messages .popupContent .entry-popup").html('');
      $('.saving').hide();
      $('.confirm').show();
      $('.success').hide();
      $('.failure').hide();
      render_view(tplsale,"#popup-messages .popupContent .entry-popup",{
        player_id:$(this.element).data('player'),
        team_id:$(this.element).data('team'),
        player_name:$(this.element).data('player-name'),
        team_name:$(this.element).data('team-name'),
        transfer_value:$(this.element).data('price')
      });
      $jqOpta.widgetStart(_optaParams);
    },
});
</script>

<script type="text/template" id="tplsale">
    <%
      var uid = player_id.replace('p','');
      var team = team_id.replace('t','');
    %>
    <div class="confirm">
        <h1>Apakah kamu ingin menjual pemain ini?</h1>
        <h3>Pemain yang sudah dijual akan hilang dari lineup dan tidak dapat di undo</h3>
        <h4>ss$<%=transfer_value%></h4>
        <opta widget="playerprofile" sport="football" competition="8" season="2013" team="<%=team%>" 
          player="<%=uid%>" show_image="true" show_nationality="true" opta_logo="false" 
          narrow_limit="400"></opta>
        <p><a href="#/sale/<%=player_id%>/1" class="button">Jual</a>
            <a href="#" class="button" onclick="$.fancybox.close();return false;">Batal</a></p>
    </div>
    <div class="saving" style="display:none;">
        <h1>Menjual Pemain.</h1>
        <h3>Harap tunggu sebentar..</h3>
        <p><img src="<?=$this->Html->url('/css/fancybox/fancybox_loading@2x.gif')?>"/></p>
    </div>
    <div class="success" style="display:none;">
        <h1>Penjualan Berhasil</h1>
        <h3><%=player_name%> sudah dijual dari <%=team_name%></h3>
    </div>
    <div class="failure" style="display:none;">
        <h1>Penjualan Tidak Berhasil</h1>
        <h3>Silahkan coba kembali !</h3>
    </div>
    <div class="invalid_transfer" style="display:none;">
        <h1>Dijual lagi??</h1>
        <h3>Pemain yang baru dibeli tidak bisa di jual dalam transfer window yang sama. 
            Next time, jangan buru-buru jual pemain elo ya !</h3>
        
    </div>
    <div class="window_closed" style="display:none;">
        <h1>Transfer Window Sedang Tutup</h1>
        <h3>Transfer window SuperSoccer Football Manager sedang tutup, silahkan balik lagi tanggal <?=$transfer_date?>.</h3>
        
    </div>
</script>