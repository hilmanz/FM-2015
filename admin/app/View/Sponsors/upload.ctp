<div>
<?php if($success==1):?>
Upload completed.
<?php else:?>
Cannot upload the image, please try again later !
<?php endif;?>
</div>
<div>
<a href="<?=$this->Html->url('/sponsors/edit/'.$sponsor_id)?>" class="button">Back to Previous Page</a>
</div>