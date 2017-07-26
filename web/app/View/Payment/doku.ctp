<div id="catalogPage">
	<div class="content">
		<div class="row-2">
			<h1 class="red">DETAIL TRANSAKSI</h1>
			<div class="col-content">
				<div class="row">
                    <label><h3>Nama</h3></label>
                    <label><h3><?=$doku_param['NAME']?></h3></label>
                </div>
                <div class="row">
                    <label><h3>Email</h3></label>
                    <label><h3><?=$doku_param['EMAIL']?></h3></label>
                </div>
                <div class="row">
                    <label><h3>Pembayaran</h3></label>
                    <label><h3><?=$basket?></h3></label>
                </div>
                <div class="row">
                    <label><h3>Biaya</h3></label>
                    <label><h3><?=$doku_param['AMOUNT']?></h3></label>
                </div>
                <div class="row">
                    <label><h3>Metode Pembayaran</h3></label>
                    <?php
                    	$payment_method = 'Kartu Kredit';
                    	if($doku_param['PAYMENTCHANNEL'] == '05'){
                    		$payment_method = 'Transfer Bank';
                    	}
                    ?>
                    <label><h3><?=$payment_method?></h3></label>
                </div>
                <div class="row">
                	<label><h3>&nbsp;</h3></label>
                    <form method="post" action="<?=$doku_api?>" enctype="application/x-www-form-urlencoded">
                    	<?php 
                    		foreach($doku_param as $key => $value)
                    		{
                    			echo '<input type="hidden" name="'.$key.'" value="'.$value.'" />
                    			';
                    		}
                    	?>
                    	<input value="Proses" class="button" type="submit">
                	</form>
                </div>
			</div><!-- end .col-content -->
	    </div>
	</div>
</div>