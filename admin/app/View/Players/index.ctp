<div class="titleBox">
	<h1>Players</h1>
</div>
<div class="theContainer">
	<h4>Total Players : <?=number_format($total_users)?></h4>
	<table width="100%" border="0" cellspacing="0" cellpadding="0" class="dataTable">
		<thead>
			<tr>
				<th width="1">No</th>
				<th></th>
				<th>User</th>
				<th>Phone Number</th>
				<th>Joined</th>
				<th>Registration</th>
				<th>Team Name</th>
				<th class="center">Rank</th>
				<th class="center">Points</th>
			</tr>
		</thead>
		<tbody>
		<?php
		foreach($rs as $n=>$m):
		?>
		<tr>
			<td><?=$n+1?></td>
			<td>
				<?php if($m['User']['avatar_img']!=null):?>
					<img width="50"
						src="<?=Configure::read('AVATAR_URL').'120x120_'.$m['User']['avatar_img']?>"/>
				<?php else:?>
					<img src="http://graph.facebook.com/<?=$m['User']['fb_id']?>/picture"/>
				<?php endif;?>
			</td>
			
			<td><a href="<?=$this->Html->url('/players/view/'.$m['User']['id'])?>">
				<?=h($m['User']['name'])?><br/>
				<?=h($m['User']['fb_id'])?><br/>
				<?=h($m['User']['email'])?>
				</a>
			</td>
			
			
			<td><?=h($m['User']['phone_number'])?></td>
			<td><?=h($m['User']['register_date'])?></td>
			<td>
				<?php if($m['User']['register_completed']==1):?>
					Completed
				<?php else:?>
					In Progress
				<?php endif;?>
			</td>
			<td>
				<a href="<?=$this->Html->url('/players/view/'.$m['User']['id'])?>">
					<?=h($m['Team']['team_name'])?>
				</a>
			</td>
			<td><?=number_format(@$m['Point']['rank'])?></td>
			<td><?=number_format(@$m['Point']['points'])?></td>
		</tr>
		<?php endforeach;?>
	</table>
	</tbody>
</table>
</div>
<div class="paging">
<?php echo $this->Paginator->numbers();?>
</div>