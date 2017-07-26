<div class="theContainer">
<h3 class="titles">Player Info</h3>
<table width="100%" border="0" cellspacing="0" cellpadding="0" class="dataTable">
		<tbody>
			<tr>
				<td><strong>Name</strong></td><td><?=h($user['User']['name'])?></td>
				<td><strong>Team</strong></td><td><?=h($user['Team']['team_name'])?> (<?=$team_data['c']['name']?>)</td>
				<td><strong>Budget</strong></td><td>SS$ <?=number_format($budget)?></td>
			</tr>
			<tr>
				<td><strong>FBID</strong></td><td><?=h($user['User']['fb_id'])?></td><td><strong>Joined</strong></td>
				<td><?=date("d-m-Y H:i:s",strtotime($user['User']['register_date']))?></td>
				<td><strong>Points</strong></td><td><?=@number_format($point['points'])?></td>
			</tr>
			<tr>
				<td><strong>Email</strong></td><td><?=h($user['User']['email'])?></td>
				<td><strong>Reg.Status</strong></td>
				<td>
					<?php if($user['User']['register_completed']==1):?>
						Completed
					<?php else:?>
						In Progress
					<?php endif;?>
				</td>
				<td><strong>Rank</strong></td><td><?=@number_format($point['rank'])?></td>
			</tr>
			<tr>
				<td><strong>Mobile</strong></td><td><?=h($user['User']['phone_number'])?></td>
				<td></td><td></td>
				<td><strong>Total Matches</strong></td><td><?=number_format($total_matches)?></td>
			</tr>
			<tr>
				<td><strong>Location</strong></td><td><?=h($user['User']['location'])?></td>
				<td></td><td></td><td></td><td></td>
			</tr>
		</tbody>
</table>
<a href="<?=$this->Html->url('/players/view/'.$user['User']['id'])?>" class="button">BACK TO PREVIOUS PAGE</a>
<h3 class="titles">Vs. <?=$match_detail['against']?> (<?=ceil($match_detail['points'])?>)</h3>
<?php foreach($match_detail['lineups'] as $lineup):?>
	<h3 class="titles">
		<?=h($lineup['name'])?>
		<?php
			if($lineup['position_no']<=11):
		?>
			(Starter)
		<?php else:?>
			(Subs)
		<?php endif;?>
	</h3>
	<?php foreach($lineup['stats'] as $category=>$stats):?>
<table width="100%" border="0" cellspacing="0" cellpadding="0" class="dataTable">
		<thead>
			<tr>
				<tr>
					<th><?=$category?></th>
					<th>Frequency</th>
					<th>Points</th>
				</tr>
		</thead>
		<tbody>
			<?php
				foreach($stats as $n=>$s):

			?>
				<tr>
					<td>
						<?=$s['stats_name']?>
					</td>
					<td><?=number_format(@$s['stats_value'])?></td>
					<td>
						<?=round($s['points']);?>
					</td>
				</tr>
				<?php
				endforeach;
				?>
		</tbody>
</table>
<?php endforeach;?>
<?php endforeach;?>