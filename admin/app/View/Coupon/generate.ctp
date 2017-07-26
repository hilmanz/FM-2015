<div class="titleBox">
	<h1>Coupon</h1>
</div>
<div class="theContainer">
	<h3>Generate new codes for `<?=h($coupon['Coupon']['vendor_name'])?> - <?=h($coupon['Coupon']['service_name'])?>`</h3>
	<div class="row">
		<form action="<?=$this->Html->url('/coupon/generate/'.$coupon['Coupon']['id'])?>"
				method="post"
				enctype="application/x-www-form-urlencoded">
			<label>How Many Codes ?</label>
			<input type="text" name="amount" value="0"/>
			<a href="#" class="button btnGenerate">Generate Codes</a>
			<a href="<?=$this->Html->url('/coupon')?>" class="button">Back to Coupon List</a>
		</form>
	</div>
</div>
<div id="dialog" title="Please Wait.." class="popup">
	<div class="popupContainer">
		  <p>We're generating your codes, please wait and don't close the browser !</p>
		  <div class="progress"></div>
	</div>
</div>
<script>
var n_total = 0;
var n_done = 0;
$( "#dialog" ).dialog({
  modal: true,
  buttons: {
    Ok: function() {
      $( this ).dialog( "close" );
    }
  }
});
$( "#dialog" ).dialog('close');
$(".btnGenerate").click(function(e){
	n_total = $("input[name=amount]").val();
	
	$( "#dialog" ).dialog('open');
	generate_code(n_total);
});
function generate_code(queue_left){
	var total_code = queue_left;
	if(queue_left > 100){
		total_code = 100;
	}
	if(queue_left > 0){
		api_call("<?=$this->Html->url('/coupon/ajax_generate/'.$coupon['Coupon']['id'])?>?total="+total_code+'?rand='+Math.random()*9999,
		function(response){
			if(response.status==1){
				n_done+=total_code;
				$(".progress").html(n_done);
				queue_left-=total_code;
				generate_code(queue_left);
			}
		});
	}
}

</script>