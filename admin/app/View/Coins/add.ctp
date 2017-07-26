<h3>
	Give Coins
</h3>
<div class="row">
	<a href="<?=$this->Html->url('/coins/history')?>" class="button">View History</a>
</div>
<h4>Search Users</h4>
<div class="row">
Enter the list of user's IDs.
</div>
<div class="row">
	<form id="frm1" action="<?=$this->Html->url('/coins/add')?>"
		  method="POST"
		  enctype="application/x-www-form-urlencoded">

	<div>
		<textarea name="id_list" rows="20"></textarea>
	</div>
	<div>
		<a href="javascript:;" class="button" id="btn-search">Search</a> 
	</div>
	</form>

</div>
<div class="row">
	<form action="<?=$this->Html->url('/coins/bulk_send')?>"
		 method="POST"
		  enctype="application/x-www-form-urlencoded">
			<table class="table">
				<tr>
					<td>User Id</td>
					<td>Player Name</td>
					<td>Team</td>
					<td>Original Team</td>
					<td>Email</td>
					<td>Amount</td>
					<td>Pilih</td>
					
				</tr>
				<?php if(isset($teams)):foreach($teams as $team):?>
				<tr>
					<td>
						<?=intval($team['game_team']['id']) + intval(Configure::read('RANK_RANDOM_NUM'))?>
					</td>
					<td><?=h($team['user']['name'])?></td>
					<td><?=h($team['teams']['team_name'])?></td>
					<td><?=h($team['master_team']['name'])?></td>
					<td><?=h($team['user']['email'])?></td>
					<td>
						<?=number_format($team['cash']['cash'])?>
					</td>
					<td>
						<input type="checkbox" name="team_id[]" value="<?=intval($team['game_team']['id'])?>" checked='checked'/>

					</td>
				</tr>
				<?php endforeach;endif;?>
			</table>
			<input name="name" type="text" placeholder="Type a reason here..." value=""/>
			<div>
				<label>Notification Message</label>
				<input name="message" type="text" placeholder="Type a notification message here.." value=""/>
			</div>
			
			<div>
				<input type="text" name="amount" placeholder="" value="0"/> Coins
			</div>
			<div>
				<label>Authorization</label>
				<input type='password' name="authcode" value=""/>
			</div>
			<div class="row">
			<input type="submit" name="btn" value="Send Coins" class="button"/>
			</div>
	</form>
</div>

<script>
$("#btn-search").on('click',function(e){
	$("#frm1").submit();
});
</script>