<div class="titleBox">
	<h1>`<?=$sponsor['name']?>` Statistics</h1>
</div>
<div class="row-2">
<table width="100%">
	<tr>
		<td>Name</td><td><?=$banner['banner_name']?></td>
	</tr>
	<tr>
		<td>File</td><td><?=$banner['banner_file']?></td>
	</tr>
	<tr>
		<td>URL</td><td><?=$banner['url']?></td>
	</tr>
	<tr>
		<td>SLOT</td>
		<td>
			<?=$banner['slot']?>
		</td>
	</tr>
</table>
</div>
<div class="row-2">
<a href="<?=$this->Html->url('/sponsors/stats_detail/'.$sponsor['id'].'/'.$banner['id'])?>" class="button">Kembali</a>
</div>
<div class="row-2">
<h4>`<?=$location?>` Monthly Statistics</h4>

<table width="100%">
<tr><td>Month</td><td>Year</td><td>Impressions</td><td>Clicks</td></tr>
<?php foreach($overall_monthly as $stats):?>
<tr>
	<td><?=$stats['sponsor_banner_logs']['mt']?></td><td><?=$stats['sponsor_banner_logs']['yr']?></td>
	<td><?=number_format($stats[0]['views'])?></td><td><?=number_format($stats[0]['clicks'])?></td>
</tr>
<?php endforeach;?>
</table>
</div>