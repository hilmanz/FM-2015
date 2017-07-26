<?php
$sponsors = (isset($sponsors))? $sponsors : array();
?>
<div class="titleBox">
<h1>Sponsorships</h1>
</div>
<div class="row-2">
	<a href="<?=$this->Html->url('/sponsors/create')?>" class="button">Create Sponsorship</a>
	<a href="<?=$this->Html->url('/sponsors/perks')?>" class="button">Show Perks</a>
</div>
<div class="row-2">

	<h4>Current Perks</h4>
	* Warning - these is developer-only feature. Please stay away from these page if you're not the developer of the Supersoccer FM.
<table width="100%">
	<tr>
		<td>No</td>
		<td>PerkID</td>
		<td>Name</td>
		<td>Description</td>
		<td>Amount</td>
		<td>Action</td>
	</tr>
	<?php
	foreach($rs as $n=>$v):
	?>
	<tr>
		<td><?=$n+1?></td>
		<td><?=h($v['Perk']['perk_name'])?></td>
		<td>
			<?=h($v['Perk']['name'])?>
		</td>
		<td>
			<?=h($v['Perk']['description'])?>
		</td>
		<td>
			<?=number_format($v['Perk']['amount'])?>
		</td>
		<td><a href="<?=$this->Html->url('/sponsors/edit_perk/'.$v['Perk']['id'])?>">Edit</a></td>
	</tr>
	<?php endforeach;?>
</table>
</div>
<div class="row-2">
<h3>Create Perk</h3>
* Warning - these is developer-only feature. Please stay away from these page if you're not the developer of the Supersoccer FM.
<form action="<?=$this->Html->url('/sponsors/create_perk')?>" method="POST" enctype="application/x-www-form-urlencoded">
	<table width="100%">
		<tr>
			<td>Perk ID</td><td><input type="text" name="perk_name" value=""></td>
		</tr>
		<tr>
			<td>Name</td><td><input type="text" name="name" value=""></td>
		</tr>
		<tr>
			<td>Descriptions</td><td><input type="text" name="description" value=""></td>
		</tr>
		<tr>
			<td>Amount</td><td><input type="text" name="amount" value="5000000"></td>
		</tr>
		<tr>
			<td colspan="2"><input type="submit" name="btn" value="Add Perk"/></td>
		</tr>
	</table>
</form>
</div>
