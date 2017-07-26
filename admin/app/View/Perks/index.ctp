<h3>
	Game Perks
</h3>
<div class="row">
<a href="<?=$this->Html->url('/perks/create')?>" class="button">
	Create New Perk
</a>
</div>
<div class="row">
	<table width="100%" class="table">
	<?php
		echo $this->Html->tableHeaders(array('ID', 'Category','Name', 'Action'));
	?>
	<?php
	$tblArray = array();
	for($i=0;$i<sizeof($data);$i++){
		
		$tblArray[] = array(
			$data[$i]['MasterPerk']['id'],
			h($data[$i]['MasterPerk']['perk_name']),
			h($data[$i]['MasterPerk']['name']),
			'<a href="'.$this->Html->url('/perks/view/'.$data[$i]['MasterPerk']['id']).'">View</a>'
		);
	}
	echo $this->Html->tableCells($tblArray);
	?>
	</table>
</div>
<?php if(isset($data)):?>
<?php echo $this->Paginator->numbers();?>
<?php endif;?>