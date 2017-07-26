<div class="titleBox">
	<h1>Players</h1>
</div>
<div class="theContainer">

<form class="searchBox" action="<?=$this->Html->url('/players/search')?>" method="get" 
	enctype="application/x-www-form-urlencoded">
	<input type="text" name="q" value=""/><input type="submit" name="btn" value="Search"/>
</form>
	<div style="overflow:auto;">
	<table width="100%" border="0" cellspacing="0" cellpadding="0" class="dataTable">
		<thead>
			<tr>
				<th>No</th>
				<th></th>
				<th><?php echo $this->Paginator->sort('user_id','User');?></th>
				<th>Original Team</th>
				<th>Phone Number</th>
				<th>Joined</th>
				<th>Registration</th>
				<th>Team Name</th>
				<th><?php echo $this->Paginator->sort('rank','Rank');?></th>
				<th><?php echo $this->Paginator->sort('import','Import Player Counts');?></th>
				<th><?php echo $this->Paginator->sort('points','Points');?></th>
				<th><?php echo $this->Paginator->sort('money','Money');?></th>
				<th><?php echo $this->Paginator->sort('games','Games');?></th>
				<th><?php echo $this->Paginator->sort('passing_and_attacking','Passing and Attacking');?></th>
				<th><?php echo $this->Paginator->sort('defending','Defending');?></th>
				<th><?php echo $this->Paginator->sort('goalkeeping','Goalkeeping');?></th>
				<th><?php echo $this->Paginator->sort('mistakes_and_errors','Mistakes and Errors');?></th>
			</tr>
		</thead>
		<tbody>
	<?php

	foreach($rs as $n=>$m):
		
	?>
	<tr>
		<td><?=$m['no']?></td>
		<td>
			<?php if(strlen($m['User']['avatar_img'])>1):?>
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
		<td><?=h($m['MasterTeam']['name'])?></td>
		
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
		<td><?=number_format(@$m['ImportPlayerCounts'])?></td>
		
		<td><?=number_format(@$m['Point']['points'])?></td>
		<td><?=number_format(@$m['Money'])?></td>
		<td><?=number_format(@$m['Summary']['games'])?></td>
		<td><?=number_format(@$m['Summary']['passing_and_attacking'])?></td>
		<td><?=number_format(@$m['Summary']['defending'])?></td>
		<td><?=number_format(@$m['Summary']['goalkeeping'])?></td>
		<td><?=number_format(@$m['Summary']['mistakes_and_errors'])?></td>
	</tr>
	<?php endforeach;?>
		</tbody>
</table>
	</div>
</div>
<div class="paging">
<?php echo $this->Paginator->numbers();?>
</div>