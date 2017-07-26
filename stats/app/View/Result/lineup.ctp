<?php
$lineup = array();
$subs = array();

foreach($result['lineup']['home'] as $player){
  if($player['Lineup']['status']=='Start'){
    $lineup[] = array('home'=>$player['Player'],'away'=>array());
  }else{
    $subs[] = array('home'=>$player['Player'],'away'=>array());
  }
}
$n=0;
$m=0;

foreach($result['lineup']['away'] as $t=>$player){
   if($player['Lineup']['status']=='Start'){
      if(!isset($lineup[$m])){
        $lineup[$m] = array('home'=>array(),'away'=>array());
      }
      $lineup[$m]['away']=$player['Player'];
      $m++;
    }else{
      if(!isset($subs[$n])){
        $subs[$n] = array('home'=>array(),'away'=>array());
      }
      $subs[$n]['away']=$player['Player'];
      $n++;
    }
}   
$result['lineup'] = null;
//pr($lineup);
//pr($subs);
function position($p){
  switch($p){
    case "Defender":
      return "D";
    break;
    case "Forward":
      return "F";
    break;
    case "Midfielder":
      return "M";
    break;
    case "Goalkeeper":
      return "GK";
    break;
    default : 
      return $p;
    break;
  }
}
//pr($result);
?>
<div class="widget">
    <div class="widget-title">
        <h3>LINE UP</h3>
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
            <?php foreach($lineup as $lu):?>
              <tr>
                <td class="tcenter">
                  <a class="red-arrow">
                      <?=@$lu['home']['name']?> (<?=position(@$lu['home']['position'])?>)</a>
                </td>
                <td class="tcenter">&nbsp;</td>
                <td class="tcenter">
                     <a class="red-arrow">
                     <?=@$lu['away']['name']?> (<?=position(@$lu['away']['position'])?>)</a>
                </td>
              </tr>
             <?php endforeach;?>
          </tbody>                    
        </table>
        <table width="100%" border="0" cellspacing="0" cellpadding="0" class="blacktable">
        <thead>
              <tr>
                <th class="tcenter" colspan="3">
                  SUBSTITUTIONS
                </th>
              </tr>
          </thead>
          <tbody>
            <?php foreach($subs as $lu): ?>
              <tr>
                <td class="tcenter">
                  <?php if(@$lu['home']['name']!=null):?>
                  <a class="red-arrow">
                       <?=@h($lu['home']['name'])?> (<?=position(@$lu['home']['position'])?>)</a>
                  <?php endif;?>
                </td>
                <td class="tcenter">&nbsp;</td>
                <td class="tcenter">
                   <?php if(@$lu['away']['name']!=null):?>
                     <a class="red-arrow">
                       <?=@h($lu['away']['name'])?> (<?=position(@$lu['away']['position'])?>)</a>
                    <?php endif;?>
                </td>
              </tr>
             <?php endforeach;?>
          </tbody>                    
        </table>
     
        <table width="100%" border="0" cellspacing="0" cellpadding="0" class="blacktable">
        <thead>
              <tr>
                <th class="tcenter" colspan="3">
                  MANAGER
                </th>
              </tr>
          </thead>
          <tbody>
           
              <tr>
                <td class="tcenter">
                  <a class="red-arrow">
                      <?=@__($result['team_refs']['home']['team_manager'])?></a>
                </td>
                <td class="tcenter">&nbsp;</td>
                <td class="tcenter">
                     <a class="red-arrow">
                      <?=@__($result['team_refs']['away']['team_manager'])?></a>
                </td>
              </tr>
            
          </tbody>                    
        </table>
    </div><!-- end .widget-content -->
</div><!-- end .widget -->