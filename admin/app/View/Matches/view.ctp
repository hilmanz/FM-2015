<h3>Match Details</h3>
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
	<h3>LINEUP</h3>
	<div style="width:450px;float:left;margin-right:10px;">
	<h3><?=$d['home']['name']?></h3>
	<table width="450">
		<?php foreach($home_lineup as $lineup):?>
		<tr>
			<td>
				<a href="<?=$this->Html->url('/matches/player_stats/'.$game_id.'/'.$d['home']['uid'].'/'.$lineup['Player']['uid'])?>"><?=$lineup['Player']['name']?></a>
			</td>
			<td>
				<?=$lineup['Player']['position']?>
			</td>
			<td>
				<?=$lineup['Lineup']['status']?>
			</td>
		</tr>
		<?php endforeach;?>
	</table>
	</div>
	<div style="width:450px;float:left;margin-right:10px;">
	<h3><?=$d['away']['name']?></h3>
	<table width="450">
		<?php foreach($away_lineup as $lineup):?>
		<tr>
			<td>
				<a href="<?=$this->Html->url('/matches/player_stats/'.$game_id.'/'.$d['away']['uid'].'/'.$lineup['Player']['uid'])?>"><?=$lineup['Player']['name']?></a>
			</td>
			<td>
				<?=$lineup['Player']['position']?>
			</td>
			<td>
				<?=$lineup['Lineup']['status']?>
			</td>
		</tr>
		<?php endforeach;?>
		
	</table>
	</div>
</div>
<div class="row">
	<div style="width:450px;float:left;margin-right:10px;">
	<h3>Goal Scorers</h3>
	<table width="450">
		<tr>
			<td><?=$d['home']['name']?></td>
			<td><?=$d['away']['name']?></td>
		</tr>
		<?php if(isset($goals)):foreach($goals as $goal):?>
		<tr>
			<td>
				<?php if(isset($goal['home']['player_id'])):?>
				<?=$goal['home']['player_name']?> (<?=$goal['home']['time']?>)
				<?php endif;?>
			</td>
			<td>
				<?php if(isset($goal['away']['player_id'])):?>
				<?=$goal['away']['player_name']?> (<?=$goal['away']['time']?>)
				<?php endif;?>
			</td>
		</tr>
		<?php endforeach;endif;?>
	</table>
	</div>
	<div style="width:450px;float:left;">
	<h3>Bookings</h3>
	<table width="450">
		<tr>
			<td><?=$d['home']['name']?></td>
			<td><?=$d['away']['name']?></td>
		</tr>
		<?php if(isset($bookings)):foreach($bookings as $book):?>
		<tr>
			<td>
				<?php if(isset($book['home']['player_id'])):?>
				<?=$book['home']['player_name']?> (<?=$book['home']['card']?>)
				<?php endif;?>
			</td>
			<td>
				<?php if(isset($book['away']['player_id'])):?>
				<?=$book['away']['player_name']?> (<?=$book['away']['card']?>)
				<?php endif;?>
			</td>
		</tr>
		<?php endforeach;endif;?>
	</table>
	</div>
</div>
<div style="width:450px;float:left;margin-right:10px;">
<h3><?=$d['home']['name']?> Statistics</h3>
<table width="450">
	<?php if(isset($home_stats)):foreach($home_stats as $stats):?>
	<tr>
		<td><?=$stats['Team_stat']['stats_name']?></td>
		<td><?=$stats['Team_stat']['stats_value']?></td>
	</tr>
	<?php endforeach;endif;?>
</table>
</div>
<div style="width:450px;float:left;margin-right:10px;">
<h3><?=$d['away']['name']?> Statistics</h3>
<table width="450">
	<?php if(isset($away_stats)):foreach($away_stats as $stats):?>
	<tr>
		<td><?=$stats['Team_stat']['stats_name']?></td>
		<td><?=$stats['Team_stat']['stats_value']?></td>
	</tr>
	<?php endforeach;endif;?>
</table>
</div>



