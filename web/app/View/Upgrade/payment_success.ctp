<div id="catalogPage">
    <div class="rowd">
        <?php echo $this->element('infobar'); ?>
    </div>
    <div class="content">
        <div class="row-2">
            <h1 class="red">PRO LEAGUE SUBSCRIPTION</h1>
            <div class="col-content">
                <h3 class="yellow">SELAMAT, AKUN ANDA TELAH BERHASIL DIUPGRADE.</h3>
                <h3>Layanan Pro League berlaku selama 30 hari, dan harus diperpanjang di bulan berikutnya.</h3>
                <p><a class="button2" href="<?=$this->Html->url('/login?league='.$_SESSION['league'])?>"><span class="ico icon-history">&nbsp;</span> Lanjutkan</a></p>
            </div><!-- end .col-content -->
        </div>
    </div>
</div>