
<!DOCTYPE html>
<!--[if lt IE 7]> <html dir="ltr" lang="en-US" class="ie6"> <![endif]-->
<!--[if IE 7]>    <html dir="ltr" lang="en-US" class="ie7"> <![endif]-->
<!--[if IE 8]>    <html dir="ltr" lang="en-US" class="ie8"> <![endif]-->
<!--[if gt IE 8]><!--> <html dir="ltr" lang="en-US"> <!--<![endif]-->

<!-- BEGIN head -->
<head>
	<!--Meta Tags-->
	<meta name="viewport" content="width=device-width; initial-scale=1.0">
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
		
	<!--Title-->
	<title>SUPER SOCCER - FANTASY FOOTBALL LEAGUE</title>

	<?php echo $this->Html->css(
			  array('fflmobile'),
		null,array('media'=>'all')); 
	?>
	<?php echo $this->Html->script(
	  array('jquery-1.9.1',
	  ));
	?>
</head>
<body>
	<div id="body">
		
		<a href="index.php" id="logo">
			 <img src="<?=$this->Html->url('/images/mobile/logo.png')?>" />
		</a>
		<div id="container">
			<?php if(@eregi('Android',env('HTTP_USER_AGENT'))):?>
				<h1>Mau Main di Android?</h1>
				<h3>Download aplikasi Football Manager versi Android untuk mendapatkan pengalaman bermain terbaik</h3>
				<a href="https://play.google.com/store/apps/details?id=com.fullspade.ffleague" class="btn_android"> 
					<img src="<?=$this->Html->url('/images/mobile/android_download.png')?>" />
				</a>
				<h3 class="yellow">Anda harus terdaftar terlebih dahulu di versi web</h3>
			<?php else:?>
				<?php 
				if(@eregi('Iphone',env('HTTP_USER_AGENT'))){
					$device = "Iphone";
				}else if(@eregi('Blackberry',env('HTTP_USER_AGENT'))){
					$device = "Blackberry";
				}else if(@eregi('Ipad',env('HTTP_USER_AGENT'))){
					$device = "Ipad";
				}else if(@eregi('Ipod',env('HTTP_USER_AGENT'))){
					$device = "Ipod";
				}else{
					$device = "device";
				}
				?>
				<h3 class="yellow">Maaf, aplikasi FM Supersoccer belum dapat digunakan oleh <?=h($device)?> kamu. Tunggu release berikutnya dari kami.</h3>
				<h3 class="yellow">Silahkan bermain dengan menggunakan komputer desktop.</h3>
			<?php endif;?>	
		</div>
		
		<a href="<?=$this->Html->url('/')?>" class="btnWeb"> 
			<img src="<?=$this->Html->url('/images/mobile/web_btn.png')?>" />
		</a>
		
	</div>
</body>
</html>
