<div class="titleBox">
	<h1>`<?=$sponsor['name']?>` Statistics</h1>
</div>
<div class="row-1">
<table width="100%">
<tr>
	<td>Banner</td>
	<td>Slot</td>
	<td>Impressions</td>
	<td>Clicks</td>
	<td>Action</td>
</tr>
<?php foreach($banners as $banner):?>
<tr>
	<td><?=h($banner['a']['banner_name'])?></td>
	<td><?=h($banner['a']['slot'])?></td>
	<td><?=number_format($banner[0]['views'])?></td>
	<td><?=number_format($banner[0]['clicks'])?></td>
	<td>
		<a href="<?=$this->Html->url('/sponsors/stats_detail/'.$sponsor['id'].'/'.$banner['a']['id'])?>" 
		class="button">Details</a>
	</td>
</tr>
<?php endforeach;?>
</table>
</div>