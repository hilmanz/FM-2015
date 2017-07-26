<div class="titleBox">
	<h1>Matchday Setup</h1>
</div>
<div class="theContainer">
<table width="100%" border="0" cellspacing="0" cellpadding="0" class="dataTable">
	<thead>
		<tr>
			
			<th>Matchday</th>
			<th title="the time when the formation setup should be closed.">Start Time *</th>
			<th title="the time when the formation setup is opened.">End Time **</th>
			
			<th class="center">Action</th>
		</tr>
	</thead>
	<tbody>
	<?php
	foreach($match as $n=>$m):
	?>
	<tr>
		
		<td><?=h($m['MasterMatchday']['matchday'])?></td>
		<td title="the time when the formation setup should be closed."><?=date("d-m-Y H:i:s",strtotime($m['MasterMatchday']['start_dt']))?></td>
		<td title="the time when the formation setup is opened."><?=date("d-m-Y H:i:s",strtotime($m['MasterMatchday']['end_dt']))?></td>
		<td class="center">
			<a href="<?=$this->Html->url('/schedule/edit_matchday/'.h($m['MasterMatchday']['id']))?>" class="button">Edit</a>
		</td>
	</tr>
	<?php endforeach;?>
	</tbody>
</table>
<p>
* ) the time when the formation setup should be closed.
* ) the time when the formation setup opened.
</p>
</div>