<div class="row">
	<a href="<?=$this->Html->url('/players/banned_list')?>" class="button">Back</a>
</div>
<div id="thecontent">
	<h3>Banned Detail</h3>
    Name : <?=$rs_user[0]['a']['name']?> <br />
    Email : <?=$rs_user[0]['a']['email']?>
	<table width="100%" border="0" cellspacing="0" cellpadding="0">
	<thead>
        <tr>
            <th>Date</th>
            <th class="aligncenter">Banned Type</th>
            <th class="aligncenter">Banned Reason</th>
            <th class="aligncenter">&nbsp;</th>
        </tr>
	 </thead>
	 <tbody>
        <?php if(count($rs) > 0): ?>
            <?php foreach($rs as $key => $value): ?>
            <tr>
                <td>
                	<?=$value['a']['log_dt']?>
                </td>
                <td class="aligncenter"><?=$value['a']['banned_type']?></td>
                <td class="aligncenter"><?=$value['a']['reason']?></td>
                <td class="aligncenter">
                    <a href="<?=$this->Html->url('/players/remove_banned/'.$value['a']['id'].'/'.$value['a']['user_id'])?>">
                        DELETE
                    </a>
                </td>
            </tr>
            <?php endforeach;?>
        <?php else: ?>
            <tr>
                <td colspan="4">
                    No Data
                </td>
            </tr>
        <?php endif; ?>
		</tbody>
    </table>
</div>