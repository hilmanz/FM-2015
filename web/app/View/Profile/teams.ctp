
<div id="fillDetailsPage">
	
    <div id="thecontent">
        <div id="content">
            <div class="content">
                <div class="row-2">
                    <h1 class="red">Pilih Tim Anda</h1>
                    <p>Buat unik nama tim Anda dan jangan lupa pilih logo yang paling mewakili Anda.</p>
                </div><!-- end .row-2 -->
                <div class="select-team">
                    <form class="theForm" action="teams" method="post">
                        <h3>Hei, Sepertinya Anda Menyukai Tim ini:</h3>
                        <div class="row">
                            <a class="teamBox" href="#">
                                <img src="images/team/logo1.png" />
                            </a><!-- end .teamBox -->
                            <a class="teamBox" href="#">
                                <img src="images/team/logo2.png" />
                            </a><!-- end .teamBox -->
                        </div><!-- end .row -->
                        <h3>Atau Anda dapat memilih Tim yang lain:</h3>
                        <div class="row">
                            <?php 
                                $teamList = json_decode($team_list,true);
                                for($i=0;$i < sizeof($teamList);$i++):
                            ?>
                            
                            <a class="teamBox" href="#selectTeam/<?=$o[$i]['uid']?>" title="<?=$teamList[$i]['name']?>">
                                <img src="images/team/logo1.png" />
                            </a><!-- end .teamBox -->
                            <?php endfor;?>
                        </div><!-- end .row -->
                        <div class="row">
                            <label>Personalisasi Nama Tim Anda</label>
                            <input type="text" />
                        </div><!-- end .row -->
                        <div class="row">
                            <input type="hidden" name="team_id" value="<?=$team['uid']?>"/>
                            <input type="hidden" name="fb_id" value="<?=$fb_id?>"/>
                            <input type="hidden" name="create_team" value="1"/>
                            <input type="submit" value="Simpan &amp; Lanjutkan" class="button" />
                        </div><!-- end .row -->
                    </form>
                </div><!-- end .select-team -->
            </div><!-- end .content -->
        </div><!-- end #content -->
	<div id="sidebar" class="tr">
	    <div class="widget">
	        <div class="nav-side">
	            <ul>
                   <li><span>Isi data lengkap Anda</span></li>
                   <li class="current"><span>Pilih Tim</span></li>
                   <li><span>Pilih Pemain</span></li>
                   <li ><span>Pilih Staff</span></li>
	            </ul>
	        </div><!-- end .nav-side -->
	    </div><!-- end .widget -->
	    <div class="widget">
	        <div class="cash-left">
	            <h3 class="red">SISA UANG</h3>
	            <h1>SS$ <?=number_format($team_bugdet)?></h1>
	            <h3 class="red">EST. PENGELUARAN BULANAN</h3>
	            <h1>SS$ 0</h1> 
	        </div>
	    </div><!-- end .widget -->
	</div><!-- end #sidebar -->
    </div><!-- end #thecontent -->
</div><!-- end #fillDetailsPage -->