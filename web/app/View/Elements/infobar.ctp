
<div id="info-bar" class="tr2">
    <h4 class="date-now fl"><?=date("d/m/Y")?></h4>
    <div id="newsticker" <?php if(time() > @$open_time):?> <?php else:?> style="width:460px;" <?php endif;?>>
          <ul class="slides">
          	<?php if(isset($next_match)):?>
            <li class="newsticker-entry">
                <h3><a href="#" target="_blank">
                	Pertandingan berikutnya : <?=__(@$next_match['home_name'])." VS ".h(@$next_match['away_name'])?> (Minggu ke <?=@$next_match['matchday']?>) Tanggal <?=date("d/m/Y",strtotime(@$next_match['match_date']))?></a></h3>
            </li><!-- end .newsticker-entry -->
        	<?php endif;?>
          	<?php 
          	if(isset($tickers)):
          		foreach($tickers as $ticker):
          	?>
            <li class="newsticker-entry">
                <h3><a href="<?=$this->Html->url($ticker['Ticker']['url'])?>" target="_blank">
                	<?=__($ticker['Ticker']['content'])?></a></h3>
            </li><!-- end .newsticker-entry -->
            
            <?php endforeach;endif;?>
            
          </ul><!-- end #newsticker -->
    </div>
    <?php if(time() > @$open_time):?>
	    <h4 id='ct1' class="fr countdown"><span class="yellow ctday">6</span> HARI  <span class="yellow cthour">0</span> JAM  <span class="yellow ctminute">0</span> MENIT ke penutupan</h4>
	    <h4 id='ct0' class="fr countdown" style="display:none;"><span class="yellow ctsec">0</span> DETIK ke penutupan</h4>
	    <h4 id='ct2' class="fr countdown" style="display:none;">
	    	<span class="yellow">Batas Waktu Set Formasi Sudah Lewat</span>
	    </h4>
    <?php else:?>
	     <h4 id='ct3' class="fr countdown">
	    	<span class="yellow">
	    		Saat ini penyusunan formasi sedang ditutup
	    	</span>
	    </h4>
	<?php endif;?>
</div><!-- end #info-bar -->
<script>
<?php
if(date_default_timezone_get()=='Asia/Jakarta'){
    $match_date_ts += 6*60*60;
}
?>
var match_date_ts = <?=$close_time['ts']?>;
function countdown(){
	var date = new Date();
	var td = date.getTime();
	var d = (match_date_ts*1000) - td;
	//get days
	var days = Math.floor((d)/(24*60*60*1000));
	var d0 = (d)/(24*60*60*1000);

	//get hours
	var d1 = d0-days;
	var h0 = d1*24;
	var hour = Math.floor(h0);
	

	//get minutes
	var minute = Math.floor((d)/(60*1000))%60;

	var second = Math.round(d/1000);

	if(days >= 0){
		$('.ctday').html(days);
		$('.cthour').html(hour);
		$('.ctminute').html(minute);
		$("#ct1").show();
		$("#ct2").hide();
		$("#ct0").hide();
		if(second <= 60){
			
			$("#ct1").hide();
			$("#ct0").show();
			$(".ctsec").html(second);
		}
	}else{
		$('#ct1').hide();
		$('#ct2').show();
		$("#ct0").hide();
	}
	//console.log(second);
	setTimeout(function(){
		countdown();

	}, 1000);
}
countdown();
</script>