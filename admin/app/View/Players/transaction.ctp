<div class="row">
	<a href="<?=$this->Html->url('/players/view/'.$user_id)?>" class="button">Back</a>
</div>
<div id="thecontent">
	<h3>Transaction</h3>
	<table width="100%" border="0" cellspacing="0" cellpadding="0">
	<thead>
        <tr>
            <th>Date</th>
            <th class="aligncenter">Transaction Name</th>
            <th class="aligncenter">Details</th>
            <th class="aligncenter">Amount</th>
        </tr>
	 </thead>
	 <tbody>
        <?php
            $overall_coins = 0;
            foreach($rs_transaction as $key => $value):
        ?>
        <tr>
            <td>
            	<?=$value['game_transactions']['transaction_dt']?>
            </td>
            <td class="aligncenter"><?=$value['game_transactions']['transaction_name']?></td>
            <td class="aligncenter"><?=$value['game_transactions']['details']?></td>
            <td class="aligncenter"><?=$value['game_transactions']['amount']?></td>
        </tr>
        <?php $overall_coins += $value['game_transactions']['amount']; ?>
        <?php endforeach;?>
        <tr>
            <td>
            	&nbsp;
            </td>
            <td class="aligncenter">&nbsp;</td>
            <td class="aligncenter">&nbsp;</td>
            <td class="aligncenter"><?=number_format($overall_coins)?></td>
        </tr>
		</tbody>
    </table>
</div>