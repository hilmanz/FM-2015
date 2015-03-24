<?php
function isNotFree($user_plan){
    if($plan!='free'){
        return true;
    }
}


$paid_teams = array('t1','t3','t8','t6','t11','t14','t43',
                    't128','t121','t120','t127','t459','t129');
?>
<div id="fillDetailsPage">
	
    <div id="thecontent">
        <div id="content">
            <div class="content">
                <div class="row-2">
                    <h1 class="red">Pilih Tim Fav</h1>
                    <p>Pilih tim yang elo mau urus, kalo mau silahkan kasih nama yang unik sekalian, dan nantinya ganti logonya juga. Ingat, penggunaan logo yang kami anggap tidak layak untuk ditampilkan bisa membuat tim elo di diskualifikasi.</p>
                </div><!-- end .row-2 -->
                <div class="select-team">
                    <form class="theForm" action="<?=$this->Html->url('/profile/register_team')?>" method="post" enctype="multipart/form-data">
                        <div class="row">
                            <label>Personalisasi Nama Tim Anda</label>
                            <?php
                            $init_team_name = (isset($previous_team)) ? $previous_team['team_name'] : $USER_DATA['name'].' FC';
                            ?>
                            <input maxlength="20" type="text" name="team_name" value="<?=htmlspecialchars($init_team_name)?>"/>
                           
                            <span class="icon_available check"></span>

                        </div><!-- end .row -->
                        <h3>Pilih tim:</h3>
                        <div class="row">
                        	<?php $previous_team_id = "";?>
                            <?php foreach($team_list as $team):?>
                            <?php
                            	$selected = "";
                            	if(isset($previous_team) &&
                            			$team['uid']==$previous_team['team_id']){
                            		$previous_team_id = $team['uid'];
                            		$selected = "selected";
                            	}
                            ?>
                            <?php if($plan=='free' && in_array($team['uid'],$paid_teams)):?>
                            <a class="teamBox-disabled" title="<?=$team['name']?>">
                                <img style="height:46px" src="http://widgets-images.s3.amazonaws.com/football/team/badges_65/<?=str_replace('t','',$team['uid'])?>.png"/>
                                <div class="team-name"><?=$team['name']?></div>
                            </a><!-- end .teamBox -->
                            <?php else:?>
                            <a class="teamBox <?=$selected?>" no="<?=$team['uid']?>" href="#/selectTeam/<?=$team['uid']?>" title="<?=$team['name']?>">
                                <img style="height:46px" src="http://widgets-images.s3.amazonaws.com/football/team/badges_65/<?=str_replace('t','',$team['uid'])?>.png"/>
                                <div class="team-name"><?=$team['name']?></div>
                            </a><!-- end .teamBox -->
                            <?php endif;?>
                            <?php endforeach;?>
                        </div><!-- end .row -->
                        <?php if($plan=='free'):?>
                        <div class="row">
                            * Tim Papan Atas hanya bisa dipilih oleh Pro League Member
                        </div>
                        <?php endif;?>
                        <div class="row">
                            <input type="hidden" name="fb_id" value="<?=$USER_DATA['fb_id']?>"/>
                            <input type="hidden" name="team_id" value="<?=$previous_team_id?>"/>
                            <input type="hidden" name="create_team" value="1"/>
                            <input type="button" value="Simpan &amp; Lanjutkan" class="button fr" id="btnsave"/>
                        </div><!-- end .row -->
                    </form>
                </div><!-- end .select-team -->
            </div><!-- end .content -->
        </div><!-- end #content -->
	<div id="sidebar" class="tr">
		
	    <div class="widget">
	        <div class="nav-side">
	            <ul>
                   <li><span>Isi data lengkap Anda</span></li>
                   <li class="current"><span>Pilih Tim</span></li>
                   <li><span>Pilih Pemain</span></li>
                   <li ><span>Pilih Staff</span></li>
	            </ul>
	        </div><!-- end .nav-side -->
	    </div><!-- end .widget -->
	    <div class="widget">
	        <div class="cash-left">
	            <h3 class="red">SISA UANG</h3>
	            <h1>SS$ <?=number_format($INITIAL_BUDGET)?></h1>
	            <h3 class="red">Est. PENGELUARAN MINGGUAN</h3>
	            <h1>SS$ <span class="expense">0</span></h1> 
	        </div>
	    </div><!-- end .widget -->
	</div><!-- end #sidebar -->
    </div><!-- end #thecontent -->
</div><!-- end #fillDetailsPage -->

<!--popups-->
<div class="popup">
    <div class="popupContainer popup-small" id="popup-messages">
        <div class="popupHeader">
        </div><!-- END .popupHeader -->
        <div class="popupContent">
            <div class="entry-popup">
                <h1>Nama Tim sudah digunakan</h1>
		        <p>Nama Tim Anda telah digunakan oleh orang lain, silahkan masukan nama lain.</p>
            </div><!--END .entry-popup-->
        </div><!-- END .popupContent -->
    </div><!-- END .popupContainer -->
</div><!-- END .popup --> 
<a class="fancy" href="#popup-messages"></a>
<?php 
    if(!is_array($team_list)) $team_list = array();
?>
<script>
var team_list = <?=json_encode($team_list)?>;
var ktimer;
function check_name(){
	check_team_name($("input[name='team_name']").val(),function(exists){
		$("span.check").removeClass('icon_available');
		$("span.check").removeClass('icon_unvailable');
		if(!exists){
			$("span.check").html('Available');
			$("span.check").addClass('icon_available');
		}else{
			$("span.check").addClass('icon_unvailable');
			$("span.check").html('Not Available');
		}
	});
}
$(document).ready(function(){

	check_name();
    $("input[name='team_name']").keyup(function(){
    	if(typeof ktimer !== 'undefined'){
    		clearTimeout(ktimer);
    	}
    	ktimer = setTimeout(function(){
    		//console.log();
    		check_name();
    	},1000);
    	
	});
    $(".fancy").fancybox();
	$("#btnsave").click(function(){
		if(typeof tmp.team_name_taken !== 'undefined'){
			if(tmp.team_name_taken == false){
				$('.theForm').submit();
			}else{
				
				$(".fancy").trigger('click');
			}
		}
	});
}); 
</script>
