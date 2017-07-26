<div class="titleBox">
<h1>Sponsorships</h1>
</div>
<div class="row-2">
	<a href="<?=$this->Html->url('/sponsors/create')?>" class="button">Create Sponsorship</a>
	<a href="<?=$this->Html->url('/sponsors/perks')?>" class="button">Show Perks</a>
</div>

<div class="row-2">
	<h3>SPONSORSHIP : <?=$sponsor['name']?></h3>
	<h4>Perks: </h4>
	<?php foreach($sponsor['perks'] as $perk):?>
	<div class="perklist" style="width:400px;clear:both;">
		<span style="float:left;"><?=h($perk['name'])?></span>
		<span style="float:right;">$SS <?=h($perk['amount'])?></span>
	</div>
	<?php endforeach;?>
</div>
<div class="row-2">
	<h4>Sending Invitation</h4>
	<div>
		Filter : 
		<select name="filter">
			<option value="everyone_once">Everyone (only Once)</option>
			<option value="tier1">Tier 1</option>
			<option value="tier2">Tier 2</option>
			<option value="tier3">Tier 3</option>
			<option value="tier4">Tier 4</option>
		</select>
	</div>
	<div class="resultbox alert" style="display:none;">

	</div>
	<div class="execbox">
		<a href="#" class="button" id="btn-sending">Send</a>
	</div>
</div>

<script>
var start = 0;
var total_scan = 0;
var in_queue = 0;
$('#btn-sending').click(function(e){
	$(".resultbox").html('Sending to Queue');
	$(".resultbox").show();
	var filter = $('select[name=filter]').val();

	start = 0;
	total_scan = 0;
	in_queue = 0;

	queueing(filter);
	
});
function queueing(filter){
	api_call('<?=$this->Html->url('/sponsors/queueing/?sponsor_id='.$sponsor['id'])?>&email_type=invitation&filter='+filter+'&start='+start,
	 function(response){
	 	if(response.status==1){
	 		total_scan += response.total;
	 		in_queue += response.in_queue;
	 		if(response.total==0){
	 			$(".resultbox").html($(".resultbox").html()+'<br/>All email has been queued, it will take several minutes to hours to complete.');
	 		}else{
	 			$(".resultbox").html((total_scan)+' scanned, '+in_queue+' in queue..');
	 			start+=20;
	 			queueing(filter);
	 		}
	 	}else{
	 		$(".resultbox").html('Cannot send the emails, please try again later !');
	 	}
	 });
}
</script>