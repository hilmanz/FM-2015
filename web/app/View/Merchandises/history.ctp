
<script>
var base_price = {};
var base_coin = {};
</script>
<div id="catalogPage">
      <div class="rowd">
     	 <?php echo $this->element('infobar'); ?>
      </div>
    <div id="thecontent">
        <div class="content pad20">
        	<div class="titlePage">
				<h1 class="yellow">Transaction History</h1>
                <?php 
                $msg = $this->Session->flash();
                if(strlen($msg) > 0):
                ?>
                <div class="error">
                    <?php echo $msg;?>
                </div>
                <?php endif;?>
            </div>
            <div class="rowd">
				<div class="contents">
					<div class="tr widgets">
        			<form 
                        id="frm" method="post" 
                        enctype="application/x-www-form-urlencoded"
        				action="<?=$this->Html->url('/merchandises/cart')?>">
						<table width="100%" border="0" cellspacing="0" cellpadding="0" class="theTable footable">
							<thead>
                            <tr>
                               
                                <th>Tanggal</th>
                                <th>Kode Transaksi</th>
                                <th>Daftar Barang</th>
                                <th>Payment Method</th>
                                <th>Status</th>
                                <th>Aksi</th>
                            </tr>
							</thead>
							<tbody>
                            <?php for($i=0;$i<sizeof($rs);$i++):?>
                            <tr>
                                <td><?=date("d/m/Y",strtotime($rs[$i]['MerchandiseOrder']['order_date']))?></td>
                                <td><?=h($rs[$i]['MerchandiseOrder']['po_number'])?></td>
                                <td>
                                    <?php
                                        $items = unserialize($rs[$i]['MerchandiseOrder']['data']);
                                        for($n=0;$n<sizeof($items);$n++):
                                               
                                    ?>
                                    <div>
                                        <?=h($items[$n]['data']['MerchandiseItem']['name'])?> 
                                        (<?=h($items[$n]['qty'])?>)
                                    </div>
                                    <?php endfor;?>
                                </td>
                                <td>
                                    <?=h(strtoupper($rs[$i]['MerchandiseOrder']['payment_method']))?>
                                </td>
                                <td>
                                    <?php
                                    $n_status = $rs[$i]['MerchandiseOrder']['n_status'];
                                    switch($n_status){
                                        case 1:
                                            echo "Siap Dikirim";
                                        break;
                                        case 2:
                                            echo "Sudah terkirim";
                                        break;
                                        case 3:
                                            echo "COMPLETED";
                                        break;
                                        case 4:
                                            echo "Dibatalkan";
                                        break;
                                        default:
                                            echo "Belum dibayar";
                                        break;
                                    }
                                    ?>
                                </td>
                                <td>
                                    <?php if($n_status==0):?>
                                    <a href="<?=$this->Html->url('/merchandises/pay/ongkir/'.$rs[$i]['MerchandiseOrder']['id'])?>" class="button">Bayar Ongkir</a> 
                                    <?php endif;?>
                                    <a href="<?=$this->Html->url('/merchandises/view_order/'.$rs[$i]['MerchandiseOrder']['id'])?>" class="button">Detil</a>
                                </td>
                            </tr>
                            <?php endfor;?>
							</tbody>
                        </table>
              
                       
                        </form>
        		  </div><!-- end .widget -->
                   <div class="widgets action-button tr">
                      <?php
                      echo $this->Paginator->prev(__('Sebelumnya'), array(), null, 
                                                  array('class' => 'prev'));
                      ?>
                      <?php
                      echo $this->Paginator->next(__('Berikutnya'), array(), null, 
                                              array('class' => 'next'));
                      ?>
                    </div><!-- end .widget -->
				</div><!-- end .contents -->
				 <div class="widget tr catalog-list" style="padding:10px;">
                        Jika ada pertanyaan atau keluhan, kirimkan email ke 
                        <span class="yellow">store@supersoccer.co.id</span>
                    </div>
            </div><!-- end .row-3 -->
        </div><!-- end .content -->
    </div><!-- end #thecontent -->
</div><!-- end #catalogPage -->