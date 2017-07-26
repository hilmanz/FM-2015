<?php
$browse_url = $this->Html->url('/merchandises/?cid=');
$subtitle = "";
if(isset($category_name)){
	$subtitle = "- ".$category_name;
}
?>
<div id="catalogPage">
      <div class="rowd">
     	 <?php echo $this->element('infobar'); ?>
      </div>
    <div id="thecontent">
        <div class="content pad20">
        	<div class="titlePage">
				<h1 class="red">
					Online Catalog <?=$subtitle?>
				</h1>
				<h4>Tukarkan coin elo dan dapatkan merchandise seru dari Supersoccer Football Manager</h4>
				<h4>Ongkos Kirim Ditanggung Pemenang</h4>
            </div>
            <div class="rowd">
				<div class="col-content fr tr widget">
					
					<?php
						$pic = Configure::read('avatar_web_url').
										"merchandise/thumbs/0_".
										$item['MerchandiseItem']['pic'];
						$buy_url = $this->Html->url('/merchandises/select/'.$item['MerchandiseItem']['id']);
					?>
					<div class="catalog-item">
						<div class="imagesCatalog tr widget">
							<?php if($item['MerchandiseItem']['available'] > 0):?>
							<a href="<?=$buy_url?>">
							<?php endif;?>
							  <img src="<?=$this->Html->url($pic)?>" />
							<?php if($item['MerchandiseItem']['available'] > 0):?>
							</a>
							<?php endif;?>
						</div>
					</div>
					<div class="detailCatalog" style="width:440px;">
							<h3 class="MerchandiseItemName"><?=h($item['MerchandiseItem']['name'])?></h3>
							<?php if($item['MerchandiseItem']['price_credit'] == 0):?>
							<p class="points red">Rp. <?=number_format($item['MerchandiseItem']['price_money'])?></p>
							<p>* Tidak bisa dibeli dengan Coins.</p>
							<?php else:?>
							<p class="price"><?=number_format($item['MerchandiseItem']['price_credit'])?> Coins</p>
							<?php if(intval($item['MerchandiseItem']['price_money']) > 0):?>
							<p class="idrprice">(or buy now for Rp. <?=number_format($item['MerchandiseItem']['price_money'])?>)</p>
							<?php else:?>
							<p>* hanya bisa dibeli dengan Coins.</p>
							<?php endif;?>
							<?php endif;?>
							<div>
							<?php if($item['MerchandiseItem']['available'] > 0):?>
							<?php
							if($can_update_formation):
							?>
								<?php
									if($item['MerchandiseItem']['is_pro_item'] == 1 && 
										$user_detail['User']['paid_member'] == 1 &&
										$user_detail['User']['paid_member_status'] == 0):
								?>
									<p class="price">
								    	Loe belum bayar bulanan, klik <a href="<?=$this->Html->url('/upgrade/paymonthly')?>">disini</a> untuk loe bayar bulanan
								    </p>
									
								<?php
									elseif($item['MerchandiseItem']['is_pro_item'] == 1 && 
										$user_detail['User']['paid_member'] == 0 &&
										$user_detail['User']['paid_member'] == 0):
								?>
									<p class="price">
										Item ini hanya untuk pro member, klik <a href="<?=$this->Html->url('/upgrade/member')?>">disini</a> untuk menjadi pro member
									</p>
								<?php else: ?>
									<a class="buyBtn button" href="<?=$buy_url?>">
										BUY
									</a>
								<?php endif; ?>
							<?php else:?>
							Maaf, Tidak dapat membeli ketika pertandingan sedang berlangsung.
							<?php endif;?>
							<?php else:?>
							SOLD OUT
							<?php endif;?>
							</div>
							<div class="desc">
								<?=$item['MerchandiseItem']['description']?>
							</div>
							
							
						</div>

					
				</div><!-- end .box4 -->
				<div class="box4 fr">
					<div class="widget tr catalog-categories">
						<form>
							<select name="cid" class="styled">
								<option value="0">Categories</option>
								<?php
								foreach($categories as $category):
									if($category['MerchandiseCategory']['id'] != Configure::read('ticket_category_id')):
								?>
								<option value="<?=$category['MerchandiseCategory']['id']?>">
									<?=h($category['MerchandiseCategory']['name'])?>
								</option>
								<?php
								endif;
								endforeach;
								?>
							</select>
						</form>
					</div><!-- end .widget -->
					<?php if(isset($sub_categories) && sizeof($sub_categories) > 0 ):?>
					<div class="widget tr catalog-list">
						<h2>Browse the Catalog</h2>
						<ul>
							<?php
								foreach($sub_categories as $subcat):
									$jump_url = $browse_url.$subcat['MerchandiseCategory']['id'];

							?>
							<li>
								<a href="<?=$jump_url?>">
									<?=h($subcat['MerchandiseCategory']['name'])?>
								</a>
							</li>
							<?php endforeach;?>
						</ul>
					</div><!-- end .widget -->
					<?php endif;?>
					<?php if(isset($orders) && sizeof($orders) > 0 ):?>
					<div class="widget tr catalog-list">
						<h2>Your Order(s) : </h2>
						<ul>
							<?php foreach($orders as $order):?>
							<li>
								
								<a href="#" title="<?=$order['MerchandiseOrder']['notes']?>">
									<?=h($order['MerchandiseOrder']['po_number'])?> 
									-
									<?php
									$status = array('Pending','Processing / On Delivery','Delivered','Closed',
													'Canceled');
									echo $status[$order['MerchandiseOrder']['n_status']];
									?>
								</a>
							</li>
							<?php endforeach;?>
						</ul>
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
</div><!-- end #catalogPage -->

<script>
var browse_url = "<?=$browse_url?>";
$("select[name=cid]").change(function(e){
	document.location = browse_url+''+parseInt($(this).val());
});
</script>