<div class="titleBox">
	<h1>Master Player Stats</h1>
</div>
<div class="theContainer">
<a href="<?=$this->Html->url('/players/playerstats')?>" class="button">Overall Stats</a>
<a href="<?=$this->Html->url('/players/playerweekly')?>" class="button">Weekly Stats</a>
<div class="msg alert" style="display:none;">Loading player data....</div>

<div>
<label>Week</label>
<select name="week">
	<option value="0">--WEEK--</option>
	<?php for($i=1;$i<=38;$i++):?>
	<?php
		if($i==$last_week){
			$selected = "selected='true'";
		}else{
			$selected = "";
		}
	?>
		<option value="<?=$i?>" <?=$selected?>><?=$i?></option>
	<?php endfor;?>
</select>
</div>


<table width="100%" border="0" cellspacing="0" cellpadding="0" class="dataTable dataTablePlayer" id="tbl">
	
</table>
</div>
<?php echo $this->Html->script('jquery.dataTables.min');?>
<script>
var start = 0;
var data = [];
var oTable = null;
function getdata(week){
	api_call("<?=$this->Html->url('/players/player_performances_weekly/?start=')?>"+start+'&week='+week,
		function(response){
			if(response.status==1){
				if(response.data.length > 0){
					for(var i in response.data){
						var uid = response.data[i].uid.split('p').join('');
						var team_id = response.data[i].team_id.split('t').join('');
						data.push([
								'<a class="thumbPlayer"><img src="http://omo.akamai.opta.net/image.php?custID=c8bb60c8f6d0184c33a87e6f3041b9cc&sport=football&entity=player&description='+team_id+'&dimensions=103x155&id='+uid+'"/></a>',
								'<a href="<?=$this->Html->url('/players/playerweekly_details/')?>'+response.data[i].uid+'/'+week+'">'+response.data[i].name+'</a>',
								response.data[i].position,
								response.data[i].team_name,
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
					getdata(week);
				}else{
					//draw table
					draw_table();
					$(".msg").hide();
				}
			}
		});
}

function draw_table(){

	
	oTable = $('#tbl').dataTable( {
		"aaData": data,
		"aoColumns": [
			{ "sTitle": "" },
			{ "sTitle": "Name" },
			{ "sTitle": "Position" },
			{ "sTitle": "Team" },
			{ "sTitle": "Games", "sClass": "center" },
			{ "sTitle": "Pass/Atk", "sClass": "center" },
			{ "sTitle": "Def", "sClass": "center" },
			{ "sTitle": "GK", "sClass": "center" },
			{ "sTitle": "Mistakes/Errors", "sClass": "center" },
			{ "sTitle": "Total Points", "sClass": "center" },
		]
	} );
}
$('select[name=week]').change(function(e){
	$('.msg').html('Loading Data');
	$('.msg').show();
	start = 0;
	data = [];
	if(oTable!=null){
		oTable.fnDestroy();
		$("#tbl").html('');
	}
	getdata($(this).val());
});

$('.msg').html('Loading Data');
$('.msg').show();
getdata(<?=$last_week?>);
</script>