<form class="shipaddress" 
	action="<?=$this->Html->url('/merchandises/view_order/'.$rs['MerchandiseOrder']['id'])?>"
	method="post" 
	enctype="application/x-www-form-urlencoded">
<h3>View Order - <?=h($rs['MerchandiseOrder']['po_number'])?></h3>
<h4>Payment Method : <?=h($rs['MerchandiseOrder']['payment_method'])?></h4>
<h4>Shipping : Rp. <?=number_format(h($rs['MerchandiseOrder']['ongkir_value']))?></h4>
<?php 

if(strtoupper($rs['MerchandiseOrder']['payment_method'])=='ECASH'):
?>
<h4>Admin Fee : Rp. <?=number_format($admin_fee)?></h4>
<?php endif;?>
<?php

$rs['MerchandiseOrder']['data'] = unserialize($rs['MerchandiseOrder']['data']);

if($rs['MerchandiseOrder']['data'] == null):
	$pic = Configure::read('avatar_web_url')."merchandise/thumbs/1_".$rs['MerchandiseItem']['pic'];	


?>

	<div class="row">
		<img src="<?=$pic?>"/>
	</div>
	<div class="row">
		<label><?=h($rs['MerchandiseItem']['name'])?></label>
		
	</div><!-- end .row -->
	<div class="row">
		<?=h($rs['MerchandiseItem']['description'])?>
		
	</div><!-- end .row -->
	<div class="row">
		<label>Price</label>
		<p>ss$ <?=h($rs['MerchandiseItem']['price_currency'])?></p>
		
		
	</div><!-- end .row -->
	<div class="row">
		<label>Price</label>
		
		<p><?=h($rs['MerchandiseItem']['price_credit'])?> Coins</p>
		
	</div><!-- end .row -->
	<div class="row">
		<label>Price</label>
		
		<p>IDR <?=h($rs['MerchandiseItem']['price_money'])?></p>
	</div><!-- end .row -->
	<div class="row">
		<label>Stock</label>
		<?=h($rs['MerchandiseItem']['stock'])?>
	</div><!-- end .row -->

<?php else:?>
	<div class="row">
		<table width="100%" class="table">
			<tr>
				<td>
					Item
				</td>
				<td>
					Qty
				</td>
				<td>
					Price
				</td>
				<td>
					Total Price
				</td>
			</tr>
			<?php 
			for($i=0;$i<sizeof($rs['MerchandiseOrder']['data']); $i++):
				$item = $rs['MerchandiseOrder']['data'][$i];
			
			?>
			<tr>
				<td>
					<?=h($item['data']['MerchandiseItem']['id'])?> - 
					<?=h($item['data']['MerchandiseItem']['name'])?>
				</td>
				<td>
					<?=intval($item['qty'])?>
				</td>
				<td>
					Rp. <?=number_format($item['data']['MerchandiseItem']['price_money'])?> / 
					<?=number_format($item['data']['MerchandiseItem']['price_credit'])?> Coins
				</td>
				<td>
					Rp. <?=number_format(intval($item['data']['MerchandiseItem']['price_money']) * 
											intval($item['qty']))?> / 
					 <?=number_format(intval($item['data']['MerchandiseItem']['price_credit']) * 
											intval($item['qty']))?>  Coins
				</td>
			</tr>
			<?php endfor;?>
		</table>
	</div>

<?php endif;?>

	<h4>Shipping Info</h4>
	<div class="row">
		<label>First Name</label>
		<input type="text" name="first_name" value="<?=h($rs['MerchandiseOrder']['first_name'])?>"/>
	</div><!-- end .row -->
	<div class="row">
		<label>Last Name</label>
		<input type="text" name="last_name" value="<?=h($rs['MerchandiseOrder']['last_name'])?>"/>
	</div><!-- end .row -->
	<div class="row">
		<label>Mobile Phone</label>
		<input type="text" name="phone" value="<?=h($rs['MerchandiseOrder']['phone'])?>"/>
	</div><!-- end .row -->
	<div class="row">
		<label>Email</label>
		<input type="text" name="email" value="<?=h($rs['MerchandiseOrder']['email'])?>"/>
	</div><!-- end .row -->
	<h3>Shipping Address</h3>
	<div class="row">
		<label>Street</label>
		<input type="text" name="address" value="<?=h($rs['MerchandiseOrder']['address'])?>"/>
	</div><!-- end .row -->
	<div class="row">
		<label>City</label>
		<input type="text" name="city" value="<?=h($rs['MerchandiseOrder']['city'])?>"/>
	</div><!-- end .row -->
	<div class="row">
		<label>Province</label>
		<input type="text" name="province" value="<?=h($rs['MerchandiseOrder']['province'])?>"/>
	</div><!-- end .row -->
	<div class="row">
		<label>Country</label>
		<input type="text" name="country" value="<?=h($rs['MerchandiseOrder']['country'])?>"/>
	</div><!-- end .row -->
	<div class="row">
		<label>Post Code</label>
		<input type="text" name="zip" value="<?=h($rs['MerchandiseOrder']['zip'])?>"/>
	</div><!-- end .row -->
	<div class="row">
		<label>Status</label>
		<!-- sementara kalo statusnya canceled, uda gak bisa dibalikin lagi -->
		
		<select name="n_status">
			<option value="0" <?php if($rs['MerchandiseOrder']['n_status']==0):echo 'selected';endif;?>>
				Pending
			</option>
			<option value="1" <?php if($rs['MerchandiseOrder']['n_status']==1):echo 'selected';endif;?>>Order accepted, waiting for delivery</option>
			<option value="2" <?php if($rs['MerchandiseOrder']['n_status']==2):echo 'selected';endif;?>>Delivered</option>
			<option value="3" <?php if($rs['MerchandiseOrder']['n_status']==3):echo 'selected';endif;?>>Closed</option>
			<option value="4" <?php if($rs['MerchandiseOrder']['n_status']==4):echo 'selected';endif;?>>Canceled</option>
		</select>
		
	</div><!-- end .row -->
	<div class="row">
		<label>Update Reason</label>
		<input type="text" name="notes" value="<?=h($rs['MerchandiseOrder']['notes'])?>"/>
	</div><!-- end .row -->
	<div class="row">
		<input type="submit" value="Confirm" class="Update"/>
	</div><!-- end .row -->
</form>