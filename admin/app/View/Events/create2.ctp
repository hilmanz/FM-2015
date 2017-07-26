<h3>
	Create Triggered Event
</h3>
<div class="row">
	<a href="<?=$this->Html->url('/events/')?>" class="button">Back to Events</a>
</div>

<div class="row">
<form action="<?=$this->Html->url('/events/create2')?>" method="post" enctype="multipart/form-data">

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
			Scenario
		</td>
		<td>
			<select name="event_type">
				<option value="1">
					Mengeluarkan/Membayarkan sejumlah uang untuk mendapatkan sejumlah uang setelah proses match weekend
				</option>
				<option value="2">Mengeluarkan/Membayarkan sejumlah uang untuk menghindari penalty uang 	 atau point (% dari pemain single/multiple) setelah proses match
      				weekend.
  				</option>
  				<option value="3">Offer Beli satu orang pemain (dengan catatan dia blm punya pemain tersebut)
  				</option>
  				<option value="4">Offer Jual satu orang pemain (dengan catatan di sudah punya pemain tersebut)
  				</option>
			</select>
		</td>
	</tr>
	<tr>
		<td valign="top">
			Cost
		</td>
		<td>
			SS$<input type="text" name="money_cost" value="0"/>
		</td>
	</tr>
	
	<tr>
		<td valign="top">
			Schedule
		</td>
		<td>
			<input type="text" id="datepicker" name="schedule_dt"/>
		</td>
	</tr>
	<tr>
		<td valign="top">
			Expiry Date
		</td>
		<td>
			<input type="text" id="datepicker2" name="expired_dt"/>
		</td>
	</tr>
	<tr>
		<td valign="top">
		Recipients
		</td>
		<td>
			<select name="recipient_type">
				<option value="0">
					All Teams
				</option>
				<option value="1">
					Tier 1 Teams
				</option>
				<option value="2">Tier 2 Teams
  				</option>
  				<option value="3">Tier 3 Teams
  				</option>
  				<option value="4">Tier 4 Teams
  				</option>
  				<option value="5">
					By Original Team
				</option>
			</select>
		</td>
	</tr>
	<tr>
		<td valign="top">
			Email Subject
		</td>
		<td>
			<input type="text" name="email_subject" value=""/>
		</td>
	</tr>
	<tr>
		<td valign="top">
			Email Body
		</td>
		<td>
			<textarea name="email_body_txt" class="wysiwyg" style="width:300px"></textarea>
		</td>
	</tr>
	<tr>
		<td valign="top">
			Email Image
		</td>
		<td>
			<input type="file" name="email_img"/>
		</td>
	</tr>
	<tr>
		<td valign="top">
			`Yes` Text
		</td>
		<td>
			<input type="text" name="yes_txt" value="" size='140'/>
		</td>
	</tr>
	<tr>
		<td valign="top">
			`No` Text
		</td>
		<td>
			<input type="text" name="no_txt" value="" size="140"/>
		</td>
	</tr>
	<tr>
		<td colspan="2">
			<input type="hidden" name="step" value="2"/>
			<input type="submit" name="btn" value="NEXT"/>
		</td>
	</tr>
</table>

</form>
</div>
 <script>
  $(document).ready(function() {
  	$( "#datepicker" ).datepicker();
    $( "#datepicker" ).datepicker("option", "dateFormat", "dd/mm/yy");
     $( "#datepicker" ).datepicker("hide");
     $( "#datepicker2" ).datepicker();
    $( "#datepicker2" ).datepicker("option", "dateFormat", "dd/mm/yy");
     $( "#datepicker2" ).datepicker("hide");
  });
  </script>

  <?php echo $this->element('misc'); ?>