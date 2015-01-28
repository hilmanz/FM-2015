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
						<h1>Kode Transaksi : <?=$transaction_id?></h1>
						<h3>Loe akan diteruskan ke halaman pembayaran Mandiri E-Cash, silahkan klik tombol dibawah untuk melakukan pembayaran
						</h3>
					</div>
					<div class="tr widgets">
			
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
		                    	$kg += floatval($item['weight']) * intval($shopping_cart[$i]['qty']);
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
		                    		$total_ongkos = $ongkos * $kg;
		                    		?>
		                        	Rp. <?=number_format($total_ongkos)?>
		                        </td>
		                      
		                    </tr>
		                    <tr>
		                        <td></td>
		                        <td>Administrative &amp; Handling Fee</td>
		                        <td></td>
		                        <td></td>
		                        <td>Rp. <?=number_format($admin_fee)?></td>
		                      
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