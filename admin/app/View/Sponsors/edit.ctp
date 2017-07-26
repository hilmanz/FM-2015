<div class="titleBox">
	<h1>Edit Sponsor</h1>
</div>
<div class="row">
<form action="<?=$this->Html->url('/sponsors/edit/'.$sponsor['id'])?>" method="POST" enctype="application/x-www-form-urlencoded">
	<table width="100%">
		<tr>
			<td>Name</td><td><input type="text" name="name" value="<?=$sponsor['name']?>"></td>
		</tr>
		<tr>
			<td>Expiry Time</td><td><input type="text" name="expiry_time" value="<?=$sponsor['expiry_time']?>"></td>
		</tr>
		<tr>
			<td>Availability</td>
			<td>

				<select name="is_available">
					<option value="1" <?php if($sponsor['is_available']==1): echo 'selected';endif;?>>Yes</option>
					<option value="0" <?php if($sponsor['is_available']==0): echo 'selected';endif;?>>No</option>
				</select>
			</td>
		</tr>
		<tr>
			<td valign="top">Invitation Email </td>
			<td><textarea name="invitation_email" style="width: 762px; height: 272px;"><?=$sponsor['invitation_email']?></textarea></td>
		</tr>
		<tr>
			<td valign="top">Win Bonus Email</td>
			<td>
				<textarea name="win_bonus_email" style="width: 762px; height: 272px;">
					<?=$sponsor['win_bonus_email']?>
				</textarea>
			</td>
		</tr>
		<tr>
			<td valign="top">SMS Text</td>
			<td><textarea name="sms_text" style="width: 762px; height: 272px;"><?=$sponsor['sms_text']?></textarea></td>
		</tr>
		<tr>
			<td colspan="2"><input type="submit" name="btn" value="Save"/></td>
		</tr>
	</table>
</form>
</div>
<div class="row">
	<h4>Perks</h4>
	<div class="perk-list-<?=$sponsor['id']?> perks">
	<?php 
	
	if(isset($sponsor['perks'])):foreach($sponsor['perks'] as $p):
	?>
		<div><a href="#"><?=$p['name']?> (<?=number_format($p['amount'])?>)</a></div>
	<?php endforeach;endif;?>
	</div>
	<a class="btnPerks" href="#popup-perks" data-id="<?=$sponsor['id']?>">Add Perks</a>
</div>
<div class="row">
<h4>Banner Assets</h4>
<table width="100%">
<tr>
<td>ID</td><td>SLOT</td><td>Name</td><td>File</td><td>URL</td><td>Action</td>
</tr>
<?php
if(isset($banners)):
	foreach($banners as $rs):

?>
<tr>
<td><?=$rs['SponsorBanner']['id']?></td>
<td><?=h($rs['SponsorBanner']['slot'])?></td>
<td><?=h($rs['SponsorBanner']['banner_name'])?></td>
<td><img src='<?=$this->Html->url(Configure::read('avatar_web_url').$rs['SponsorBanner']['banner_file'])?>' width="400"/></td>
<td><?=h($rs['SponsorBanner']['url'])?></td>
<td><a href="<?=$this->Html->url('/sponsors/remove_banner/?sponsor_id='.$sponsor['id'].'&id='.$rs['SponsorBanner']['id'])?>" class="button">Remove</a></td>
</tr>
<?php endforeach;endif;?>
</table>

<h4>Upload new Banner</h4>
<form action="<?=$this->Html->url('/sponsors/upload/'.$sponsor['id'])?>" 
		method="post" enctype="multipart/form-data">
<table width="400">
	<tr>
		<td>File</td><td><input type="file" name="file"/></td>
	</tr>
	<tr>
		<td>Name</td><td><input type="text" name="name"/></td>
	</tr>
	<tr>
		<td>URL</td><td><input type="text" name="url" placeholder="http://"/></td>
	</tr>
	<tr>
		<td>SLOT</td>
		<td>
			<select name="slot">
				<option value="MY_CLUB_LONG">Klub Saya (top-right) (460x88 pixels)</option>
				<option value="TEAM_SMALL">Mengelola Tim (bottom-left) (270x100 pixels)</option>
			</select>
		</td>
	</tr>
	<tr>
		<td colspan="2">
			<span style="float:left;width:300px;">File Max : 1 MB</span>
			<input type="submit" name="btn" value="Upload" style="float:right"/>
		</td>
	</tr>
</table>
</form>
</div>

<div id="popup-perks" style="background-color:#e5e5e5;padding:10px; display:none;">
	<?php foreach($perks as $perk):?>
		<div>
			<a href="#" class="btn btn-perk-selected" data-perkID="<?=$perk['id']?>" 
				data-perkName="<?=$perk['name']?>"
				data-amount="<?=$perk['amount']?>"
				>
				<h4><?=$perk['name']?></h4>
			</a>
			<p style="margin-top:-21px;"><?=$perk['description']?></p>
			<p style="margin-top:0px;"><?=number_format($perk['amount'])?></p>
		</div>
	<?php endforeach;?>
</div>

<script>
var current_id = 0;
$('.btnPerks').click(function(e){
	current_id = $(this).attr('data-id');
});
$('.btn-perk-selected').click(function(e){
	var perkID = $(this).attr('data-perkID');
	var perkName = $(this).attr('data-perkName');
	var amount = window.prompt('Amount',0);
	api_post('<?=$this->Html->url('/sponsors/add_perk')?>',
		{sponsor_id:current_id,
		 perkID:perkID,
		 amount:amount},
		function(response){
			if(response.status==1){
				$(".perk-list-"+current_id).append('<div>'+perkName+' ('+number_format(amount)+')'+'</div>');
			}else{
				//do nothing
			}
			$.fancybox.close();
	});
});
$('.btnPerks').fancybox();
</script>