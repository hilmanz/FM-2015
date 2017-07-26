<div id="catalogPage">
      <div class="rowd">
         <?php echo $this->element('infobar'); ?>
      </div>
  
    <div id="thecontent">
        <div class="content pad20">
            <div class="titlePage">
                <h1 class="yellow">Bayar Ongkos Kirim</h1>
                <p>
                    <table width="100%" border="0" cellspacing="0" cellpadding="0" bgcolor="#FFFFFF">
                        <h3>Total : <?=$doku_data['AMOUNT']?></h3>
                        <h3>Payment Method : <?=$payment_method?></h3>
                    </table>
                 </p>
                 <p>
                    <form method="post" action="<?=$action_form?>" enctype="application/x-www-form-urlencoded">
                        <?php
                            foreach ($doku_data as $key => $value) {
                                echo '<input type="hidden" name="'.$key.'" value="'.$value.'" />';
                            }
                        ?>
                        <input class="button" type="submit" value="Bayar">
                    </form>
                 </p>
            </div>
        </div><!-- end .content -->
    </div><!-- end #thecontent -->
</div><!-- end #catalogPage -->