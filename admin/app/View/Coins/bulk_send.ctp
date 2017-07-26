<?php if($transfer_ok):?>
<h3>Fund has been transfered successfully !</h3>
<?php else:?>
<h3>Transfer Failed. Please try again later !</h3>
<?php endif;?>
<a href="<?=$this->Html->url('/coins/add')?>">CONTINUE</a>