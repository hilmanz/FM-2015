<div id="catalogPage">
    <div class="rowd">
        <?php echo $this->element('infobar'); ?>
    </div>
    <div class="content">
        <div class="row-2">
            <h1 class="red">PRO LEAGUE SUBSCRIPTION</h1>
            <div class="col-content">
                <h3 class="yellow">TRANSAKSI ANDA PENDING.</h3>
                <h4>Kode Pembayaran : <?=$paymentcode?></h4>
                <p><a class="button2" href="<?=$this->Html->url('/')?>"><span class="ico icon-history">&nbsp;</span> Kembali</a></p>
            </div><!-- end .col-content -->
        </div>
        <div class="rowd">
            <div class="detil-transaksi">
                <p>
                    Silahkan lakukan pembayaran via ATM ke Bank Permata.
                </p>
                <h3>Tata Cara Pembayaran via ATM</h3>
                    <ul>
                        <li>1. Masukkan PIN</li>
                    <li>2. Pilih "TRANSAKSI LAINNYA"</li>
                    <li>3. Pilih "TRANSFER"</li>
                    <li>4. Pilih "KE REK BANK LAIN"</li>
                    <li>5. Masukkan Kode Bank Permata (013) kemudian tekan "Benar"</li>
                    <li>6. Masukkan Jumlah pembayaran sesuai dengan yang ditagihkan (Jumlah yang ditransfer harus sama persis tidak boleh lebih dan kurang).<br/>
                    Jumlah nominal yang tidak sesuai dengan tagihan akan menyebabkan transaksi gagal.</li>
                    <li>7. Masukkan Nomor Rekening tujuan dengan menggunakan Nomor Kode Pembayaran. Contoh : <?=$paymentcode?> lalu tekan "Benar"</li>
                    <li>8. Muncul Layar Konfirmasi Transfer yang berisi nomor rekening tujuan Bank Permata dan Nama beserta jumlah yang dibayar, jika sudah benar, Tekan "Benar".</li>
                    <li>9. Selesai.</li>


                    <h4>NOTE :</h4>
                    <p>Pembayaran hanya bisa dilakukan di ATM atau Internet Banking yang terhubung ke jaringan ATM Bersama, Prima atau ALTO.</p>
                    <p>Pelanggan dapat melakukan transfer melalui ATM ke bank-bank yang telah di tentukan dengan batas maksimal waktu transfer (6) jam sejak saat ini.</p>
                    <p>Daftar Bank untuk Pembayaran Melalui ATM : BCA, MANDIRI, BNI, BII, BRI, DANAMON, PERMATA, MEGA, BUKOPIN, CIMB Niaga, PANIN, dan lain lain.</p>
            </div>
        </div>
    </div>
</div>