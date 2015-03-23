<div id="fillDetailsPage">
	
    <div id="thecontent">
        <div id="content">
        	<div class="content">
            	<div class="row-2">
                    <h1 class="red">Isi data lengkap</h1>
                    <p>Sebelum menjadi manajer, kita perlu elo isi dulu data pribadi elo dengan lengkap. Semua ini akan membantu kita memberikan layanan permainan Football Manager yang maksimal. Email elo kita perlukan untuk interaksi dalam game, sedangkan nomor HP kita perlukan untuk menghubungi elo untuk pengiriman hadiah, dan lain lain.</p>
    			</div><!-- end .row-2 -->
                <?php echo $this->Session->flash();?>
                <form class="theForm" action="<?=$this->Html->url('/profile/register')?>" method="post" enctype="multipart/form-data">
                    
                    <div class="row">
                        <label>Nama</label>
                        <input type="text" name="name" value="<?=h($user['name'])?>"/>
                    </div><!-- end .row -->
                    <div class="row">
                        <label>Email</label>
                        <?php
                            if(isset($email_empty)):
                                $error_email = '<span class="error">harap isi dahulu.</span>';
                            endif;
                        ?>
                        <input type="text" name="email" value="<?=h(@$user['email'])?>" />
                        <?=@$error_email?>
                    </div><!-- end .row -->
                    <?php
                        $birthdate = explode('/', $user['birthday']);
                    ?>
                    <div class="row">
                        <label>Tanggal Lahir</label>
                        <select name="bod_dt">
                            <option value="0">Tanggal</option>
                            <?php for($i=1;$i<=31;$i++): ?>
                                <?php if($i == intval($birthdate[1])): ?>
                                    <option selected="selected" value="<?=$i?>"><?=$i?></option>
                                <?php else: ?>
                                    <option value="<?=$i?>"><?=$i?></option>
                                <?php endif; ?>
                            <?php endfor; ?>
                        </select>
                        -
                         <select name="bod_mt">
                            <option value="0">Bulan</option>
                            <?php for($i=1;$i<=12;$i++): ?>
                                <?php if($i == intval($birthdate[0])): ?>
                                    <option selected="selected" value="<?=$i?>"><?=$i?></option>
                                <?php else: ?>
                                    <option value="<?=$i?>"><?=$i?></option>
                                <?php endif; ?>
                            <?php endfor; ?>
                        </select>
                        -
                         <select name="bod_yr">
                            <option value="0">Tahun</option>
                            <?php for($i=1930;$i<(date("Y"));$i++): ?>
                                <?php if($i == intval($birthdate[2])): ?>
                                    <option selected="selected" value="<?=$i?>"><?=$i?></option>
                                <?php else: ?>
                                    <option value="<?=$i?>"><?=$i?></option>
                                <?php endif; ?>
                            <?php endfor; ?>
                        </select>
                    </div><!-- end .row -->
                    <div class="row">
                        <label>Lokasi</label>
                        <input type="text" name="city" value="<?=h(@$user['location']['name'])?>"/>
                    </div><!-- end .row -->
                    <div class="row">
                        <label>Nomor HP</label>
                        <input type="text" name="phone_number" value="<?=h(@$user['phone_number'])?>" />
                        <?php if($phone_empty):?>
                        <span class="error">harap isi dahulu.</span>
                        <?php endif;?>
                    </div><!-- end .row -->
                    <div class="row">
                        <label>Password</label>
                        <input type="password" name="password" style="width:50%;"/>
                        <?php if(@$password):?>
                        <span class="error">harap isi dahulu.</span>
                        <?php endif;?>
                    </div><!-- end .row -->
                    <div class="row">
                        <label>Ulangi Password</label>
                        <input type="password" name="password_repeat" style="width:50%;"/>
                        <?php if(@$password_repeat):?>
                        <span class="error">ulangi password harus sama dengan password.</span>
                        <?php endif;?>
                    </div><!-- end .row -->
                    <div class="row inputRadio">
                        <label>Dari mana elo tau tentang Super Soccer Football Manager?</label>
                        <input type="radio" class="styled" name="hearffl" checked="checked" value="1"/><span>Supersoccer</span>
                        <input type="radio" class="styled" name="hearffl" value="2"/><span>TV</span>
                        <input type="radio" class="styled" name="hearffl" value="3"/><span>Radio</span>
                        <input type="radio" class="styled" name="hearffl" value="4"/><span>Facebook</span>
                        <input type="radio" class="styled" name="hearffl" value="5"/><span>Twitter</span>
                    </div><!-- end .row -->
                    <div class="row inputRadio">
                        <label>Apakah ini permainan Liga fantasi pertama elo?</label>
                        <input type="radio" class="styled" name="firstime" checked="checked" value="1"/><span>Ya</span>
                        <input type="radio" class="styled" name="firstime" value="0"/><span>Tidak</span>
                    </div><!-- end .row -->
                    <div class="row">
                        <label>Sebutkan klub favorit elo</label>
                        <input type="text" name="faveclub" value="<?=h(@$user['faveclub'])?>"/>
                    </div><!-- end .row -->
                    <div class="row">
                        <div>
                          <?php echo $this->Html->image($this->Html->url(
                              array('controller'=>'profile', 'action'=>'captcha'), true),
                              array('id'=>'img-captcha','vspace'=>2))
                          ?>
                          <p><a href="#" id="a-reload">Gak kebaca? Muat ulang</a></p>
                        </div>
                    </div>
                    <div class="row">
                      <label>Masukan kode diatas</label>
                      <input type="text" name="captcha" value=""/>
                    </div>
                    <div class="row">
                        <input type="hidden" name="save" value="1"/>
                        <input type="hidden" name="step" value="1"/>
                        <input type="submit" value="Simpan &amp; Lanjutkan" class="button fr" />
                    </div><!-- end .row -->
                </form>
			</div><!-- end .content -->
        </div><!-- end #content -->
	<div id="sidebar" class="tr">
        <!--
		<div class="widget tr videotutorial">
			<h2>BINGUNG?</h2>
			<span class="yellow">Mainkan video dibawah untuk petunjuk mengisi form ini</span>
			<div id="vidbox">
				<iframe width="100%" height="200" src="//www.youtube.com/embed/EE_V-mSnH3M" frameborder="0" allowfullscreen></iframe>
			</div>
            <!-- end #vidbox -->
		<!--</div>-->
        <!-- end .videotutorial -->
	    <div class="widget">
	        <div class="nav-side">
	            <ul>
                   <li class="current"><span>Isi data lengkap Anda</span></li>
                   <li><span>Pilih Tim</span></li>
                   <li><span>Pilih Pemain</span></li>
                   <li><span>Pilih Staff</span></li>
	            </ul>
	        </div><!-- end .nav-side -->
	    </div><!-- end .widget -->
	    <div class="widget">
	        <div class="cash-left">
	            <h3 class="red">SISA UANG</h3>
	            <h1>SS$ <?=number_format($INITIAL_BUDGET)?></h1>
	            <h3 class="red">Est. PENGELUARAN MINGGUAN</h3>
	            <h1>SS$ <span class="expense">0</span></h1>
	        </div>
	    </div><!-- end .widget -->
	</div><!-- end #sidebar -->
    </div><!-- end #thecontent -->
</div><!-- end #fillDetailsPage -->
<script>
$('#a-reload').click(function() {
  var $captcha = $("#img-captcha");
    $captcha.attr('src', $captcha.attr('src')+'?'+Math.random());
  return false;
});
</script>

<!-- Conversion: Supersoccer_activity_create_team_page -->
<img src="http://avn.innity.com/conversion/?cb=46236&conversion=708&value=[VALUE]" 
            width="1" height="1" border="0"/>
