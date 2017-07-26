<h3>FRONTPAGE BANNERS</h3>
<table width="100%">
<tr>
<td>ID</td><td>SLOT</td><td>Name</td><td>File</td><td>URL</td><td>Action</td>
</tr>
<?php
if(isset($results)):
	foreach($results as $rs):
?>
<tr>
<td><?=$rs['Banners']['id']?></td>
<td><?=h($rs['Banners']['slot'])?></td>
<td><?=h($rs['Banners']['banner_name'])?></td>
<td><img src='<?=$this->Html->url(Configure::read('avatar_web_url').$rs['Banners']['banner_file'])?>' width="100"/></td>
<td><?=h($rs['Banners']['url'])?></td>
<td><a href="<?=$this->Html->url('/banners/remove/?id='.$rs['Banners']['id'])?>" class="button">Remove</a></td>
</tr>
<?php endforeach;endif;?>
</table>






<h3>Upload new Banner</h3>
<form action="<?=$this->Html->url('/banners/upload')?>" method="post" enctype="multipart/form-data">
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
				<option value="FRONTPAGE">Frontpage Landing (740x350 pixels)</option>
				<option value="MY_CLUB_LONG">Klub Saya (top-right) (460x88 pixels)</option>
				<option value="TEAM_SMALL">Mengelola Tim (bottom-left) (270x100 pixels)</option>
				<option value="FRONTPAGE_SMALL_MIDDLE">
					Frontpage (bottom-middle) (379x193 pixels)
				</option>
				<option value="FRONTPAGE_SMALL_RIGHT">
					Frontpage (bottom-right) (300x175 pixels)
				</option>
				<option value="FRONTPAGE_SIDEBAR">Frontpage (SIDEBAR) (300x250 pixels)</option>
				<option value="INSIDE_SIDEBAR">ALL PAGES (SIDEBAR) (250x250 pixels)</option>
				<option value="MY_CLUB_INFO">MY CLUB INFO (300x250 pixels)</option>
				<option value="MY_CLUB_LONG2">MY CLUB LONG BANNER BOTTOM (468x60 pixels)</option>
				<option value="PROFILE_BANNER">PROFILE PAGE BANNER (468x60 pixels)</option>
				<option value="LEADERBOARD_TOP">LEADERBOARD TOP (468x60 pixels)</option>
				<option value="LEADERBOARD_BOTTOM">LEADERBOARD BOTTOM (468x60 pixels)</option>
				<option value="TRANSFERWINDOW_TOP">TRANSFER WINDOW TOP (468x60 pixels)</option>
				<option value="TRANSFERWINDOW_BOTTOM">TRANSFER WINDOW BOTTOM (468x60 pixels)</option>
				<option value="CATALOG_SIDEBAR">ONLINE CATALOG SIDEBAR (300x250 pixels)</option>
				
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