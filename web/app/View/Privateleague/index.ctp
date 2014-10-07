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
                    <h1 class="red">Daftar Private League</h1>
                    <p>Private League adalah tempat elo membuat liga elo sendiri, dan elo bisa mengajak teman-teman elo.
                    Disini satu akun hanya bisa membuat satu private league saja.
                    Dan satu akun hanya bisa ikut satu private league saja.
                    </p>
                    <br />
                    <?php if($join_button): ?>
                        <a class="button fr" href="<?=$this->Html->url('/privateleague/join_confirmation')?>">
                            Join Private League
                        </a> 
                    <?php endif; ?>
                    <a class="button fr" href="<?=$this->Html->url('/privateleague/create')?>">
                        Create Private League
                    </a>
    			</div><!-- end .row-2 -->
                <table class="theTable footable" width="100%" cellpadding="0" cellspacing="0" border="0">
                    <thead>
                        <tr>
                            <th data-class="expand">No.</th>
                            <th>Nama Liga</th>
                            <th data-hide="phone,tablet">Max Player</th>
                            <th data-hide="phone,tablet">Invited Player</th>
                            <th data-hide="phone,tablet">Joined Player</th>
                            <th data-hide="phone,tablet">&nbsp;</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(count($rs) > 0): ?>
                            <?php $i=1; foreach ($rs as $key => $value): ?>
                                <tr>
                                    <td><?=$i?></td>
                                    <td><?=$value['b']['name']?></td>
                                    <td><?=$value['b']['max_player']?></td>
                                    <td><?=$value['a']['total_invited']?></td>
                                    <td><?=$value['a']['total_joined']?></td>
                                    <td>
                                        <a class="button" 
                                        href="<?=$this->Html->url('/privateleague/leaderboard')?>">
                                            Peringkat
                                        </a>
                                    <?php if($rs[0]['b']['user_id'] == $user_id): ?>
                                        <a class="button" 
                                        href="<?=$this->Html->url('/privateleague/pengaturan')?>">
                                            Atur Liga
                                        </a>
                                    <?php endif; ?>
                                    </td>
                                </tr>
                            <?php $i++; endforeach; ?>
                        <?php else: ?>
                            <tr class="odd">
                                <td colspan="6">Loe belom punya private league.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
                </form>
			</div><!-- end .content -->
        </div><!-- end #content -->
    </div><!-- end #thecontent -->
</div><!-- end #fillDetailsPage -->