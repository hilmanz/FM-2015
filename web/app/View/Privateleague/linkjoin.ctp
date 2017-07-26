<div id="fillDetailsPage">
      <div class="rowd">
     	 <?php echo $this->element('infobar'); ?>
      </div>
      <div class="rowd">
       
    <div id="thecontent">
        <div id="content">
        	<div class="content">
            	<div class="row-2">
                    <h1 class="red">Join Private League</h1>
                    <h3>
                      <span class="yellow">
                        <?=$rs_league[0]['c']['name']?>
                      </span> Telah Mengundang LOE Untuk Bergabung di Private League<br />
                      <span class="red">
                        <?=$rs_league[0]['b']['name']?>
                      </span>
                    </h3>
                    <a class="button" href="<?=$this->Html->url('/privateleague/join/?trx='.$trx_code)?>">Join</a>
                    &nbsp;
                    <a class="button" href="<?=$this->Html->url('/privateleague/reject/?trx='.$trx_code)?>">Reject</a>
    			    </div><!-- end .row-2 -->
			     </div><!-- end .content -->
        </div><!-- end #content -->
	<div id="sidebar" class="tr">
      
      <div class="widget">
          <div class="cash-left">
          </div>
      </div><!-- end .widget -->
       
  </div><!-- end #sidebar -->
    </div><!-- end #thecontent -->
</div><!-- end #fillDetailsPage -->