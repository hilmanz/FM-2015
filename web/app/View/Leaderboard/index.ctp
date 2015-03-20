<?php
$monthly = isset($monthly) ? "selected='selected'":"";
$weekly = isset($weekly) ? "selected='selected'":"";
$overall = isset($overall) ? "selected='selected'":"";
$manager = isset($manager) ? "selected='selected'":"";
$pro_weekly = isset($pro_weekly) ? "selected='selected'":"";
?>
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
                            <img src="http://widgets-images.s3.amazonaws.com/football/team/badges_65/<?=str_replace('t','',$club['team_id'])?>.png"/>
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
        <div class="leaderboard-head fl">
         
        	<h1>Papan Peringkat – Minggu ke <?=$matchday?></h1>
            <p>Daftar urutan manajer berdasarkan poin tertinggi.<br />Diperbaharui secara mingguan. </p>
        </div>
        <div class="leaderboard-rank fr">
            <span>Peringkat Anda:</span>
            <h3><?=number_format($rank)?></h3>
            <span>Tier <?=$tier?></span>
        </div>
    </div><!-- end .headbar -->
    <div class="headbar tr">
     
      <div class="fl">
        <form action="<?=$this->Html->url('/leaderboard')?>" 
          method="get" enctype="application/x-www-form-urlencoded">
          <select name="period" class="styled">
              <option value="weekly" <?=$weekly?>>Mingguan</option>
              <option value="monthly" <?=$monthly?>>Bulanan</option>
              <option value="overall" <?=$overall?>>Keseluruhan</option>
              <option value="pro_weekly" <?=$pro_weekly?>>PROLeague - Mingguan</option>
               <option value="manager" <?=@$manager?>>Manager Standings</option>
          </select>
        </form>
      </div>
      
        <?php if($matchday>1):?>
        <div class="fr">
          <a href="<?=$this->Html->url('/leaderboard/?week='.($matchday-1))?>" 
            class="button">Minggu Lalu</a>
        </div>
        <?php endif;?>
        <?php if(($next_match['match']['matchday']-1) > $matchday):?>
        <div class="fr">
          <a href="<?=$this->Html->url('/leaderboard/?week='.($matchday+1))?>" 
            class="button">Minggu Berikutnya</a>
        </div>
        <?php endif;?>
    </div>

    <div id="thecontent">
        <div class="contents">
        	<table width="100%" border="0" cellspacing="0" cellpadding="0" class="theTable footable">
                <thead>
                    <tr>
          				<th data-class="expand">Peringkat</th>
                        <th>Klab</th>
                        <th data-hide="phone,tablet">Manajer</th>
                        <th data-hide="phone" class="alignright">Jumlah Poin</th>
                    </tr>
                </thead>
                <tbody>
                  <?php
                  $params = $this->Paginator->params();
                
                    foreach($team as $n=>$t):
                      $no = $n+1 + (($params['page']-1) * $params['limit']);

                  ?>
                  <tr class="odd">
                    <td class="l-rank"><?=number_format($no)?></td>
                    <td class="l-club"><?=h($t['Team']['team_name'])?></td>
                    <td class="l-manager"><a href="#" title="<?=$t['manager_id']?>"><?=h($t['Manager']['name'])?></a></td>
                    <td class="l-points alignright"><?=number_format($t['Point']['points'])?></td>
                  </tr>
                  <?php
                  endforeach;
                  ?>
                </tbody>
            </table>
            <div class="widget action-button tr">
              <?php
              echo $this->Paginator->prev(__('Sebelumnya'), array(), null, 
                                          array('class' => 'prev'));
              ?>
              <?php
              echo $this->Paginator->next(__('Berikutnya'), array(), null, 
                                      array('class' => 'next'));
              ?>
            </div><!-- end .widget -->
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

<script>
$("select[name='period']").change(function(){
  
  switch($(this).val()){
    case 'monthly':
      document.location="<?=$this->Html->url('/leaderboard/monthly')?>";
    break;
    case 'overall':
      document.location="<?=$this->Html->url('/leaderboard/overall')?>";
    break;
    case 'manager':
      document.location="<?=$this->Html->url('/leaderboard/manager')?>";
    break;
    case 'pro_weekly':
      document.location="<?=$this->Html->url('/leaderboard/pro_weekly')?>";
    break;
    default:
      document.location="<?=$this->Html->url('/leaderboard')?>";
    break;
  }
});
</script>