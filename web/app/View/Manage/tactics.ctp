<?php
$can_update_formation = true;

if(isset($close_time)):

if(time() > @$close_time['ts'] && Configure::read('debug') == 0){
    $can_update_formation = false;
    if(time() > $open_time){
     
        $can_update_formation = true;
    }
}else{
    if(time() < @$open_time){
       
        $can_update_formation = false;
    }
}
endif;

if($is_new_user){
    $can_update_formation = true;
}

?>



<?php
 $home_logo = "http://widgets-images.s3.amazonaws.com/football/team/badges_65/".str_replace('t','',@$next_match['home_id']).".png";
  $away_logo = "http://widgets-images.s3.amazonaws.com/football/team/badges_65/".str_replace('t','',@$next_match['away_id']).".png";

if(strlen(@$user['avatar_img'])!=0 && @$user['avatar_img']!='0'){
    if($next_match['home_id']==$club['team_id']){
        $home_logo = $this->Html->url('/files/120x120_'.@$user['avatar_img']);
    }else{
       $away_logo = $this->Html->url('/files/120x120_'.@$user['avatar_img']);
    }
}
?>
<div id="fillDetailsPage">
	<h1 class="fake-title">Mengelola Tim</h1>
      <div class="rowd">
     	 <?php echo $this->element('infobar',array('next_match'=>$next_match)); ?>
      </div>
    <div id="thecontent">
        <div class="box4 fl" id="bannerBox">
           
        	<div class="banner250x250">
			<?=$this->element('sponsor_banner',array('slot'=>'TEAM_SMALL','game_team_id'=>$game_team_id));?>
            </div>
            <?php for($i=0;$i<sizeof($sidebar_banner);$i++):?>
        	<div class="banner250x250">
                <a 
                href="<?=$this->Html->url('/sponsors/jump/1/'.$sidebar_banner[$i]['Banners']['id'])?>" 
                    target="_blank">
                    <img src="<?=$this->Html->url(Configure::read('avatar_web_url').
                                $sidebar_banner[$i]['Banners']['banner_file'])?>" />
                </a>
            </div>
            <?php endfor;?>
        	
            
        </div><!-- end .box4 -->
        <div class="box3 tr fl" id="tacticBox">
            <h3>
                Set Up Tactics for Matchday <?=$next_match['matchday']?>
            </h3>
            <?php if(isset($msg)):?>
            <div class="msg">
                <?=$msg?>
            </div>
            <?php endif;?>
            <form id="frmTactic" action="<?=$this->Html->url('/manage/tactics')?>" method="POST" enctype="application/x-www-form-urlencoded">
                <table width="100%" border="0" cellspacing="0" cellpadding="0" class="table">
                    <thead>
                        <tr>
                            <th width="160px" align="center">Pemain</th>
                            <th class="aligncenter" width="60" align="center">Instruction</th>
                            <th class="aligncenter" width="1" align="center">Amount</th>
                        </tr>
                    </thead>
                    <tbody id="myplayerlist">
                        <?php for($i=0;$i<sizeof($lineup);$i++):?>

                        <?php
                        $instruction_id = 0;
                        $amount = 0;

                        for($j=0;$j<sizeof($tactics);$j++){
                            if($lineup[$i]['player_id']==$tactics[$j]['player_id']){
                                $instruction_id = intval(@$tactics[$j]['instruction_id']);

                                $amount = intval(@$tactics[$j]['amount']);
                                break;
                            }
                        }

                        ?>
                        <tr>
                            <td><?=$lineup[$i]['name']?></td>
                            <td>
                                <select name="instruction[]">

                                    <option value="0" <?php if($instruction_id == 0): echo 'selected="selected"';endif;?>>Tidak Ada</option>
                                    <?php for($t=0;$t<sizeof($available_tactics);$t++):?>
                                    <option value="<?=$available_tactics[$t]['id']?>" <?php if($instruction_id == $available_tactics[$t]['id']): echo 'selected="selected"';endif;?>><?=$available_tactics[$t]['name']?></option>
                                    <?php endfor;?>
                                </select>

                            </td>
                            <td>
                                <input type="text" name="points[]" value="<?=$amount?>"/>
                                <input type="hidden" name="player[]" value="<?=$lineup[$i]['player_id']?>"/>
                            </td>
                        </tr>
                        <?php endfor;?>
                    </tbody>
                </table>
                <a href="<?=$this->Html->url('/manage/team')?>" class="button">KEMBALI KE FORMASI</a><a href="#" class="button btn-save-tactic">SIMPAN TAKTIK</a>
            </form>
        </div><!-- end .box3 -->
        <div class="box4 fr" id="boxSquad">
            <div class="widget tr squad-team-name">
                <h3><?=h($club['team_name'])?></h3>
                <div class="RingkasanKlab" id="RingkasanKlab">
                    <div class="RingkasanKlabWidget">
                        <table width="100%" border="0" cellspacing="0" cellpadding="0">
                          <tr>
                            <td align="center">
                                <a href="#">
                                <?php if(strlen(@$user['avatar_img'])==0 || @$user['avatar_img']=='0'):?>
                                <img src="http://widgets-images.s3.amazonaws.com/football/team/badges_65/<?=str_replace('t','',$club['team_id'])?>.png"/>
                                <?php else:?>
                                <img width="65" src="<?=$this->Html->url('/files/120x120_'.@$user['avatar_img'])?>" />
                                <?php endif;?>
                             </a>
                            </td>
                            <td>
                                <span>Rank: <strong><?=number_format($USER_RANK)?></strong></span>
                                <span>ss$: <strong><?=number_format($team_bugdet)?> </strong></span>
                                <span>Point: <strong><?=number_format($USER_POINTS)?></strong></span>
                            </td>
                          </tr>
                        </table>
                    </div><!-- end .entry -->
                </div><!-- end .widget -->

            </div><!-- end .widget -->
            <div class="widget tr squad-team-name">
                <h3>INSTRUCTION POINTS : <span id="point_left" class="yellow"><?=$INSTRUCTION_POINTS?></span></h3>
            </div><!-- end .widget -->
            <div class="widget tr squad-team-name">
                <h3>HOW TO USE TACTICS</h3>
                <ol>
                    <li>
                        1. Pilih pemain yang akan elo kasih Instruksi Taktik
                    </li>
                    <li>
                        2. Pilih Instruksi apa yang akan elo kasih ke pemain
                    </li>
                    <li>
                        3. Isi poin dengan Instruction Points maksimal 5 points per pemain
                    </li>
                </ol>
            </div><!-- end .widget -->
        </div><!-- end .box4 -->
    </div><!-- end #thecontent -->
</div><!-- end #fillDetailsPage -->
<div id="draggable" class="jersey-player" style="display:none;position:absolute;">
</div>
<!--popups-->
<div class="popup">
    <div class="popupContainer popup-small" id="popup-messages">
        <div class="popupHeader">
        </div><!-- END .popupHeader -->
        <div class="popupContent">
            <div class="entry-popup">
                
            </div><!--END .entry-popup-->
        </div><!-- END .popupContent -->
    </div><!-- END .popupContainer -->
</div><!-- END .popup --> 



<script>
$(document).ready(function(){
    var max_inspoint = <?=$INSTRUCTION_POINTS?>;
    var total_point  = 0;
    $('input[name="points[]"]').each(function(){
        var points = Math.abs($(this).val());
        if(points == ''){
            points = 0;
        }
        total_point = total_point + points;
    });

    $('#point_left').html(max_inspoint - total_point);

});

$(".btn-save-tactic").click(function(){
    var max_inspoint = <?=$INSTRUCTION_POINTS?>;
    var total_point  = 0;
    $('input[name="points[]"]').each(function(){
        var points = parseInt($(this).val());
        if(points == '' || isNaN(points))
        {
            points = 0;
        }
        total_point = total_point + points;
    });
    if(total_point <= max_inspoint)
    {
        $('#frmTactic').submit();
    }
});

$('input[name="points[]"]').on('keyup',function(e){
    
    var max_point = 5;
    var max_inspoint = <?=$INSTRUCTION_POINTS?>;

    if(isNaN(Math.abs($(this).val()))){
        $(this).val(0);
    }else if($(this).val() < 0){
        $(this).val(0);
    }else if($(this).val() > max_point){
        $(this).val(max_point);
    }

    var total_point  = 0;
    $('input[name="points[]"]').each(function(){
        var points = Math.abs($(this).val());
        if(points == ''){
            points = 0;
        }
        total_point = total_point + points;
    });

    if(total_point > max_inspoint){
        total_point = total_point - $(this).val();
        $('#point_left').html(max_inspoint - total_point);
        $(this).val(0);
    }else{
        $('#point_left').html(max_inspoint - total_point);
    }

});
</script>