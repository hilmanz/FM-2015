<h3>
	Merchandises - Ongkos Kirim
</h3>
<div class="row">
	<a href="<?=$this->Html->url('/merchandises/create')?>" class="button">Add Merchandise</a>
	<a href="<?=$this->Html->url('/merchandises/categories')?>" class="button">Categories</a>
	<a href="<?=$this->Html->url('/merchandises/orders')?>" class="button">Purchase Orders</a>
	<a href="<?=$this->Html->url('/merchandises/update_ongkir')?>" class="button">Update Ongkir</a>
</div>

<div class="row">
<h3>
	Upload new Price List
	<form action="<?=$this->Html->url('/merchandises/update_ongkir')?>"
		method="post"
		enctype="multipart/form-data">
		<div>
		<input type="file" name="file"/>
		</div>
		<div>
		<input type="submit" name="btn" value="Upload"/>
		</div>
	</form>
</h3>
</div>