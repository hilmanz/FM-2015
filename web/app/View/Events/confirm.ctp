
<div id="maintenancePage">
	<?php if($success):?>
		<h1 class="yellow">SUCCESS !</h1>
		<?php
		if(isset($success_message)):
		?>
		<h2><?=$success_message?></h2>
		<?php else:?>
		<h2>Terima kasih atas kerjasama anda !</h2>
		<?php endif;?>
	<?php else:?>
		<h1 class="yellow">GAGAL !</h1>
		<?php if(@$event_type==3):?>
		<h2>Lo gak bisa membeli pemain ini karena pemain ini sudah ada di tim lo.</h2>
		<?php elseif(@$event_type==4):?>
		<h2>Lo gak bisa menjual pemain ini karena pemain ini tidak ada di tim lo.</h2>
		<?php elseif(!$can_spend):?>
		<h2>Maaf, budget lo gak cukup untuk melakukan transaksi ini !</h2>
		<?php else:?>
		<h2>Mohon maaf, kami tidak berhasil memproses permintaan anda !</h2>
		<?php endif;?>
	<?php endif;?>
	<a class="backtohome button" href="<?=$this->Html->url('/manage/club')?>">Kembali ke Klab Saya</a>

</div><!-- end #logoutpage -->
