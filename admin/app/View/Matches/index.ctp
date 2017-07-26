<div class="titleBox">
	<h1>Matches</h1>
</div>
<div class="theContainer">
	<h3 class="titles">The list of ongoing and finished matches based on Opta.</h3>
	<table width="100%" border="0" cellspacing="0" cellpadding="0" class="dataTable">
		<thead>
			<tr>
				<th>GameID</th>
				<th>Week</th>
				<th>Date</th>
				<th>Home</th>
				<th>Score</th>
				<th>Away</th>
				<th>Period</th>
				<th>Attendance</th>
				<th>Ref</th>
				<th>Venue</th>
				<th>Last Update</th>
			</tr>
		</thead>
		<tbody>
		<?php
		if(isset($data)):foreach($data as $d):
		?>
		<tr>
			<td><a href="<?=$this->Html->url('/matches/view/'.$d['Matches']['game_id'])?>"><?=$d['Matches']['game_id']?></a></td>
			<td><?=$d['Matches']['matchday']?></td>
			<td><?=date("d/m/Y H:i:s",strtotime($d['Matches']['matchdate']))?></td>
			<td><?=$d['home']['name']?></td>
			<td><?=intval($d['Matches']['home_score'])?> - <?=intval($d['Matches']['away_score'])?></td>
			<td><?=$d['away']['name']?></td>
			<td><?=$d['Matches']['period']?></td>
			<td><?=$d['Matches']['attendance']?></td>
			<td><?=$d['Matches']['referee']?></td>
			<td><?=$d['Matches']['venue_name']?></td>
			<td><?=$d['Matches']['last_update']?></td>
		</tr>
		<?php endforeach;endif;?>
		</tbody>
	</table>
</div>
<div class="paging">
<?php if(isset($data)):?>
<?php echo $this->Paginator->numbers();?>
<?php endif;?>
</div>
