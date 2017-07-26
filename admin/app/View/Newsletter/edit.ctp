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
	<h3 class="titles">Edit Newsletter</h3>
	<?php 
		echo $this->Form->create('Newsletter');
		echo $this->Form->input('subject',array('value'=>$rs['subject']));
		echo $this->Form->input('content',array('value'=>$rs['content'],
													'class'=>'wysiwyg','width'=>'900px'));
	?>
	<div class="row">
		<?php
			echo $this->Form->end('Update');
		?>
	</div>
</div>

<?php
echo $this->element('misc');
?>