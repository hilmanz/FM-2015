<div id="fullcontent">
    <div>
      
      <a href="<?=$this->Html->url('/pages/matchstats/?game_id='.$game_id.'&team_id='.$data['player']['team_id'])?>" class="button">Back</a>
    </div>
    <div class="section">
        <div class="col12">
            <div class="widget">
                <div class="widget-title">
                    <h3><?=$data['player']['name']?></h3>
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
                            <td>Club</td>
                            <td class="tright">
                              <?=$data['player']['team_name']?>
                            </td>
                          </tr>
                        <tr>
                            <td>Country</td>
                            <td class="tright">
                              <?=$data['player']['country']?>
                            </td>
                          </tr>
                          <tr>
                            <td>Birthdate</td>
                            <td class="tright">
                              <?=$data['player']['country']?>
                            </td>
                          </tr>
                         <tr>
                            <td>Position</td>
                            <td class="tright">
                              <?=$data['player']['position']?>
                            </td>
                          </tr>
                          <tr>
                            <td>Shirt No.</td>
                            <td class="tright">
                              <?=$data['player']['jersey_num']?>
                            </td>
                          </tr>
                           <tr>
                            <td>Points Earned</td>
                            <td class="tright">
                              <?=$data['total_points']?>
                            </td>
                          </tr>
                          <tr>
                            <td>Games Played</td>
                            <td class="tright">
                              <?=intval($data['player']['games_played'])?>
                            </td>
                          </tr>
                          <tr>
                            <td>Minutes Played</td>
                            <td class="tright">
                              <?=intval($data['player']['mins_played'])?>
                            </td>
                          </tr>
                      </tbody>
                    </table>
                  </div>
                </div><!-- end .widget-content -->
                
        </div><!-- end .col4 -->
    </div><!-- end .section -->
    
    <div class="section">
        <div class="col4">
            <div class="widget">
                <div class="widget-title">
                    <h3>Goals and Assists</h3>
                </div><!-- end .widget-title -->
                <div class="widget-content">
                  <div style="text-align:center">
                  <table width="100%" border="0" cellspacing="0" cellpadding="0" class="blacktable">
                    <thead>
                          <tr>
                            <th><h4>Stats</h4></th>
                            <th><h5>#</h5></th>
                          </tr>
                      </thead>
                      <tbody>
                        <?php
                        foreach($data['goals_and_assists'] as $stats=>$st):
                        ?>
                        <tr>
                          <td><?=ucfirst(str_replace('_',' ',$stats))?></td>
                          <td class="">
                             <?=$st?>
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
                    <h3>Shooting</h3>
                </div><!-- end .widget-title -->
                <div class="widget-content">
                  <div style="text-align:center">
                  <table width="100%" border="0" cellspacing="0" cellpadding="0" class="blacktable">
                    <thead>
                          <tr>
                            <th><h4>Stats</h4></th>
                            <th><h5>#</h5></th>
                            <th><h5>%</h5></th>
                            <th><h5>AVG/Game</h5></th>
                          </tr>
                      </thead>
                      <tbody>
                        <?php
                        foreach($data['shooting'] as $stats=>$st):
                        ?>
                        <tr>
                          <td><?=ucfirst(str_replace('_',' ',$stats))?></td>
                          <td class="">
                              <?=$st['total']?>/<?=$st['overall']?> 
                          </td>
                          <td class="">
                              <?=round(@($st['total']/$st['overall'])*100,1)?>%
                          </td>
                          <td class="">
                              <?=$st['total']?>
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
                    <h3>Passing</h3>
                </div><!-- end .widget-title -->
                <div class="widget-content">
                  <div style="text-align:center">
                  <table width="100%" border="0" cellspacing="0" cellpadding="0" class="blacktable">
                    <thead>
                          <tr>
                            <th><h4>Stats</h4></th>
                            <th><h5>#</h5></th>
                            <th><h5>%</h5></th>
                            <th><h5>AVG/Game</h5></th>
                          </tr>
                      </thead>
                      <tbody>
                        <?php
                        foreach($data['passing'] as $stats=>$st):
                        ?>
                        <tr>
                          <td><?=ucfirst(str_replace('_',' ',$stats))?></td>
                           <td class="">
                            <?php if(isset($st['overall'])):?>
                            <?=$st['total']?>/<?=$st['overall']?>
                            <?php else:?>
                            <?=$st['total']?>
                            <?php endif;?>
                          </td>
                          <td class="">
                            <?php if(isset($st['overall'])):?>
                              <?=round(@($st['total']/$st['overall'])*100,1)?>%
                            <?php endif;?>
                          </td>
                          <td class="">
                              <?=$st['total']?>
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
                    <h3>Defending</h3>
                </div><!-- end .widget-title -->
                <div class="widget-content">
                  <div style="text-align:center">
                  <table width="100%" border="0" cellspacing="0" cellpadding="0" class="blacktable">
                    <thead>
                          <tr>
                            <th><h4>Stats</h4></th>
                            <th><h5>#</h5></th>
                            <th><h5>%</h5></th>
                            <th><h5>AVG/Game</h5></th>
                          </tr>
                      </thead>
                      <tbody>
                        <?php
                        foreach($data['defending'] as $stats=>$st):
                        ?>
                        <tr>
                          <td><?=ucfirst(str_replace('_',' ',$stats))?></td>
                           <td class="">
                            <?php if(isset($st['overall'])):?>
                            <?=$st['total']?>/<?=$st['overall']?>
                            <?php else:?>
                            <?=$st['total']?>
                            <?php endif;?>
                          </td>
                          <td class="">
                            <?php if(isset($st['overall'])):?>
                              <?=round(@($st['total']/$st['overall'])*100,1)?>%
                            <?php endif;?>
                          </td>
                          <td class="">
                              <?=$st['total']?>
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
                    <h3>Dribbling</h3>
                </div><!-- end .widget-title -->
                <div class="widget-content">
                  <div style="text-align:center">
                  <table width="100%" border="0" cellspacing="0" cellpadding="0" class="blacktable">
                    <thead>
                          <tr>
                            <th><h4>Stats</h4></th>
                            <th><h5>#</h5></th>
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
                    <h3>Goal Keeping</h3>
                </div><!-- end .widget-title -->
                <div class="widget-content">
                  <div style="text-align:center">
                  <table width="100%" border="0" cellspacing="0" cellpadding="0" class="blacktable">
                    <thead>
                          <tr>
                            <th><h4>Stats</h4></th>
                            <th><h5>#</h5></th>
                          </tr>
                      </thead>
                      <tbody>
                        <?php
                        foreach($data['goalkeeping'] as $stats=>$st):
                        ?>
                        <tr>
                          <td><?=ucfirst(str_replace('_',' ',$stats))?></td>
                          <td class="">
                             <?=$st?>
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
                    <h3>Mistakes &amp; Errors</h3>
                </div><!-- end .widget-title -->
                <div class="widget-content">
                  <div style="text-align:center">
                  <table width="100%" border="0" cellspacing="0" cellpadding="0" class="blacktable">
                    <thead>
                          <tr>
                            <th><h4>Stats</h4></th>
                            <th><h5>#</h5></th>
                          </tr>
                      </thead>
                      <tbody>
                        <?php
                        foreach($data['mistakes_and_errors'] as $stats=>$st):
                        ?>
                        <tr>
                          <td><?=ucfirst(str_replace('_',' ',$stats))?></td>
                          <td class="">
                             <?=$st?>
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

