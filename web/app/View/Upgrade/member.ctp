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
                  <h1 class="red">Upgrade Member</h1>
                  <p>Upgrade keanggotaan loe untuk kesempatan mendapatkan hadiah yang lebih menarik.

                  Biaya keanggotaan adalah Rp.10,000 / bulan per liga.</p>
                  <br />
                  <table class="theTable footable" border="0" cellpadding="0" cellspacing="0" width="100%">
                    <thead>
                        <tr>
                            <th>No.</th>
                            <th>Nama Liga</th>
                            <th width="20%">Tanggal Daftar</th>
                            <th class="alignright">Biaya Perbulan</th>
                            <th class="alignright">Lama Bermain</th>
                            <th class="alignright">Jumlah</th>
                        </tr>
                    </thead>
                    <tbody>
                      <?php 
                        $total_bill = 0; 
                        $j=1; 
                        for ($i=0;$i<count($data_view['league']);$i++): 
                      ?>
                        <tr class="odd">
                          <td><?=$j?></td>
                            <td><?=$data_view['league'][$i]?></td>
                            <td width="20%"><?=$data_view['register_date'][$i]?></td>
                            <td class="alignright"><?=number_format($data_view['charge'][$i])?></td>
                            <td class="alignright"><?=number_format($data_view['period'][$i])?> Bulan</td>
                            <td class="alignright">
                              IDR. <?=number_format($data_view['period'][$i]*$data_view['charge'][$i])?>
                            </td>
                        </tr>
                      <?php
                        $total_bill += $data_view['period'][$i]*$data_view['charge'][$i];
                        $j++; 
                        endfor; 
                      ?>
                        <tr class="odd">
                          <td colspan="5" style="text-align:right;">Total:</td>
                          <td>IDR. <?=number_format($total_bill)?></td>
                        </tr>
                    </tbody>
                </table>
                  <p>
                      <?php if(!isset($rs['data'])): ?>
                        Tidak bisa terhubung dengan eCash Bank Mandiri
                        <a class="button" href="<?=$this->Html->url('/upgrade/member')?>">Refresh</a>
                      <?php elseif($rs['data'] == '#'): ?>
                        Tidak bisa terhubung dengan eCash Bank Mandiri
                        <a class="button" href="<?=$this->Html->url('/upgrade/member')?>">Refresh</a>
                      <?php else: ?>
                        <a class="button" href="<?=$rs['data']?>">Upgrade member</a>
                      <?php endif; ?>
                  </p>
    			</div><!-- end .row-2 -->
			</div><!-- end .content -->
        </div><!-- end #content -->
	<div id="sidebar" class="tr">
	    
	    <div class="widget">
	        <div class="cash-left">
	        </div>
	    </div><!-- end .widget -->
       
	</div><!-- end #sidebar -->
    </div><!-- end #thecontent -->
</div><!-- end #fillDetailsPage -->