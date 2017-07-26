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
			<td>Name</td>
			<td>Email</td>
			<td>Phone</td>
			<td>Action</td>
		</tr>
		<?php foreach($agents as $agent):?>
		<tr>
			<td><?=h($agent['Agent']['name'])?></td>
			<td><?=h($agent['Agent']['email'])?></td>
			<td><?=h($agent['Agent']['phone'])?></td>
			<td>
				<a href="#" class="button">View Profile</a>
				<a href="#" class="button">View Stocks</a>
				<a href="<?=$this->Html->url('/merchandises/agent_sales/'.$agent['Agent']['id'])?>" class="button">View Sales</a>
			</td>
		</tr>
		<?php endforeach;?>	
	</table>
	<?php if(isset($agents)):?>
	<?php echo $this->Paginator->numbers();?>
	<?php endif;?>
</div>
<div class="row">
	<form action="<?=$this->Html->url('/merchandises/add_agent')?>" method="POST">
	<table class="table">
		<tr>
			<td>Name</td>
			<td><input type="text" name="name" value=""/></td>
		</tr>
		<tr>
			<td>Email</td>
			<td><input type="text" name="email" value=""/></td>
		</tr>
		<tr>
			<td>Password</td>
			<td><input type="text" name="password" value=""/></td>
		</tr>
		<tr>
			<td>Telp</td>
			<td><input type="text" name="phone" value=""/></td>
		</tr>
		<tr>
			<td>Address</td>
			<td><input type="text" name="address" value=""/></td>
		</tr>
		
	</table>
	<input type="submit" name="btn" value="Add Agent"/>
	</form>
</div>
