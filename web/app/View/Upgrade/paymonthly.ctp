<div id="fillDetailsPage">
      <div class="rowd">
     	 <?php echo $this->element('infobar'); ?>
      </div>
      <div class="rowd">
    <div id="thecontent">
        <?=$this->Session->flash();?>
        <div id="content">
        	<div class="content">
            	<div class="row-2">
                  <h1 class="red">Pro League - Monthly Subscription</h1>
                  <p>Biaya keanggotaan Pro League loe adalah Rp 10,000 / Bulan untuk Satu Liga</p>
                  <br />
                  <table class="theTable footable" border="0" cellpadding="0" cellspacing="0" width="100%">
                    <thead>
                        <tr>
                            <th>No.</th>
                            <th>Tagihan</th>
                            <th width="20%">Liga Terdaftar</th>
                            <th class="alignright">Jumlah yang Harus Dibayar </th>
                        </tr>
                    </thead>
                    <tbody>
                      <tr class="odd">
                        <td class="l-rank">1.</td>
                        <td class="l-club"><?=$invoice['desc']?></td>
                        <td class="l-manager"><?=$invoice['total_team']?></td>
                        <td class="l-points alignright">IDR. <?=number_format($invoice['amount'])?></td>
                      </tr>
                    </tbody>
                </table>
                  <p>
                      <?php if(!isset($rs['data'])): ?>
                        Tidak bisa terhubung dengan eCash Bank Mandiri
                        <a class="button" href="<?=$this->Html->url('/upgrade/paymontly')?>">Refresh</a>
                      <?php elseif($rs['data'] == '#'): ?>
                        Tidak bisa terhubung dengan eCash Bank Mandiri
                        <a class="button" href="<?=$this->Html->url('/upgrade/paymontly')?>">Refresh</a>
                      <?php else: ?>
                        <a class="button" href="<?=$rs['data']?>">Bayar Bulanan</a>
                      <?php endif; ?>
                  </p>
    			</div><!-- end .row-2 -->
			</div><!-- end .content -->
        </div><!-- end #content -->
    </div><!-- end #thecontent -->
</div><!-- end #fillDetailsPage -->