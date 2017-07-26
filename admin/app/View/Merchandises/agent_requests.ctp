<h3>
	Merchandises
</h3>
<div class="row">
	<a href="<?=$this->Html->url('/merchandises/create')?>" class="button">Add Merchandise</a>
	<a href="<?=$this->Html->url('/merchandises/categories')?>" class="button">Categories</a>
	<a href="<?=$this->Html->url('/merchandises/orders')?>" class="button">Purchase Orders</a>
	<a href="<?=$this->Html->url('/merchandises/ticketorders')?>" class="button">Ticket Orders</a>
	<a href="<?=$this->Html->url('/merchandises/agent')?>" class="button">Ticket Agent</a>
	<a href="<?=$this->Html->url('/merchandises/ongkir')?>" class="button">Ongkos Kirim</a>
</div>
<div class="row">
<a href="<?=$this->Html->url('/merchandises/agent')?>" class="button">Agents</a>
<a href="<?=$this->Html->url('/merchandises/agent/request')?>" class="button">Ticket Requests</a>
<a href="#" class="button">Sales Summary</a>
</div>
<div class="row">
<h3>Ticket Agents</h3>
</div>
<div class="row">
	<table class="table">
		<tr>
			<td>Date</td>
			<td>Agent</td>
			<td>Item</td>
			<td>Request Quota</td>
			<td>Available Stock</td>
			<td>Action</td>
		</tr>
		<?php foreach($rs as $r):?>
		<tr>
			<td><?=date("d-m-Y H:i:s",strtotime($r['AgentRequest']['request_date']))?></td>
			<td><?=h($r['Agent']['name'])?> - <?=h($r['Agent']['email'])?></td>
			<td><?=h($r['Item']['name'])?></td>
			<td><?=number_format($r['AgentRequest']['request_quota'])?></td>
			<td><?=number_format($r['Item']['stock'])?></td>
			<td>
				<a href="<?=$this->Html->url('/merchandises/agent/approve_request/'.$r['AgentRequest']['id'])?>" 
					class="button">
					Approve
				</a>
				<a href="<?=$this->Html->url('/merchandises/agent/reject_request/'.$r['AgentRequest']['id'])?>" 
					class="button">
					Reject
				</a>
			</td>
		</tr>
		<?php endforeach;?>	
	</table>
	<?php if(isset($rs)):?>
	<?php echo $this->Paginator->numbers();?>
	<?php endif;?>
</div>
