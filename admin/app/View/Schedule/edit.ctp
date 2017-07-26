<div class="titleBox">
	<h1>Edit Schedule</h1>
</div>
<div class="theContainer">
<a href="<?=$this->Html->url('/schedule')?>" class="button">Kembali</a>
<table width="100%" border="0" cellspacing="0" cellpadding="0" class="dataTable">
	<thead>
		<tr>
			
			<th>Matchday</th>
			<th>Home</th>
			<th class="center"></th>
			<th>Away</th>
			
		</tr>
	</thead>
	<tbody>
		<tr>
			
			<td><?=$rs['Fixture']['matchday']?></td>
			<td><?=$rs['Home']['name']?></td>
			<td>VS</td>
			<td><?=$rs['Away']['name']?></td>
		</tr>
	</tbody>
</table>
<?php
$match_date = $rs['Fixture']['match_date'];
$arr = explode(" ",$match_date);
?>
<form action="<?=$this->Html->url('/schedule/edit/'.$rs['Fixture']['game_id'])?>" 
		method="post" 
	enctype="application/x-www-form-urlencoded">
<h4>Match Date : </h4>
<input type="text" name="dt" value="<?=$arr[0]?>" placeholder="YYYY-mm-dd"/>
<h4>Time : </h4>
<input type="text" name="tm" value="<?=$arr[1]?>" placeholder="HH:MM:SS"/>
<input type="submit" name="btn" value="Save"/>
<div style="margin-top:10px;">
<?php if($is_postponed==0):?>
<a href="<?=$this->Html->url('/schedule/postponed/'.$rs['Fixture']['game_id'].'/1')?>" class="button">POSTPONE</a>
<?php else: ?>
<a href="<?=$this->Html->url('/schedule/postponed/'.$rs['Fixture']['game_id'].'/0')?>" class="button">REACTIVATE</a>
<?php endif;?>
</div>
</form>
</div>