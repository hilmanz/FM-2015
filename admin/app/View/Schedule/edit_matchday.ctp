<div class="titleBox">
	<h1>Matchday Setup</h1>
</div>
<div class="theContainer">
	<a href="<?=$this->Html->url('/schedule/matchday')?>" class="button">Kembali</a>
<form action="<?=$this->Html->url('/schedule/edit_matchday/'.$rs['MasterMatchday']['id'])?>" 
	method="post" 
enctype="application/x-www-form-urlencoded">
<table width="100%" border="0" cellspacing="0" cellpadding="0" class="dataTable">
	<tr>
		<td colspan="2">Matchday : <?=$rs['MasterMatchday']['matchday']?></td>
	</tr>
	<tr>
		<td>Start Date *</td>
		<td><input type="text" name="start_dt" value="<?=$rs['MasterMatchday']['start_dt']?>"/></td>
	</tr>
	<tr>
		<td>End Date **</td>
		<td><input type="text" name="end_dt" value="<?=$rs['MasterMatchday']['end_dt']?>"/></td>
	</tr>
	<tr>
		<td colspan="2">
			<input type="submit" name="btn" value="Update"/>
			
		</td>
	</tr>
</table>
</form>
<p>
* ) the time when the formation setup should be closed.
* ) the time when the formation setup opened.
</p>
</div>