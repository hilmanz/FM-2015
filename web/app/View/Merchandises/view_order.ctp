
<?php
	$items = unserialize($rs['MerchandiseOrder']['data']);
	$total_item = count($items);

	$enable_ongkir = true;
	$enable_admin_fee = true;

	$admin_fee = Configure::read('PO_ADMIN_FEE');
	if($total_item < 2 && $total_item != 0)
	{
		//check ongkir
		if($items[0]['data']['MerchandiseItem']['enable_ongkir'] != 1)
		{
			$enable_ongkir = false;
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
				<h1 class="red">
					Online Catalog
				</h1>
				<h4>Detil Pemesanan
				</h4>
            </div>
            <div class="rowd">
				<div class="col-content">
					<div class="tr widgets">
						<h1>Kode Transaksi : <?=h($rs['MerchandiseOrder']['po_number'])?></h1>
						
					</div>
					<div class="tr widget">
						<?php
							$shopping_cart = unserialize($rs['MerchandiseOrder']['data']);
						?>
						<table width="100%" border="0" cellspacing="0" cellpadding="0" class="theTable footable">
							<thead>
                                <tr>
                                    <th></th>
                                    <th>Item</th>
                                    <th>Harga Satuan</th>
                                    <th>Jml</th>
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
		                    	$kg += ceil(floatval($item['weight'])) * intval($shopping_cart[$i]['qty']);
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
		                        <td> #<?=h($item['id'])?> -
		                            <?=h($item['name'])?>
		                        </td>
		                        <td>
		                           
		                            <p class="price">   
		                                Rp. <?=number_format(intval($item['price_money']))?> 
		                            </p> 
		                           
		                            
		                        </td>
		                        <td>
		                            <?=(intval($shopping_cart[$i]['qty']))?>
		                            <?php
		                            	
		                            	$price = intval($shopping_cart[$i]['qty']) * 
		                            						intval($item['price_money']);

		                            	$total_price += $price;
		                            ?>
		                        </td>
		                        <td>
		                            Rp. <span class="prices price-<?=$item['id']?>">
		                                <?=number_format(intval($price))?>
		                            </span>
		                           
		                           
		                        </td>
		                        
		                    </tr>
		                   
		                    <?php endfor;?>
		                    <tr>
		                        <td></td>
		                        <td>Ongkos Kirim</td>
		                        <td></td>
		                        <td></td>
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

		                    		$total_ongkos = intval($ongkos) * floatval($kg);
		                    		?>
		                        	Rp. <?=number_format($total_ongkos)?>
		                        </td>
		                      
		                    </tr>
		                    <tr>
		                        <td></td>
		                        <td>Administrative &amp; Handling Fee</td>
		                        <td></td>
		                        <td></td>
		                        <td>
		                        	<?=number_format($rs['MerchandiseOrder']['total_admin_fee'])?>
		                        </td>
		                    </tr>
		                    <tr>
		                    	<td></td>
		                        <td colspan="3" align="right">Belanja Total</td>
		                        <td>
		                            <span class="total-price">
		                            	
		                            	
		                            		Rp. <?=number_format($total_price+$admin_fee+$total_ongkos)?>
		                                   
		                               
		                            </span>
		                        </td>
		                    </tr>
                            </tbody>
		                </table>
 
					</div><!-- end .widget -->
					<?php if($rs['MerchandiseOrder']['n_status']==1):?>
					<div class="tr widgets">
						<?php
							
							for($i=0;$i<sizeof($shopping_cart);$i++):
		                        $item = $shopping_cart[$i]['data']['MerchandiseItem'];

		                    	if($item['merchandise_category_id'] == Configure::read('ticket_category_id')):
		                 ?>
		             			<?php for($t=0;$t<$shopping_cart[$i]['qty'];$t++):?>
		             			<?php
		             			$hashed_data = encrypt_param(serialize($rs['MerchandiseOrder']));
		             			?>
		                  		<a href="<?=$this->Html->url('/vouchers/get/'.$i.'/?d='.$hashed_data)?>" 
		                  			class="button">Download Voucher `<?=$item['name']?>`</a>
		                  		
	                    <?php endfor;endif;endfor;?>	
					</div>
					<?php endif;?>
					<div class="tr widgets">
						<p>
							<a class="button2" href="<?=$this->Html->url('/merchandises/history')?>">
								<span class="ico icon-undo-2">&nbsp;</span> Kembali ke Daftar Transaksi
							</a>
						</p>
					</div><!-- end .widget -->
				</div><!-- end .col-content -->
				
           
				<div class="box4 fr">
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