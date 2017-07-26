<div id="fullcontent">
    <div>
      
      <a href="<?=$this->Html->url('/stats/matchstats/?game_id='.$game_id.'&team_id='.$data['player']['team_id'])?>" class="button">Back</a>
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

<div class="rawstats">
  <div class="rows" style="width:350px;height:24px;">
    <a href="#" id='hiderawstats' class="color:white;">Hide Panel</a>
  </div>
  <div class="list">
    <h3>Raw Stats</h3>
    <table>
      <tr>
        <td>Stats Name</td><td>Stats Value</td>
      </tr>
      <?php
      foreach($playerstats as $statsName=>$statsValue):?>
        <tr>
          <td><?=$statsName?></td><td><?=$statsValue?></td>
        </tr>
      <?php endforeach;?>
    </table>
    <h3>Raw TeamB Stats</h3>
    <table>
      <tr>
        <td>Stats Name</td><td>Stats Value</td>
      </tr>
      <?php
      foreach($teamBstats as $statsName=>$statsValue):?>
        <tr>
          <td>teamB_<?=$statsName?></td><td><?=$statsValue?></td>
        </tr>
      <?php endforeach;?>
    </table>
  </div>
  <h3>Try Formula</h3>
  <div class="formulaform">
    <div>
      <textarea name="formula" cols="50" rows="5"></textarea>
    </div>
    <div>
      <input type="button" name='btnFormula' value="Test"/>
    </div>
  </div>
  <div class="formulaout">0</div>
</div>
<div class="rawstatstoggle">RAW STATS</div>
<script>
var rawstats_show = false;


/**team_stats**/
<?php foreach($playerstats as $statsName=>$statsValue):?>
var <?=$statsName?> = <?=$statsValue?>;
<?php endforeach;?>
/**teamB**/
<?php foreach($teamBstats as $statsName=>$statsValue):?>
var teamB_<?=$statsName?> = <?=$statsValue?>;
<?php endforeach;?>
function evalFormula(input){
  var a = input.match(/[a-zA-Z0-9\_]+/g);
  for(var i in a){
      if(typeof this[a[i]] === 'undefined'){
          this[a[i]] = 0;
      }
  }
  return eval(input);
}
$("input[name='btnFormula']").click(function(e){
  $('.formulaout').html(evalFormula($('textarea[name=formula]').val()));
  e.preventDefault();
});
$('#hiderawstats').click(function(e){
  $('.rawstats').animate({"left": '-1200'});
  $('.rawstatstoggle').fadeIn();
});
$('.rawstatstoggle').click(function(e){
  $('.rawstatstoggle').hide();
  $('.rawstats').show();
  $('.rawstats').animate({"left": '10'});
});
</script>
