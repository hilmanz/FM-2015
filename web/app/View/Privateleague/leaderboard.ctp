<?php
$monthly = isset($monthly) ? "selected='selected'":"";
$weekly = isset($weekly) ? "selected='selected'":"";
$overall = isset($overall) ? "selected='selected'":"";

?>
<div id="leaderboardPage">
      <div class="rowd">
         <?php echo $this->element('infobar'); ?>
      </div>
    <div class="headbar tr">
        <div class="leaderboard-head fl">
            <h1>Papan Peringkat Private League Keseluruhan</h1>
            <p>Daftar urutan manajer berdasarkan poin tertinggi secara keseluruhan.<br />Diperbaharui secara mingguan. </p>
        </div>
    </div><!-- end .headbar -->
    <div class="headbar tr">  
      <div class="fl">
        <form action="<?=$this->Html->url('/leaderboard')?>" 
          method="get" enctype="application/x-www-form-urlencoded">
          <select name="period" class="styled">
              <option value="weekly">Mingguan</option>
          </select>
        </form>
      </div>
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
                    if(count($rs_leaderboard) > 0):
                    $no=1;
                    foreach ($rs_leaderboard as $key => $value):
                  ?>
                    <tr class="odd">
                      <td class="l-rank"><?=number_format($no)?></td>
                      <td class="l-club"><?=h($value['c']['team_name'])?></td>
                      <td class="l-manager"><?=h($value['d']['name'])?></td>
                      <td class="l-points alignright"><?=number_format($value['b']['total_points'])?></td>
                    </tr>
                  <?php
                  $no++;
                  endforeach;
                  else:
                  ?>
                    <tr class="odd">
                      <td colspan="4">Belum Ada Data</td>
                    </tr>
                  <?php 
                  endif;
                  ?>
                </tbody>
            </table>
        </div><!-- end .content -->
    </div><!-- end #thecontent -->
</div><!-- end #leaderboardPage -->