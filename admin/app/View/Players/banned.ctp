<div id="content">
<?php echo $this->Session->flash(); ?>
</div>
<div class="row">
	<a href="<?=$this->Html->url('/players/view/'.$users[0]['a']['id'])?>" class="button">Back</a>
</div>
<div>
	<form action="<?=$this->Html->url('/players/banned')?>" method="POST" enctype="application/x-www-form-urlencoded">
		<table class="table">
			<tbody>
				<tr class="odd">
					<td>User Id</td>
					<td>Player Name</td>
					<td>Team</td>
					<td>Email</td>
				</tr>
				<tr class="even">
					<td><?=$users[0]['a']['id']?></td>
					<td><?=$users[0]['a']['name']?></td>
					<td><?=$users[0]['b']['name']?> (<?=$users[0]['d']['name']?>)</td>
					<td><?=$users[0]['a']['email']?></td>
				</tr>
			</tbody>
		</table>
		<div>
			<label>Banned Type</label>
			<select name="banned_type">
				<option value="">
					--SELECT--
				</option>
				<option value="CANT_USE_COIN">
					CANT_USE_COIN
				</option>
			</select>
		</div>
		<div>
			<label>Reason</label>
			<input name="reason" placeholder="Type a reason here..." value="" type="text">
		</div>
		<input type="hidden" name="user_id" value="<?=$users[0]['a']['id']?>" />
		<div class="row">
		<input name="btn" value="Banned User" class="button" type="submit">
		</div>
	</form>
</div>