<div id="fillDetailsPage">
    
    <div id="thecontent">
        <div id="content">
            <div class="content">
                <div class="row-2">
                    <h1 class="red">Pilih Staff Anda</h1>
                    <p>Tentukan sendiri staff mana yang akan Anda rekrut untuk membantu Anda mengelola tim dan klab secara maksimal. Pilih dengan bijak dan sesuaikan dengan kondisi keuangan.</p>
                </div><!-- end .row-2 -->
                <form class="theForm" action="<?=$this->Html->url('/profile/register_staff')?>"
                    method="post" enctype="application/x-www-form-urlencoded">
                    <div class="row-2">
                        <div class=" staff-list" id="available">
                            <?php
                                foreach($officials as $official):
                                    $img = str_replace(' ','_',strtolower($official['name'])).'.jpg';
                            ?>
                            <div class="thumbStaff">
                                <div class="avatar-big">
                                    <img src="<?=$this->Html->url('/content/thumb/'.$img)?>" />
                                </div><!-- end .avatar-big -->
                                <p><?=h($official['name'])?></p>
                                <div>
                                    SS$<?=number_format($official['salary'])?> / minggu
                                </div>
                                <div>
                                    <?php if(@$official['hired']):?>
                                        <a id="staff-<?=$official['id']?>" href="#/dismiss/<?=$official['id']?>" class="button">Direkrut</a>
                                    <?php else:?>
                                        <a id="staff-<?=$official['id']?>" href="#/hire/<?=$official['id']?>" class="button">Pilih</a>
                                    <?php endif;?>
                                </div>
                            </div><!-- end .thumbStaff -->
                            <?php
                                endforeach;
                            ?>
                        </div><!-- end .col2 -->
                      
                    </div><!-- end .row-2 -->
                   <div class="row-2">
                        <input type="hidden" name="fb_id" value="<?=$USER_DATA['fb_id']?>"/>
                        <input type="hidden" name="complete_registration" value="1"/>
                        <input type="submit" value="Simpan &amp; Lanjutkan" class="button" />
                   </div>
                </form>
            </div><!-- end .content -->
        </div><!-- end #content -->
    <div id="sidebar" class="tr">
		<div class="widget tr videotutorial">
			<h2>BINGUNG?</h2>
			<span class="yellow">Mainkan video dibawah untuk petunjuk mengisi form ini</span>
			<div id="vidbox">
				<iframe width="100%" height="200" src="//www.youtube.com/embed/EE_V-mSnH3M" frameborder="0" allowfullscreen></iframe>
			</div><!-- end #vidbox -->
		</div><!-- end .videotutorial -->
        <div class="widget">
            <div class="nav-side">
                <ul>
                   <li><span>Profile Anda</span></li>
                   <li><span>Pilih Tim</span></li>
                   <li><span>Pilih Pemain</span></li>
                   <li class="current"><span>Pilih Staff</span></li>
                  
                </ul>
            </div><!-- end .nav-side -->
        </div><!-- end .widget -->
        <div class="widget">
            <div class="cash-left">
                <h3 class="red">SISA UANG</h3>
                <h1>SS$ <?=number_format($team_bugdet)?></h1>
                <h3 class="red">EST. PENGELUARAN MINGGUAN</h3>
                <h1 class="expenses">SS$ <?=number_format($weekly_salaries)?></h1> 
            </div>
        </div><!-- end .widget -->
    </div><!-- end #sidebar -->
    </div><!-- end #thecontent -->
</div><!-- end #fillDetailsPage -->

<script>
est_expenses = <?=intval($weekly_salaries)?>;
staffs = <?=json_encode($officials)?>;
</script>