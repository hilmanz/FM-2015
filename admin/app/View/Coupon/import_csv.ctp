<div class="titleBox">
	<h1>Coupon</h1>
</div>
<div class="theContainer">
	<div class="title">
		<h3>Import Data for `<?=h($coupon['Coupon']['vendor_name'])?> - <?=h($coupon['Coupon']['service_name'])?>` </h3>
	</div>
	<div class="row">
		<?php if(isset($success_codes) && sizeof($success_codes) > 0):?>
		<h4>The followings are updated successfully : </h4>
		<table width="100%">
			<tr>
				<td>Code</td><td>Purchased Date</td>
			</tr>
			<?php foreach($success_codes as $success):?>
			<tr>
				<td><?=h($success['code'])?></td>
				<td><?=h($success['purchase_date'])?></td>
				
			</tr>
			<?php endforeach;?>
		</table>
		<?php endif;?>
	</div>
	<div class="row">
		<?php if(isset($error_codes) && sizeof($error_codes) > 0):?>
		<h4>The followings are unable to update : </h4>
		<table width="100%">
			<tr>
				<td>Code</td><td>Purchased Date</td><td>Status</td>
			</tr>
			<?php foreach($error_codes as $errors):?>
			<tr>
				<td><?=h($errors['code'])?></td>
				<td><?=h($errors['purchase_date'])?></td>
				<td><?=h($errors['reason'])?></td>
			</tr>
			<?php endforeach;?>
		</table>
		<?php endif;?>
	</div>
	<div class="row">
		<a href="<?=$this->Html->url('/coupon')?>" class="button">Back to Coupon List</a>
	</div>
	
</div>

