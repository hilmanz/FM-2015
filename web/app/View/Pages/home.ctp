
<div id="content">	
	<div id="slider">
	    <div id="banner">
	      <div class="bannerslider">
	          <ul class="slides">
              	<li>
                    <div id="videoIntro">
						<div onclick="thevid=document.getElementById('thevideo'); thevid.style.display='block'; this.style.display='none'"><img style="cursor: pointer;" src="<?=$this->Html->url('/content/cover_video.jpg')?>" alt="" /></div>
						
<div id="thevideo" style="display: none;">
<object width="100%" height="350" classid="clsid:d27cdb6e-ae6d-11cf-96b8-444553540000" codebase="http://download.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=6,0,40,0">
<param name="allowFullScreen" value="true" /><param name="allowscriptaccess" value="always" />
<param name="src" value="http://www.youtube.com/v/r0R7n9hvfKo?version=3&amp;hl=en_US&amp;autoplay=1" />
<param name="allowfullscreen" value="true" /><embed width="100%" height="350" type="application/x-shockwave-flash" src="http://www.youtube.com/v/r0R7n9hvfKo?version=3&amp;hl=en_US&amp;autoplay=1" allowFullScreen="true" allowscriptaccess="always" allowfullscreen="true" />
</object></div>
			
                    </div>		
    			</li>
            
	          </ul>
	      </div><!-- end .slider -->
	    </div><!-- end #banner -->
	</div><!-- end #slider -->
	<div class="rowboxs tr">
		<h1>Super Soccer Football Manager</h1>
		<div class="entry entry-home">
			<img src="<?=$this->Html->url('/content/hadiah.png')?>" style="float:right; margin:0 0 0 10px; width:170px;"/>
			<p><strong>Mau jalan-jalan ke Inggris dan Italia nonton tim favorit elo main? </strong>Atau mau dapat hadiah-hadiah keren seperti jersey retro dan langka, jersey bertandatangan pemain dan hadiah elektronik lainnya? Semuanya bisa elo dapetin di <strong>Super Soccer Football Manager.</strong></p>
			<p>Buat elo yang sukanya mengkritik manajer dan ngerasa lebih pinter dari mereka, buktiin omongan elo dan skill manajerial  elo disini, karena game ini menggunakan semua kriteria yang menentukan sukses seorang manajer di lapangan. <strong>Menghitung lebih dari 64 statistik pemain di pertandingan secara real time,</strong> penilaian elo sebagai ahli taktik akan benar benar diuji, karena poin elo dihitung berdasarkan gol, assist, crossing akurat, passing berbahaya, memotong umpan, menepis bola dan aksi lainnya yang dilakukan pemain sebenarnya di dunia nyata.</p>

			<img src="<?=$this->Html->url('/content/stats.png')?>" style="float:left; margin:0 10px 0 0;"/>
			<p>Setiap <strong>Arteta</strong> memotong umpan, elo dapat 10 poin, setiap kali <strong>Courtois</strong> menepis bola elo dapat 10 poin, setiap kali <strong>Lovren</strong> menghalau bola elo dapat  20 poin. Nah, dengan penghitungan se-realistik ini, apa yang elo lakukan di game ini sama seperti apa yang dilakukan manajer sepakbola di dunia nyata. </p>
			<p>Seperti kata <strong>Roy Evans</strong> manajer legendaris <strong>Liverpool</strong>, semua fans merasa dirinya mampu menjadi manajer, namun dengan <strong>Super Soccer Football Manager</strong>, akhirnya elo harus mempertanggung jawabkan omongan elo. </p>
			<p>Jadi, tunggu apalagi ayo bergabung dan bersaing dengan ratusan ribu manajer lainnya,</p>
			<h4>Its Time to Show What You Know!</h4>
		</div><!-- end .entry -->
    </div><!-- end .rowbox -->
	<div id="listBox">
        <div class="bannerBox last">
	               <a href="<?=$small_banner_2[0]['Banners']['url']?>" target="_blank"><img src="<?=$this->Html->url(Configure::read('avatar_web_url').$small_banner_2[0]['Banners']['banner_file'])?>" /></a>
        </div>
		<div class="bannerBox">
				<a href="<?=$small_banner_1[0]['Banners']['url']?>" target="_blank"><img src="<?=$this->Html->url(Configure::read('avatar_web_url').$small_banner_1[0]['Banners']['banner_file'])?>" /></a>
		</div>
    <div class="widgets tr" id="topManager">
        <h3>Top Manager Minggu Ini</h3>
        <div class="topManager">
            <ul>
            <?php for($i=0;$i<sizeof($team);$i++):?>
				 <li>
          
  					<a class="smallerThumb" href="#">
  					 <img src="<?=$team[$i]['pic']?>"/>
  					</a>
          
				   <h3>
					<span class="manager_name"><?=h($team[$i]['Manager']['name'])?>  </span>
					<span class="team_name"> <?=h($team[$i]['Team']['team_name'])?> </span> &bull; 
					<span class="points"> <?=floatval($team[$i]['Weekly_point']['TotalPoints'])?> Pts</span>
				  </h3>
				</li>
            <?php endfor;?>
            </ul>
        </div><!-- end .entry -->
    </div><!-- end .box -->
    <div class="widgets tr" id="topPlayerWeek">
        <h3>Pemain Top Minggu Ini</h3>
        <div class="topPlayerWeek">
            <ul>
              <?php 
                for($i=0;$i<sizeof($top_players);$i++):
              ?>
                 <li>
                      <a class="smallerThumb" href="#">
                        <img src="http://omo.akamai.opta.net/image.php?custID=c8bb60c8f6d0184c33a87e6f3041b9cc&sport=football&entity=player&description=<?=str_replace('t','',$top_players[$i]['team_id'])?>&dimensions=103x155&id=<?=str_replace('p','',$top_players[$i]['player_id'])?>"/></a>
                       <h3>
						<span><?=h($top_players[$i]['NAME'])?></span>
						<span class="points"><?=number_format($top_players[$i]['total'])?> Pts</span>
                     </h3>
                     
                </li>
                <?php endfor;?>
            </ul>
        </div><!-- end .entry -->
    </div><!-- end .box -->
    </div><!-- end #listBox -->
	
</div><!-- end #content -->

<div id="sidebar">
    <?php if(!isset($disable_login)):?>
    <div id="loginbox" class="widgets tr" >
        <a href="<?=$this->Html->url('/pages/liga');?>" class="boxButton loginBtn">&nbsp;</a>
        <a href="<?=$this->Html->url('/login/register');?>" class="boxButton createAccount">&nbsp;</a>
    </div>
    <?php endif;?>
    <div class="widgets tr" id="gameNews">
        <h3>Game News & Update</h3>
        <div class="gameNews">
            <ul>
              <?php for($i=0;$i<sizeof($tickers);$i++):?>
                <li>
                    <span class="date">
                    <?=date("d/m/Y",strtotime($tickers[$i]['Ticker']['post_dt']))?>
                  </span>
                    <p>
                    <a href="<?=$tickers[$i]['Ticker']['url']?>" target="_blank">
                      <?=h($tickers[$i]['Ticker']['content'])?>
                     </a>
                  </p>
              </li>
                <?php endfor;?>
            </ul>
        </div><!-- end .entry -->
    </div><!-- end .box -->
    <?php for($i=0;$i<sizeof($sidebar_banner);$i++):?>
    <div class="banner300x250">
        <a href="<?=$sidebar_banner[$i]['Banners']['url']?>" target="_blank">
          <img src="<?=$this->Html->url(Configure::read('avatar_web_url').$sidebar_banner[$i]['Banners']['banner_file'])?>" />
        </a>
    </div>
    <?php endfor;?>
    
</div><!-- end #sidebar -->