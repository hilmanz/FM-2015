<div class="titleBox">
	<h1>Payment</h1>
</div>
<div class="theContainer">

	<table class="dataTable" border="0" cellpadding="0" cellspacing="0" width="100%">
		<tbody>
			<tr class="odd">
				<td><strong>Pro League User:</strong> <?=number_format($pro_league_user)?></td>
				<td>Active Pro League User: <?=number_format($pro_league_user)?></td>
				<td>Trivia Game Player:<?=number_format($total_trivia)?></td>
			</tr>
			<tr class="even">
				<td>Unlock Catalog 01: <?=number_format($total_cat1)?></td>
				<td>Unlock Catalog 02: <?=number_format($total_cat2)?></td>
				<td>Unlock Catalog 03: <?=number_format($total_cat3)?></td>
			</tr>
		</tbody>
	</table>

<div class="row">
	<table 
		width="100%" border="0" cellspacing="0" cellpadding="0" 
		class="dataTable dataTableTeam" 
		id="tbl">

</table>
</div>

</div>

<?php echo $this->Html->script('jquery.dataTables.min');?>
<script>
	var start = 0;
	var data = [];
	function getdata(){
		api_call("<?=$this->Html->url('/payment/get_payment/?start=')?>"+start,
			function(response){
				if(response.status==1){
					if(response.data.length > 0){
						for(var i in response.data){
							data.push([
									response.data[i].MembershipTransaction.id,
									response.data[i].MembershipTransaction.fb_id,
									response.data[i].User.name,
									response.data[i].MembershipTransaction.transaction_name,
									response.data[i].MembershipTransaction.transaction_type,
									response.data[i].MembershipTransaction.transaction_dt,
									response.data[i].MembershipTransaction.hp,
									response.data[i].MembershipTransaction.league
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
				//initClickEvents();
			},

			"aaData": data,
			"aoColumns": [
				{ "sTitle": "ID" },
				{ "sTitle": "FB ID" },
				{ "sTitle": "Name" },
				{ "sTitle": "Transaction Name" },
				{ "sTitle": "Transaction Type" },
				{ "sTitle": "Date"},
				{ "sTitle": "Handphone"},
				{ "sTitle": "League"}
			]
		} );
	}
	getdata();
</script>