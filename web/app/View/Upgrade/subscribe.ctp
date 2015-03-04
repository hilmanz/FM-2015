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
                  <h1 class="red"><?=$plan_setting['name']?> - Monthly Subscription</h1>
                  <h4>Kode Transaksi : <?=$po_number?></h4>
                  <p>Biaya keanggotaan Pro League loe adalah Rp <?=number_format($plan_setting['price'])?> / Bulan.</p>
                  <br />
                  <table class="theTable footable" border="0" cellpadding="0" cellspacing="0" width="100%">
                    <thead>
                        <tr>
                            <th>No.</th>
                            <th>Tagihan</th>
                            <th width="20%">Biaya</th>
                            
                        </tr>
                    </thead>
                    <tbody>
                      <tr class="odd">
                        <td class="l-rank">1.</td>
                        <td class="l-club"><?=$plan_setting['name']?> - Monthly Subscription</td>
                        <td class="l-manager"> Rp <?=number_format($plan_setting['price'])?></td>
                        
                      </tr>
                       <tr class="odd">
                        <td class="l-rank">2.</td>
                        <td class="l-club">Admin Fee</td>
                        <td class="l-manager"> Rp <?=number_format($plan_setting['admin_fee'])?></td>
                       
                      </tr>
                       <tr class="odd">
                        <td class="l-rank subtotal" colspan="2">Total Yang harus dibayar : </td>
                        
                        <td class="l-manager"><h4> Rp <?=number_format($plan_setting['price']+$plan_setting['admin_fee'])?></h4></td>
                       
                      </tr>
                    </tbody>

                </table>

                  
    			</div><!-- end .row-2 -->
          <div class="row metode-pembayaran">
            <form action="<?=$this->Html->url('/upgrade/pay')?>"
              method="POST" enctype="application/x-www-form-urlencoded">
              <label>Metode Pembayaran</label>
              <div class="metode">
                <input type="radio" name="payment_method" value="ecash" disabled='disabled'/> Ecash Mandiri (Rupiah)<br />
                <input type="radio" name="payment_method" value="bank_transfer"/> Transfer Bank<br />
                <input type="radio" name="payment_method" value="kartu_kredit"/> Kartu Kredit(Visa/Master)
              </div>
              <div>
                <input type="hidden" name="plan" value="<?=$plan?>"/>
                <input type="hidden" name="trxinfo" value="<?=$trxinfo?>"/>
                <input type="hidden" name="po_number" value="<?=$po_number?>"/>
                <input type="submit" name="btn" value="BAYAR SEKARANG" class="button"/>
              </div>
              </form>
            </div><!-- end .row -->
			</div><!-- end .content -->
        </div><!-- end #content -->
    </div><!-- end #thecontent -->
</div><!-- end #fillDetailsPage -->