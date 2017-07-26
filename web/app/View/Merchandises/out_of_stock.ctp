<?php
$pic = Configure::read('avatar_web_url').
				"merchandise/thumbs/0_".
				$item['pic'];
?>
<div id="catalogPage">
      <div class="rowd">
     	 <?php echo $this->element('infobar'); ?>
      </div>
    <div id="thecontent">
        <div class="content pad20">
        	<div class="titlePage">
				      <h1 class="red">Online Catalog</h1>
            </div>
            <div class="rowd">
      				<div class="col-content">
                
                <h3 class="yellow">Maaf, Stok <span class="white"><?=h($item['name'])?></span> telah habis !</h3>

  						 <div class="rowButton">
  							<a href="<?=$this->Html->url('/merchandises')?>" class="button2">
  							  <span class="ico icon-undo-2">&nbsp;</span> Kembali Belanja
  							</a>
  						  </div>
               
                
      				</div><!-- end .col-content -->
            </div><!-- end .row-3 -->
        </div><!-- end .content -->
    </div><!-- end #thecontent -->
</div><!-- end #catalogPage -->

<script>
function cancel(){
	document.location="<?=$this->Html->url('/merchandises')?>";
}
</script>