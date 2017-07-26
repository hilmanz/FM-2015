<?php
$pic = Configure::read('avatar_web_url').
				"merchandise/thumbs/0_".
				$item['pic'];
?>
<div id="catalogPage">
      <div class="rowd">
     	 <?php echo $this->element('infobar'); ?>
      </div>
    <div id="thecontent">
        <div class="content pad20">
        	<div class="titlePage">
				<h1 class="red">Order Status</h1>
            </div>
            <div class="rowd">
				<div class="col-content">
					<div class="tr widgets">
					
					</div><!-- end .widget -->
				</div><!-- end .col-content -->
				<div class="box4 fr">
					<div class="tr widgets order-detail">
						<h2>Your Order</h2>
						<h4><?=h($item['name'])?></h4>
						<p class="price">ss$<?=number_format($item['price_currency'])?></p>
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