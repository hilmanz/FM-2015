<div id="leaderboardPage">
	 <?php echo $this->element('infobar'); ?>
    <div class="headbar tr">
        <div class="leaderboard-head">
        	<h3>Tidak boleh Redeem Code</h3>
           
        </div>
    </div><!-- end .headbar -->
    <div id="thecontent">
        <div class="contents tr">
            <p>Loe sudah salah <?=Configure::read('REDEEM_MAXIMUM_TRY')?>x memasukkan kode. Silahkan coba lagi besok !</p>
        </div><!-- end .content -->
    </div><!-- end #thecontent -->
</div><!-- end #leaderboardPage -->