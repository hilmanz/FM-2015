<div class="titleBox">
	<h1>Coupon</h1>
</div>
<div class="theContainer">
	<div class="title">
		<h3></h3>
	</div>
	<div class="row">

		<?php
			echo $this->Form->create('Coupon',array('type' => 'file'));
		?>
		<div>
			<h3>Vendor</h3>
		</div>
		<?php
		echo $this->Form->input('vendor_name',
								array('default'=>$coupon['Coupon']['vendor_name'],
										'label'=>false));   
		?>
		<div>
			<h3>Service Name</h3>
		</div>
		<?php
		echo $this->Form->input('service_name',array('default'=>$coupon['Coupon']['service_name'],
														'label'=>false));   
		?>
		<div>
			<h3>Created By : <?=h($coupon['Admin']['username'])?></h3>
		</div>
		<div>
			<h3>Description</h3>
		</div>
		<?php
		echo $this->Form->input('description',array('default'=>$coupon['Coupon']['description'],
														'label'=>false)); 
		?>
		<div>
			<h3>Coin Reward</h3>
		</div>
		<?php
		echo $this->Form->input('coin_amount',array('default'=>intval($coupon['Coupon']['coin_amount']),
														'label'=>false)); 

		?>
		<div>
			<h3>ss$ Reward</h3>
		</div>
		<?php
		echo $this->Form->input('ss_dollar',array('default'=>intval($coupon['Coupon']['ss_dollar']),
														'label'=>false)); 
		?>
		<div>
			<h3>Image</h3>
		</div>
		<div>
			<img src="<?=Configure::read('avatar_web_url').$coupon['Coupon']['img']?>"/>
			<input type="file" name="img"/>
		</div>
		<div><h3>Status</h3></div>
		<div class="row">
			<?php
				$options = array('1'=>'Enabled','0'=>'Pending');
				echo $this->Form->input('n_status',

										array('label'=>false,'type'=>'select','options'=>$options,
											  'default'=>$coupon['Coupon']['n_status']));
			?>
		</div>
		<div class="row">
		<?php 
			echo $this->Form->end('Update');
		?>
		</div>
		<div class="row">
			<a href="<?=$this->Html->url('/coupon')?>" class="button">Back to Coupon List</a>
			<a href="<?=$this->Html->url('/coupon/generate/'.$coupon['Coupon']['id'])?>" 
					class="button">
					Generate Codes
			</a>
		</div>
	</div>

	<div class="row">
		<h3>Generated Codes (<?=number_format($coupon_count)?>)</h3>
		<table width="100%" class="table">
			<tr>
				<td>
					Code
				</td>
				<td>
					Used
				</td>
			</tr>
		</table>
	</div>
	<div class="row">
		<h3>REDEEM HISTORY</h3>
		<table width="100%" border="0" cellspacing="0" cellpadding="0" 
		class="dataTable dataTablePlayer" id="tbl">
		
		</table>
	</div>
	<div class="row">
		<a href="<?=$this->Html->url('/coupon/update_data/'.$coupon['Coupon']['id'])?>" class="button">Upload Data</a><br/>
		<a href="<?=$this->Html->url('/coupon/download/'.$coupon['Coupon']['id'])?>" class="button">Download Unused Codes</a>
	</div>
</div>

<?php echo $this->Html->script('jquery.dataTables.min');?>
<script>
var data = [];
function loadRedeemedCode(start){
	api_call("<?=$this->Html->url('/coupon/ajax_code_redeemed/'.$coupon['Coupon']['id'])?>?start="+start,
				function(response){
					if(response.status==1){
						for(var i in response.data){
							var paid_status = 'unpaid';
							if(response.data[i].CouponCode.paid==1){
								paid_status = 'paid';
							}
							var transaction_status = 'FAILED';
							if(response.data[i].CouponCode.n_status==1){
								transaction_status = 'SUCCESS';
							}
							data.push(
								[
									response.data[i].CouponCode.redeem_dt,
									response.data[i].User.user_id+' - '+response.data[i].User.name,
									response.data[i].CouponCode.coupon_code,
									paid_status,
									transaction_status,

								]
							)
						}
						if(response.total_rows == 100){
							loadRedeemedCode(start+100);
						}else{
							drawTable();
						}
					}
	});
}
function drawTable(){
	
	$('#tbl').dataTable({
		"aaData": data,
		"aoColumns": [
			{ "sTitle": "Redeem Date" },
			{ "sTitle": "User" },
			{ "sTitle": "Code" },
			{ "sTitle": "Payment" },
			{ "sTitle": "Status" }
		]
	});
}

loadRedeemedCode(0);
</script>