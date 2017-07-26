<h3>
	Fund Redeemed History
</h3>
<div class="theContainer">
	
	<table width="100%" border="0" cellspacing="0" cellpadding="0" class="dataTable">
		<thead>
			<tr>
				<th width="1">No</th>
				<th>Name</th>
				<th>Amount</th>
				<th>Send Date</th>
				<th></th>
			</tr>
		</thead>
		<tbody>
		<?php
		foreach($rs as $n=>$m):
		?>
		<tr>
			<td style="width:20px;"><?=$n+1?></td>
			<td>
				<?=h($m['AddFundHistory']['name'])?>
			</td>
			<td>
				<?=h($m['AddFundHistory']['amount'])?>
			</td>
			<td>
				<?=h($m['AddFundHistory']['post_dt'])?>
			</td>
			<td>
				<a href="<?=$this->Html->url('/funds/history/').$m['AddFundHistory']['id']?>" class="button">View</a>
			</td>
		</tr>
		<?php endforeach;?>
	</table>
	</tbody>
</table>
</div>
<div class="paging">
<?php echo $this->Paginator->numbers();?>
</div>
<?php
if($view_transaction):
?>
<h4><?=h($transaction['name'])?></h4>
<div class="row">
	<table width="100%" border="0" cellspacing="0" cellpadding="0" 
		class="dataTable dataTablePlayer" id="tbl">
	
	</table>
</div>
<?php echo $this->Html->script('jquery.dataTables.min');?>
<script>
var start = 0;
var teamlist = <?=json_encode($transaction['teams'])?>;
var base_id = <?php echo Configure::read('RANK_RANDOM_NUM'); ?>;
var data = [];
for(var i in teamlist){
	data.push([
		parseInt(teamlist[i].game_team.id) + parseInt(base_id),
		teamlist[i].game_users.name,
		teamlist[i].teams.team_name,
		teamlist[i].master_team.name
	]);
}


function draw_table(){
	console.log(data);
	$('#tbl').dataTable({
		"aaData": data,
		"aoColumns": [
			{ "sTitle": "No.ID" },
			{ "sTitle": "Name" },
			{ "sTitle": "Team Name" },
			{ "sTitle": "Original Team" }
		]
	});
}

draw_table();
</script>
<?php endif;?>