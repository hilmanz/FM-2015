
<div id="fullcontent">
   <div>
      <h3><?=$report['results']['home_name']?> VS <?=$report['results']['away_name']?></h3>
    </div>
<?php
$data = $report['home'];
?>

    <div>
      <a href="<?=$this->Html->url('/pages/team/?team_id='.$team_id)?>" class="button">Back</a>
    </div>
    <div class="section">
        <div class="col12">
            <div class="widget">
                <div class="widget-title">
                    <h3><a href="<?=$this->Html->url('/pages/matchstats/?game_id='.$report['results']['game_id'].'&team_id='.$report['results']['home_team'])?>"><?=$report['results']['home_name']?></a></h3>
                </div><!-- end .widget-title -->
                <div class="widget-content">
                  <div style="text-align:center">
                  <table width="100%" border="0" cellspacing="0" cellpadding="0" class="blacktable">
                    <thead>
                          <tr>
                            <th><h4>Overview</h4></th>
                            <th><h5></h5></th>
                          </tr>
                      </thead>
                      <tbody>
                        <tr>
                            <td>League Position</td>
                            <td class="tright">
                              N/A
                            </td>
                          </tr>
                          <tr>
                            <td>Most Influencal Player</td>
                            <td class="tright">
                              <?=@$data['most_influential_player'][0]['name']?>
                            </td>
                          </tr>
                          <tr>
                            <td>Top Assist</td>
                            <td class="tright">
                              <?php
                                if(sizeof($data['top_assist'])>0){
                                  $top_assist = $data['top_assist'][0]['name']."({$data['top_assist'][0]['total']})";
                                  for($i=1;$i<sizeof($data['top_assist']);$i++){
                                      if($data['top_assist'][$i]['total']==$data['top_assist'][$i-1]['total']){
                                        $top_assist.=','.$data['top_assist'][$i]['name']."({$data['top_assist'][$i]['total']})";
                                      }
                                  }
                                }else{
                                  $top_assist = '';
                                }
                              ?>
                              <?=@$top_assist?>
                            </td>
                          </tr>
                          <tr>
                            <td>Top Scorer</td>
                            <td class="tright">
                              <?php
                               if(sizeof($data['top_scorer'])>0){
                                $top_scorer = $data['top_scorer'][0]['name']."({$data['top_scorer'][0]['total']})";
                                for($i=1;$i<sizeof($data['top_scorer']);$i++){
                                    if($data['top_scorer'][$i]['total']==$data['top_scorer'][$i-1]['total']){
                                      $top_scorer.=','.$data['top_scorer'][$i]['name']."({$data['top_scorer'][$i]['total']})";
                                    }
                                }
                              }else{
                                $top_scorer = '';
                              }
                              ?>
                              <?=@$top_scorer?>
                              
                            </td>
                          </tr>
                          <tr>
                            <td>Dangerous Passer</td>
                            <td class="tright">
                              <?php
                                if(sizeof($data['dangerous_passer'])>0){
                                  $dangerous_passer = $data['dangerous_passer'][0]['name'];
                                  for($i=1;$i<sizeof($data['dangerous_passer']);$i++){
                                      if($data['dangerous_passer'][$i]['total']==$data['dangerous_passer'][$i-1]['total']){
                                        $dangerous_passer.=','.$data['dangerous_passer'][$i]['name'];
                                      }
                                  }
                                }else{
                                  $dangerous_passer = '';
                                }
                              ?>
                              <?=@$dangerous_passer?>
                            </td>
                          </tr>
                          <tr>
                            <td>Greatest Liability</td>
                            <td class="tright">
                             
                              <?php
                                if(sizeof($data['greatest_liability'])>0){
                                  $liable = $data['greatest_liability'][0]['name'];
                                  for($i=1;$i<sizeof($data['greatest_liability']);$i++){
                                      if($data['greatest_liability'][$i]['total']==$data['greatest_liability'][$i-1]['total']){
                                        $liable.=','.$data['greatest_liability'][$i]['name'];
                                      }
                                  }
                                }else{
                                  $liable = '';
                                }
                              ?>
                              <?=@$liable?>
                            </td>
                          </tr>
                      </tbody>
                    </table>
                  </div>
                </div><!-- end .widget-content -->
                
        </div><!-- end .col4 -->
    </div><!-- end .section -->
    
    <div class="section">
        <div class="col12">
            <div class="widget">
                <div class="widget-title">
                    <h3>Attacking Play</h3>
                </div><!-- end .widget-title -->
                <div class="widget-content">
                  <div style="text-align:center">
                  <table width="100%" border="0" cellspacing="0" cellpadding="0" class="blacktable">
                    <thead>
                          <tr>
                            <th><h4>Stats</h4></th>
                            
                            <th><h5>Chances</h5></th>
                            <th><h5>Goals</h5></th>
                            <th><h5>Conversion Rate</h5></th>
                            <th><h5>Average/Game</h5></th>
                          </tr>
                      </thead>
                      <tbody>
                        <?php
                        foreach($data['attacking_play'] as $stats=>$st):
                          
                        ?>
                        <tr>
                          <td><?=ucfirst(str_replace('_',' ',$stats))?></td>
                          <td class="">
                             <?=$st['chances']?> (<?=round($st['efficiency']*100,1)?> %)
                          </td>
                          <td class="">
                             <?=$st['goals']?>
                          </td>
                          <td class="">
                             <?=round($st['conversion_rate']*100,1)?> %
                          </td>
                          <td class="">
                             <?php
                              $overall = $home_overall['attacking_play'][$stats];
                             ?>
                              <?=round($overall['frequency']/$data['total_games'],1)?>
                          </td>
                        </tr>
                      <?php endforeach;?>
                      </tbody>
                    </table>
                  </div>
                </div><!-- end .widget-content -->
            </div>
        </div><!-- end .col4 -->
    </div><!-- end .section -->

    <div class="section">
        <div class="col4">
            <div class="widget">
                <div class="widget-title">
                    <h3>Goals</h3>
                </div><!-- end .widget-title -->
                <div class="widget-content">
                  <div style="text-align:center">
                  <table width="100%" border="0" cellspacing="0" cellpadding="0" class="blacktable">
                    <thead>
                          <tr>
                            <th><h4>Stats</h4></th>
                            <th><h5>#</h5></th>
                           
                            <th><h5>Avg/Game</h5></th>
                          </tr>
                      </thead>
                      <tbody>
                        <?php
                        foreach($data['goals'] as $stats=>$st):
                        ?>
                        <tr>
                          <td><?=ucfirst(str_replace('_',' ',$stats))?></td>
                          <td class="">
                             <?=$st['total']?>
                          </td>
                          <td class="">
                             <?=round($st['total']/$data['total_games'],1)?>
                          </td>
                         
                        </tr>
                      <?php endforeach;?>
                      </tbody>
                    </table>
                  </div>
                </div><!-- end .widget-content -->
              </div>  
        </div><!-- end .col4 -->
        <div class="col4">
            <div class="widget">
                <div class="widget-title">
                    <h3>Shootings</h3>
                </div><!-- end .widget-title -->
                <div class="widget-content">
                  <div style="text-align:center">
                  <table width="100%" border="0" cellspacing="0" cellpadding="0" class="blacktable">
                    <thead>
                          <tr>
                            <th><h4>Stats</h4></th>
                            <th><h5>#</h5></th>
                            <th><h5>Accuracy</h5></th>
                            <th><h5>Avg/Game</h5></th>
                            <th><h5>Goals</h5></th>
                          </tr>
                      </thead>
                      <tbody>
                        <?php
                        foreach($data['shooting'] as $stats=>$st):
                        ?>
                        <tr>
                          <td><?=ucfirst(str_replace('_',' ',$stats))?></td>
                          <td class="">
                             <?=$st['total']?>
                          </td>
                          <td class="">
                             <?=round($st['accuracy']*100,1)?>%
                          </td>
                          <td class="">
                             <?=round($st['total']/$data['total_games'],1)?>
                          </td>
                          <td class="">
                             <?=$st['goals']?>
                          </td>
                        </tr>
                      <?php endforeach;?>
                      </tbody>
                    </table>
                  </div>
                </div><!-- end .widget-content -->
              </div>  
        </div><!-- end .col4 -->
        <div class="col4">
            <div class="widget">
                <div class="widget-title">
                    <h3>Ball Movement</h3>
                </div><!-- end .widget-title -->
                <div class="widget-content">
                  <div style="text-align:center">
                  <table width="100%" border="0" cellspacing="0" cellpadding="0" class="blacktable">
                    <thead>
                          <tr>
                            <th><h4>Stats</h4></th>
                            <th><h5>#</h5></th>
                            <th><h5>Avg/Game</h5></th>
                          </tr>
                      </thead>
                      <tbody>
                        <?php
                        foreach($data['ball_movement'] as $stats=>$st):
                        ?>
                        <tr>
                          <td><?=ucfirst(str_replace('_',' ',$stats))?></td>
                          <td class="">
                             <?=round($st['total'],1)?>
                             <?php if($stats=='chances_conversion'): echo "%";endif;?>
                          </td>
                          <td class="">
                            <?php if($stats!='chances_conversion'):?>
                             <?=round($st['total']/$data['total_games'],1)?>
                           <?php endif;?>
                          </td>
                         
                        </tr>
                      <?php endforeach;?>
                      </tbody>
                    </table>
                  </div>
                </div><!-- end .widget-content -->
              </div>  
        </div><!-- end .col4 -->
        <div class="col4">
            <div class="widget">
                <div class="widget-title">
                    <h3>Attacking Style</h3>
                </div><!-- end .widget-title -->
                <div class="widget-content">
                  <div style="text-align:center">
                  <table width="100%" border="0" cellspacing="0" cellpadding="0" class="blacktable">
                    <thead>
                          <tr>
                            <th><h4>Stats</h4></th>
                            <th><h5>#</h5></th>
                            <th><h5>%</h5></th>
                            <th><h5>Avg/Game</h5></th>
                          </tr>
                      </thead>
                      <tbody>
                        <?php
                        foreach($data['attacking_style'] as $stats=>$st):
                        ?>
                        <tr>
                          <td><?=ucfirst(str_replace('_',' ',$stats))?></td>
                          <td class="">
                             <?=$st['total']?>
                          </td>
                          <td class="">
                             <?=round($st['average']*100,1)?> %
                          </td>
                          <td class="">
                             <?php
                              $overall = $home_overall['attacking_style'][$stats];

                             ?>
                             <?=round($overall['total']/$data['total_games'],1)?>
                          </td>
                         
                        </tr>
                      <?php endforeach;?>
                      </tbody>
                    </table>
                  </div>
                </div><!-- end .widget-content -->
              </div>  
        </div><!-- end .col4 -->
        <div class="col4">
            <div class="widget">
                <div class="widget-title">
                    <h3>Dribbling</h3>
                </div><!-- end .widget-title -->
                <div class="widget-content">
                  <div style="text-align:center">
                  <table width="100%" border="0" cellspacing="0" cellpadding="0" class="blacktable">
                    <thead>
                          <tr>
                            <th><h4>Stats</h4></th>
                            <th><h5>#</h5></th>
                            <th><h5>Avg/Game</h5></th>
                          </tr>
                      </thead>
                      <tbody>
                        <?php
                        foreach($data['dribbling'] as $stats=>$st):
                        ?>
                        <tr>
                          <td><?=ucfirst(str_replace('_',' ',$stats))?></td>
                          <td class="">
                             <?=$st?>
                          </td>
                         
                          <td class="">
                            <?php
                              $overall = $home_overall['dribbling'][$stats];
                             ?>
                             <?=round($overall/$data['total_games'],1)?>
                             
                          </td>
                         
                        </tr>
                      <?php endforeach;?>
                      </tbody>
                    </table>
                  </div>
                </div><!-- end .widget-content -->
            </div>
        </div><!-- end .col4 -->
      <div class="col4 ">
            <div class="widget">
                <div class="widget-title">
                    <h3>Passing Style</h3>
                </div><!-- end .widget-title -->
                <div class="widget-content">
                  <div style="text-align:center">
                  <table width="100%" border="0" cellspacing="0" cellpadding="0" class="blacktable">
                    <thead>
                          <tr>
                            <th><h4>Stats</h4></th>
                            <th><h5>#</h5></th>
                            <th><h5>%</h5></th>
                            <th><h5>Accuracy</h5></th>
                            <th><h5>Avg/Game</h5></th>
                          </tr>
                      </thead>
                      <tbody>
                        <?php
                        $total_pass = $data['passing_style']['total_pass']['total'];

                        $total_passings =  $data['passing_style']['long_ball']['total']+
                                          $data['passing_style']['short_passes']['total']+
                                          $data['passing_style']['launches']['total']+
                                          $data['passing_style']['through_balls']['total']+
                                          $data['passing_style']['chipped_passes']['total'];
                        foreach($data['passing_style'] as $stats=>$st):
                          if($stats!='total_pass'):
                        ?>
                        <tr>
                          <td><?=ucfirst(str_replace('_',' ',$stats))?></td>
                          <td class="">
                            <?php if($stats!='leftside_pass' && $stats!='rightside_pass' && $stats!='short_passes'):?>
                             <?=$st['accurate']?> /<?=$st['total']?>
                            <?php else:?>
                               <?=$st['total']?>
                            <?php endif;?>
                          </td>
                           <td>
                              <?php
                                if($stats!='leftside_pass' && $stats!='rightside_pass' && $stats!='short_passes' 
                                  && $stats!='forward_passes' && $stats!='accurate_passes'){
                                 
                                  echo round(@($st['total'] / $total_passings)*100,1).'%';

                                }else if($stats=='short_passes'){
                                  echo round(($st['total'] / $total_passings)*100,1).'%';
                                }else if($stats=='forward_passes'){
                                  echo round(($st['total'] / $total_pass)*100,1).'%';
                                }else if($stats=='leftside_pass' || $stats=='rightside_pass'){
                                  echo round(($st['average'])*100,1).'%';
                                }else{
                                  //echo 0.'%';
                                }
                              ?>
                           </td>
                          <td class="">
                              <?php if($stats!='short_passes'&&$stats!='leftside_pass' && $stats!='rightside_pass'):?>
                              <?=round($st['accuracy']*100,1)?> %
                              <?php endif;?>
                          </td>
                          <td class="">
                            <?php
                              $overall = $home_overall['passing_style'][$stats];

                             ?>
                             <?=round($overall['total']/$data['total_games'],1)?>
                             
                          </td>
                         
                        </tr>
                      <?php endif;endforeach;?>
                      </tbody>
                    </table>
                  </div>
                </div><!-- end .widget-content -->
            </div>    
        </div><!-- end .col4 -->
      <div class="col4 col4last">
            <div class="widget">
                <div class="widget-title">
                    <h3>defending_style</h3>
                </div><!-- end .widget-title -->
                <div class="widget-content">
                  <div style="text-align:center">
                  <table width="100%" border="0" cellspacing="0" cellpadding="0" class="blacktable">
                    <thead>
                          <tr>
                            <th><h4>Stats</h4></th>
                            <th><h5>#</h5></th>
                            <th><h5>%</h5></th>
                            <th><h5>Avg/Game</h5></th>
                          </tr>
                      </thead>
                      <tbody>
                        <?php
                        foreach($data['defending_style'] as $stats=>$st):
                        ?>
                        <tr>
                          <td><?=ucfirst(str_replace('_',' ',$stats))?></td>
                          <td class="">
                             <?=$st['total']?>
                          </td>
                         <td class="">
                             <?=round($st['average']*100,1)?> %
                          </td>
                          <td class="">
                            <?php
                              $overall = $home_overall['defending_style'][$stats];

                             ?>
                             <?=round($overall['total']/$data['total_games'],1)?>
                             
                          </td>
                         
                        </tr>
                      <?php endforeach;?>
                      </tbody>
                    </table>
                  </div>
                </div><!-- end .widget-content -->
          </div>
        </div><!-- end .col4 -->
        
        
    </div><!-- end .section -->

    <div class="section">
      <div class="col4 ">
            <div class="widget">
                <div class="widget-title">
                    <h3>Goalkeeping</h3>
                </div><!-- end .widget-title -->
                <div class="widget-content">
                  <div style="text-align:center">
                  <table width="100%" border="0" cellspacing="0" cellpadding="0" class="blacktable">
                    <thead>
                          <tr>
                            <th><h4>Stats</h4></th>
                            <th><h5>#</h5></th>
                            <th><h5>%</h5></th>
                            <th><h5>Avg/Game</h5></th>
                          </tr>
                      </thead>
                      <tbody>
                        <?php
                        foreach($data['goalkeeping'] as $stats=>$st):

                        ?>
                        <tr>
                          <td><?=ucfirst(str_replace('_',' ',$stats))?></td>
                          <td class="">
                             <?=$st['total']?><?php if(isset($st['total2'])): echo '/'.$st['total2'];endif;?>
                          </td>
                          <td class="">
                             <?=round($st['average']*100,1)?> %
                          </td>
                          <td class="">
                            <?php
                              $overall = $home_overall['goalkeeping'][$stats];

                             ?>
                             <?=round($overall['total']/$data['total_games'],1)?>
                             
                          </td>
                         
                        </tr>
                      <?php endforeach;?>
                      </tbody>
                    </table>
                  </div>
                </div><!-- end .widget-content -->
            </div>    
        </div><!-- end .col4 -->
      <div class="col4">
            <div class="widget">
                <div class="widget-title">
                    <h3>DEF strength &amp; weakness</h3>
                </div><!-- end .widget-title -->
                <div class="widget-content">
                  <div style="text-align:center">
                  <table width="100%" border="0" cellspacing="0" cellpadding="0" class="blacktable">
                    <thead>
                          <tr>
                            <th><h4>Stats</h4></th>
                            <th><h5>#</h5></th>
                            <th><h5>%</h5></th>
                            <th><h5>Avg/Game</h5></th>
                          </tr>
                      </thead>
                      <tbody>
                        <?php
                        foreach($data['defending_strength_and_weakness'] as $stats=>$st):
                        ?>
                        <tr>
                          <td><?=ucfirst(str_replace('_',' ',$stats))?></td>
                          <td class="">
                             <?=$st['total']?><?php if(isset($st['total2'])): echo '/'.$st['total2'];endif;?>
                          </td>
                         <td class="">
                             <?php if($stats != 'challenge_lost' &&
                                      $stats != 'attempts_conceded_from_fastbreak' &&
                                      $stats != 'attempts_conceded_from_setpieces' &&
                                      $stats != 'error_lead_to_shot' &&
                                      $stats != 'error_lead_to_goal' &&
                                      $stats != 'total_errors' &&
                                      $stats != 'penalty_conceded' &&
                                      $stats != 'fouls_conceded_in_attacking_3rd'):?>
                              <?=round($st['average']*100,1)?> %
                              <?php endif;?>
                          </td>
                          <td class="">
                             <?php
                              $overall = $home_overall['defending_strength_and_weakness'][$stats];
                             ?>
                             <?=round($overall['total']/$data['total_games'],1)?>
                          </td>
                         
                        </tr>
                      <?php endforeach;?>
                      </tbody>
                    </table>
                  </div>
                </div><!-- end .widget-content -->
          </div>
        </div><!-- end .col4 -->

        <div class="col4 ">
            <div class="widget">
                <div class="widget-title">
                    <h3>Aerial Strength</h3>
                </div><!-- end .widget-title -->
                <div class="widget-content">
                  <div style="text-align:center">
                  <table width="100%" border="0" cellspacing="0" cellpadding="0" class="blacktable">
                    <thead>
                          <tr>
                            <th><h4>Stats</h4></th>
                            <th><h5>#</h5></th>
                            <th><h5>%</h5></th>
                            <th><h5>Avg/Game</h5></th>
                          </tr>
                      </thead>
                      <tbody>
                        <?php
                        foreach($data['aerial_strength'] as $stats=>$st):
                        ?>
                        <tr>
                          <td><?=ucfirst(str_replace('_',' ',$stats))?></td>
                          <td class="">
                             <?=$st['total']?><?php if(isset($st['total2'])): echo '/'.$st['total2'];endif;?>
                          </td>
                          <td class="">
                             <?=round($st['average']*100,1)?> %
                          </td>
                          <td class="">
                             <?php
                              $overall = $home_overall['aerial_strength'][$stats];
                             ?>
                             <?=round($overall['total']/$data['total_games'],1)?>
                             
                          </td>
                         
                        </tr>
                      <?php endforeach;?>
                      </tbody>
                    </table>
                  </div>
                </div><!-- end .widget-content -->
            </div>    
        </div><!-- end .col4 -->
      <div class="col4 col4last">
            <div class="widget">
                <div class="widget-title">
                    <h3>Set Plays</h3>
                </div><!-- end .widget-title -->
                <div class="widget-content">
                  <div style="text-align:center">
                  <table width="100%" border="0" cellspacing="0" cellpadding="0" class="blacktable">
                    <thead>
                          <tr>
                            <th><h4>Stats</h4></th>
                            <th><h5>#</h5></th>
                            <th><h5>Accuracy</h5></th>
                            <th><h5>Chance</h5></th>
                            <th><h5>Avg/Game</h5></th>
                          </tr>
                      </thead>
                      <tbody>
                        <?php
                        foreach($data['setplays'] as $stats=>$st):
                        ?>
                        <tr>
                          <td><?=ucfirst(str_replace('_',' ',$stats))?></td>
                          <td class="">
                             <?=$st['total']?><?php if(isset($st['total2'])): echo '/'.$st['total2'];endif;?>
                          </td>
                         <td class="">
                          <?php if($stats!='corners_won' && $stats!='freekicks_won'):?>
                             <?=round($st['accuracy']*100,1)?> %
                          <?php endif;?>
                          </td>
                          <td class="">
                             <?php if($stats!='corners_won' && $stats!='freekicks_won'):?>
                             <?=round($st['chance_ratio']*100,1)?> %
                           <?php endif;?>
                          </td>
                          <td class="">
                             <?php
                              $overall = $home_overall['setplays'][$stats];
                             ?>
                             <?=round($overall['total']/$data['total_games'],1)?>
                          </td>
                         
                        </tr>
                      <?php endforeach;?>
                      </tbody>
                    </table>
                  </div>
                </div><!-- end .widget-content -->
          </div>
        </div><!-- end .col4 -->
        
        
    </div><!-- end .section -->



<!-- AWAY TEAM STATS -->


<?php
$data = $report['away'];
?>

    <div>
      <a href="<?=$this->Html->url('/pages/team/?team_id='.$team_id)?>" class="button">Back</a>
    </div>
    <div class="section">
        <div class="col12">
            <div class="widget">
                <div class="widget-title">
                    <h3><a href="<?=$this->Html->url('/pages/matchstats/?game_id='.$report['results']['game_id'].'&team_id='.$report['results']['away_team'])?>">
                      <?=$report['results']['away_name']?>
                    </a></h3>
                </div><!-- end .widget-title -->
                <div class="widget-content">
                  <div style="text-align:center">
                  <table width="100%" border="0" cellspacing="0" cellpadding="0" class="blacktable">
                    <thead>
                          <tr>
                            <th><h4>Overview</h4></th>
                            <th><h5></h5></th>
                          </tr>
                      </thead>
                      <tbody>
                        <tr>
                            <td>League Position</td>
                            <td>
                              N/A
                            </td>
                          </tr>
                          <tr>
                            <td>Most Influencal Player</td>
                            <td>
                              <?=@$data['most_influential_player'][0]['name']?>
                            </td>
                          </tr>
                          <tr>
                            <td>Top Assist</td>
                            <td>
                              <?=@$data['top_assist'][0]['name']?>
                            </td>
                          </tr>
                          <tr>
                            <td>Top Scorer</td>
                            <td>
                              <?=@$data['top_scorer'][0]['name']?>
                            </td>
                          </tr>
                          <tr>
                            <td>Dangerous Passer</td>
                            <td>
                              <?php
                                if(sizeof($data['dangerous_passer'])>0){
                                  $dangerous_passer = $data['dangerous_passer'][0]['name'];
                                  for($i=1;$i<sizeof($data['dangerous_passer']);$i++){
                                      if($data['dangerous_passer'][$i]['total']==$data['dangerous_passer'][$i-1]['total']){
                                        $dangerous_passer.=','.$data['dangerous_passer'][$i]['name'];
                                      }
                                  }
                                }else{
                                  $dangerous_passer = '';
                                }
                              ?>
                              <?=@$dangerous_passer?>
                            </td>
                          </tr>
                          <tr>
                            <td>Greatest Liability</td>
                            <td>
                               <?php
                                if(sizeof($data['greatest_liability'])>0){
                                  $liable = $data['greatest_liability'][0]['name'];
                                  for($i=1;$i<sizeof($data['greatest_liability']);$i++){
                                      if($data['greatest_liability'][$i]['total']==$data['greatest_liability'][$i-1]['total']){
                                        $liable.=','.$data['greatest_liability'][$i]['name'];
                                      }
                                  }
                                }else{
                                  $liable = '';
                                }
                              ?>
                              <?=@$liable?>
                            </td>
                          </tr>
                      </tbody>
                    </table>
                  </div>
                </div><!-- end .widget-content -->
                
        </div><!-- end .col4 -->
    </div><!-- end .section -->
    
    <div class="section">
        <div class="col12">
            <div class="widget">
                <div class="widget-title">
                    <h3>Attacking Play</h3>
                </div><!-- end .widget-title -->
                <div class="widget-content">
                  <div style="text-align:center">
                  <table width="100%" border="0" cellspacing="0" cellpadding="0" class="blacktable">
                    <thead>
                          <tr>
                            <th><h4>Stats</h4></th>
                            
                            <th><h5>Chances</h5></th>
                            <th><h5>Goals</h5></th>
                            <th><h5>Conversion Rate</h5></th>
                            <th><h5>Average/Game</h5></th>
                          </tr>
                      </thead>
                      <tbody>
                        <?php
                        foreach($data['attacking_play'] as $stats=>$st):
                          
                        ?>
                        <tr>
                          <td><?=ucfirst(str_replace('_',' ',$stats))?></td>
                          
                         
                          <td class="">
                             <?=$st['chances']?> (<?=round($st['efficiency']*100,1)?> %)
                          </td>
                          <td class="">
                             <?=$st['goals']?>
                          </td>
                          <td class="">
                             <?=round($st['conversion_rate']*100,1)?> %
                          </td>
                          <td class="">
                             <?php
                              $overall = $away_overall['attacking_play'][$stats];
                             ?>
                              <?=round($overall['frequency']/$data['total_games'],1)?>
                          </td>
                        </tr>
                      <?php endforeach;?>
                      </tbody>
                    </table>
                  </div>
                </div><!-- end .widget-content -->
            </div>
        </div><!-- end .col4 -->
    </div><!-- end .section -->

    <div class="section">
        <div class="col4">
            <div class="widget">
                <div class="widget-title">
                    <h3>Goals</h3>
                </div><!-- end .widget-title -->
                <div class="widget-content">
                  <div style="text-align:center">
                  <table width="100%" border="0" cellspacing="0" cellpadding="0" class="blacktable">
                    <thead>
                          <tr>
                            <th><h4>Stats</h4></th>
                            <th><h5>#</h5></th>
                           
                            <th><h5>Avg/Game</h5></th>
                          </tr>
                      </thead>
                      <tbody>
                        <?php
                        foreach($data['goals'] as $stats=>$st):
                        ?>
                        <tr>
                          <td><?=ucfirst(str_replace('_',' ',$stats))?></td>
                          <td class="">
                             <?=$st['total']?>
                          </td>
                          <td class="">
                             <?=round($st['total']/$data['total_games'],1)?>
                          </td>
                         
                        </tr>
                      <?php endforeach;?>
                      </tbody>
                    </table>
                  </div>
                </div><!-- end .widget-content -->
              </div>  
        </div><!-- end .col4 -->
        <div class="col4">
            <div class="widget">
                <div class="widget-title">
                    <h3>Shootings</h3>
                </div><!-- end .widget-title -->
                <div class="widget-content">
                  <div style="text-align:center">
                  <table width="100%" border="0" cellspacing="0" cellpadding="0" class="blacktable">
                    <thead>
                          <tr>
                            <th><h4>Stats</h4></th>
                            <th><h5>#</h5></th>
                            <th><h5>Accuracy</h5></th>
                            <th><h5>Avg/Game</h5></th>
                            <th><h5>Goals</h5></th>
                          </tr>
                      </thead>
                      <tbody>
                        <?php
                        foreach($data['shooting'] as $stats=>$st):
                        ?>
                        <tr>
                          <td><?=ucfirst(str_replace('_',' ',$stats))?></td>
                          <td class="">
                             <?=$st['total']?>
                          </td>
                          <td class="">
                             <?=round($st['accuracy']*100,1)?>%
                          </td>
                          <td class="">
                             <?=round($st['total']/$data['total_games'],1)?>
                          </td>
                          <td class="">
                             <?=$st['goals']?>
                          </td>
                        </tr>
                      <?php endforeach;?>
                      </tbody>
                    </table>
                  </div>
                </div><!-- end .widget-content -->
              </div>  
        </div><!-- end .col4 -->
        <div class="col4">
            <div class="widget">
                <div class="widget-title">
                    <h3>Ball Movement</h3>
                </div><!-- end .widget-title -->
                <div class="widget-content">
                  <div style="text-align:center">
                  <table width="100%" border="0" cellspacing="0" cellpadding="0" class="blacktable">
                    <thead>
                          <tr>
                            <th><h4>Stats</h4></th>
                            <th><h5>#</h5></th>
                            <th><h5>Avg/Game</h5></th>
                          </tr>
                      </thead>
                      <tbody>
                        <?php
                        foreach($data['ball_movement'] as $stats=>$st):
                        ?>
                        <tr>
                          <td><?=ucfirst(str_replace('_',' ',$stats))?></td>
                          <td class="">
                             <?=round($st['total'],1)?>
                             <?php if($stats=='chances_conversion'): echo "%";endif;?>
                          </td>
                          <td class="">
                            <?php if($stats!='chances_conversion'):?>
                             <?=round($st['total']/$data['total_games'],1)?>
                           <?php endif;?>
                          </td>
                         
                        </tr>
                      <?php endforeach;?>
                      </tbody>
                    </table>
                  </div>
                </div><!-- end .widget-content -->
              </div>  
        </div><!-- end .col4 -->
        <div class="col4">
            <div class="widget">
                <div class="widget-title">
                    <h3>Attacking Style</h3>
                </div><!-- end .widget-title -->
                <div class="widget-content">
                  <div style="text-align:center">
                  <table width="100%" border="0" cellspacing="0" cellpadding="0" class="blacktable">
                    <thead>
                          <tr>
                            <th><h4>Stats</h4></th>
                            <th><h5>#</h5></th>
                            <th><h5>%</h5></th>
                            <th><h5>Avg/Game</h5></th>
                          </tr>
                      </thead>
                      <tbody>
                        <?php
                        foreach($data['attacking_style'] as $stats=>$st):
                        ?>
                        <tr>
                          <td><?=ucfirst(str_replace('_',' ',$stats))?></td>
                          <td class="">
                             <?=$st['total']?>
                          </td>
                          <td class="">
                             <?=round($st['average']*100,1)?> %
                          </td>
                          <td class="">
                            <?php
                              $overall = $away_overall['attacking_style'][$stats];
                             ?>
                             <?=round($overall['total']/$data['total_games'],1)?>
                             
                          </td>
                         
                        </tr>
                      <?php endforeach;?>
                      </tbody>
                    </table>
                  </div>
                </div><!-- end .widget-content -->
              </div>  
        </div><!-- end .col4 -->
        <div class="col4">
            <div class="widget">
                <div class="widget-title">
                    <h3>Dribbling</h3>
                </div><!-- end .widget-title -->
                <div class="widget-content">
                  <div style="text-align:center">
                  <table width="100%" border="0" cellspacing="0" cellpadding="0" class="blacktable">
                    <thead>
                          <tr>
                            <th><h4>Stats</h4></th>
                            <th><h5>#</h5></th>
                            <th><h5>Avg/Game</h5></th>
                          </tr>
                      </thead>
                      <tbody>
                        <?php
                        foreach($data['dribbling'] as $stats=>$st):
                        ?>
                        <tr>
                          <td><?=ucfirst(str_replace('_',' ',$stats))?></td>
                          <td class="">
                             <?=$st?>
                          </td>
                         
                          <td class="">
                             <?=round($st/$data['total_games'],1)?>
                             <?php
                              $overall = $away_overall['dribbling'][$stats];
                             ?>
                             <?=round($overall/$data['total_games'],1)?>
                          </td>
                         
                        </tr>
                      <?php endforeach;?>
                      </tbody>
                    </table>
                  </div>
                </div><!-- end .widget-content -->
            </div>
        </div><!-- end .col4 -->
      <div class="col4 ">
            <div class="widget">
                <div class="widget-title">
                    <h3>Passing Style</h3>
                </div><!-- end .widget-title -->
                <div class="widget-content">
                  <div style="text-align:center">
                  <table width="100%" border="0" cellspacing="0" cellpadding="0" class="blacktable">
                    <thead>
                          <tr>
                            <th><h4>Stats</h4></th>
                            <th><h5>#</h5></th>
                            <th><h5>%</h5></th>
                            <th><h5>Accuracy</h5></th>
                            <th><h5>Avg/Game</h5></th>
                          </tr>
                      </thead>
                      <tbody>
                     <?php
                        $total_pass = $data['passing_style']['total_pass']['total'];
                        $total_passings =  $data['passing_style']['long_ball']['total']+
                                          $data['passing_style']['short_passes']['total']+
                                          $data['passing_style']['launches']['total']+
                                          $data['passing_style']['through_balls']['total']+
                                          $data['passing_style']['chipped_passes']['total'];
                        foreach($data['passing_style'] as $stats=>$st):
                          if($stats!='total_pass'):
                        ?>
                        <tr>
                          <td><?=ucfirst(str_replace('_',' ',$stats))?></td>
                          <td class="">
                            <?php if($stats!='leftside_pass' && $stats!='rightside_pass' && $stats!='short_passes'):?>
                             <?=$st['accurate']?> /<?=$st['total']?>
                            <?php else:?>
                               <?=$st['total']?>
                            <?php endif;?>
                          </td>
                           <td>
                             <?php
                                if($stats!='leftside_pass' && $stats!='rightside_pass' && $stats!='short_passes' 
                                  && $stats!='forward_passes' && $stats!='accurate_passes'){
                                 
                                  echo round(@($st['total'] / $total_passings)*100,1).'%';

                                }else if($stats=='short_passes'){
                                  echo round(($st['total'] / $total_passings)*100,1).'%';
                                }else if($stats=='forward_passes'){
                                  echo round(($st['total'] / $total_pass)*100,1).'%';
                                }else if($stats=='leftside_pass' || $stats=='rightside_pass'){
                                  echo round(($st['average'])*100,1).'%';
                                }else{
                                  //echo 0.'%';
                                }
                              ?>
                           </td>
                          <td class="">
                             <?php if($stats!='short_passes'&&$stats!='leftside_pass' && $stats!='rightside_pass'):?>
                              <?=round($st['accuracy']*100,1)?> %
                              <?php endif;?>
                          </td>
                          <td class="">
                             <?php
                              $overall = $away_overall['passing_style'][$stats];
                             ?>
                             <?=round($overall['total']/$data['total_games'],1)?>
                          </td>
                         
                        </tr>
                      <?php endif;endforeach;?>
                      </tbody>
                    </table>
                  </div>
                </div><!-- end .widget-content -->
            </div>    
        </div><!-- end .col4 -->
      <div class="col4 col4last">
            <div class="widget">
                <div class="widget-title">
                    <h3>defending_style</h3>
                </div><!-- end .widget-title -->
                <div class="widget-content">
                  <div style="text-align:center">
                  <table width="100%" border="0" cellspacing="0" cellpadding="0" class="blacktable">
                    <thead>
                          <tr>
                            <th><h4>Stats</h4></th>
                            <th><h5>#</h5></th>
                            <th><h5>%</h5></th>
                            <th><h5>Avg/Game</h5></th>
                          </tr>
                      </thead>
                      <tbody>
                        <?php
                        foreach($data['defending_style'] as $stats=>$st):
                        ?>
                        <tr>
                          <td><?=ucfirst(str_replace('_',' ',$stats))?></td>
                          <td class="">
                             <?=$st['total']?>
                          </td>
                         <td class="">
                             <?=round($st['average']*100,1)?> %
                          </td>
                          <td class="">
                             <?php
                              $overall = $away_overall['defending_style'][$stats];
                             ?>
                             <?=round($overall['total']/$data['total_games'],1)?>
                          </td>
                         
                        </tr>
                      <?php endforeach;?>
                      </tbody>
                    </table>
                  </div>
                </div><!-- end .widget-content -->
          </div>
        </div><!-- end .col4 -->
        
        
    </div><!-- end .section -->

    <div class="section">
      <div class="col4 ">
            <div class="widget">
                <div class="widget-title">
                    <h3>Goalkeeping</h3>
                </div><!-- end .widget-title -->
                <div class="widget-content">
                  <div style="text-align:center">
                  <table width="100%" border="0" cellspacing="0" cellpadding="0" class="blacktable">
                    <thead>
                          <tr>
                            <th><h4>Stats</h4></th>
                            <th><h5>#</h5></th>
                            <th><h5>%</h5></th>
                            <th><h5>Avg/Game</h5></th>
                          </tr>
                      </thead>
                      <tbody>
                        <?php
                        foreach($data['goalkeeping'] as $stats=>$st):
                        ?>
                        <tr>
                          <td><?=ucfirst(str_replace('_',' ',$stats))?></td>
                          <td class="">
                             <?=$st['total']?><?php if(isset($st['total2'])): echo '/'.$st['total2'];endif;?>
                          </td>
                          <td class="">
                             <?=round($st['average']*100,1)?> %
                          </td>
                          <td class="">
                              <?php
                              $overall = $away_overall['goalkeeping'][$stats];
                             ?>
                             <?=round($overall['total']/$data['total_games'],1)?>
                          </td>
                         
                        </tr>
                      <?php endforeach;?>
                      </tbody>
                    </table>
                  </div>
                </div><!-- end .widget-content -->
            </div>    
        </div><!-- end .col4 -->
      <div class="col4">
            <div class="widget">
                <div class="widget-title">
                    <h3>DEF strength &amp; weakness</h3>
                </div><!-- end .widget-title -->
                <div class="widget-content">
                  <div style="text-align:center">
                  <table width="100%" border="0" cellspacing="0" cellpadding="0" class="blacktable">
                    <thead>
                          <tr>
                            <th><h4>Stats</h4></th>
                            <th><h5>#</h5></th>
                            <th><h5>%</h5></th>
                            <th><h5>Avg/Game</h5></th>
                          </tr>
                      </thead>
                      <tbody>
                        <?php
                        foreach($data['defending_strength_and_weakness'] as $stats=>$st):
                        ?>
                        <tr>
                          <td><?=ucfirst(str_replace('_',' ',$stats))?></td>
                          <td class="">
                             <?=$st['total']?><?php if(isset($st['total2'])): echo '/'.$st['total2'];endif;?>
                          </td>
                         <td class="">
                            <?php if($stats != 'challenge_lost' &&
                                      $stats != 'attempts_conceded_from_fastbreak' &&
                                      $stats != 'attempts_conceded_from_setpieces' &&
                                      $stats != 'error_lead_to_shot' &&
                                      $stats != 'error_lead_to_goal' &&
                                      $stats != 'total_errors' &&
                                      $stats != 'penalty_conceded' &&
                                      $stats != 'fouls_conceded_in_attacking_3rd'):?>
                              <?=round($st['average']*100,1)?> %
                              <?php endif;?>
                          </td>
                          <td class="">
                             <?php
                              $overall = $away_overall['defending_strength_and_weakness'][$stats];
                             ?>
                             <?=round($overall['total']/$data['total_games'],1)?>
                          </td>
                         
                        </tr>
                      <?php endforeach;?>
                      </tbody>
                    </table>
                  </div>
                </div><!-- end .widget-content -->
          </div>
        </div><!-- end .col4 -->

        <div class="col4 ">
            <div class="widget">
                <div class="widget-title">
                    <h3>Aerial Strength</h3>
                </div><!-- end .widget-title -->
                <div class="widget-content">
                  <div style="text-align:center">
                  <table width="100%" border="0" cellspacing="0" cellpadding="0" class="blacktable">
                    <thead>
                          <tr>
                            <th><h4>Stats</h4></th>
                            <th><h5>#</h5></th>
                            <th><h5>%</h5></th>
                            <th><h5>Avg/Game</h5></th>
                          </tr>
                      </thead>
                      <tbody>
                        <?php
                        foreach($data['aerial_strength'] as $stats=>$st):
                        ?>
                        <tr>
                          <td><?=ucfirst(str_replace('_',' ',$stats))?></td>
                          <td class="">
                             <?=$st['total']?><?php if(isset($st['total2'])): echo '/'.$st['total2'];endif;?>
                          </td>
                          <td class="">
                             <?=round($st['average']*100,1)?> %
                          </td>
                          <td class="">
                              <?php
                              $overall = $away_overall['aerial_strength'][$stats];
                             ?>
                             <?=round($overall['total']/$data['total_games'],1)?>
                          </td>
                         
                        </tr>
                      <?php endforeach;?>
                      </tbody>
                    </table>
                  </div>
                </div><!-- end .widget-content -->
            </div>    
        </div><!-- end .col4 -->
      <div class="col4 col4last">
            <div class="widget">
                <div class="widget-title">
                    <h3>Set Plays</h3>
                </div><!-- end .widget-title -->
                <div class="widget-content">
                  <div style="text-align:center">
                  <table width="100%" border="0" cellspacing="0" cellpadding="0" class="blacktable">
                    <thead>
                          <tr>
                            <th><h4>Stats</h4></th>
                            <th><h5>#</h5></th>
                            <th><h5>Accuracy</h5></th>
                            <th><h5>Chance</h5></th>
                            <th><h5>Avg/Game</h5></th>
                          </tr>
                      </thead>
                      <tbody>
                        <?php
                        foreach($data['setplays'] as $stats=>$st):
                        ?>
                        <tr>
                          <td><?=ucfirst(str_replace('_',' ',$stats))?></td>
                          <td class="">
                             <?=$st['total']?><?php if(isset($st['total2'])): echo '/'.$st['total2'];endif;?>
                          </td>
                        <td class="">
                          <?php if($stats!='corners_won' && $stats!='freekicks_won'):?>
                             <?=round($st['accuracy']*100,1)?> %
                          <?php endif;?>
                          </td>
                          <td class="">
                             <?php if($stats!='corners_won' && $stats!='freekicks_won'):?>
                             <?=round($st['chance_ratio']*100,1)?> %
                           <?php endif;?>
                          </td>
                          <td class="">
                              <?php
                              $overall = $away_overall['setplays'][$stats];
                             ?>
                             <?=round($overall['total']/$data['total_games'],1)?>
                          </td>
                         
                        </tr>
                      <?php endforeach;?>
                      </tbody>
                    </table>
                  </div>
                </div><!-- end .widget-content -->
          </div>
        </div><!-- end .col4 -->
        
        
    </div><!-- end .section -->


</div><!-- end #fullcontent -->

