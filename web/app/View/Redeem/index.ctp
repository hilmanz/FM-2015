<div id="leaderboardPage">
	 <?php echo $this->element('infobar'); ?>
    <div class="headbar tr">
        <div class="leaderboard-head">
        	<h3>Redeem Your Code</h3>
            <p>Masukkan kode voucher loe disini untuk ditukarkan dengan ss$</p>
        </div>
    </div><!-- end .headbar -->
   
    <div id="thecontent">
        <div class="contents tr">
        	<?php $flashMsg = $this->Session->flash();?>
        	<?php if(strlen($flashMsg) > 0):?>
        	<div class="message">
        		<?php echo $flashMsg;?>
        	</div>
        	<?php endif;?>
        	<form action="<?=$this->Html->url('/redeem')?>" 
        			method="post" 
        			enctype="application/x-www-form-urlencoded">
        		<div class="row">
        			<input type="text" name="kode" value="" placeholder="Masukkan kode loe"/>
        		</div>
        		<div class="row">
        			<?=$captcha_html?>
        		</div>
        		<div class="row">
        			<input type="submit" name="btn" value="KIRIM" class="button"/>
        		</div>
        	</form>
            
        </div><!-- end .content -->
    </div><!-- end #thecontent -->
</div><!-- end #leaderboardPage -->