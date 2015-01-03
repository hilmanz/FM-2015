<div id="content">
    <div class="content">
    	<div class="row-2">
            <h1 class="red">Isi data lengkap</h1>
            <p>Sebelum menjadi manajer, kita perlu elo isi dulu data pribadi elo dengan lengkap. Semua ini akan membantu kita memberikan layanan permainan Football Manager yang maksimal. Email elo kita perlukan untuk interaksi dalam game, sedangkan nomor HP kita perlukan untuk menghubungi elo untuk pengiriman hadiah, dan lain lain.</p>
    	</div><!-- end .row-2 -->
      <?php echo $this->Session->flash();?>
        <form class="theForm" action="<?=$this->Html->url('/login/register')?>" method="post" enctype="multipart/form-data">
            <div class="row">
                <label>Nama</label>
                <input name="name" value="<?=@$data_post['name']?>" type="text">
            </div><!-- end .row -->
            <div class="row">
                <label>Email</label>
                <input name="email" value="<?=@$data_post['email']?>" type="text">
                                    </div><!-- end .row -->
            <div class="row">
                <label>Tanggal Lahir</label>
                <select name="bod_dt">
                    <option value="0">Tanggal</option>
                    <?php for($i=1;$i<=31;$i++): ?>
                    <?php if(@$data_post['bod_dt'] == $i): ?>
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
                    <?php if(@$data_post['bod_mt'] == $i): ?>
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
                    <?php if(@$data_post['bod_yr'] == $i): ?>
                        <option selected="selected" value="<?=$i?>"><?=$i?></option>
                    <?php else: ?>
                        <option value="<?=$i?>"><?=$i?></option>
                    <?php endif; ?>
                    <?php endfor; ?>                
                </select>
            </div><!-- end .row -->
            <div class="row">
                <label>Lokasi</label>
                <input name="city" value="<?=@$data_post['city']?>" type="text">
            </div><!-- end .row -->
            <div class="row">
                <label>Nomor HP</label>
                <input name="phone_number" value="<?=@$data_post['phone_number']?>" type="text">
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
                <input type="text" name="faveclub" value="<?=@$data_post['faveclub']?>"/>
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
                <input type="hidden" name="not_facebook" value="1" >
                <input name="save" value="1" type="hidden">
                <input name="step" value="1" type="hidden">
                <input value="Simpan &amp; Lanjutkan" class="button fr" type="submit">
            </div><!-- end .row -->
        </form>
    </div>
</div>

<!-- Conversion: Supersoccer_acitvity_landingpage_form -->
<img src="http://avn.innity.com/conversion/?cb=85229&conversion=672&value=" width="1" height="1" border="0">
<img src="http://avn.innity.com/rt/?cb=1418375215&a=1454&e=8760" width="1" height="1" border="0">

<div id="sidebar">
   <?php if(!isset($disable_login)):?>
    <div id="loginbox" class="widgets tr" >
        <a href="<?=$this->Html->url('/login');?>" class="boxButton loginBtn">&nbsp;</a>
        <a href="<?=$this->Html->url('/login/register');?>" class="boxButton createAccount">&nbsp;</a>
    </div>
    <?php endif;?>
</div>
<!-- -->
<script>
  window.fbAsyncInit = function() {
    // init the FB JS SDK
    FB.init({
      appId      : '<?=$FB_APP_ID?>',                        // App ID from the app dashboard
      channelUrl : '//<?=$DOMAIN?>/channel.html', // Channel file for x-domain comms
      status     : true,                                 // Check Facebook Login status
      xfbml      : true,                                  // Look for social plugins on the page
      cookie : true
    });

    // Additional initialization code such as adding Event Listeners goes here
    
  };

  // Load the SDK asynchronously
  (function(d, s, id){
     var js, fjs = d.getElementsByTagName(s)[0];
     if (d.getElementById(id)) {return;}
     js = d.createElement(s); js.id = id;
     js.src = "//connect.facebook.net/en_US/all.js";
     fjs.parentNode.insertBefore(js, fjs);
   }(document, 'script', 'facebook-jssdk'));
</script>


<script>
function fb_login(){
    FB.login(function(response) {
       if (response.authResponse) {
        
         FB.api('/me', function(response) {
           console.log('Good to see you, ' + response.name + '.');
           window.location = window.location;
           document.location = "<?=$FB_AFTER_LOGIN_URL?>";
         });
       } else {
         
           document.location = "<?=$FB_AFTER_LOGIN_URL?>";
       }
     },{scope: 'email,user_location,user_birthday'});
    
}
</script>

<script>
$('#a-reload').click(function() {
  var $captcha = $("#img-captcha");
    $captcha.attr('src', $captcha.attr('src')+'?'+Math.random());
  return false;
});
</script>