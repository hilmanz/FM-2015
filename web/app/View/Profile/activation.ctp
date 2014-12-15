<div id="boxgrey" >
	<?php echo $this->Session->flash(); ?>
	Kode Aktivasi Telah dikirim ke email Anda <?=$user_data['email']?><br />
	Silahkan buka email
	<form method="post" action="<?=$this->Html->url('/profile/activation')?>" 
		enctype="application/x-www-form-urlencoded">
		<div class="row">
	        <label>Activation Code</label>
	        <input type="text" name="act_code" >
	    </div>
	    <div class="row">
	        <input value="Submit" class="button" type="submit">
	    </div>
    </form>
</div><!-- -->
<!-- Conversion: Supersoccer_acitvity_activationcode_form -->
<img src="http://avn.innity.com/conversion/?cb=73615&conversion=673&value=" width="1" height="1" border="0">