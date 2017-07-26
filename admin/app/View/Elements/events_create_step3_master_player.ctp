<h3>Step 3 : Choose Affected Player(s)</h3>
<div class="progress">Loading </div>
<table width="100%" border="0" cellspacing="0" cellpadding="0" class="dataTable dataTableTeam" id="tbl">

</table>

<table width="100%">
	<?php if($data['affected_item']==1):?>
	<tr>
		<td valign="top">
			Money Penalty / Award
		</td>
		<td>
			SS$ <input type="text" name="amount" value="0"/> 
		</td>
	</tr>
	<?php else:?>
	<tr>
		<td valign="top">
			Points Penalty / Award
		</td>
		<td>
			<input type="text" name="amount" value="0"/> % from Original Points.
		</td>
	</tr>
	<?php endif;?>
	<tr>
		<td valign="top">
			Apply only to team who has completing the following events : 
		</td>
		<td>
			<select name='prequisite_event_id'>
				<option value='0'>None</option>
				<?php foreach($triggered as $t):?>
				<option value='<?=$t['TriggeredEvents']['id']?>'>
					<?=$t['TriggeredEvents']['id']?># <?=$t['TriggeredEvents']['name']?>
				</option>
				<?php endforeach;?>
			</select>
		</td>
	</tr>
	<tr>
		<td valign="top">
			Apply only to team who has rejecting the following events : 
		</td>
		<td>
			<select name='rejected_prequisite_event_id'>
				<option value='0'>None</option>
				<?php foreach($triggered as $t):?>
				<option value='<?=$t['TriggeredEvents']['id']?>'>
					<?=$t['TriggeredEvents']['id']?># <?=$t['TriggeredEvents']['name']?>
				</option>
				<?php endforeach;?>
			</select>
		</td>
	</tr>
	<tr>
		<td colspan="2">
			<input type="hidden" name="targets" value=""/>
			<input type="hidden" name="step" value="4"/>
			<input type="submit" name="btn" value="NEXT"/>
		</td>
	</tr>
</table>
<?php echo $this->Html->script('jquery.dataTables.min');?>
<script>
	var start = 0;
	var data = [];
	function getdata(){
		api_call("<?=$this->Html->url('/events/master_players/?start=')?>"+start,
			function(response){
				if(response.status==1){
					if(response.data.length > 0){
						for(var i in response.data){
							data.push([
									response.data[i].name,
									response.data[i].known_name,
									response.data[i].position,
									response.data[i].team_name,
									'<input class="selectbox" type="checkbox" name="the_target" value="'+
											response.data[i].id+'"/>'
								]);
						}
						start = response.next_offset;
						$(".progress").html($(".progress").html()+'.');
						getdata();
					}else{
						//draw table
						draw_table();
						$(".progress").hide();
						
					}
				}
			});
	}
	function draw_table(){
		$('#tbl').dataTable( {
			"fnDrawCallback":function(){
				initClickEvents();
			},

			"aaData": data,
			"aoColumns": [
				{ "sTitle": "Name" },
				{ "sTitle": "Known Name" },
				{ "sTitle": "Position" },
				{ "sTitle": "Club" },
				{ "sTitle": "Select"}
			]
		} );
	}
	function initClickEvents(){
		
		
		console.log('attach events');
		//unbind previous events
		$(".selectbox").unbind("click");
		//attach click events
		$(".selectbox").click(function(e){
			console.log('click');
			var s = $('input[name="targets"]').val();
			//if the id is exists, we remove it at once.
			//because it means that the checkbox were untoggled.
			if(s.match($(this).val())){
				s = s.replace(','+$(this).val(),'');
				s = s.replace($(this).val(),'');
				
			}else{
				if(s.length!=0){
					s+=',';
				}
				s+=$(this).val();
			}
			
			$('input[name="targets"]').val(s);
			e.stopPropagation();
			
		});
		
		
	}
	getdata();
</script>