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
    <?php if($is_transaction_ok):?>
    <div id="thecontent">
        <div class="content pad20">
        	<div class="titlePage">
				<h1 class="red">Online Catalog</h1>
				<h4>Order Complete!</h4>
            </div>
            <div class="rowd">
				<div class="col-content">
					<div class="tr widgets">
						<h1>Your order has been successfully processed.</h1>
						<h3>We've also
deducted the balance from your Supersoccer Football manager
funds.</br> Thanks for your purchase!</h3>
						<p class="mt20"><a class="button2" href="<?=$this->Html->url('/merchandises/history')?>"><span class="ico icon-undo-2">&nbsp;</span> Back to the Game</a></p>
					</div><!-- end .widget -->
				</div><!-- end .col-content -->
				<div class="box4 fr">
					<?php if(isset($item)):?>
					<div class="tr widgets order-detail">
						<h2>Your Order</h2>
						<h4><?=h($item['name'])?></h4>
						<p class="price"><?=number_format($item['price_credit'])?> Coins</p>
						<div class="imagesCatalog tr widget">
							  <img src="<?=$pic?>" />
						</div>
					</div><!-- end .widget -->
					<?php endif;?>
				</div><!-- end .box4 -->
            </div><!-- end .row-3 -->
        </div><!-- end .content -->
    </div><!-- end #thecontent -->
	<?php else:?>
	<div id="thecontent">
        <div class="content pad20">
        	<div class="titlePage">
				<h1 class="red">Online Catalog</h1>
				<h4>Order Failed!</h4>
            </div>
            <div class="rowd">
				<div class="col-content">
					<div class="tr widgets">
						<h1>Pesanan loe tidak dapat di proses</h1>
						<?php if(isset($apply_digital_perk_error) && $apply_digital_perk_error == 1):?>
						<h3>
							Maaf, Perk ini sedang aktif di klub loe, perk ini hanya bisa loe beli setelah masa berlakunya telah habis !
						</h3>
						<?php elseif(isset($apply_digital_perk_error) && $apply_digital_perk_error == 2):?>
						<h3>
							Maaf, Perk ini tidak dapat lo beli saat ini. Silahkan coba lagi nanti !
						</h3>
						
						<?php elseif($no_fund):?>
						<h3>Coins loe gak cukup untuk melakukan transaksi ini.</h3>
						<?php else:?>
						<h3>Maaf, transaksi loe tidak dapat diproses. Silahkan coba lagi nanti !</h3>
						<?php endif;?>
						<p class="mt20"><a class="button2" href="<?=$this->Html->url('/merchandises/history')?>"><span class="ico icon-undo-2">&nbsp;</span> Back to the Game</a></p>
					</div><!-- end .widget -->
				</div><!-- end .col-content -->
				<div class="box4 fr">
					<?php if(isset($item)):?>
					<div class="tr widgets order-detail">
						<h2>Your Order</h2>
						<h4><?=h($item['name'])?></h4>
						<p class="price"><?=number_format($item['price_credit'])?> Coins</p>
						<div class="imagesCatalog tr widget">
							  <img src="<?=$pic?>" />
						</div>
					</div><!-- end .widget -->
					<?php endif;?>
                    <?php for($i=0;$i<sizeof($sidebar_banner);$i++):?>
			        	<div class="banner300x250">
						     <a href="javascript:banner_click(<?=$sidebar_banner[$i]['Banners']['id']?>,'<?=$sidebar_banner[$i]['Banners']['url']?>');" target="_blank">
			                    <img src="<?=$this->Html->url(Configure::read('avatar_web_url').
			                                $sidebar_banner[$i]['Banners']['banner_file'])?>" />
			                </a>
			            </div>
		            <?php endfor;?>
				</div><!-- end .box4 -->
            </div><!-- end .row-3 -->
        </div><!-- end .content -->
    </div><!-- end #thecontent -->
	<?php endif;?>
</div><!-- end #catalogPage -->