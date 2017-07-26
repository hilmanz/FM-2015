<div class="titleBox">
	<h1>Coupon</h1>
</div>
<div class="theContainer">
	<div class="title">
		<h3>Create Coupon / Voucher</h3>
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
								array('label'=>false));   
		?>
		<div>
			<h3>Service Name</h3>
		</div>
		<?php
		echo $this->Form->input('service_name',array('label'=>false));   
		?>
		
		<div>
			<h3>Description</h3>
		</div>
		<?php
		echo $this->Form->input('description',array('label'=>false)); 
		?>
		<div>
			<h3>Coin Reward</h3>
		</div>
		<?php
		echo $this->Form->input('coin_amount',array('label'=>false)); 

		?>
		<div>
			<h3>ss$ Reward</h3>
		</div>
		<?php
		echo $this->Form->input('ss_dollar',array('label'=>false)); 
		?>
		<div>
			<h3>Image</h3>
		</div>
		<div>
			<input type="file" name="img"/>
		</div>
		<div><h3>Status</h3></div>
		<div class="row">
			<?php
				$options = array('1'=>'Enabled','0'=>'Pending');
				echo $this->Form->input('n_status',
										array('label'=>false,'type'=>'select','options'=>$options));
			?>
		</div>
		<div class="row">
		<?php 
			echo $this->Form->end('Create');
		?>
		</div>
		<a href="<?=$this->Html->url('/coupon')?>" class="button">Back to Coupon List</a>
		
	</div>
	
</div>
