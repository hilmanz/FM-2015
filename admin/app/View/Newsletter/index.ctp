<div class="titleBox">
	<h1>Newsletter</h1>
</div>
<div class="row">
	<?php
	echo $this->Html->link('Create Newsletter',
					  array('controller'=>'newsletter',
					  		'action'=>'create'),
					  array('class'=>'button'));
	?>
</div>
<div class="theContainer">
	<h3 class="titles">The list of created newsletter</h3>


	<table width="100%" border="0" cellspacing="0" cellpadding="0" class="dataTable">
		<thead>
			<?php
				echo $this->Html->tableHeaders(
								array(
									'ID','Date','Subject','Last Send','Action'
								)
				);
			?>
		</thead>
		<tbody>
		<?php
		if(isset($data)){
			foreach($data as $d){
				echo $this->Html->tableCells(
							array(
								$d['Newsletter']['id'],
								$d['Newsletter']['created_dt'],
								$d['Newsletter']['subject'],
								$d['Newsletter']['last_send'],
								$this->Html->link('Edit',
													array('controller'=>"newsletter",
														  'action'=>'edit',
														  intval($d['Newsletter']['id'])),
													array('class'=>'button'))
								."&nbsp;".
								$this->Html->link('Sending',
													array('controller'=>"newsletter",
														  'action'=>'sending',
														  intval($d['Newsletter']['id'])),
													array('class'=>'button'))

							)
						);
			}
		}
		?>
		</tbody>
	</table>
</div>
<div class="paging">
<?php if(isset($data)):?>
<?php echo $this->Paginator->numbers();?>
<?php endif;?>
</div>
