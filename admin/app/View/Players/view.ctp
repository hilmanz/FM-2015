<div class="row">
	<a href="<?=$this->Html->url('/players/overall')?>" class="button">Back</a><a href="<?=$this->Html->url('/players/banned/'.$user['User']['id'])?>" class="button">Banned User</a>
</div>
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
			<td><strong>FBID/USERID</strong></td><td><?=h($user['User']['fb_id'])?> / <?=h($user['User']['id'])?></td><td><strong>Joined</strong></td>
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
			<td><strong>Total Coin</strong></td>
			<td>
				<?=number_format($cash)?>
				<a href="<?=$this->Html->url('/players/transaction/'.$user['User']['id'].'/'.$user['User']['fb_id'])?>">
					View Transaction
				</a>
			</td>
			<td><strong>Total Matches</strong></td><td><?=number_format($total_matches)?></td>
		</tr>
		<tr>
			<td><strong>Location</strong></td><td><?=h($user['User']['location'])?></td>
			<td></td><td></td><td></td><td></td>
		</tr>
	</tbody>
</table>

<h3 class="titles">Matches</h3>
<table width="100%" border="0" cellspacing="0" cellpadding="0" class="dataTable">
		<thead>
			<tr>
				<tr>
					<th>Match Day</th>
					<th>Against</th>
					<th>Points</th>
					<th>Earnings</th>
					<th>Action</th>
				</tr>
		</thead>
		<tbody>
			<?php
				foreach($previous_matches as $n=>$s):
					
			?>
				<tr>
					<td><?=$s['matchday']?></td>
					<td>
						<a href="#">
							<?=$s['against']?>
						</a>
					</td>
					<td><?=number_format(@$s['points'])?></td>
					<td>
						<?=number_format(@$s['ticket_sold'])?>
						<?php if($s['ticket_sold_penalty']>0):?>
							(<?=number_format(@$s['ticket_sold_penalty'])?>)
						<?php endif;?>
					</td>
					<td>
						<a href="<?=$this->Html->url('/players/get_match_detail/'.$user['User']['id'].'/'.$team_data['b']['id'].'/'.$s['game_id'].'/'.$s['matchday'])?>">
							Detail
						</a>
					</td>
				</tr>
				<?php
				endforeach;
				?>
		</tbody>
</table>

