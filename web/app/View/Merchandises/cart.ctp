
<script>
var base_price = {};
var base_coin = {};
</script>
<div id="catalogPage">
      <div class="rowd">
     	 <?php echo $this->element('infobar'); ?>
      </div>
    <div id="thecontent">
        <div class="content pad20">
        	<div class="titlePage">
				<h1 class="yellow">Keranjang Belanja</h1>
            </div>
            <div class="rowd">
				<div class="col-contents">
					<div class="widgets tr">
                        <?php
                            $msg = $this->Session->flash();
                            if(isset($msg)):
                        ?>
                        <div class="error">
                            <?=($msg)?>
                        </div>
                        <?php endif;?>
			<form 
                id="frm" method="post" 
                enctype="application/x-www-form-urlencoded"
				action="<?=$this->Html->url('/merchandises/cart')?>">
                <table width="100%" border="0" cellspacing="0" cellpadding="0" class="theTable footable nomargin">
					<thead>
                    <tr>
                       
                        <th>Item</th>
                        <th>Harga Satuan</th>
                        <th>Jumlah</th>
                        <th>Total</th>
                        <th>Hapus</th>
                    </tr>
					</thead>
					<tbody>
					<?php
                    $kg = 0;
					for($i=0;$i<sizeof($shopping_cart);$i++):

                        $item = $shopping_cart[$i]['data']['MerchandiseItem'];
                        $kg += $item['weight'] * $shopping_cart[$i]['qty'];
                    ?>
                    <tr class="tr-<?=intval($item['id'])?>">
                        <?php if(@$shopping_cart[$i]['out_of_stock']):?>
                        <td style="text-decoration:line-through"> 
                            #<?=h($item['id'])?> -
                            <?=h($item['name'])?>
                        </td>
                        <?php else:?>
                        <td> 
                            #<?=h($item['id'])?> -
                            <?=h($item['name'])?>
                        </td>
                        <?php endif;?>
                        <td>
                            <?php if(intval($item['price_money'])>0):?>
                            <p class="price">   
                                Rp. <?=number_format(intval($item['price_money']))?> 
                            </p> 
                            <?php endif;?>
                            <?php if($item['price_credit']>0):?>
                            <p class="price">   
                                <?php if( intval($item['price_money']) > 0):?>
                                    (<?=number_format(intval($item['price_credit']))?> Coins)
                                <?php else:?>
                                    <?=number_format(intval($item['price_credit']))?> Coins
                                <?php endif;?>

                            </p> 
                            <?php endif;?>
                            
                        </td>
                        <td>
                            <input type="hidden" name="item_id[]"  
                                value="<?=intval($item['id'])?>"
                            />
                            <?php if($item['merchandise_type']==0):?>
                            <input style="width:30px;" type="text" name="qty[]" class="qty" data-id="<?=intval($item['id'])?>" 
                                value="<?=intval($shopping_cart[$i]['qty'])?>"/>
                            <?php else: ?>
                            <input style="width:30px;" type="text" name="qty[]" class="qty" data-id="<?=intval($item['id'])?>" 
                                value="<?=intval($shopping_cart[$i]['qty'])?>" readonly="readonly"/>
                            <?php endif;?>
                        </td>
                        <td>
                            <?php if(intval($item['price_money'])>0):?>
                            Rp. <span class="prices price-<?=$item['id']?>">
                                <?=number_format(intval($item['price_money']))?>
                            </span>
                            <?php endif;?>
                            <?php if($item['price_credit']>0):?>
                                <?php if($item['price_money']>0):?>
                                    (<span class="coins coin-<?=$item['id']?>">
                                        <?=number_format(intval($item['price_credit']))?>
                                    </span> Coins)
                                <?php else:?>
                                    <span class="coins coin-<?=$item['id']?>">
                                        <?=number_format(intval($item['price_credit']))?>
                                    </span> Coins
                                <?php endif;?>
                            <?php endif;?>
                            <script>
                                 base_price['<?=$item['id']?>'] = <?=intval($item['price_money'])?>;
                                 base_coin['<?=$item['id']?>'] = <?=intval($item['price_credit'])?>;
                            </script>
                        </td>
                        <td>
                            <a class="btnDelete button" href="javascript:;" data-id="<?=intval($item['id'])?>">Hapus</a>
                        </td>
                    </tr>
                    <?php endfor;?>
                    <tr>
                        <td colspan="3">
                            <div class="col3">
                                Ongkos Kirim
                            </div>
                            <select name="city_id" class="col3">
                                <?php if($city_id==0):?>
                                    <option value="0" selected="selected">Pilih Kota</option>
                                <?php else:?>
                                    <option value="0">Pilih Kota</option>
                                <?php endif;?>

                                <?php $i=0; foreach($ongkir as $cost):?>
                                    <?php if($city_id == $cost['Ongkir']['id']):?>
                                    <option value="<?=intval($cost['Ongkir']['id'])?>" 
                                            selected="selected">
                                    <?php else:?>
                                    <option value="<?=intval($cost['Ongkir']['id'])?>">
                                    <?php endif;?>
                                        <?=strtoupper($cost['Ongkir']['kecamatan']." - ".$cost['Ongkir']['city'])?>
                                    </option>

                                    <?php
                                        if(!$enable_ongkir)
                                        {
                                            $ongkir[$i]['Ongkir']['cost'] = 0;
                                        }
                                    ?>
                                <?php $i++; endforeach;?>
                            </select>
                        </td>
                        
                        
                        <td colspan="2">
                            <span class="shipping"></span>
                        </td>
                    </tr>
                    <tr>
                        <td colspan="3">Belanja Total</td>
                        <td colspan="3">
                            <span class="total-price">0</span>
                        </td>
                    </tr>
					</tbody>

                </table>
                <input type="hidden" name="update_type" value="0"/>
                <a href="<?=$this->Html->url('/merchandises')?>" class="button2"><span class="ico icon-undo">&nbsp;</span> Kembali Belanja</a>
                <a href="javascript:;" id="btnUpdate"class="button2"><span class="ico icon-checkmark-circle">&nbsp;</span> Update Keranjang Belanja</a>
                <a href="javascript:;" id="btnCheckout"class="button2"><span class="ico icon-cart">&nbsp;</span> Checkout</a>
               
                </form>
					</div><!-- end .widget -->
				</div><!-- end .col-content -->
            </div><!-- end .row-3 -->
        </div><!-- end .content -->
    </div><!-- end #thecontent -->
</div><!-- end #catalogPage -->

<script>
var kg = <?=floatval($kg)?>;
var ongkir = <?=json_encode($ongkir)?>;
var total_ongkir = 0;
function cancel(){
	document.location="<?=$this->Html->url('/merchandises')?>";
}
function updateCart(){
    $("input[name=update_type]").val(0);
    $("#frm").submit();

}
function updateOngkir(){
    var city_id = $("select[name=city_id]").val();
    var cost = 0;
    for(var i in ongkir){
        if(ongkir[i].Ongkir.id == city_id){
            cost = ongkir[i].Ongkir.cost;
        }
    }
    total_ongkir = cost * kg;
    $('.shipping').html('Rp. '+number_format(total_ongkir));
}
function checkout(){
    $("input[name=update_type]").val(1);
    $("#frm").submit();
}
function totalCost(callback){
    var total_coins = 0;
    var total_price = 0;
    var n_total = $(".qty").length;
    $(".qty").each(function(i,item){
        var id = $(item).attr('data-id');
        $(".price-"+id).html((parseInt(base_price[id]) * parseInt($(item).val())));
        $(".coin-"+id).html((parseInt(base_coin[id]) * parseInt($(item).val())));
        total_coins += parseInt(base_coin[id]) * parseInt($(item).val());
        total_price += parseInt(base_price[id]) * parseInt($(item).val());
        if(parseInt(i) == (n_total - 1)){
           total_price += parseInt(total_ongkir);
            callback(total_price,total_coins);
        }
    });
}

$(".qty").on('keyup',function(e){
    updateCost();
});

$("#btnUpdate").on('click',function(e){
    updateCart();
});
$("#btnCheckout").on('click',function(e){
    checkout();
});
$(".btnDelete").on('click',function(e){
    var id = $(this).attr('data-id');
    $(".tr-"+id).remove();
});

function updateCost(){
    totalCost(function(total_price,total_coins){
        console.log(total_price,total_coins);
        var str = "";
        if(total_price > 0){
            str += "Rp. " + number_format(total_price);
        }
        if(total_coins >0){
            if(total_price > 0){
                str += " ("+number_format(total_coins) + " Coins)";
            }else{
                str += number_format(total_coins) + " Coins";    
            }
            
        }
        console.log('->',str);
        $(".total-price").html(str);
    });
}

$('select[name=city_id]').on('change',function(e){
    updateOngkir();
    updateCost();
});

$(document).ready(function(){
    updateOngkir();    
    updateCost();
});


</script>