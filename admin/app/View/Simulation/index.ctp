<div class="titleBox">
	<h1>Simulation</h1>
</div>
<div class="theContainer">
	<?php
	if($league=='epl'){
		$next_league = 'italy';
	}else{
		$next_league = 'epl';
	}
	?>
	<a href="<?=$this->Html->url('/simulation?league='.$next_league)?>" class="button">Switch League</a>
	<h3>
		<?=$league?>
	</h3>
	<form action="<?=$this->Html->url('/simulation')?>" method="post">
		<select name="team_id">
			<option value="0">
				Pilih Tim
			</option>
			<?php foreach($teams as $team):?>
			<option value="<?=$team['uid']?>">
				<?=$team['name']?>
			</option>
			<?php endforeach;?>

		</select>
		<select name="matchday">
			<option value="0">
				Pilih Matchday
			</option>
			<?php for($i=1;$i<39;$i++):?>
				<option value="<?=$i?>">
				<?=$i?>
				</option>
			<?php endfor;?>
		</select>
		<div style="margin-top:20px">
			<input type="submit" name="btn" value="GO"/>
		</div>

	</form>
<table width="100%" border="0" cellspacing="0" cellpadding="0" class="dataTable">
	<thead>
		<tr>
			<th>Name</th>
			<th>Position</th>
			<th>Points</th>
		</tr>
	</thead>
	<tbody>
	<?php
	$total_points = 0;
	foreach($stats as $n=>$m):
		$total_points += intval($m['points']);
	?>
	<tr>
		<td><?=$m['name']?></td>
		<td><?=$m['position']?></td>
		<td><?=$m['points']?></td>
	</tr>
	<?php endforeach;?>
	</tbody>
	<h3>TOTAL : <?=$total_points?> Points</h3>
</table>
</div>