<h3>
	Digital Coupon / Voucher
</h3>
<div class="row">
<a href="<?=$this->Html->url('/coupon/create')?>" class="button">
	Create Coupon / Voucher
</a>
</div>
<div class="row">
	<table width="100%" class="table">
	<?php
		echo $this->Html->tableHeaders(array('Date', 'Created By','Vendor', 'Service', 'Reward', 'Status','Action'));
	?>
	<?php
	$tblArray = array();
	for($i=0;$i<sizeof($data);$i++){
		$reward = '';
		if($data[$i]['Coupon']['coin_amount'] > 0){
			$reward .= number_format($data[$i]['Coupon']['coin_amount']).' Coins';
		}elseif($data[$i]['Coupon']['ss_dollar']){
			$reward .= 'ss$ '.number_format($data[$i]['Coupon']['ss_dollar']).'';
		}
		switch($data[$i]['Coupon']['n_status']){
			case 1:
				$status = 'Active';
			break;
			case 2:
				$status = 'Disabled';
			break;
			default:
				$status = 'Pending';
			break;
		}
		$tblArray[] = array(
			$data[$i]['Coupon']['created_dt'],
			$data[$i]['Admin']['username'],
			$data[$i]['Coupon']['vendor_name'],
			$data[$i]['Coupon']['service_name'],
			$reward,
			$status,
			'<a href="'.$this->Html->url('/coupon/view/'.$data[$i]['Coupon']['id']).'">View</a>'
		);
	}
	echo $this->Html->tableCells($tblArray);
	?>
	</table>
</div>
<?php if(isset($data)):?>
<?php echo $this->Paginator->numbers();?>
<?php endif;?>