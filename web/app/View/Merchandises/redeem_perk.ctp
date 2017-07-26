<?php
$pic = Configure::read('avatar_web_url').
				"merchandise/thumbs/0_".
				$item['pic'];
?>
<?php
$can_update_formation = true;

if(time() > $close_time['ts'] && Configure::read('debug') == 0){
    
    $can_update_formation = false;
    if(time() > $open_time){
       
        $can_update_formation = true;
    }
}else{
    if(time() < $open_time){
       
        $can_update_formation = false;
    }
}

?>
<div id="catalogPage">
      <div class="rowd">
     	 <?php echo $this->element('infobar'); ?>
      </div>
    <div id="thecontent">
        <div class="content pad20">
        	<div class="titlePage">
				<h1 class="red">Online Catalog</h1>
				<?php 
				if($can_update_formation):
				?>
				<h4><?=h($item['name'])?> akan di-redeem menggunakan coin kamu. Lanjutkan ?</h4>
				<?php else:?>
				<h4>
					<?=h($item['name'])?> tidak dapat dibeli selama pertandingan sedang berlangsung !
				</h4>
				<?php endif;?>
            </div>
            <div class="rowd">
				<div class="col-content">
					<div class="tr widgets">
						<?php
						if($can_update_formation):
						?>
					<form class="shipaddress" 
						action="<?=$this->Html->url('/merchandises/order')?>" 
						method="post" 
						enctype="application/x-www-form-urlencoded">
					
						<div class="row">
							<input type="hidden" name="ct" value="<?=$csrf_token?>"/>
							<input type="button" value="Cancel" class="button" onclick="cancel();"/>
							<input type="submit" value="Confirm" class="button"/>
						</div><!-- end .row -->
					</form>
					<?php else:?>
						<a href="<?=$this->Html->url('/merchandises')?>" class="button2"><span class="ico icon-undo-2">&nbsp;</span>  Kembali ke Online Catalog</a>
					<?php endif;?>
					</div><!-- end .widget -->
				</div><!-- end .col-content -->
				<div class="box4 fr">
					<div class="tr widgets order-detail">
						<h2>Your Order</h2>
						<h4><?=h($item['name'])?></h4>
						<p class="price"><?=number_format($item['price_credit'])?> Coins</p>
						<div class="imagesCatalog tr widget">
							  <img src="<?=$pic?>" />
						</div>
					</div><!-- end .widget -->
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
</div><!-- end #catalogPage -->

<script>
function cancel(){
	document.location="<?=$this->Html->url('/merchandises')?>";
}
</script>