<div id="leaderboardPage">
      <div class="rowd">
     	 <?php echo $this->element('infobar'); ?>
      </div>
      <div class="rowd">
        <div class="col2">
            <div class="widget RingkasanKlab" id="RingkasanKlab">
                <div class="entry tr">
                    <table width="100%" border="0" cellspacing="0" cellpadding="0">
                      <tr>
                        <td align="center">
                        	<a href="#">
							<?php if(strlen(@$user['avatar_img'])==0 || @$user['avatar_img']=='0'):?>
                            <img src="http://widgets-images.s3.amazonaws.com/football/team/badges_65/<?=str_replace('t','',$my_team['team_id'])?>.png"/>
                            <?php else:?>
                            <img width="65" src="<?=$this->Html->url('/files/120x120_'.@$user['avatar_img'])?>" />
                            <?php endif;?>
       					 </a>
    					</td>
                        <td>
                            <span>Rank: <strong><?=number_format($USER_RANK)?></strong></span>
                            <span>ss$: <strong><?=number_format($team_bugdet)?></strong></span>
                            <span>Point: <strong><?=number_format($USER_POINTS)?></strong></span>
                        </td>
                        <!--<td colspan="2" class="pendapatan">
                        	
                            <p><span class="ico icon-plus-alt">&nbsp;</span>
                            	<strong class="amounts">ss$ <?=number_format($last_earning)?></strong></p>
                            <p><span class="ico icon-minus-alt">&nbsp;</span>
                            	<strong class="amounts">ss$ <?=number_format($last_expenses)?></strong></p>
                        </td>-->
                      </tr>
                    </table>
                </div><!-- end .entry -->
            </div><!-- end .widget -->
        </div><!-- end .col2 -->
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
      </div><!-- end .rowd -->
    <div class="headbar tr">
        <div class="leaderboard-head">
        	<h1>Bursa Transfer</h1>
            <p>Bursa Transfer <span class="yellow">Supersoccer Football Manager</span> adalah tempat
                dimana kamu bisa beli pemain untuk ditambahkan ke line-up team mu.</p>
        </div>
    </div><!-- end .headbar -->
   
    <div id="thecontent">
        <div class="headbars tr">
            <h3 class="fl"><?=h($club['name'])?></h3>
        	<div class="fr"><a href="<?=$this->Html->url('/market')?>" class="button">KEMBALI</a></div>
        </div>
        <div class="contents tr">
        	<table width="100%" border="0" cellspacing="0" cellpadding="0" class="theTable footable">
                <thead>
                    <tr>
                        <th data-class="expand" width="1"></th>
                        <th>Name</th>
                        <th data-hide="phone,tablet" class="aligncenter">Position</th>
                        <th data-hide="phone,tablet" class="alignright">Gaji</th>
                       
                        <th data-hide="phone,tablet" class="alignright">Nilai Transfer</th>
                        <th data-hide="phone" width="1"></th>
                    </tr>
                </thead>
                <tbody>
                <?php 
                if(isset($players)):
                    foreach($players as $player):
                       
                        if($player['transfer_value']>0):
                            $urlto = $this->Html->url('/market/player/'.$player['uid']);
                            
                            if(intval(@$player['stats']['last_point'])!=0){
                                $player['transfer_value'] = $player['transfer_value'] + 
                                                            getTransferValueBonus(
                                                                floatval(@$player['stats']['performance']),
                                                                $player['transfer_value']);
                            }
                        
                ?>
                  <tr>
                        <td width="1"><a class="thumbPlayersSmall" href="<?=$urlto?>"><img src="http://omo.akamai.opta.net/image.php?custID=c8bb60c8f6d0184c33a87e6f3041b9cc&sport=football&entity=player&description=<?=str_replace('t','',$club['uid'])?>&dimensions=103x155&id=<?=str_replace('p','',$player['uid'])?>"/></a></td>
                        <td><a href="<?=$urlto?>" class="yellow"><?=h($player['name'])?></a></td>
                        <td class="aligncenter"><?=h($player['position'])?></td>
                        <td class="alignright">SS$ <?=number_format($player['salary'])?></td>
                       
                        <td class="alignright">ss$ <?=number_format($player['transfer_value'])?></td>
                        <td>
                            <a href="<?=$urlto?>" 
                                class="button">LIHAT</a>
                        </td>
                  </tr>
                <?php endif;endforeach;endif;?>
                </tbody>
            </table>
            
        </div><!-- end .content -->
        <div class="rows">
             <?php for($i=0;$i<sizeof($long_banner2);$i++):?>
                  <div class="col2">
                      <div class="mediumBanner">
                        <a href="javascript:banner_click(<?=$long_banner2[$i]['Banners']['id']?>,'<?=$long_banner2[$i]['Banners']['url']?>');">
                            <img src="<?=$this->Html->url(Configure::read('avatar_web_url').
                              $long_banner2[$i]['Banners']['banner_file'])?>" />
                        </a>
                      </div><!-- end .mediumBanner -->
                  </div><!-- end .col2 -->
            <?php endfor;?>
        </div><!-- end .rows -->
    </div><!-- end #thecontent -->
</div><!-- end #leaderboardPage -->