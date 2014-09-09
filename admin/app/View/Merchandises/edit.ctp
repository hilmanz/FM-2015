<?php
	$array_data	= json_decode($rs['MerchandiseItem']['data'], true);
?>
<form action="<?=$this->Html->url('/merchandises/edit/'.$rs['MerchandiseItem']['id'])?>" method="post" enctype="multipart/form-data">
	<h3>Edit Merchandise</h3>
	<table width="100%">
		<tr>
			<td valign="top">
				Name
			</td>
			<td>
				<input type="text" name="name" value="<?=$rs['MerchandiseItem']['name']?>"/>
			</td>
		</tr>
		<tr>
			<td valign="top">
				Category
			</td>
			<td>
				<select name="merchandise_category_id">
					<?php foreach($categories as $category):
						if($category['MerchandiseCategory']['id'] == $rs['MerchandiseItem']['merchandise_category_id']){
							$opt = ' selected';
						}else{
							$opt = '';
						}
					?>
					<option value="<?=$category['MerchandiseCategory']['id']?>"<?=$opt?>>
						<?=h($category['MerchandiseCategory']['name'])?>
					</option>
					<?php endforeach;?>
				</select>

			</td>
		</tr>
		<tr>
			<td valign="top">
				Item Type
			</td>
			<td>
				<select name="merchandise_type">
					<option value="0">
						Non-Digital Item
					</option>
					<option value="1">
						Digital In-Game Item
					</option>
				</select>
				<script>
				$("select[name=merchandise_type]").val(<?=$rs['MerchandiseItem']['merchandise_type']?>);
				</script>
			</td>
		</tr>
		<tr>
			<td valign="top">
				Perks
			</td>
			<td>
				<select name="perk_id">
					<option value="0">
						N/A
					</option>
					<?php foreach($perks as $perk):?>
					<option value="<?=$perk['MasterPerk']['id']?>">
						<?=h($perk['MasterPerk']['id'])?> - <?=h($perk['MasterPerk']['perk_name'])?> - <?=h($perk['MasterPerk']['name'])?>
					</option>
					<?php endforeach;?>
				</select>
				<script>
				$("select[name=perk_id]").val(<?=$rs['MerchandiseItem']['perk_id']?>);
				</script>
			</td>
		</tr>
		<tr>
			<td valign="top">
				Description
			</td>
			<td>
				<textarea name='description' cols="100" rows="10"><?=$rs['MerchandiseItem']['description']?></textarea>
			</td>
		</tr>
		<tr>
			<td valign="top">
				In-game price
			</td>
			<td>
				ss$ <input type="text" name="price_currency" value="<?=$rs['MerchandiseItem']['price_currency']?>"/>
			</td>
		</tr>
		<tr>
			<td valign="top">
				FM Credits Value
			</td>
			<td>
				<input type="text" name="price_credit" value="<?=$rs['MerchandiseItem']['price_credit']?>"/>
			</td>
		</tr>
		<tr>
			<td valign="top">
				Real price
			</td>
			<td>
				IDR <input type="text" name="price_money" value="<?=$rs['MerchandiseItem']['price_money']?>"/>
			</td>
		</tr>
		<tr>
			<td valign="top">
				Stock
			</td>
			<td>
				<h3><?=intval($rs['MerchandiseItem']['stock'])?></h3>
			</td>
		</tr>
		<tr>
			<td valign="top">
				Add New Stock
			</td>
			<td>
				<input type="text" name="new_stock" value="0"/>
			</td>
		</tr>
		<tr>
			<td valign="top">
				<?php
				$pic = Configure::read('avatar_web_url')."merchandise/thumbs/1_".$rs['MerchandiseItem']['pic'];
				?>
				<img src="<?=$pic?>"/>
				<div>Upload New Picture</div>
			</td>
			<td>
				<input type="file" name="pic"/>
			</td>
		</tr>
		<tr>
			<td valign="top">
				Status
			</td>
			<td>
				<select name="n_status">
					<option value="1" <?php if($rs['MerchandiseItem']['n_status']==1): echo 'selected';endif;?>>
						Publish
					</option>
					<option value="0" <?php if($rs['MerchandiseItem']['n_status']==0): echo 'selected';endif;?>>
						Hide
					</option>
				</select>
				
			</td>
		</tr>
		<tr>
			<td valign="top">
				Enable Admin Fee
			</td>
			<td>
				<table width="100%">
					<tbody>
					<tr>
						<td width="10%">
							<input id="admin_fee_yes" 
							<?=($rs['MerchandiseItem']['enable_admin_fee']==1) ? 'checked="true"' : '' ;?> 
							type="radio" name="enable_admin_fee" value="1" />
						</td>
						<td>
							<label for="admin_fee_yes">
								Yes
							</label>
						</td>
					</tr>
					<tr>
						<td width="10%">
							<input id="admin_fee_no"
							<?=($rs['MerchandiseItem']['enable_admin_fee']==0) ? 'checked="true"' : '' ;?> 
							type="radio" name="enable_admin_fee" value="0" />
						</td>
						<td>
							<label for="admin_fee_no">
								No
							</label>
						</td>
					</tr>
					</tbody>
				</table>
			</td>
		</tr>
		<tr>
			<td valign="top">
				Admin Fee
			</td>
			<td>
				IDR <input type="text" name="admin_fee" value="<?=$rs['MerchandiseItem']['admin_fee']?>"/>
			</td>
		</tr>
		<tr>
			<td valign="top">
				Enable Ongkir
			</td>
			<td>
				<table width="100%">
					<tbody>
						<tr>
							<td width="10%">
								<input id="enable_ongkir_yes" 
								<?=($rs['MerchandiseItem']['enable_ongkir']==1) ? 'checked="true"' : '' ;?>
								type="radio" name="enable_ongkir" value="1" />
							</td>
							<td>
								<label for="enable_ongkir_yes">
									Yes
								</label>
							</td>
						</tr>
						<tr>
							<td width="10%">
								<input id="enable_ongkir_no" 
								<?=($rs['MerchandiseItem']['enable_ongkir']==0) ? 'checked="true"' : '' ;?>
								type="radio" name="enable_ongkir" value="0" />
							</td>
							<td>
								<label for="enable_ongkir_no">
									No
								</label>
							</td>
						</tr>
					</tbody>
				</table>
			</td>
		</tr>
		<tr>
			<td valign="top">
				Perks Non Digital Item
			</td>
			<td>
				<table width="100%">
					<tbody>
						<?php foreach($perks as $perk):?>
						<?php
							$checked = '';
							if(in_array($perk['MasterPerk']['id'], $rs_perks)){
								$checked = 'checked="checked"';
							}
						?>
							<tr>
								<td>
									<input <?=$checked?> id="perk_<?=$perk['MasterPerk']['id']?>" 
									type="checkbox" name="perk_nondigital[]" value="<?=$perk['MasterPerk']['id']?>" />
								</td>
								<td valign="top">
									<label for="perk_<?=$perk['MasterPerk']['id']?>">
										<?=h($perk['MasterPerk']['name'])?>
									</label>
								</td>
							</tr>
						<?php endforeach;?>
					</tbody>
				</table>
			</td>
		</tr>
		<tr>
			<td valign="top">
				Venue
			</td>
			<td>
				<input type="text" name="json_data[venue]" value="<?=@$array_data['venue']?>" />
			</td>
		</tr>
		<tr>
			<td valign="top">
				Tanggal
			</td>
			<td>
				<input type="text" name="json_data[tanggal]" value="<?=@$array_data['tanggal']?>" />
			</td>
		</tr>
		<tr>
			<td valign="top">
				Waktu
			</td>
			<td>
				<input type="text" name="json_data[waktu]" value="<?=@$array_data['waktu']?>" />
			</td>
		</tr>
		<tr>
			<td valign="top">
				Kelas
			</td>
			<td>
				<input type="text" name="json_data[kelas]" value="<?=@$array_data['kelas']?>" />
			</td>
		</tr>
		<tr>
			<td valign="top">
				Section
			</td>
			<td>
				<input type="text" name="json_data[section]" value="<?=@$array_data['section']?>" />
			</td>
		</tr>
		<tr>
			<td valign="top">
				Info
			</td>
			<td>
				<input type="text" name="json_data[info]" value="<?=@$array_data['info']?>" />
			</td>
		</tr>
		<tr>
			<td valign="top">
				Pro Item
			</td>
			<td>
				<select name="is_pro_item">
					<?php
						$yes = '';
						$no = 'selected="selected"';
						if($rs['MerchandiseItem']['is_pro_item'] == 1)
						{
							$yes = 'selected="selected"';
							$no = '';
						}
					?>
					<option <?=$no?> value="0">Tidak</option>
					<option <?=$yes?> value="1">Ya</option>
				</select>
			</td>
		</tr>
		<tr>
			<td valign="top">
				Button Label
			</td>
			<td>
				<input type="text" name="json_data[button_label]" value="<?=@$array_data['button_label']?>" />
			</td>
		</tr>
		<tr>
			<td valign="top">
				Parent Id
			</td>
			<td>
				<select name="parent_id">
					<option value="0">--</option>
					<?php 
						foreach ($merchandise_items as $value):
							$selected = '';
							if($rs['MerchandiseItem']['parent_id'] == $value['MerchandiseItem']['id']):
								$selected = 'selected="selected"';
							endif;
					?>
						<option <?=$selected?> value="<?=$value['MerchandiseItem']['id']?>">
							<?=$value['MerchandiseItem']['name']?>
						</option>
					<?php endforeach?>
				</select>
			</td>
		</tr>
		<tr>
			<td colspan="2">
				<input type="submit" name="btn" value="UPDATE"/>
			</td>
		</tr>
	</table>
</form>