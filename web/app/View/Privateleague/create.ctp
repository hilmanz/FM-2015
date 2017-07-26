<div id="fillDetailsPage">
      <div class="rowd">
     	 <?php echo $this->element('infobar'); ?>
      </div>
      <div class="rowd">
       
    <div id="thecontent">
        <div id="content">
        	<div class="content">
            	<div class="row-2">
                    <h1 class="red">Create Private League</h1>
                    <p>Lengkapi data di bawah ini, selanjutnya loe bisa undang temen loin untuk bergabung ke private league yang loe buat</p>
    			</div><!-- end .row-2 -->
                <form class="theForm" action="<?=$this->Html->url('/privateleague/create')?>" 
                  enctype="multipart/form-data" method="post">
                    <div class="row">
                        <label>Nama Liga</label>
                        <input type="text" name="name" value="" maxlength="30"/>
                    </div><!-- end .row -->
                    <div class="row">
                        <label>Logo Liga</label>
                        <input type="file" name="logo">
                    </div>
                    <div class="row">
                        <label>Jumlah Team Maksimum</label>
                        <input type="text" value="30" disabled="disabled"/>
                    </div><!-- end .row -->
                    <div class="row">
                        <input type="hidden" name="save" value="1"/>
                        <input type="submit" value="Buat Liga" class="button" />
                    </div><!-- end .row -->
                </form>
			</div><!-- end .content -->
        </div><!-- end #content -->
	<div id="sidebar" class="tr">
	    
	    <div class="widget">
	        <div class="cash-left">
	            <h3 class="red">Daftar Private League</h3> 
	        </div>
	    </div><!-- end .widget -->
       
	</div><!-- end #sidebar -->
    </div><!-- end #thecontent -->
</div><!-- end #fillDetailsPage -->
