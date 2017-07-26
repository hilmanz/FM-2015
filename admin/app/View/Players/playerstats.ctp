<div class="titleBox">
	<h1>Master Player Stats</h1>
</div>
<div class="theContainer">
<a href="<?=$this->Html->url('/players/playerstats')?>" class="button">Overall Stats</a>
<a href="<?=$this->Html->url('/players/playerweekly')?>" class="button">Weekly Stats</a>
<div class="msg alert">Loading player data....</div>
<table width="100%" border="0" cellspacing="0" cellpadding="0" class="dataTable dataTablePlayer" id="tbl">
	
</table>
</div>
<?php echo $this->Html->script('jquery.dataTables.min');?>
<script>
var start = 0;
var data = [];

function getdata(){
	api_call("<?=$this->Html->url('/players/player_performances/?start=')?>"+start,
		function(response){
			if(response.status==1){
				if(response.data.length > 0){
					for(var i in response.data){
						var uid = response.data[i].uid.split('p').join('');
						var team_id = response.data[i].team_id.split('t').join('');
						data.push([
								'<a class="thumbPlayer"><img src="http://omo.akamai.opta.net/image.php?custID=c8bb60c8f6d0184c33a87e6f3041b9cc&sport=football&entity=player&description='+team_id+'&dimensions=103x155&id='+uid+'"/></a>',
								'<a href="<?=$this->Html->url('/players/playerweekly_details/')?>'+response.data[i].uid+'/0">'+response.data[i].name+'</a>',
								response.data[i].position,
								response.data[i].team_name,
								(response.data[i].salary),
								(response.data[i].transfer_value),
								response.data[i].stats.games,
								response.data[i].stats.passing_and_attacking,
								response.data[i].stats.defending,
								response.data[i].stats.goalkeeping,
								response.data[i].stats.mistakes_and_errors,
								response.data[i].stats.total,

							]);
					}
					start += 20;
					$(".msg").html($(".msg").html()+'.');
					getdata();
				}else{
					//draw table
					draw_table();
					$(".msg").hide();
				}
			}
		});
}

function draw_table(){
	$('#tbl').dataTable( {
		"aaData": data,
		"aoColumns": [
			{ "sTitle": "" },
			{ "sTitle": "Name" },
			{ "sTitle": "Position" },
			{ "sTitle": "Team" },
			{ "sTitle": "Salary" },
			{ "sTitle": "Base Value" },
			{ "sTitle": "Games", "sClass": "center" },
			{ "sTitle": "Pass/Atk", "sClass": "center" },
			{ "sTitle": "Def", "sClass": "center" },
			{ "sTitle": "GK", "sClass": "center" },
			{ "sTitle": "Mistakes/Errors", "sClass": "center" },
			{ "sTitle": "Total Points", "sClass": "center" },
		]
	} );
}

getdata();
</script>