<?php
//pr($result);
$all_zero = false;
$goals = array();
$n=0;
while(!$all_zero){
  if(!isset($result['goals']['home'][$n]) &&
      !isset($result['goals']['away'][$n])){
    $all_zero = true;
    break;
  }
  $goal = array();
  if(isset($result['goals']['home'][$n])){
    $goal['home'] = $result['goals']['home'][$n];
  }
  if(isset($result['goals']['away'][$n])){
    $goal['away'] = $result['goals']['away'][$n];
  }
  $goals[] = $goal;
  $n++;
}

?>
<div class="widget">
    <div class="widget-title">
        <h3>GOAL SCORERS</h3>
    </div><!-- end .widget-title -->
    <div class="widget-content">
        <table width="100%" border="0" cellspacing="0" cellpadding="0" class="blacktable">
        <thead>
              <tr>
                <th class="tcenter"><h5 class="jersey"><img src="http://widgets-images.s3.amazonaws.com/football/team/badges_20/<?=str_replace("t","",@$result['info']['home_team'])?>.png"/></h5></th>
                <th class="tcenter">Vs</th>
                <th class="tcenter"><h5 class="jersey"><img src="http://widgets-images.s3.amazonaws.com/football/team/badges_20/<?=str_replace("t","",@$result['info']['away_team'])?>.png"/></h5></th>
              </tr>
          </thead>
          <tbody>
            <?php foreach($goals as $goal):?>
              <tr>
                <td class="tcenter">
                  <?php if(isset($goal['home'])):?>
                    <a class="red-arrow"><?=@$goal['home']['Goal']['player']['name']?> 
                      (<?=@$goal['home']['Goal']['time']?>)</a>
                  <?php endif;?>
                </td>
                <td class="tcenter">&nbsp;</td>
                <td class="tcenter">
                 <?php if(isset($goal['away'])):?>
                    <a class="red-arrow"><?=@$goal['away']['Goal']['player']['name']?> 
                      (<?=@$goal['away']['Goal']['time']?>)</a>
                  <?php endif;?>
                
                </td>
              </tr>
             <?php endforeach;?>
          </tbody>                    
        </table>
    </div><!-- end .widget-content -->
</div><!-- end .widget -->