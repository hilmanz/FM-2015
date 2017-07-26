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
	<h3 class="titles">Sending Newsletter</h3>
	<form action="<?=$this->Html->url('/newsletter/sending/'.$rs['Newsletter']['id'].'/2')?>">
	<div class="content">
		<h3><?=h($rs['Newsletter']['subject'])?></h3>
		<div class="email-body">
			<?=$rs['Newsletter']['content']?>
		</div>
	</div>
	<table width="100%" class="table">
		<tr>
			<td>
				<select name="recipient_type">
					<option value="1">
						Everyone
					</option>
					<option value="1">
						By Original Team
					</option>
				</select>
			</td>
		</tr>
		<tr>
			<td>
				<input type="submit" name="btn" value="Next"/>
			</td>
		</tr>
	</table>
</div>

<?php
echo $this->element('misc');
?>