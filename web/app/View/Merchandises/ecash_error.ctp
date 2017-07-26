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
				<h1 class="yellow">Online Catalog</h1>
				<h4>Proses Pembayaran</h4>
            </div>
            <div class="rowd">
				<div class="col-contents">
					<div class="tr widgets">
						<h3>Saat ini tidak bisa terhubung dengan ecash mandiri, silahkan coba beberapa saat lagi
						</h3>
					</div>
				</div><!-- end .col-contents -->
            </div><!-- end .row-3 -->
        </div><!-- end .content -->
    </div><!-- end #thecontent -->
</div><!-- end #catalogPage -->