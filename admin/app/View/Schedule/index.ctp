<div class="titleBox">
	<h1>Game Schedule</h1>
</div>
<div class="theContainer">
<table width="100%" border="0" cellspacing="0" cellpadding="0" class="dataTable">
	<thead>
		<tr>
			<th width="1">No</th>
			<th>Matchday</th>
			<th>Game_id</th>
			<th>Date</th>
			<th>Home</th>
			<th class="center">Score</th>
			<th>Away</th>
			<th>Period</th>
			<th class="center">Attendance</th>
			<th class="center">Processed</th>
			<th class="center">Action</th>
		</tr>
	</thead>
	<tbody>
	<?php
	foreach($match as $n=>$m):
	?>
	<tr>
		<td><?=$n+1?></td>
		<td><?=h($m['Fixture']['matchday'])?></td>
		<td><?=h($m['Fixture']['game_id'])?></td>
		<td><?=date("d-m-Y H:i:s",strtotime($m['Fixture']['match_date']))?></td>
		<td><?=h($m['Home']['name'])?></td>
		<td class="center"><?=h($m['Fixture']['home_score'].' - '.$m['Fixture']['away_score'])?></td>
		<td><?=h($m['Away']['name'])?></td>
		<td><?=h($m['Fixture']['period'])?></td>
		<td class="center"><?=number_format($m['Fixture']['attendance'])?></td>
		<td class="center"><?=h($m['Fixture']['is_processed'])?></td>
		<td class="center">
			<a href="<?=$this->Html->url('/schedule/edit/'.h($m['Fixture']['game_id']))?>" class="button">Edit</a>
		</td>
	</tr>
	<?php endforeach;?>
	</tbody>
</table>
</div>