<h3>Match Detail</h3>
<div class="row">
<table width="100%">
	<tr>
		<td>GameID</td>
		<td>Week</td>
		<td>Date</td>
		<td>Home</td>
		<td>Score</td>
		<td>Away</td>
		<td>Period</td>
		<td>Attendance</td>
		<td>Ref</td>
		<td>Venue</td>
		<td>Last Update</td>
	</tr>
	<?php
	$d = $match;
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
</table>
</div>
<div class="row">
<div style="width:450px;float:left;">
<h3>Player Detail</h3>
<table width="450">
	<tr>
		<td>Name</td>
		<td><?=$player['name']?></td>
	</tr>
	<tr>
		<td>Position</td>
		<td><?=$player['position']?></td>
	</tr>
	<tr>
		<td>Jersey Number</td>
		<td><?=$player['jersey_num']?></td>
	</tr>
	<tr>
		<td>Team</td>
		<td><?=$player['team']['name']?></td>
	</tr>
	
</table>

</div>
<div style="width:450px;float:left;margin-right:10px;">
<h3>Statistics</h3>

<table width="450">
	<?php if(isset($stats)):foreach($stats as $stat):?>
	<tr>
		<td><?=$stat['Player_stat']['stats_name']?></td>
		<td><?=$stat['Player_stat']['stats_value']?></td>
	</tr>
	<?php endforeach;endif;?>
</table>
</div>
</div>



