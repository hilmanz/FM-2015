<div id="info-bar" class="tr2">
    <h4 class="date-now fl"><?=date("d/m/Y")?></h4>
    <div id="newsticker">
          <ul class="slides">
            <li class="newsticker-entry">
                <h3><a href="#n1">Lorem ipsum FC VS Dolor</a></h3>
            </li><!-- end .newsticker-entry -->
            <li class="newsticker-entry">
                <h3><a href="#n1">2 Goals Sit amet, consectetuer</a></h3>
            </li><!-- end .newsticker-entry -->
            <li class="newsticker-entry">
                <h3><a href="#n1">Sdipiscing elit VS Rincidunt Team 3-0,</a></h3>
            </li><!-- end .newsticker-entry -->
            <li class="newsticker-entry">
                <h3><a href="#n1">Sed diam nonummy nibh euismod tincidunt ut</a></h3>
            </li><!-- end .newsticker-entry -->
          </ul><!-- end #newsticker -->
    </div>
    <h4 class="fr countdown"><span class="yellow ctday">6</span> DAYS <span class="yellow cthour">0</span> HOUR <span class="yellow ctminute">0</span> MINUTE to close</h4>
</div><!-- end #info-bar -->
<script>
var match_date_ts = <?=$match_date_ts?>;
function countdown(){
	var date = new Date();
	var td = date.getTime();
	var d = (match_date_ts*1000) - td;
	//get days
	var days = Math.floor((d/1000)/(24*60*60));
	var d0 = (d/1000)/(24*60*60);

	//get hours
	var d1 = d0-days;
	var h0 = d1*24;
	var hour = Math.floor(h0);
	

	//get minutes
	var h1 = h0 - hour;
	
	var m0 = h1 * 60;
	var minute = Math.round(m0);
	

	if(days >= 0){
		$('.ctday').html(days);
		$('.cthour').html(hour);
		$('.ctminute').html(minute);
		$('.countdown').show();
	}else{
		$('.countdown').hide();
	}
	setTimeout(function(){
		countdown();
	}, 30000);
}
countdown();
</script>