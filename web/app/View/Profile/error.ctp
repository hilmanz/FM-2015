<div class="boxMessages">
	<?php echo $this->Session->flash();?>
	<?php
		if(@$error_type=='team'):
	?>
	<a href="<?=$this->Html->url('/profile/register_team')?>" class="button">Kembali ke halaman sebelumnya</a>
	<?php 
		elseif(isset($attempt)):
	?>
	<a href="<?=$this->Html->url('/profile')?>" class="button">Kembali ke halaman sebelumnya</a>
	<?php else:?>
	<a href="<?=$this->Html->url('/')?>" class="button">Kembali ke halaman utama</a>
	<?php endif;?>
</div>