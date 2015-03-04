<?php
if(isset($item)){
$pic = Configure::read('avatar_web_url').
				"merchandise/thumbs/0_".
				$item['pic'];	
}

?>
<div id="catalogPage">
      <div class="rowd">
     	 <?php echo $this->element('infobar'); ?>
      </div>
  
    <div id="thecontent">
        <div class="content pad20">
        	<div class="titlePage">
				<h1 class="yellow">PRO LEAGUE SUBSCRIPTION</h1>
				<h4>Proses Pembayaran</h4>
            </div>
            <div class="rowd">
				<div class="col-contents">
					<div class="tr widgets">
						<h1>Kode Transaksi : <?=$transaction_id?></h1>
						<h3>Loe akan diteruskan ke halaman pembayaran, silahkan klik tombol dibawah untuk melakukan pembayaran
						</h3>
					</div>
					
					<div class="tr widgets">
						<form method="post" action="<?=$doku_api?>" enctype="application/x-www-form-urlencoded">
				            <?php
				            foreach ($order_doku as $key => $value) {
				                echo '<input type="hidden" name="'.$key.'" value="'.$value.'" />';
				            }
				            ?>
				            <div class="popup-button">
				                <input class="button" type="submit" value="Bayar">
				            </div>
				        </form>
					</div><!-- end .widget -->
				</div><!-- end .col-contents -->
            </div><!-- end .row-3 -->
        </div><!-- end .content -->
    </div><!-- end #thecontent -->
</div><!-- end #catalogPage -->