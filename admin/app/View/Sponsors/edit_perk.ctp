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
<h3>Modify Perk</h3>

<form action="<?=$this->Html->url('/sponsors/edit_perk/'.$perk['id'])?>" method="POST" enctype="application/x-www-form-urlencoded">
	<table width="100%">
		<tr>
			<td>Perk ID</td>
			<td>
				<input type="text" name="perk_name" value="<?=$perk['perk_name']?>" disabled='true'>
			</td>
		</tr>
		<tr>
			<td>Name</td>
			<td>
				<input type="text" name="name" value="<?=$perk['name']?>" disabled='true'>
			</td>
		</tr>
		<tr>
			<td>Descriptions</td>
			<td>
				<input type="text" name="description" value="<?=$perk['description']?>">
			</td>
		</tr>
		<tr>
			<td>Amount</td>
			<td>
				<input type="text" name="amount" value="<?=$perk['amount']?>">
			</td>
		</tr>
		<tr>
			<td colspan="2"><input type="submit" name="btn" value="UPDATE"/></td>
		</tr>
	</table>
</form>
</div>
