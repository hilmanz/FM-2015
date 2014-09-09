<div class="row">
	<a href="<?=$this->Html->url('/merchandises')?>" class="button">Catalog List</a>
</div>
<form action="<?=$this->Html->url('/merchandises/create')?>" method="post" enctype="multipart/form-data">
	<h3>Add Merchandise</h3>
	<table width="100%">
		<tr>
			<td valign="top">
				Name
			</td>
			<td>
				<input type="text" name="name" value=""/>
			</td>
		</tr>
		<tr>
			<td valign="top">
				Category
			</td>
			<td>
				<select name="merchandise_category_id">
					<?php foreach($categories as $category):?>
					<option value="<?=$category['MerchandiseCategory']['id']?>">
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
			</td>
		</tr>
		<tr>
			<td valign="top">
				Description
			</td>
			<td>
				<textarea name='description' cols="100" rows="10"></textarea>
			</td>
		</tr>
		<tr>
			<td valign="top">
				In-game price
			</td>
			<td>
				ss$ <input type="text" name="price_currency" value="0"/>
			</td>
		</tr>
		<tr>
			<td valign="top">
				FM Credits Value
			</td>
			<td>
				<input type="text" name="price_credit" value="0"/>
			</td>
		</tr>
		<tr>
			<td valign="top">
				Real price
			</td>
			<td>
				IDR <input type="text" name="price_money" value="0"/>
			</td>
		</tr>
		<tr>
			<td valign="top">
				Stock
			</td>
			<td>
				<input type="text" name="stock" value="0"/>
			</td>
		</tr>
		<tr>
			<td valign="top">
				Picture
			</td>
			<td>
				<input type="file" name="pic"/>
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
								checked="true" type="radio" name="enable_admin_fee" value="1" />
							</td>
							<td>
								<label for="admin_fee_yes">
									Yes
								</label>
							</td>
						</tr>
						<tr>
							<td width="10%">
								<input id="admin_fee_no" type="radio" name="enable_admin_fee" value="0" />
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
				IDR <input type="text" name="admin_fee" value="0"/>
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
								checked="true" type="radio" name="enable_ongkir" value="1" />
							</td>
							<td>
								<label for="enable_ongkir_yes">
									Yes
								</label>
							</td>
						</tr>
						<tr>
							<td width="10%">
								<input id="enable_ongkir_no" type="radio" name="enable_ongkir" value="0" />
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
							<tr>
								<td width="10%">
									<input id="perk_<?=$perk['MasterPerk']['id']?>" 
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
				<input type="text" name="json_data[venue]" value="" />
			</td>
		</tr>
		<tr>
			<td valign="top">
				Tanggal
			</td>
			<td>
				<input type="text" name="json_data[tanggal]" value="" />
			</td>
		</tr>
		<tr>
			<td valign="top">
				Waktu
			</td>
			<td>
				<input type="text" name="json_data[waktu]" value="" />
			</td>
		</tr>
		<tr>
			<td valign="top">
				Kelas
			</td>
			<td>
				<input type="text" name="json_data[kelas]" value="" />
			</td>
		</tr>
		<tr>
			<td valign="top">
				Section
			</td>
			<td>
				<input type="text" name="json_data[section]" value="" />
			</td>
		</tr>
		<tr>
			<td valign="top">
				Info
			</td>
			<td>
				<input type="text" name="json_data[info]" value="" />
			</td>
		</tr>
		<tr>
			<td valign="top">
				Pro Item
			</td>
			<td>
				<select name="is_pro_item">
					<option value="0">Tidak</option>
					<option value="1">Ya</option>
				</select>
			</td>
		</tr>
		<tr>
			<td valign="top">
				Button Label
			</td>
			<td>
				<input type="text" name="json_data[button_label]" value="" />
			</td>
		</tr>
		<tr>
			<td valign="top">
				Parent Id
			</td>
			<td>
				<select name="parent_id">
					<option value="0">--</option>
					<?php foreach ($merchandise_items as $value): ?>
						<option value="<?=$value['MerchandiseItem']['id']?>">
							<?=$value['MerchandiseItem']['name']?>
						</option>
					<?php endforeach?>
				</select>
			</td>
		</tr>
		<tr>
			<td colspan="2">
				<input type="hidden" name="perks" value=""/>
				<input type="submit" name="btn" value="UPLOAD"/>
			</td>
		</tr>
	</table>
</form>