<div class="row">
<h3>Agen Sales</h3>
<div id="tbl_paginate" class="dataTables_paginate paging_two_button">
	<a href="<?=$this->Html->url('/merchandises/agent')?>" aria-controls="tbl" id="tbl_previous" role="button">
		<< Back
	</a>
</div>
</div>
<div class="row">
	<table class="table">
		<tr>
			<td>Date</td>
			<td>PO Number</td>
			<td>Voucher Code</td>
			<td>Item Name</td>
			<td>User</td>
			<td>Email</td>
		</tr>
		<?php if(isset($agent_sales)):?>
			<?php foreach($agent_sales as $agent):?>
			<tr>
				<td><?=$agent['AgentVoucher']['created_dt']?></td>
				<td><?=$agent['AgentOrder']['po_number']?></td>
				<td><?=$agent['AgentVoucher']['voucher_code']?></td>
				<td><?=$agent['MerchandiseItem']['name']?></td>
				<td><?=$agent['AgentOrder']['first_name']?> <?=$agent['AgentOrder']['last_name']?></td>
				<td><?=$agent['AgentOrder']['email']?></td>
			</tr>
			<?php endforeach;?>
		<?php else: ?>
			<tr>
				<td>No Data</td>
			</tr>
		<?php endif; ?>
	</table>
	<?php if(isset($agent_sales)):?>
	<?php echo $this->Paginator->numbers();?>
	<?php endif;?>
</div>