<div id="fillDetailsPage">
     <?php echo $this->element('infobar'); ?>
    <div id="thecontent">
        <div id="content">
            <div class="content">
                <div class="row-2">
                    <h1 class="red">Perekrutan Staff</h1>
                    <p>Tentukan sendiri staff mana yang akan Anda rekrut untuk membantu Anda mengelola tim dan klab secara maksimal. Pilih dengan bijak dan sesuaikan dengan kondisi keuangan.</p>
                </div><!-- end .row-2 -->
                <form class="theForm">
                    <div class="row-2">
                        <div class=" staff-list" id="available">
                            <?php
                                foreach($officials as $official):
                                    $img = str_replace(' ','_',strtolower($official['staff_type'])).'.jpg';
                            ?>
                            <div class="thumbStaff">
                                <div class="avatar-big">
                                    <img src="<?=$official['image']?>" />
                                </div><!-- end .avatar-big -->
                                <p><?=h($official['name'])?></p>
                                <p class="rank">
                                    <?php for($i=0;$i<$official['rank'];$i++):?>
                                        <img src="<?=$this->Html->url('/images/Icon_star.gif')?>"/>
                                    <?php endfor;?>
                                </p>
                                <div>
                                    SS$ <?=number_format($official['salary'])?> / minggu
                                </div>
                                <?php if(isset($official['tactics'])):?>
                                <div>
                                    <h4>Available Tactic(s) :</h4> <?=$official['tactics']?>
                                </div>
                                <?php endif;?>
                                <div>
                                    <?php if(!isset($official['hashed_data'])){
                                        $official['hashed_data'] = encrypt_param(json_encode(array('staff_id'=>$official['id'])));
                                    }?>
                                    <?php if($official['price'] > 0):?>
                                    <div class="price">
                                        <h4 class="yellow"><?=number_format($official['price'])?> Coins</h4>
                                    </div>
                                    <?php else:?>
                                    <div class="price">
                                        <h4 class="yellow">FREE</h4>
                                    </div>
                                    <?php endif;?>
                                    <a href="<?=$this->Html->url('/manage/hiring_staff?hire=1&id='.$official['id'].'&r='.$official['hashed_data'])?>" class="button">
                                        Rekrut
                                    </a>
                                </div>
                            </div><!-- end .thumbStaff -->
                            <?php
                                endforeach;
                            ?>
                        </div><!-- end .col2 -->
                      
                    </div><!-- end .row-2 -->
                   
                </form>
            </div><!-- end .content -->
        </div><!-- end #content -->
    <div id="sidebar" class="tr">
        <div class="widget">
            <h3>Informasi</h3>
            <p>
                Setiap perekrutan akan dikenakan biaya hiring sebesar Gaji dari staff yang bersangkutan.
            </p>
        </div><!-- end .widget -->
    </div><!-- end #sidebar -->
    </div><!-- end #thecontent -->
</div><!-- end #fillDetailsPage -->