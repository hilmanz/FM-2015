
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
        <div class="col2">
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
        </div><!-- end .col2 -->
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
            <h3>Pilih Team</h3>
        </div>
        <div class="contents tr">
        	<table width="100%" border="0" cellspacing="0" cellpadding="0" class="theTable footable">
                <thead>
                    <tr>
                        <th data-class="expand"></th>
                        <th>Team Name</th>
                        <th data-hide="phone,tablet" class="aligncenter">Games Played</th>
                        <th data-hide="phone,tablet" class="aligncenter">Games Won</th>
                        <th data-hide="phone,tablet" class="aligncenter">Games Drawn</th>
                        <th data-hide="phone,tablet" class="aligncenter">Games Lost</th>
                        <th data-hide="phone,tablet" class="aligncenter">Games Scored</th>
                        <th data-hide="phone,tablet" class="aligncenter">Goal Conceded</th>
                        <th data-hide="phone,tablet" class="aligncenter">Top Scorer</th>
                        <th data-hide="phone,tablet" class="aligncenter">Top Assist</th>
                        <th data-hide="phone"></th>
                    </tr>
                </thead>
                <tbody>
                <?php 
                if(isset($teams)):
                    foreach($teams as $team):
                        $urlto= $this->Html->url('/market/team/'.$team['team']['team_id']);
                ?>
                  <tr>  
                        <td><a href="<?=$urlto?>"><img style="height:46px" src="http://widgets-images.s3.amazonaws.com/football/team/badges_65/<?=str_replace('t','',$team['team']['team_id'])?>.png"/></a></td>
                        <td><a class="yellow" href="<?=$urlto?>"><?=h($team['team']['name'])?></a></td>
                        <td align="center"><?=number_format(@$team['stats']['games'])?></td>
                        <td align="center"><?=number_format(@$team['stats']['wins'])?></td>
                        <td align="center"><?=number_format(@$team['stats']['draws'])?></td>
                        <td align="center"><?=number_format(@$team['stats']['loses'])?></td>
                        <td align="center"><?=number_format(@$team['stats']['goals'])?></td>
                        <td align="center"><?=number_format(@$team['stats']['condeded'])?></td>
                        <td align="center"><?=h(@$team['stats']['top_score']['name'])?></td>
                        <td align="center"><?=h(@$team['stats']['top_assist']['name'])?></td>
                        <td align="center">
                            <a href="<?=$urlto?>" 
                                class="button">PILIH</a>
                        </td>
                  </tr>
                <?php endforeach;endif;?>
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