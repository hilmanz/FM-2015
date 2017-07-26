<div class="titleBox">
	<h1>Administrators</h1>
</div>
<div class="theContainer">
	
	<table width="100%" border="0" cellspacing="0" cellpadding="0" class="dataTable">
		<thead>
			<tr>
				<th width="1">No</th>
				
				<th>User</th>
				<th>Action</th>
				
			</tr>
		</thead>
		<tbody>
		<?php
		foreach($rs as $n=>$m):
		?>
		<tr>
			<td><?=$n+1?></td>
			<td>
				<?=h($m['Admin']['username'])?>
			</td>
			<td>
				<a href="<?=$this->Html->url('/dashboard/change_password/'.$m['Admin']['id'])?>">
					Edit
				</a>
			</td>
		</tr>
		<?php endforeach;?>
	</table>
	</tbody>
</table>
</div>
<div class="paging">
<?php echo $this->Paginator->numbers();?>
</div>