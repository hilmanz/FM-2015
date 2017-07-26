<div class="titleBox">
	<h1>Welcome to Administration Page</h1>
</div>
<div class="row-2">
	<h3 class="titles">News Ticker</h3>

	<table width="100%">
	<?php 
	if(isset($tickers)):
	foreach($tickers as $ticker):
	?>
	<tr>
	<td><?=$ticker['Ticker']['content']?></td>
	<td><?=$ticker['Ticker']['url']?></td>
	<td><a href="<?=$this->Html->url('/dashboard/delete_tickers?id='.$ticker['Ticker']['id'])?>" class="button">Delete</a></td>
	</tr>
	<?php endforeach;endif;?>
	</table>
	<form action="<?=$this->Html->url('/dashboard/add_ticker')?>" method="post" enctype="application/x-www-form-urlencoded">
	<input type="text" name='content' value="" placeholder="Type New Info"/>
	<input type="text" name='url' value="" placeholder="http://"/>
	<input type="submit" name="btnTickerSaved" value="Save Tickers"/>
	</form>
</div>

<div class="row-2">
	<h3 class="titles">Notifications</h3>
	<table width="100%">
	<?php 
	if(isset($notifications)):
	foreach($notifications as $notif):
	?>
	<tr>
	<td><?=date("d/m/Y H:i:s",strtotime($notif['Notification']['dt']))?></td>
	<td><?=$notif['Notification']['content']?></td>
	<td><?=$notif['Notification']['url']?></td>
	<td><a href="<?=$this->Html->url('/dashboard/delete_notification?id='.$notif['Notification']['id'])?>" class="button">Delete</a></td>
	</tr>
	<?php endforeach;endif;?>
	</table>
	<form action="<?=$this->Html->url('/dashboard/add_notification')?>" 
		  method="post" 
		  enctype="application/x-www-form-urlencoded">
	<input type="text" name='content' value="" placeholder="Type New Info"/>
	<input type="text" name='url' value="" placeholder="http://"/>
		<input type="submit" name="btnNotifSaved" value="Save Notification"/>
	</form>
</div>