<h3>
	Create Triggered Event
</h3>
<div class="row">
	<a href="<?=$this->Html->url('/events/')?>" class="button">Back to Events</a>
</div>

<div class="row">
<form action="<?=$this->Html->url('/events/create2')?>" method="post" enctype="multipart/form-data">

<table width="100%">
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
							 'By Original Team');
	foreach($data as $name=>$val):
		if($name!=='step' && $name!=='btn'):
			if($name=='event_type'){
				$val = $event_type[$val];
			}
			if($name=='recipient_type'){
				$val = $recipient_type[$val];
			}
	?>
	<tr>
		<td valign="top">
			<?=strtoupper(str_replace('_',' ',$name))?>
		</td>
		<td>
			<?=$val?>
		</td>
	</tr>
	<?php endif;endforeach;?>
	
	<tr>
		<td colspan="2">
			<input type="hidden" name="step" value="4"/>
			<input type="submit" name="btn" value="OK, CREATE"/>
		</td>
	</tr>
</table>

</form>
</div>
 <script>
  $(document).ready(function() {
  	$( "#datepicker" ).datepicker();
    $( "#datepicker" ).datepicker("option", "dateFormat", "dd/mm/yy");
     $( "#datepicker" ).datepicker("hide");
  });
  </script>