<h3>Step 3 : Choose Team and Rewards</h3>
	<div class="progress">Loading </div>
	<table width="100%" border="0" cellspacing="0" cellpadding="0" class="dataTable dataTableTeam" id="tbl">
	
	</table>
	<table width="100%">
		<tr>
			<td valign="top">
				Reward Amounts
			</td>
			<td>
				<input type="text" name="amount" value="0"/>
			</td>
		</tr>
		<tr>
			<td colspan="2">
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
			api_call("<?=$this->Html->url('/events/get_teams/?start=')?>"+start,
				function(response){
					if(response.status==1){
						if(response.data.length > 0){
							for(var i in response.data){
								data.push([
										response.data[i].name,
										response.data[i].email,
										response.data[i].phone_number,
										response.data[i].location,
										response.data[i].team_name,
										response.data[i].original_team,
										response.data[i].points,
										response.data[i].rank,
										'<input type="checkbox" name="targets[]" value="'+response.data[i].game_team_id+'"/>'
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
				"aaData": data,
				"aoColumns": [
					{ "sTitle": "Name" },
					{ "sTitle": "Email" },
					{ "sTitle": "Phone" },
					{ "sTitle": "Location"},
					{ "sTitle": "Team"},
					{ "sTitle": "Original Team"},
					{ "sTitle": "Points", "sClass": "center" },
					{ "sTitle": "Rank", "sClass": "center" },
					{ "sTitle": "Select"}
				]
			} );
		}
		getdata();
	</script>