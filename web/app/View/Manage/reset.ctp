<?php
if($confirm==0):
?>
<p> Are you sure to restart from beginning ? these action will remove your account and also your club.</p>
<div>
<a href="<?=$this->Html->url('/manage/reset/?confirm=1')?>" class="button"> Remove Me</a>
<a href="<?=$this->Html->url('/manage/reset/?confirm=2')?>" class="button"> Cancel</a>
</div>
<?php 
else:
?>
Your acccount has been removed
<p>
<a href="<?=$this->Html->url('/')?>">Back to login menu</a>
</p>
<?php endif;?>