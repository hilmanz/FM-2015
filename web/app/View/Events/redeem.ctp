<?php if($offer_valid):?>

<div id="maintenancePage">
	<h1 class="yellow">Penawaran Khusus </h1>
	<p style="text-align:left;margin-left:200px;margin-right:200px;margin-bottom:20px;">
		<?=(nl2br($offer['email_body_plain']))?>
	</p>
	<p>
		Penawaran ini hanya berlaku sebelum tanggal <?=date("d/m/Y",strtotime($offer['expired_dt']))?>
	</p>
	<a class="backtohome button" href="<?=$this->Html->url('/events/confirm/1?osign='.$osign)?>">
		TERIMA PENAWARAN 
	</a>
	<a class="backtohome button" href="<?=$this->Html->url('/events/confirm/0?osign='.$osign)?>">
		TOLAK PENAWARAN
	</a>
</div><!-- end #logoutpage -->
<?php else:?>
<div id="maintenancePage">
	<?php if($can_apply):?>
		<h1 class="yellow">OFFER IS EXPIRED </h1>
		<h2>Penawaran ini hanya berlaku sebelum tanggal <?=date("d/m/Y",strtotime($offer['expired_dt']))?></h2>
	<?php else:?>
		<?php if(isset($tier_fault)):?>
		<h1 class="yellow">ACCESS DENIED !</h1>
		<h2>Maaf, penawaran ini hanya berlaku untuk tim yang berada di tier <?=$tier?></h2>
		<?php elseif(isset($wrong_offer)):?>
		<h1 class="yellow">WRONG OFFER !</h1>
		<h2>Penawaran ini tidak ditujukan kepada klab anda !</h2>
		<?php else:?>
		<h1 class="yellow">OFFER IS EXPIRED </h1>
		<h2>Lo sudah pernah menjawab penawaran ini.</h2>
		<?php endif;?>
	<?php endif;?>
	<a class="backtohome button" href="<?=$this->Html->url('/manage/club')?>">Kembali ke Klab Saya</a>

</div><!-- end #logoutpage -->
<?php endif;?>