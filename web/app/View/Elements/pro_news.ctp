<?php
//pastikan hanya paid member yang bisa mengakses halaman ini
if($user['paid_member']==1):
?>
<div class="widget">
    <p><a href="<?=$this->Html->url('/pages/injury-news')?>">Injury News</a></p>
    <p><a href="<?=$this->Html->url('/pages/top-player-news')?>">Top Player News</a></p>
</div>
<?php endif;?>