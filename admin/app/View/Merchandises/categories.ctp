<h3>
	Merchandises - Categories
</h3>
<div class="row">
	<a href="<?=$this->Html->url('/merchandises/create')?>" class="button">Add Merchandise</a>
	<a href="<?=$this->Html->url('/merchandises/categories')?>" class="button">Categories</a>
	<a href="<?=$this->Html->url('/merchandises/orders')?>" class="button">Purchase Orders</a>
</div>

<div class="row">
	<table width="100%">
		<tr>
			<td>Name</td>
			<td>Action</td>
		</tr>
		<?php 
			if(isset($rs)): foreach($rs as $r):
			$data = $r['MerchandiseCategory'];
		?>
		<tr>
			<td><a href="#"><?=h($data['name'])?></a></td>
			<td><a href="<?=$this->Html->url('/merchandises/delete_category/'.$data['id'])?>">Delete</a></td>
		</tr>
		<?php endforeach;endif;?>
	</table>
</div>
<div class="row">
	<form action="<?=$this->Html->url('/merchandises/add_category')?>" 
			method="post" 
			enctype="application/x-www-form-urlencoded">
			<input type="text" name="name" value="" placeholder="New Category"/>
			<select name="parent_id">
				<option value="0">Parent Category</option>
				<?php if(isset($rs)): foreach($rs as $r):
					$data = $r['MerchandiseCategory'];
					//sementara di lock jadi 2 level dulu category-nya.
					if($data['parent_id']==0):
				?>
				<option value="<?=$data['id']?>"><?=h($data['name'])?></option>
				<?php endif;endforeach;endif;?>
			</select>
			<input type="submit" name="btn" value="Add"/>
	</form>
</div>
