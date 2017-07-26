<?php

function isAllowedStats($mods,$name){
    foreach($mods as $m){
        if($name==$m){
            return true;
        }
    }
}
?>
<div id="fillDetailsPage">
     <?php echo $this->element('infobar'); ?>
    <div id="thecontent">
        <div id="contents">
            <div class="content">
                <div class="row-2">
                    <h1 class="red">Rincian Pertandingan</h1>
                    <p>Info lebih rinci dari satu pertandingan. Tak hanya skor akhir, detail statistik dari masing-masing pemain yang turun ke lapangan juga ditampilkan secara lengkap. </p>
                </div><!-- end .row-2 -->
                <div class="row">
                    <table width="100%">
                        <tr>
                            <td align="center" width="45%"><h3><?=$o['data'][0]['name']?></h3></td>
                            <td align="center"><h3><?=$o['data'][0]['score']?> - <?=$o['data'][1]['score']?></h3></td>
                            <td align="center" width="45%"><h3><?=$o['data'][1]['name']?></h3></td>
                        </tr>
                        <tr>
                            <td valign="top">
                                <table width="100%">
                                    <?php foreach($o['data'][0]['overall_stats'] as $stats=>$val):
                                            if(isAllowedStats($mods,$stats)):
                                    ?>
                                    <tr>
                                        <td><?=ucfirst(str_replace("_"," ",$stats))?></td>
                                        <td width="100" align="center"><?=$val?></td>
                                    </tr>
                                    <?php endif;endforeach;?>
                                </table>
                                <table width="100%">
                                    <?php foreach($o['data'][0]['player_stats'] as $player=>$data):?>
                                    <tr style="background-color:#353535;color:white;padding:5px">
                                        <td><?=$data['name']?></td>
                                        <td width="100" align="center"><?=$data['position']?></td>
                                    </tr>
                                        <?php
                                            foreach($data['stats'] as $stats=>$val):
                                                if(isAllowedStats($mods,$stats)):
                                        ?>
                                        <tr>
                                            <td><?=ucfirst(str_replace("_"," ",$stats))?></td>
                                            <td width="100" align="center"><?=$val?></td>
                                        </tr>
                                        <?php endif;endforeach;?>
                                    <?php endforeach;?>
                                </table>
                            </td>
                            <td>
                                
                            </td>
                            <td valign="top">
                                <table width="100%">
                                    <?php foreach($o['data'][1]['overall_stats'] as $stats=>$val):
                                            if(isAllowedStats($mods,$stats)):
                                    ?>
                                    <tr>
                                        <td><?=ucfirst(str_replace("_"," ",$stats))?></td><td><?=$val?></td>
                                    </tr>
                                    <?php endif;endforeach;?>
                                </table>
                                <table width="100%">
                                    
                                    <?php foreach($o['data'][1]['player_stats'] as $player=>$data):
                                            
                                    ?>
                                    <tr style="background-color:#353535;color:white;padding:5px">
                                        <td><?=$data['name']?></td><td><?=$data['position']?></td>
                                    </tr>
                                        <?php
                                            foreach($data['stats'] as $stats=>$val):
                                                if(isAllowedStats($mods,$stats)):
                                        ?>
                                        <tr>
                                            <td><?=ucfirst(str_replace("_"," ",$stats))?></td><td><?=$val?></td>
                                        </tr>
                                        <?php endif;endforeach;?>
                                    <?php endforeach;?>
                                </table>
                            </td>
                        </tr>
                    </table>
                    <div class="row">
                        <a href="<?=$this->Html->url('/match')?>" class="button">Kembali ke Daftar</a>
                    </div>
                </div>
            </div><!-- end .content -->
        </div><!-- end #content -->
    <div id="sidebar" class="tr">
        
    </div><!-- end #sidebar -->
    </div><!-- end #thecontent -->
</div><!-- end #fillDetailsPage -->