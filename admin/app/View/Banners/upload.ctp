<div>
<?php if($success==1):?>
Upload completed.
<?php else:?>
Cannot upload the image, please try again later !
<?php endif;?>
</div>
<div>
<a href="<?=$this->Html->url('/banners')?>" class="button">Back to Banner Management</a>
</div>