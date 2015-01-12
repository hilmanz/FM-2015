<h3>
	Report CSV
</h3>
<div class="row">
	<a href="<?=$this->Html->url('/merchandises/create')?>" class="button">Add Merchandise</a>
	<a href="<?=$this->Html->url('/merchandises/categories')?>" class="button">Categories</a>
	<a href="<?=$this->Html->url('/merchandises/orders')?>" class="button">Purchase Orders</a>
	<a href="<?=$this->Html->url('/merchandises/ticketorders')?>" class="button">Ticket Orders</a>
	<a href="<?=$this->Html->url('/merchandises/agent')?>" class="button">Ticket Agent</a>
	<a href="<?=$this->Html->url('/merchandises/ongkir')?>" class="button">Ongkos Kirim</a>
	<a href="<?=$this->Html->url('/merchandises/report')?>" class="button">Report CSV</a>
</div>
<div class="row">
	<?=$this->Session->flash()?>
</div>
<div class="row">
	<form action="<?=$this->Html->url('/merchandises/export')?>" method="POST">
	<table class="table" width="70%">
		<tr>
			<td>Payment Method</td>
			<td>
				<table>
					<tr>
						<td>
							<input id="ecash" type="checkbox" name="payment_method[]" value="ecash">
						</td>
						<td>
							<label for="ecash">Ecash</label>
						</td>
					</tr>
					<tr>
						<td>
							<input id="coins" type="checkbox" name="payment_method[]" value="coins">
						</td>
						<td>
							<label for="coins">Coins</label>
						</td>
					</tr>
				</table>
			</td>
		</tr>
		<tr>
			<td>Order Status</td>
			<td>
				<table width="100%">
					<tr>
						<td width="10%">
							<input id="pending" type="checkbox" name="n_status[]" value="0">
						</td>
						<td>
							<label for="pending">Pending</label>
						</td>
					</tr>
					<tr>
						<td>
							<input id="ready" type="checkbox" name="n_status[]" value="1">
						</td>
						<td>
							<label for="ready">Order Accepted, Ready to Ship</label>
						</td>
					</tr>
					<tr>
						<td>
							<input id="delivered" type="checkbox" name="n_status[]" value="2">
						</td>
						<td>
							<label for="delivered">Delivered</label>
						</td>
					</tr>
				</table>
			</td>
		</tr>
		<tr>
			<?php
				$start_date = explode('-', $date_start);
				$end_date 	= explode('-', $date_end);
			?>
			<td>Date From</td>
			<td>
				<select name="date_from">
					<option value="">Date</option>
					<?php for($i=1;$i<=31;$i++): ?>
						<?php if(intval($start_date[0]) == $i): ?>
							<option value="<?=$i?>" selected="selected" ><?=$i?></option>
						<?php else: ?>
							<option value="<?=$i?>"><?=$i?></option>
						<?php endif; ?>
					<?php endfor; ?>
				</select>
				-
				<select name="month_from">
					<option value="">Month</option>
					<?php for($i=1;$i<=12;$i++): ?>
						<?php if(intval($start_date[1]) == $i): ?>
							<option value="<?=$i?>" selected="selected" ><?=$i?></option>
						<?php else: ?>
							<option value="<?=$i?>"><?=$i?></option>
						<?php endif; ?>
					<?php endfor; ?>
				</select>
				-
				<select name="year_from">
					<option value="">Year</option>
					<?php for($i=$start_date[2];$i<=$end_date[2];$i++): ?>
						<?php if($start_date[2] == $i): ?>
							<option value="<?=$i?>" selected="selected" ><?=$i?></option>
						<?php else: ?>
							<option value="<?=$i?>"><?=$i?></option>
						<?php endif; ?>
					<?php endfor; ?>
				</select>
			</td>
		</tr>
		<tr>
			<td>Date End</td>
			<td>
				<select name="date_end">
					<option value="">Date</option>
					<?php for($i=1;$i<=31;$i++): ?>
						<?php if(intval($end_date[0]) == $i): ?>
							<option value="<?=$i?>" selected="selected" ><?=$i?></option>
						<?php else: ?>
							<option value="<?=$i?>"><?=$i?></option>
						<?php endif; ?>
					<?php endfor; ?>
				</select>
				-
				<select name="month_end">
					<option value="">Month</option>
					<?php for($i=1;$i<=12;$i++): ?>
						<?php if(intval($end_date[1]) == $i): ?>
							<option value="<?=$i?>" selected="selected" ><?=$i?></option>
						<?php else: ?>
							<option value="<?=$i?>"><?=$i?></option>
						<?php endif; ?>
					<?php endfor; ?>
				</select>
				-
				<select name="year_end">
					<option value="">Year</option>
					<?php for($i=$start_date[2];$i<=$end_date[2];$i++): ?>
						<?php if($end_date[2] == $i): ?>
							<option value="<?=$i?>" selected="selected" ><?=$i?></option>
						<?php else: ?>
							<option value="<?=$i?>"><?=$i?></option>
						<?php endif; ?>
					<?php endfor; ?>
				</select>
			</td>
		</tr>
	</table>
	<input type="submit" name="btn" value="Generate Report"/>
	</form>
</div>