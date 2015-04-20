

<div id="catalogPage">
      <div class="rowd">
     	 <?php echo $this->element('infobar'); ?>
      </div>
    <div id="thecontent">
        <div class="content pad20">
        	<div class="titlePage">
        		<?php 
        		$msg = $this->Session->flash();
        		if(strlen($msg) > 0):
        		?>
        		<div class="error">
        			<?php echo $msg;?>
        		</div>
        		<?php endif;?>
				<h1 class="red">Online Catalog</h1>
				<h4>Where shall we ship your merchandise?</h4>
            </div>
            <div class="rowd">
				<div class="col-content">
					<div class="tr widgets">
			
						<table width="100%" border="0" cellspacing="0" cellpadding="0" class="theTable footable">
							<thead>
		                    <tr>
		                        <th></th>
		                        <th>Item</th>
		                        <th>Harga Satuan</th>
		                        <th>Jumlah</th>
		                        <th>Total</th>
		                      
		                    </tr>
                            </thead>
                            <tbody>
							<?php
							$total_price = 0;
		                    $total_coins = 0;
		                    $kg = 0;
							for($i=0;$i<sizeof($shopping_cart);$i++):
		                        $item = $shopping_cart[$i]['data']['MerchandiseItem'];
		                    	$kg  += $item['weight'] * $shopping_cart[$i]['qty'];
		                    	$coins = 0;
		                    	$price = 0;
		                    	$pic = Configure::read('avatar_web_url').
										"merchandise/thumbs/0_".
										$item['pic'];
		                    	
		                    ?>
		                    <tr class="tr-<?=intval($item['id'])?>">
		                        <td>
		                        	<img src="<?=$pic?>" width="100px"/>
		                        </td>
		                        <td width="120"> #<?=h($item['id'])?> -
		                            <?=h($item['name'])?>
		                        </td>
		                        <td>
		                            <?php if($item['price_money']>0):?>
		                            <p class="price">   
		                                Rp. <?=number_format(intval($item['price_money']))?> 
		                            </p> 
		                            <?php endif;?>
		                            <?php if($item['price_credit']>0):?>
		                            <p class="price">   
		                                <?php if($item['price_money'] > 0):?>
		                                    (<?=number_format(intval($item['price_credit']))?> Coins)
		                                <?php else:?>
		                                    <?=number_format(intval($item['price_credit']))?> Coins
		                                <?php endif;?>

		                            </p> 
		                            <?php endif;?>
		                            
		                        </td>
		                        <td>
		                            <?=(intval($shopping_cart[$i]['qty']))?>
		                            <?php
		                            	$coins = intval($shopping_cart[$i]['qty']) * 
		                            						intval($item['price_credit']);
		                            	$total_coins += $coins;
		                            	$price = intval($shopping_cart[$i]['qty']) * 
		                            						intval($item['price_money']);

		                            	$total_price += $price;
		                            ?>
		                        </td>
		                        <td>
		                        	<?php if($price > 0):?>
		                            Rp. <span class="prices price-<?=$item['id']?>">
		                                <?=number_format(intval($price))?>
		                            </span>
		                        	<?php endif;?>
		                            <?php if($item['price_credit']>0):?>
		                                <?php if($item['price_money']>0):?>
		                                    (<span class="coins coin-<?=$item['id']?>">
		                                        <?=number_format(intval($coins))?>
		                                    </span> Coins)
		                                <?php else:?>
		                                    <span class="coins coin-<?=$item['id']?>">
		                                        <?=number_format(intval($coins))?>
		                                    </span> Coins
		                                <?php endif;?>
		                            <?php endif;?>
		                           
		                        </td>
		                        
		                    </tr>
		                    <?php endfor;?>
		                    <tr>
		                    	<td></td>
		                    	<td colspan="3">Ongkos Kirim</td>
		                    	<td>
		                    		<?php
		                    		$ongkos = 0;
		                    		if($enable_ongkir)
		                    		{
		                    			foreach($ongkir as $cost){
			                    			if($cost['Ongkir']['id']==$city_id){
			                    				$ongkos = $cost['Ongkir']['cost'];
			                    				break;
			                    			}
			                    		}
		                    		}
		                    		$total_ongkos = ceil($kg) * intval($ongkos);
		                    		?>
		                    		<span class="shipping">Rp. <?=number_format($total_ongkos)?></span>
		                    	</td>
		                    </tr>
		                    <tr class="rowtotal">
		                    	<td></td>
		                        <td colspan="3" align="right">Belanja Total</td>
		                        <td>
		                            <span class="total-price">
		                            	
		                            	<?php if($total_price > 0):?>
		                            		Rp. <?=number_format(intval($total_price)+floatval($total_ongkos))?>
		                                    (<?=number_format(intval($total_coins))?> Coins)
		                                <?php else:?>
		                                    <?=number_format(intval($total_coins))?> Coins
		                                <?php endif;?>
		                            </span>
		                        </td>
		                    </tr>
                            </tbody>
		                </table>
 
					</div><!-- end .widget -->
					<div class="tr widgets">
					<form class="shipaddress" 
						action="<?=$this->Html->url('/merchandises/order')?>" 
						method="post" 
						enctype="application/x-www-form-urlencoded">
						<h3>Personal Details</h3>
						<div class="row">
							<label>First Name</label>
							<input type="text" name="first_name" value="<?=h($first_name)?>"/>
						</div><!-- end .row -->
						<div class="row">
							<label>Last Name</label>
							<input type="text" name="last_name" value="<?=h($last_name)?>"/>
						</div><!-- end .row -->
						<div class="row">
							<label>Mobile Phone</label>
							<input type="text" name="phone" value="<?=h($phone_number)?>"/>
						</div><!-- end .row -->
						<div class="row">
							<label>Email</label>
							<input type="text" name="email" value="<?=h($email)?>"/>
						</div><!-- end .row -->
						<h3>Shipping Address</h3>
						<div class="row">
							<label>Street</label>
							<input type="text" name="address" value=""/>
						</div><!-- end .row -->
						<div class="row">
							<label>City</label>
							<input type="text" 
									name="city" 
									value="<?=h($city['city'])?>" 
									readonly="true"/>
						</div><!-- end .row -->
						<div class="row">
							<label>Province</label>
							<input type="text" name="province" value=""/>
						</div><!-- end .row -->
						<div class="row">
							<label>Country</label>
							<input type="text" name="country" value=""/>
						</div><!-- end .row -->
						<div class="row">
							<label>Post Code</label>
							<input type="text" name="zip" value=""/>
						</div><!-- end .row -->
						<div class="row">
							<label>Metode Pembayaran</label>
							<div>
							<?php if($can_use_coin):?>
							<input type="radio" name="payment_method" value="coins" checked="checked"/> Coins<br />
							<?php endif;?>
							<?php if($can_use_ecash):?>

							<?php
								$disable_radiobut = '';
								if($enable_ecash != "TRUE"){
									$disable_radiobut = 'disabled="disabled"';
								}
							?>
							<input type="radio" name="payment_method" value="ecash" <?=$disable_radiobut?> /> Ecash Mandiri (Rupiah)<br />
							<input type="radio" name="payment_method" value="bank_transfer"/> Transfer Bank<br />
							<input type="radio" name="payment_method" value="kartu_kredit"/> Kartu Kredit(Visa/Master)
							<?php endif;?>
							</div>
						</div><!-- end .row -->
						<div class="row">
							<input type="hidden" name="ct" value="<?=$csrf_token?>"/>
							<input type="button" value="Cancel" class="button" onclick="cancel();"/>
							<?php if(!$can_use_coin && !$can_use_ecash):?>
							<p>
								Mohon Maaf, satu atau lebih barang hanya bisa dibayar menggunakan coins saja, 
							atau ecash saja.</p>
							<?php else:?>
							<input type="submit" value="Confirm" class="button"/>
							<?php endif;?>
						</div><!-- end .row -->
					</form>
					</div><!-- end .widget -->
				</div><!-- end .col-content -->
				<div class="box4 fr">
					<div class="tr widgets order-detail">
						<h2>Your Order</h2>
						<h4><?=h($item['name'])?></h4>
						<p class="price"><?=number_format(@$item['price_credit'])?> Coins</p>
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