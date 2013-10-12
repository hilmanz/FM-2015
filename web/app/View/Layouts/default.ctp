<!DOCTYPE html>
<html>
<head>
	<?php echo $this->Html->charset(); ?>
	<?php echo $this->element('meta'); ?>
	
</head>
<body>
	
	<div id="fb-root"></div>
	<div id="effect"></div>
   	<div id="flag"></div>
 	<div id="body">
        <div id="universal">

                <?php if($USER_IS_LOGIN):?>

          			 <div id="header">
             		  	<a id="logo" href="<?=$this->Html->url('/manage/team')?>" title="SUPER SOCCER - FANTASY FOOTBALL LEAGUE">&nbsp;</a>
                        <div id="user-info">
                           
                            <a href="<?=$this->Html->url('/profile')?>" class="thumb40 fl">
                            <?php if(strlen(@$user['avatar_img'])==0 || @$user['avatar_img']=='0'):?>
                                <img src="http://graph.facebook.com/<?=$USER_DATA['fb_id']?>/picture" />
                            <?php else:?>
                                <img src="<?=$this->Html->url('/files/120x120_'.@$user['avatar_img'])?>" />
                            <?php endif;?>
                            </a>
                            <div class="entry fl">
                                <h3 class="username"><a href="<?=$this->Html->url('/profile')?>">
                                    <?php if(isset($user)):?>
                                    <?=h($user['name'])?>
                                    <?php else:?>
                                    <?=h($USER_DATA['name'])?>
                                    <?php endif;?>
                                </a> |
                                 <a class="logout" href="<?=$this->Html->url('/profile/logout')?>">Keluar</a></h3>
                                <span class="points red"><?=number_format($USER_POINTS)?> Pts</span>
                            </div><!-- end .entry -->
                        </div>
           			 </div><!-- end #header -->
                <?php else:?>
          			 <div id="header">
            		 <a id="logo" href="<?=$this->Html->url('/')?>" title="SUPER SOCCER - FANTASY FOOTBALL LEAGUE">&nbsp;</a>
         		     </div><!-- end #header -->
          		<?php endif;?>
            <?php
                if($USER_IS_LOGIN):
            ?>
                <div id="navigation">
                	<ul>
                    	<li>
                        	<a href="<?=$this->Html->url('/manage/team')?>">Mengelola Tim</a>
                        </li>
                    	<li>
                        	<a href="<?=$this->Html->url('/manage/club')?>">Klab Saya</a>
                        </li>
                    	<li>
                        	<a href="<?=$this->Html->url('/profile')?>">Profil Saya</a>
                        </li>
                    	<li>
                        	<a href="<?=$this->Html->url('/leaderboard/overall')?>">Papan Peringkat</a>
                        </li>
                    	<li>
                        	<a href="<?=$this->Html->url('/market')?>">Bursa Transfer</a>
                        </li>
                    	<li><a href="<?=$this->Html->url('/pages/faq')?>">Bantuan & FAQ</a></li>
                    </ul>
                </div>
			<?php else:?>
                <div id="topnavs" class="topnav">
                    <ul id="topnav">
                        <li><a href="http://www.supersoccer.co.id/" target="_blank" class="nav1">Home</a></li>
                        <li><a href="http://www.supersoccer.co.id/category/supersoccer-tv/" class="nav2">Supersoccer TV</a>
                        <ul>
                               <li><a href="#">Live Streaming Service</a></li>
                                <li><a href="#">Video Collections</a></li>
                        </ul>
                        </li>
                        <li><a href="http://www.supersoccer.co.id/category/liga-inggris/" class="nav3">Liga Inggris</a>
                          <ul>
                            <li><a href="http://www.supersoccer.co.id/category/liga-inggris/berita-liga-inggris/">Berita</a></li>
                            <li><a href="http://www.supersoccer.co.id/category/liga-inggris/ulasan-liga-inggris/">Ulasan</a></li>
                            <li><a href="http://www.supersoccer.co.id/category/liga-inggris/special-features-liga-inggris/">Special Features</a></li>
                            <li><a href="http://www.supersoccer.co.id/category/liga-inggris/preview-pertandingan-liga-inggris/">Preview Pertandingan</a></li>
                            <li><a href="http://www.supersoccer.co.id/category/liga-inggris/laporan-pertandingan-liga-inggris/">Laporan Pertandingan</a></li>
                            <li><a href="http://www.supersoccer.co.id/category/liga-inggris/photo-liga-inggris/">Photo</a></li>
                            <li><a href="http://www.supersoccer.co.id/info-statistik-liga-inggris/">Info Statistik</a></li>
                            <li class="menu2"><a href="http://www.supersoccer.co.id/category/klub-liga-inggris/">Klub Liga Inggris</a>
                                <ul>
                                    <li style="height:12px"><div class="navTop"></div></li>
                                    <li><a href="http://www.supersoccer.co.id/category/liga-inggris/klub-liga-inggris/manchester-united/">Manchester United</a></li>
                                    <li><a href="http://www.supersoccer.co.id/category/liga-inggris/klub-liga-inggris/manchester-city/">Manchester City</a></li>
                                    <li><a href="http://www.supersoccer.co.id/category/liga-inggris/klub-liga-inggris/arsenalfc/">Arsenal</a></li>
                                    <li><a href="http://www.supersoccer.co.id/category/liga-inggris/klub-liga-inggris/chelsea/">Chelsea</a></li>
                                    <li><a href="http://www.supersoccer.co.id/category/liga-inggris/klub-liga-inggris/manchester-united/">Liverpool</a></li>
                                </ul>
                            </li>
                             </ul>
                        </li>
                        <li><a href="http://www.supersoccer.co.id/category/sepakbola-internasional/" class="nav4">Sepakbola Internasional</a>
                             <ul>
                                <li><a href="http://www.supersoccer.co.id/category/sepakbola-internasional/berita-liga-internasional/">Berita</a></li>
                                <li><a href="http://www.supersoccer.co.id/category/sepakbola-internasional/ulasan-liga-internasional/">Ulasan</a></li>
                                <li><a href="http://www.supersoccer.co.id/category/sepakbola-internasional/special-features-liga-internasional/">Special Features</a></li>
                                <li><a href="http://www.supersoccer.co.id/category/sepakbola-internasional/preview-pertandingan-liga-internasional/">Preview Pertandingan</a></li>
                                <li><a href="http://www.supersoccer.co.id/category/sepakbola-internasional/laporan-pertandingan-liga-internasional/">Laporan Pertandingan</a></li>
                                <li><a href="http://www.supersoccer.co.id/photo-sepakbola-internasional/">Photo</a></li>
                                <li><a href="http://www.supersoccer.co.id/info-statistik-sepakbola-internasional/">Info Statistik</a></li>
                                <li class="menu2"><a href="#">Klub</a>
                                    <ul>
                                        <li style="height:12px"><div class="navTop"></div></li>
                                        <li><a href="http://www.supersoccer.co.id/category/sepakbola-internasional/klub-serie-a/">Klub Serie A</a></li>
                                        <li><a href="http://www.supersoccer.co.id/category/sepakbola-internasional/klub-serie-a/internazionale-milano">Internazionale Milano</a></li>
                                        <li><a href="http://www.supersoccer.co.id/category/sepakbola-internasional/klub-serie-a/juventus">Juventus</a></li>
                                        <li><a href="http://www.supersoccer.co.id/category/sepakbola-internasional/klub-serie-a/ac-milan">AC Milan</a></li>
                                        <li><a href="http://www.supersoccer.co.id/category/sepakbola-internasional/klub-la-liga/">Klub La Liga</a></li>
                                        <li><a href="http://www.supersoccer.co.id/category/sepakbola-internasional/klub-la-liga/real-madrid">Real Madrid</a></li>
                                        <li><a href="http://www.supersoccer.co.id/category/sepakbola-internasional/klub-la-liga/barcelona">Barcelona</a></li>
                                        <li><a href="http://www.supersoccer.co.id/category/sepakbola-internasional/klub-bundesliga/">Klub Bundesliga</a></li>
                                        <li><a href="http://www.supersoccer.co.id/category/sepakbola-internasional/klub-bundesliga/bayern-munich">Bayern Munich</a></li>
                                        <li><a href="http://www.supersoccer.co.id/category/sepakbola-internasional/klub-bundesliga/borussia-dortmund">Borussia Dortmund</a></li>
                                    </ul>
                                </li>
                            </ul>
                        </li>
               <!--         <li><a href="http://www.supersoccer.co.id/category/sepakbola-indonesia/" class="nav5">Sepakbola Indonesia</a>
                            <ul>
                            <li><a href="http://www.supersoccer.co.id/category/sepakbola-indonesia/berita-sepakbola-indonesia/">Berita</a></li>
                            <li><a href="http://www.supersoccer.co.id/category/sepakbola-indonesia/ulasan-sepakbola-indonesia/">Ulasan</a></li>
                            <li><a href="http://www.supersoccer.co.id/category/sepakbola-indonesia/special-features-sepakbola-indonesia/">Special Features</a></li>
                            <li><a href="http://www.supersoccer.co.id/category/sepakbola-indonesia/preview-pertandingan-sepakbola-indonesia/">Preview Pertandingan</a></li>
                            <li><a href="http://www.supersoccer.co.id/category/sepakbola-indonesia/laporan-pertandingan-sepakbola-indonesia/">Laporan Pertandingan</a></li>
                            </ul>
                        </li>-->
                        <li><a href="#" class="nav7">Supersoccer Collections</a>
                            <ul>
                            <li><a href="#">Supersoccer Collection</a></li>
                             </ul>
                        </li>
                    </ul>
                </div>
            <?php endif;?>
            <div id="container">
				<?php echo $this->fetch('content'); ?>
            </div><!-- end #container -->
            <div id="footer">
                <div id="footNav">
                  	<p>Copyright &copy; Supersoccer.co.id 2013</p>
                     <?php
                            if($debug>0):
                        ?>
                            <strong>Cheats</strong>
                            <a class="button" href="<?=$this->Html->url('/manage/reset')?>">Reset</a>
                            <a class="button" href="<?=$this->Html->url('/manage/play_match')?>">Play Match</a>
                            <a class="button" href="<?=$this->Html->url('/manage/reset_matches')?>">Reset All Matches</a>
                            <a class="button" href="<?=$this->Html->url('/manage/reset_stats')?>">Reset All Stats </a>
                            <a class="button" href="<?=$this->Html->url('/manage/reset_finance')?>">Reset Finance</a>
                            <a class="button" href="<?=$this->Html->url('/manage/new_user_event')?>">New User Event</a>
                        <?php
                        endif;?>
                </div>
                
            </div>
        </div><!-- end #universal -->

    </div><!-- end #body -->
	<?php echo $this->element('js'); ?>
    
	<?php echo $this->element('sql_dump'); ?>


 <?php if($USER_IS_LOGIN):?>

<div class="popup">
	<div class="popupContainer popup-small" id="popup-notifications">
		<div class="popupHeader">
		</div><!-- END .popupHeader -->
		<div class="popupContent">
			<div class="entry-popup">
				
			</div><!--END .entry-popup-->
		</div><!-- END .popupContent -->
	</div><!-- END .popupContainer -->
</div><!-- END .popup --> 
<!-- end of popups-->	
<script type="text/template" id="tplinbox">
    <div class="loading">
        <h1>Memuat notifikasi</h1>
        <h3>Harap tunggu sebentar..</h3>
        <p><img src="<?=$this->Html->url('/css/fancybox/fancybox_loading@2x.gif')?>"/></p>
    </div>
    <div class="inbox" style="display:none;">
        <h1 style="margin:0;">NOTIFIKASI</h1>
		<table width="100%" border="0" cellspacing="0" cellpadding="0" class="theTable">
			<thead>
				<tr>
					<th>Tgl</th>
					<th>Pesan</th>
				</tr>
			</thead>
			<tbody>
            <%for(var i in messages){%>
                <tr>
                    <td style="width:125px;"><%=messages[i].dt%></td><td><%=messages[i].content%></td>
                </tr>
            <%}%>
			</tbody>
		</table>
    </div>
</script>

<script>

var notifications = {};
var has_read_notification = <?=intval(@$has_read_notification)?>;
 get_notification(0,function(data){
            notifications = data;
            if(has_read_notification==1){
                $("#btn_inbox").html('INBOX');
            }else{
                 if(data.total_new>0){
                    $("#btn_inbox").html('INBOX ('+data.total_new+')');
                }else{
                    $("#btn_inbox").html('INBOX');
                }
            }
           
        });
        $("#btn_inbox").fancybox({
            beforeLoad : function(){
                api_call('<?=$this->Html->url('/game/read_notification')?>',function(response){
                     render_view(tplinbox,"#popup-notifications .popupContent .entry-popup",notifications);
                        $('.loading').hide();
                        $('.inbox').show();
                        $("#btn_inbox").html('INBOX');
                });
               
            },
           
        });
</script>
 <?php endif;?>
 
<script type="text/javascript">
        //<![CDATA[
        var _gaq = _gaq || [];
        _gaq.push(['_setAccount','UA-4622806-45']);
        _gaq.push(['_trackPageview']);
        (function() {
        var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
        ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
        var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
        })();
        //]]>
    </script>
</body>
</html>
