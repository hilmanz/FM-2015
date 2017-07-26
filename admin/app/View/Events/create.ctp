<h3>
	Create New Event
</h3>
<div class="row">
	<a href="<?=$this->Html->url('/events/')?>" class="button">Back to Events</a>
</div>

<div class="row">
<form action="<?=$this->Html->url('/events/create')?>" method="post" enctype="multipart/form-data">
<?php if($step==1):?>
	<h3>Step 1 : Define the Event</h3>
	<table width="100%">
		<tr>
			<td valign="top">
				Name
			</td>
			<td>
				<input type="text" name="event_name" value=""/>
			</td>
		</tr>
		<tr>
			<td valign="top">
				Affecting
			</td>
			<td>
				<select name="event_type">
					<option value="1">Player Data</option>
					<option value="2">Master Data</option>
				</select>
			</td>
		</tr>		
		<tr>
			<td colspan="2">
				<input type="hidden" name="step" value="2"/>
				<input type="submit" name="btn" value="NEXT"/>
			</td>
		</tr>
	</table>

<?php elseif($step==2):?>
	<h3>Step 2 : Define the Target</h3>
	<table width="100%">
		<tr>
			<td valign="top">
				Affecting
			</td>
			<td>
				<!--1-> individual team 2-> all teams 3-> by tier 4-> individual player teams-->
				<?php
				if($data['event_type']==1):
				?>
				<select name="target_type">
					<option value="1">Individual Team</option>
					<option value="2">all teams</option>
					<option value="3">by Ranking Tier</option>
					<option value="4">by Original Player</option>
					<option value="5">by Original Team</option>
				</select>
				<?php else:?>
				<select name="target_type">
					<option value="1">Individual Original Team</option>
					<option value="4">Individual Original Players</option>
				</select>
				<?php endif;?>
			</td>
		</tr>
		<tr>
			<td valign="top">
				Affected Attributes
			</td>
			<td>
				<!--1-> individual team 2-> all teams 3-> by tier 4-> individual player-->
				<select name="affected_item">
					<?php if($data['event_type']==1):?>
					<option value="1">Money</option>
					<?php else:?>
					<option value="1">Money</option>
					<option value="2">Points</option>
					<?php endif;?>
				</select>
			</td>
		</tr>		
		<tr>
			<td colspan="2">
				<input type="hidden" name="step" value="3"/>
				<input type="submit" name="btn" value="NEXT"/>
			</td>
		</tr>
	</table>
<?php elseif($step==3):?>
	<?php 
	
	if($data['event_type']==1 && $data['target_type']==1){
		echo $this->element('events_create_step3',array('data'=>$data));
	}elseif($data['event_type']==1 && $data['target_type']==3){
		echo $this->element('events_create_step3_tier',array('data'=>$data));
	}elseif($data['event_type']==1 && $data['target_type']==2){
		echo $this->element('events_create_step3_all_team',array('data'=>$data));
	}elseif($data['event_type']==1 && $data['target_type']==4){
		echo $this->element('events_create_step3_original_player',array('data'=>$data));
	}elseif($data['event_type']==1 && $data['target_type']==5){
		echo $this->element('events_create_step3_original_team',array('data'=>$data));
	}elseif($data['event_type']==2 && $data['target_type']==4){
		echo $this->element('events_create_step3_master_player',array('data'=>$data));
	}elseif($data['event_type']==2 && $data['target_type']==1){
		
		echo $this->element('events_create_step3_master_team',array('data'=>$data));
	}else{
		echo 'Invalid Target';
	}
	?>

<?php elseif($step==4):?>
	<h3>Step 4 : Schedule and Email Notification</h3>
	<table width="100%">
		<tr>
			<td valign="top">
				Name Appear on Report
			</td>
			<td>
				<input type="text" name="name_appear_on_report" value=""/>
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
				<textarea name="email_body_txt" cols="100" rows="20" class="wysiwyg"></textarea>
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
				Apply Event on 
			</td>
			<td>
				<input type="text" id="datepicker" name="scheduledt"/>
			</td>
		</tr>
		<tr>
			<td colspan="2">
				<input type="hidden" name="step" value="5"/>
				<input type="submit" name="btn" value="NEXT"/>
			</td>
		</tr>
	</table>
	  <?php echo $this->element('misc'); ?>
	  <script>
	  $(function() {
	  	$( "#datepicker" ).datepicker();
	    $( "#datepicker" ).datepicker("option", "dateFormat", "dd/mm/yy");
	  });
	  </script>
<?php elseif($step==5):?>
	<h3>Step 5 : Confirmation</h3>
	<table width="100%">
		<tr>
			<td valign="top">
				Event
			</td>
			<td>
				<?=$data['event_name']?>
			</td>
		</tr>
		<tr>
			<td valign="top">
				Apply to
			</td>
			<td>
				<?php
					if($data['event_type']==1){
						echo "Player Data";
					}else{
						echo "Master Data";
					}
				?>
			</td>
		</tr>
		<tr>
			<td valign="top">
				Target Type
			</td>
			<td>
				<!--1-> individual team 2-> all teams 3-> by tier 4-> individual player-->
				<?php
					switch($data['target_type']){
						case 1:
							echo 'Individual Team';
						break;
						case 2:
							echo 'All Teams';
						break;
						case 3:
							echo 'By Tier';
						break;
						case 4:
							echo 'Individual Player';
						break;
					}
				?>
			</td>
		</tr>
		<tr>
			<td valign="top">
				<?php
					switch($data['target_type']){
						case 1:
							echo 'Selected Team';
						break;
						case 3:
							echo 'Tier';
						break;
						case 4:
							echo 'Selected Player';
						break;
					}
				?>
			</td>
			<td>
				<?=@$data['target_str']?>
			</td>
		</tr>
		<tr>
			<td valign="top">
				Reward Type
			</td>
			<td>
				<?php
					if($data['affected_item']==1){
						echo "Money";
					}else{
						echo "Points";
					}
				?>
			</td>
		</tr>
		<tr>
			<td valign="top">
				Amount
			</td>
			<td>
				<?=number_format($data['amount'])?>
			</td>
		</tr>
		<tr>
			<td valign="top">
				The Name to be appear on Report
			</td>
			<td>
				<?=$data['name_appear_on_report']?>
			</td>
		</tr>
		<tr>
			<td valign="top">
				Email Subject
			</td>
			<td>
				<?=$data['email_subject']?>
			</td>
		</tr>
		<tr>
			<td valign="top">
				Email Body
			</td>
			<td>
				<?=$data['email_body_plain']?>
			</td>
		</tr>
		<tr>
			<td valign="top">
				Email Image
			</td>
			<td>
				<img src="<?=Configure::read('avatar_web_url').$data['email_body_img']?>"
			</td>
		</tr>
		<tr>
			<td valign="top">
				Schedule
			</td>
			<td>
				<?=date("d/m/Y",strtotime($data['schedule_dt'].' 00:00:00'))?>
			</td>
		</tr>
		<tr>
			<td valign="top">
				Prequisite Event Id
			</td>
			<td>
				<?=intval(@$data['prequisite_event_id'])?>
			</td>
		</tr>
		<tr>
			<td colspan="2">
				<input type="hidden" name="step" value="6"/>
				<input type="submit" name="btn" value="SAVE EVENT"/>
			</td>
		</tr>
	</table>

<?php elseif($step==6):?>
	<h3>Event Creation Completed !</h3>
<?php endif;?>

</form>
</div>

