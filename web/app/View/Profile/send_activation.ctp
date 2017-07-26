<div id="boxgrey" >
	<?php echo $this->Session->flash(); ?>
	Anda harus mengaktifkan akun anda dengan mengirimkan kode aktivasi ke email yang valid
	<form method="post" action="<?=$this->Html->url('/profile/send_activation')?>" 
		enctype="application/x-www-form-urlencoded">
		<div class="row">
	        <label>Email Anda</label>
	        <input type="text" name="email" value="<?=@$user_data['email']?>" >
	    </div>
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
          <input type="hidden" name="trxsess" value="<?=rand(100,999)?>">
	        <input value="Kirim Kode Aktivasi" class="button" type="submit">
	    </div>
    </form>
</div><!-- -->
<script>
$('#a-reload').click(function() {
  var $captcha = $("#img-captcha");
    $captcha.attr('src', $captcha.attr('src')+'?'+Math.random());
  return false;
});
</script>