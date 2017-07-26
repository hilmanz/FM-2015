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
                <a href="#popup-coins" class="showPopup btnCoin">&nbsp;</a>
                <a href="#popup-mandiri-cash" class="showPopup btnCoinMandiri">&nbsp;</a>
            </div>
            <div class="rowd">
				<div class="col-content fr tr widget">
					<?php if($has_merchandise):?>
					<?php 
							foreach($rs as $r):
						
						if($r['MerchandiseItem']['merchandise_category_id'] != Configure::read('ticket_category_id')):
						$pic = Configure::read('avatar_web_url').
										"merchandise/thumbs/0_".
										$r['MerchandiseItem']['pic'];
						$buy_url = $this->Html->url('/merchandises/view/'.$r['MerchandiseItem']['id']);
						$view_url = $this->Html->url('/merchandises/view/'.$r['MerchandiseItem']['id']);

					?>
					<div class="catalog-item">
						<div class="imagesCatalog tr widget">
							<?php if($r['MerchandiseItem']['available'] > 0):?>
							<a href="<?=$buy_url?>">
							<?php endif;?>
							  <img src="<?=$this->Html->url($pic)?>" />
							<?php if($r['MerchandiseItem']['available'] > 0):?>
							</a>
							<?php endif;?>
						</div>
						<div class="detailCatalog">
							<h4><?=h($r['MerchandiseItem']['name'])?></h4>
							<?php if(intval($r['MerchandiseItem']['price_credit']) > 0):?>
							<p class="price"><?=number_format($r['MerchandiseItem']['price_credit'])?>
								Coins
							</p>
							<?php endif;?>
							<?php if(intval($r['MerchandiseItem']['price_money']) > 0):?>
							<p class="points red">
								Rp. <?=number_format($r['MerchandiseItem']['price_money'])?>
							</p>
							<?php endif;?>
							<?php if($r['MerchandiseItem']['available'] > 0):?>
								<a class="buyBtn button" href="<?=$view_url?>">VIEW</a>
							<?php else:?>
								SOLD OUT
							<?php endif;?>
						</div>
						
					</div>
					<?php endif;endforeach;?>
					<div class="pagings tr">
						<?php
			              echo $this->Paginator->prev(__('Sebelumnya'), array(), null, 
			                                          array('class' => 'prev'));
			              ?>
			              <?php
			              echo $this->Paginator->next(__('Berikutnya'), array(), null, 
			                                      array('class' => 'next'));
			              ?>
					</div><!-- end .col-content -->
					<?php else:?>
					<div class="desc">
						Saat ini belum ada merchandise yang dapat di beli
					</div>
					<?php endif;?>
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

										if(sizeof($category['Child'])>0):
											$child = $category['Child'];

											foreach($child as $c):
									?>
										<option value="<?=$c['MerchandiseCategory']['id']?>">
											&nbsp;&nbsp;&nbsp;<?=h($c['MerchandiseCategory']['name'])?>
										</option>
									<?php endforeach;endif;?>
								<?php
								endif;endforeach;
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
					<div class="widget tr catalog-list">
						<a href="<?=$this->Html->url('/merchandises/cart')?>" class="button">
							<span class="ico icon-cart">&nbsp;</span> Keranjang Belanja
						</a>
						<a href="<?=$this->Html->url('/merchandises/history')?>" class="button">
							<span class="ico icon-airplane">&nbsp;</span> Order Tracking
						</a>
					</div>
					<div class="widget tr catalog-list">
						Jika ada pertanyaan atau keluhan, <br> kirimkan email ke 
						<span class="yellow">store@supersoccer.co.id</span>
					</div>
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
<div id="popup-coins" class="popup popups">
	<a href="#" class="closebtn"><span class="icon-close"></span></a>
	<div class="popup-content">
    	<h3>FM  COIN</h3>
        <p>
Untuk memperoleh FM Coins, lo harus ikutan main FM Super Soccer. Caranya langsung aja daftar di <a href="http://fm.supersoccer.co.id/" target="_blank">fm.supersoccer.co.id</a> isi data lengkap dan tim yang akan lo tangani sebagai manajer.
</p>
<p>
Setelah terdaftar, lo akan memperoleh poin dari tiap pertandingan. Poin tiap pekan yang lo peroleh berdasarkan performa dari pemain lo. Poin yang lo dapet otomatis akan menambah Coin lo. Coin inilah yang bisa lo gunakan untuk belanja di Super Soccer Store.
</p>
    </div><!-- end .popup-content -->
</div><!-- end #popup-coins -->
<div id="popup-mandiri-cash" class="popup popups">
	<a href="#" class="closebtn"><span class="icon-close"></span></a>
	<div class="popup-content">
    	<h3>MANDIRI E-CASH</h3>
       <h4>
	Cara Pendaftaran Account mandiri ecash di handphone
</h4>
<ol>
	<li>Tekan *141*6#</li>
    <li>Pilih ecash</li>
    <li>Pilih admin</li>
    <li>Pilih daftar</li>
    <li>Tekan 1 untuk konfirmasi penerimaan syarat dan ketentuan</li>
    <li>Masukkan nama (cukup nama depan)</li>
    <li>Masukkan kata rahasia – digunakan nanti untuk melakukan reset PIN jika lupa PIN(minimum 8 huruf, disarankan berupa kata atau kalimat)</li>
    <li>Masukkan PIN baru</li>
    <li>Masukkan PIN sekali lagi</li>
    <li>Pendaftaran selesai</li>
</ol>
<h4>Pengisian ecash</h4>
<ol>
	<li>Datangi ATM Mandiri</li>
    <li>Masukkan kartu</li>
    <li>Masukkan PIN</li>
    <li>Pilih uang elektronik</li>
    <li>Pilih isi / Top up</li>
    <li>Masukkan nomor HP yang didaftarkan diatas</li>
    <li>Masukkan jumlah pengisian</li>
    <li>Muncul layar konfirmasi nomor HP dan Nama yang didaftarkan sebelumnya</li>
    <li>Tekan benar</li>
    <li>Masukkan PIN ATM</li>
    <li>Transaksi selesai</li>
</ol>
    </div><!-- end .popup-content -->
</div><!-- end #popup-mandiri-cash -->
<div id="bgPopup" class="popup"></div>

<script>
var browse_url = "<?=$browse_url?>";
$("select[name=cid]").change(function(e){
	document.location = browse_url+''+parseInt($(this).val());
});

// popup
$("a.showPopup").click(function(){
	$("html, body").animate({ scrollTop: 0 }, 600);
	$(".popup").fadeOut();
	var targetID = jQuery(this).attr('href');
	$("#bgPopup").fadeIn();
	$(targetID).fadeIn();
	$(targetID).addClass('visible');
	    return false;
});
$("a.closebtn,#bgPopup").click(function(){
	$("#bgPopup").fadeOut();
	$(".popup").fadeOut();
	    return false;
});
</script>