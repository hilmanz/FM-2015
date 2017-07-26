
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
                    <h1 class="red">PROFIL SAYA</h1>
                    <p>Tampilan informasi seputar profil Fantasy Football League Anda. Selain melihat statistik personal, Anda juga dapat mengubah info dan foto kapan saja.</p>
                    <?php if($user['paid_member'] == 1 && $user['paid_member_status'] == 1): ?>
                        <h3 class="fr yellow">Akun loe sudah di upgrade</h3>
                    <?php elseif($user['paid_member'] == 1 && $user['paid_member_status'] == 0): ?>
                        <br />
                        <div style="border:#000 solid 1px;padding:5px;background-color:#FDCB02;">
                            <p style="text-align:center;color:#000;">
                                <span style="font-size:16px;font-weight:bold;">AKUN LOE SUDAH EXPIRED</span><br />untuk bisa terus menikmati fasilitas Pro League silahkan bayar sekarang untuk memperpanjang akun loe.
                            </p>
                            <p style="text-align:center;">
                                <a class="button" href="<?=$this->Html->url('/upgrade/paymonthly')?>">
                                    Bayar Disini
                                </a>
                            </p>
                        </div>
                    <?php else: ?>
                        <p class="fr">
                            <a class="button" href="<?=$this->Html->url('/upgrade/member')?>">Upgrade Member</a>
                        </p>
                    <?php endif; ?>
    			</div><!-- end .row-2 -->
                <form class="theForm" action="<?=$this->Html->url('/profile/update')?>" 
                  enctype="multipart/form-data" method="post">
                    <div class="tr avatarBox">
                        <div class="avatar-big">
                           
                            <?php if(strlen($user['avatar_img'])==0 || $user['avatar_img']=='0'):?>
                            <img src="http://graph.facebook.com/<?=$USER_DATA['fb_id']?>/picture" />
                            <?php else:?>
                            <img src="<?=$this->Html->url('/files/120x120_'.$user['avatar_img'])?>" />
                            <?php endif;?>
                        </div>

                        <a href="#popup-upload" class="button" id="btn_upload">Ganti Logo Klab</a>

                    </div>
                    <div class="row">
                        <label>Nama Lengkap</label>
                        <input type="text" name="name" value="<?=h($user['name'])?>" maxlength="30"/>
                    </div><!-- end .row -->
                    <div class="row">
                        <label>Nama Klab</label>
                        <input maxlength="20" type="text" name="team_name" value="<?=h($team['team_name'])?>"/>
                    </div><!-- end .row -->
                    <div class="row">
                        <label>Email</label>
                        <input type="text" name="email" value="<?=h($user['email'])?>" READONLY/>
                    </div><!-- end .row -->
                    <div class="row">
                        <label>Nomor HP</label>
                        <input type="text" name="phone_number" value="<?=h(@$user['phone_number'])?>"/>
                    </div><!-- end .row -->
                    <div class="row">
                        <label>Lokasi</label>
                        <input type="text" name="location" value="<?=h(@$user['location'])?>"/>
                    </div><!-- end .row -->
                   
                    <div class="row">
                        <input type="hidden" name="save" value="1"/>
                        <input type="submit" value="Simpan Perubahan" class="button" />
                    </div><!-- end .row -->
                </form>
			</div><!-- end .content -->
        </div><!-- end #content -->
	<div id="sidebar" class="tr">
	    
	    <div class="widget">
	        <div class="cash-left">
	            <h3 class="red">SISA BUDGET</h3>
	            <h1>ss$ <?=number_format($team_bugdet)?></h1>
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