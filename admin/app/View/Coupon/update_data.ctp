<div class="titleBox">
	<h1>Coupon</h1>
</div>
<div class="theContainer">
	<div class="title">
		<h3>Import Data for `<?=h($coupon['Coupon']['vendor_name'])?> - <?=h($coupon['Coupon']['service_name'])?>` </h3>
	</div>
	<div class="row">
		<form 
			action="<?=$this->Html->url('/coupon/import_csv/'.$coupon['Coupon']['id'])?>" 
			method="post" 
			enctype="multipart/form-data">
			<input type="file" name="csv"/>
			<input type="submit" name="btn" value="Import CSV"/>
		</form>
	</div>

	
</div>

