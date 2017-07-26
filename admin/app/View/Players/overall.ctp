<?php
$frames = array(
		'user_id'=>'profile',
		'rank'=>'summary',
		'import'=>'summary',
		'points'=>'summary',
		'money'=>'summary',
		'games'=>'stats',
		'passing_and_attacking'=>'stats',
		'defending'=>'stats',
		'goalkeeping'=>'stats',
		'mistakes_and_errors'=>'stats',
	);
?>
<div class="titleBox">
	<h1>Players</h1>
</div>
<div class="theContainer">
	<h4>Total Players : <?=number_format($total_users)?></h4>
	<form class="searchBox fl" action="<?=$this->Html->url('/players/search')?>" method="get" 
		enctype="application/x-www-form-urlencoded">
		<input type="text" name="q" value=""/><input type="submit" name="btn" value="Search"/>
	</form>
	<div class="buttonShorter">
		<a href="#profile" class="button btntoggle">Profile</a><a href="#summary" class="button btntoggle">Summary</a><a href="#stats" class="button btntoggle">Stats</a>
		<a href="<?=$this->Html->url('/players/banned_list')?>" class="button btntoggle">Banned List</a>
	</div>
	<div style="overflow:auto;" class="tableContainer">
	<table width="100%" border="0" cellspacing="0" cellpadding="0" class="dataTable">
		<thead>
			<tr>
				<th>No</th>
				<th></th>
				<th><?php echo $this->Paginator->sort('user_id','User');?></th>
				<th class="t-profile">Original Team</th>
				<th class="t-profile">Phone Number</th>
				<th class="t-profile">Joined</th>
				<th class="t-profile">Registration</th>
				<th class="t-profile">Team Name</th>
				<th class="t-summary"><?php echo $this->Paginator->sort('rank','Rank');?></th>
				<th class="t-summary"><?php echo $this->Paginator->sort('import','Import Player Counts');?></th>
				<th class="t-summary"><?php echo $this->Paginator->sort('points','Points');?></th>
				<th class="t-summary"><?php echo $this->Paginator->sort('money','Money');?></th>
				<th class="t-stats"><?php echo $this->Paginator->sort('games','Games');?></th>
				<th class="t-stats"><?php echo $this->Paginator->sort('passing_and_attacking','Passing and Attacking');?></th>
				<th class="t-stats"><?php echo $this->Paginator->sort('defending','Defending');?></th>
				<th class="t-stats"><?php echo $this->Paginator->sort('goalkeeping','Goalkeeping');?></th>
				<th class="t-stats"><?php echo $this->Paginator->sort('mistakes_and_errors','Mistakes and Errors');?></th>
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
		<td class="t-profile"><?=h($m['MasterTeam']['name'])?></td>
		
		<td class="t-profile"><?=h($m['User']['phone_number'])?></td>
		<td class="t-profile"><?=h($m['User']['register_date'])?></td>
		<td class="t-profile">
			<?php if($m['User']['register_completed']==1):?>
				Completed
			<?php else:?>
				In Progress
			<?php endif;?>
		</td>
		<td class="t-profile">
			<a href="<?=$this->Html->url('/players/view/'.$m['User']['id'])?>">
				<?=h($m['Team']['team_name'])?>
			</a>
		</td>
		<td class="t-summary"><?=number_format(@$m['Point']['rank'])?></td>
		<td class="t-summary"><?=number_format(@$m['ImportPlayerCounts'])?></td>
		
		<td class="t-summary"><?=number_format(@$m['Point']['points'])?></td>
		<td class="t-summary"><?=number_format(@$m['Money'])?></td>
		<td class="t-stats"><?=number_format(@$m['Summary']['games'])?></td>
		<td class="t-stats"><?=number_format(@$m['Summary']['passing_and_attacking'])?></td>
		<td class="t-stats"><?=number_format(@$m['Summary']['defending'])?></td>
		<td class="t-stats"><?=number_format(@$m['Summary']['goalkeeping'])?></td>
		<td class="t-stats"><?=number_format(@$m['Summary']['mistakes_and_errors'])?></td>
	</tr>
	<?php endforeach;?>
		</tbody>
</table>
	</div>
</div>
<div class="paging">
<?php echo $this->Paginator->numbers();?>
</div>
<script>

$(".t-summary").hide();
$(".t-stats").hide();
$(".t-profile").hide();
<?php
if(isset($sort)):
?>
$(".t-<?=$frames[$sort]?>").show();
<?php else:?>
$(".t-profile").show();
<?php endif;?>

$(".btntoggle").click(function(e){
	var id = $(this).attr('href').split('#').join('');
	$(".t-summary").hide();
	$(".t-stats").hide();
	$(".t-profile").hide();
	$(".t-"+id).fadeIn();
});
</script>