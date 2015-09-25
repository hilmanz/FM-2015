<?php
$role = array(
    'dof'=>array('name'=>'Director of Football','effect'=>'Player Transfer Discounts'),
    'marketing'=>array('name'=>'Marketing Manager','effect'=>'Increase Revenue, Increase player transfer offers,Increase Sponsorship revenue'),
    'pr'=>array('name'=>'Public Relations','effect'=>'Increase Revenue, Increase Stadium Occupancy'),
    'scout'=>array('name'=>'Scout','effect'=>'Player Statistic Accuracy'),
    'security'=>array('name'=>'Security Officer','effect'=>'Increase ticket revenue, reduce the affect of security related events'),
    'gk_coach'=>array('name'=>'Goalkeeper Coach','effect'=>'Increase player fitness, player points, tactics'),
    'def_coach'=>array('name'=>'Defender Coach','effect'=>'Increase player fitness, player points, tactics'),
    'mid_coach'=>array('name'=>'Midfielder Coach','effect'=>'Increase player fitness, player points, tactics'),
    'fw_coach'=>array('name'=>'Forward Coach','effect'=>'Increase player fitness, player points, tactics'),
    'physio'=>array('name'=>'Forward Coach','effect'=>'Increase player fitness'),
);
$coach_tactics = array('gk_coach'=>array(7),
                        'def_coach'=>array(5,6,7),
                        'mid_coach'=>array(1,2,3,4),
                        'fw_coach'=>array(1,2,3,4,6));

?>
<div id="fillDetailsPage">
     <?php echo $this->element('infobar'); ?>
    <div id="thecontent">
        <div id="content">
            <div class="content">
                <div class="row-2">
                    <h1 class="red">Staff</h1>
                    <p>Tentukan sendiri staff mana yang akan Anda rekrut untuk membantu Anda mengelola tim dan klab secara maksimal. Pilih dengan bijak dan sesuaikan dengan kondisi keuangan.</p>
                </div><!-- end .row-2 -->
                <?php
                $msg = $this->Session->flash();
                if(strlen($msg)>0):
                ?>
                
                        <?=$msg?>
               
                <?php endif;?>
                <form class="theForm">
                    <div class="row-2">
                        <div class=" staff-list" id="available">
                            <?php
                            foreach($role as $r=>$v):
                                $hired = false;
                                foreach($officials as $official):
                                    if($r == $official['staff_type']):
                                        $hired = true;
                                ?>
                                <div class="thumbStaff">
                                    <div class="avatar-big">
                                        <img src="<?=$this->Html->url('/content/thumb/'.$r.".jpg")?>" />
                                    </div><!-- end .avatar-big -->
                                    <h3><?=h($official['name'])?></h3>
                                    <p><?=h($role[$official['staff_type']]['name'])?></p>
                                    <p class="rank">
                                        <?php for($i=0;$i<$official['rank'];$i++):?>
                                            <img src="<?=$this->Html->url('/images/Icon_star.gif')?>"/>
                                        <?php endfor;?>
                                    </p>
                                    <div>
                                        SS$ <?=number_format($official['salary'])?> / minggu
                                    </div>
                                    <div>
                                        
                                            <a href="?dismiss=1&id=<?=$official['staff_id']?>" class="button">Berhentikan</a>
                                       
                                    </div>
                                </div><!-- end .thumbStaff -->
                                <?php
                                    break;
                                ?> 
    
                            <?php endif;?>

                            <?php
                                endforeach;
                            ?>
                            <?php if(!$hired):?>
                                 <div class="thumbStaff">
                                    <div class="avatar-big">
                                        <img src="<?=$this->Html->url('/content/thumb/'.$r.'.jpg')?>" />
                                    </div><!-- end .avatar-big -->
                                   
                                    <h3><?=h($v['name'])?></h3>
                                    <p><?=$v['effect']?></p>
                                   
                                    <div>
                                    
                                    <a href="<?=$this->Html->url('/manage/hiring_staff/?type='.$r)?>" class="button">Rekrut</a>
                                       
                                    </div>
                                </div><!-- end .thumbStaff -->
                            <?php endif;?>
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
            <div class="cash-left">
                <h3 class="red">INFORMASI</h3>
                <p>Memiliki staff sangat berguna untuk klub loe.</p>
            </div>
        </div><!-- end .widget -->
    </div><!-- end #sidebar -->
    </div><!-- end #thecontent -->
</div><!-- end #fillDetailsPage -->