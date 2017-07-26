<div>
<?php if($success==1):?>
the banner has been removed successfully !
<?php else:?>
cannot remove the banner, please try again later !
<?php endif;?>
</div>
<div>
<a href="<?=$this->Html->url('/banners')?>" class="button">Back to Banner Management</a>
</div>