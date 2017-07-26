<?php
$banners = $this->requestAction('/sponsors/banners/?slot='.$slot."&game_team_id=".$game_team_id);
?>

<?php if(sizeof($banners)>0):?>
<a href="javascript:banner_click(<?=$banners[0]['Banner']['id']?>,'<?=$banners[0]['Banner']['url']?>');">
	<img src="<?=$this->Html->url(Configure::read('avatar_web_url').$banners[0]['Banner']['banner_file'])?>" />
</a>
<script>
$(document).ready(function(){
	banner_view(<?=$banners[0]['Banner']['id']?>);
});

</script>
<?php else:?>
	<?php 
	if($slot=='MY_CLUB_LONG'):
	?>
	<a href="#" target="_blank"><img src="<?=$this->Html->url('/images/468x60_banner.png')?>" /></a>
	<?php else:?>
	<a href="#" target="_blank"><img src="<?=$this->Html->url('/images/250x250_banner.png')?>" /></a>
	<?php endif;?>
<?php endif;?>