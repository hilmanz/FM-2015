<div class="titleBox">
	<h1>Administrators</h1>
</div>
<div class="theContainer">
	<h4>Change <?=$rs['Admin']['username']?>'s Password</h4>
	<form action="<?=$this->Html->url('/dashboard/change_password/'.$rs['Admin']['id'])?>" 
				method="post" enctype="application/x-www-form-urlencoded">
	<table width="100%" border="0" cellspacing="0" cellpadding="0" class="dataTable">
		<tr>
			<td>
				<label>New Password</label>
				<input name="password" type="password" value=""/>
			</td>
		</tr>
		<tr>
			<td><input type="submit" name="btn" value="Save"/></td>
		</tr>
	</table>
	</form>