<h3>Push Logs</h3>
<div class="row">
	<form action="<?=$this->Html->url('/pushlogs/show_list')?>" method="get">
		Filter By : <select name="feedtype">
			<?php if(isset($types)): foreach($types as $type):?>
			
			<?php if($current == $type):?>
				<option value="<?=$type?>" selected='selected'><?=$type?></option>
			<?php else:?>
				<option value="<?=$type?>"><?=$type?></option>
			<?php endif;?>
			<?php endforeach; endif;?>
		</select>
		<input type="submit" name="btn" value="Go"/>
	</form>
</div>
<div id="logstable" class="row">
	<table width="100%">
		<tr>
			<td><?=$this->Paginator->sort('id')?></td>
			<td>Time</td>
			<td>Source</td>
			<td>Feed Type</td>
			<td>Saved File</td>
		</tr>
		<?php
		if(isset($data)): foreach($data as $d):
		?>
		<tr>
			<td><?=$d['Pushlogs']['id']?></td>
			<td><?=$d['Pushlogs']['push_date']?></td>
			<td><?=$d['Pushlogs']['productionServer']?></td>
			<td><?=$d['Pushlogs']['feedType']?></td>
			<td><?=$d['Pushlogs']['saved_file']?></td>
		</tr>
		<?php endforeach;endif;?>
	</table>
</div>
<div class="row">
<?php if(isset($data)):?>
<?php echo $this->Paginator->numbers();?>
<?php endif;?>
</div>
