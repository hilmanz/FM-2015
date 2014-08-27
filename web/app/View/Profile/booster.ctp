
<div id="fillDetailsPage">
      <div class="rowd">
     	 <?php echo $this->element('infobar'); ?>
      </div>
      <div class="rowd">
       <?php for($i=0;$i<sizeof($long_banner);$i++):?>
            <div class="col2">
                <div class="mediumBanner">
                  <a href="javascript:banner_click(<?=$long_banner[$i]['Banners']['id']?>,'<?=$long_banner[$i]['Banners']['url']?>');">
                      <img src="<?=$this->Html->url(Configure::read('avatar_web_url').
                        $long_banner[$i]['Banners']['banner_file'])?>" />
                  </a>
                </div><!-- end .mediumBanner -->
            </div><!-- end .col2 -->
        <?php endfor;?>
       
    <div id="thecontent">
        <div id="content">
        	<div class="content">
            	<div class="row-2">
                    <h1 class="red">POIN BOOSTER</h1>
                    <p>Tampilan informasi seputar informasi poin booster</p>
    			</div><!-- end .row-2 -->
                <table class="theTable footable" width="100%" border="0" cellpadding="0" cellspacing="0">
                    <thead>
                        <tr>
                            <th data-class="expand">No.</th>
                            <th>Booster Name</th>
                            <th data-hide="phone,tablet">Masa Berlaku</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(count($booster) != 0): ?>
                            <?php $i=1; foreach ($booster as $value): ?>
                                <?php
                                    $total = $value['a']['available']." Minggu";
                                    if($value['a']['available'] > 9000){
                                        $total = 'Sampai Musim Berakhir';
                                    }else if($value['a']['available']==0){
                                        $total = 'Sudah Habis Terpakai';
                                    }
                                ?>
                                <tr class="odd">
                                    <td class="l-rank"><?=$i?></td>
                                    <td class="l-club"><?=$value['b']['name']?></td>
                                    <td class="l-manager"><?=$total?></td>
                                </tr>
                            <?php $i++; endforeach; ?>
                        <?php else: ?>
                            <tr class="odd">
                                <td colspan="3">Lo belom punya poin booster.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
            </table>
			</div><!-- end .content -->
        </div><!-- end #content -->
	<div id="sidebar" class="tr">
	    <div class="widget">
	        <div class="cash-left">
	            <h3 class="red">SISA UANG</h3>
	            <h1><?=number_format($team_bugdet)?> ss$</h1>
	            <h3 class="red">JUMLAH POINT</h3>
	            <h1><?=number_format($USER_POINTS)?> pts</h1> 
                <h3 class="red">JUMLAH COINS</h3>
                <h1><?=number_format($USER_COINS)?></h1> 
                <h3 class="red">PERINGKAT SAAT INI</h3>
                <h1><?=number_format($USER_RANK)?></h1> 
	        </div>
	    </div><!-- end .widget -->
	</div><!-- end #sidebar -->
    </div><!-- end #thecontent -->
</div><!-- end #fillDetailsPage -->
<!--popups-->
<div class="popup">
    <div class="popupContainer popup-small" id="popup-upload">
        <div class="popupHeader">
            <h3>Upload Avatar</h3>
        </div><!-- END .popupHeader -->
        <div class="popupContent">
            <div class="entry-popup">
                <form id="uploadForm" enctype="multipart/form-data" method="post">
                    <input type="file" name="file"/>
                </form>
            </div><!--END .entry-popup-->
        </div><!-- END .popupContent -->
    </div><!-- END .popupContainer -->
</div><!-- END .popup --> 
<div class="popup">
    <div class="popupContainer popup-small" id="popup-message">
        <div class="popupHeader">
            <h3>Upload Avatar</h3>
        </div><!-- END .popupHeader -->
        <div class="popupContent">
            <div class="entry-popup">
               
            </div><!--END .entry-popup-->
        </div><!-- END .popupContent -->
    </div><!-- END .popupContainer -->
</div><!-- END .popup -->

<a id='popupmsg' href="#popup-message" style="display:none;">#</a>

<?php
echo $this->Html->script('d_uploader');
?>
<script>
var avatar_dir = "<?=$this->Html->url($avatar_dir)?>";
$("#btn_upload").fancybox({});
$("#popupmsg").fancybox({});
$("#uploadForm").file_uploader('<?=$this->Html->url("/profile/upload_image")?>',
{
    beforeSend:function(e){
        
    },
    success:function(e){
        $("#fileuploader").hide();
        var o = JSON.parse(e);
        if(o.status==1){
            $("#msg").hide();
            $("#uploaditemform").show();
            $("#uploadForm").find("input[name='file']").show();
            $("#progress").hide();
            $.fancybox.close();
            $("#popup-message").find('.entry-popup').html('Upload Completed !');
            $("#popupmsg").trigger('click');
            $(".avatar-big").find('img').attr('src',avatar_dir+'120x120_'+o.files);
        }else{
            $("#popup-message").find('.entry-popup').html('Cannot upload your image, please try again later !');
            $("#popupmsg").trigger('click');
        }
    },
    error:function(e){
        console.log("error : "+e);
    }
});
</script>