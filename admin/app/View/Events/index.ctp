<h3>
	Event Manager
</h3>
<div class="row">
	<a href="<?=$this->Html->url('/events/create')?>" class="button">Create Event</a>
	
</div>
<h4>Standard Events</h4>
<div class="row">
	<table width="100%">
		<tr>
			<td>Event</td>
			<td>Type</td>
			<td>Target</td>
			<td>Affected Item</td>
			<td>Amount</td>
			<td>Schedule</td>
			<td>Applied</td>
			<td>Status</td>
		</tr>
		<?php if(isset($rs)): foreach($rs as $r):
			$data = $r['Events'];
			
		?>
		<tr>
			<td><a href="#"><?=h($data['event_name'])?></a></td>
			<td>
				<?php
					if($data['event_type']==1){
						echo "Player Data";
					}else{
						echo "Master Data";
					}
				?>
			</td>
			<td>
				<?php
					switch($data['target_type']){
						case 1:
							echo 'Individual Team';
						break;
						case 2:
							echo 'All Teams';
						break;
						case 3:
							echo 'By Tier';
						break;
						case 4:
							echo 'Individual Player';
						break;
					}
				?>
			</td>
			<td>
				<?php
					if($data['affected_item']==1){
						echo "Money";
					}else{
						echo "Points";
					}
				?>
			</td>
			<td><?=number_format($data['amount'])?></td>
			<td><?=date("d/m/Y",strtotime($data['schedule_dt']))?></td>
			<td>
				<?php
					if($data['apply_dt']!=null){
						echo date("d/m/Y",strtotime($data['apply_dt']));
					}else{
						echo '-';
					}
					
				?>
			</td>
			<td>
				<?php
					if($data['n_status']==1){
						echo "Applied";
					}else if($data['n_status']==2){
						echo "Finished";
					}else{
						echo "Pending";
					}
				?>
			</td>
		</tr>
		<?php endforeach;endif;?>
	</table>
</div>

<h4>Triggered Events</h4>
<div class="row">
<a href="<?=$this->Html->url('/events/create2')?>" class="button">Create User-Triggered Event</a>
</div>
<div class="row">
<?php
$event_type = array('',
						'Mengeluarkan/Membayarkan sejumlah uang untuk mendapatkan sejumlah uang setelah proses match weekend',
						'Mengeluarkan/Membayarkan sejumlah uang untuk menghindari penalty uang 	 atau point (% dari pemain single/multiple) setelah proses match
      				weekend.',
						'Offer Beli satu orang pemain (dengan catatan dia blm punya pemain tersebut)',
						'Offer Jual satu orang pemain (dengan catatan di sudah punya pemain tersebut)');

$recipient_type = array('All Teams',
						 'Tier 1',
						 'Tier 2',
						 'Tier 3',
						 'Tier 4',
						 'Original Team');
$n_status = array('Pending','Applied','Canceled');

?>

<table width="100%">
	<tr>
		<td>Event ID</td>
		<td>Event</td>
		<td>Type</td>
		<td>Recipient</td>
		<td>Schedule</td>
		<td>Expired</td>
		<td>Cost</td>
		<td>Status</td>
		<td>Action</td>
	</tr>
	<?php foreach($triggered as $t):?>
	<tr>
		<td><?=h($t['Events']['id'])?></td>
		<td><?=h($t['Events']['name'])?></td>
		<td><?=h($event_type[$t['Events']['event_type']])?></td>
		<td><?=h($recipient_type[$t['Events']['recipient_type']])?></td>
		<td><?=h($t['Events']['schedule_dt'])?></td>
		<td><?=h($t['Events']['expired_dt'])?></td>
		<td><?=h(number_format($t['Events']['money_cost']))?></td>
		<td><?=h($n_status[$t['Events']['n_status']])?></td>
		<td><a href="#" class="button">Edit</a></td>
	</tr>
	<?php endforeach;?>
</table>
</div>