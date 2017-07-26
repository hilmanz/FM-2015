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
              </div><!-- end .col-contents -->
              <div class="contents tr">
                <?php $flashMsg = $this->Session->flash();?>
                <?php if(strlen($flashMsg) > 0):?>
                <div class="message">
                  <?php echo $flashMsg;?>
                </div>
                <?php endif;?>
                <form action="<?=$this->Html->url('/upgrade/redeem')?>" 
                    method="post" 
                    enctype="application/x-www-form-urlencoded">
                  <div class="row">
                    <input type="text" name="kode" value="" placeholder="Masukkan kode loe"/>
                  </div>
                  <div class="row">
                    <?=$captcha_html?>
                  </div>
                  <div class="row">
                    <input type="hidden" name="trxinfo" value="<?=$trxinfo?>"/>
                    <input type="submit" name="btn" value="KIRIM" class="button"/>
                  </div>
                </form>
                  
              </div><!-- end .content -->
          </div><!-- end .row-3 -->
        </div><!-- end .content -->
    </div><!-- end #thecontent -->
</div><!-- end #catalogPage -->