<div class="theContainer">

<?php if($week > 0):?>
<h3 class="titles"><?=$data['player']['name']?> |  Week <?=intval($week)?> Statistics</h3>

<a href="<?=$this->Html->url('/players/playerweekly/?week='.$week)?>" class="button">BACK TO PREVIOUS PAGE</a>
<?php else: ?>
<h3 class="titles"><?=$data['player']['name']?> | Overall Statistics</h3>

<a href="<?=$this->Html->url('/players/playerstats')?>" class="button">BACK TO PREVIOUS PAGE</a>
<?php endif;?>
<?php
$games = 0;
$passing_and_attacking = 0;
$defending = 0;
$goalkeeping = 0;
$mistakes_and_errors = 0;
foreach($stats['games'] as $stats_name=>$val){
	$games+=$val['points'];
}
foreach($stats['passing_and_attacking'] as $stats_name=>$val){
	$passing_and_attacking+=$val['points'];
}
foreach($stats['defending'] as $stats_name=>$val){
	$defending+=$val['points'];
}
foreach($stats['goalkeeping'] as $stats_name=>$val){
	$goalkeeping+=$val['points'];
}
foreach($stats['mistakes_and_errors'] as $stats_name=>$val){
	$mistakes_and_errors+=$val['points'];
}
$cat['games'] = $games;
$cat['passing_and_attacking'] = $passing_and_attacking;
$cat['defending'] = $defending;
$cat['goalkeeping'] = $goalkeeping;
$cat['mistakes_and_errors'] = $mistakes_and_errors;

foreach($stats as $category=>$stat):
	if(($category=='goalkeeping' && $data['player']['position']=='Goalkeeper')
		|| ($category!='goalkeeping')
	  ):
?>
<table width="100%" border="0" cellspacing="0" cellpadding="0" class="dataTable">
		<thead>
			<tr>
				<tr>
					<th><?=ucfirst(str_replace("_"," ",$category))?> (<?=number_format($cat[$category])?>)</th>
					<th>Frequency</th>
					<th>Points</th>
				</tr>
		</thead>
		<tbody>
			<?php foreach($stat as $stats_name=>$val):?>
				<tr>
					<td>
						<?=ucfirst(str_replace("_"," ",$stats_name))?>
					</td>
					<td><?=number_format(@$val['total'])?></td>
					<td>
						<?=round($val['points']);?>
					</td>
				</tr>
			<?php endforeach;?>
		</tbody>
</table>
<?php endif;endforeach;?>