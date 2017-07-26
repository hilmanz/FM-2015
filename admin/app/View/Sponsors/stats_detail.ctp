<div class="titleBox">
	<h1>`<?=$sponsor['name']?>` Statistics</h1>
</div>
<div class="row-2">
<table width="100%">
	<tr>
		<td>Name</td><td><?=$banner['banner_name']?></td>
	</tr>
	<tr>
		<td>File</td><td><?=$banner['banner_file']?></td>
	</tr>
	<tr>
		<td>URL</td><td><?=$banner['url']?></td>
	</tr>
	<tr>
		<td>SLOT</td>
		<td>
			<?=$banner['slot']?>
		</td>
	</tr>
</table>
</div>
<div class="row-2">
<h4>Monthly Overall</h4>

<table width="100%">
<tr><td>Month</td><td>Year</td><td>Impressions</td><td>Clicks</td></tr>
<?php foreach($overall_monthly as $stats):?>
<tr><td><?=$stats['sponsor_banner_logs']['mt']?></td><td><?=$stats['sponsor_banner_logs']['yr']?></td>
	<td><?=number_format($stats[0]['views'])?></td><td><?=number_format($stats[0]['clicks'])?></td></tr>
<?php endforeach;?>
</table>
</div>

<div class="row-2">
<h4>Overall Per City</h4>
<div class="progress1">
Loading statistics 
</div>
<table width="100%" border="0" cellspacing="0" cellpadding="0" class="dataTable dataTablePlayer" id="tblpercity">
	
</table>
</div>
<?php echo $this->Html->script('jquery.dataTables.min');?>
<script>
var start = 0;
var data = [];
function getCityOverallStats(){
	api_call("<?=$this->Html->url('/sponsors/banner_per_city/'.$banner['id'].'?start=')?>"+start,
		function(response){
			if(response.status==1){
				if(response.data.length > 0){
					for(var i in response.data){
						
						data.push([
								'<a href="<?=$this->Html->url('/sponsors/citystats/'.$sponsor['id'].'/'.$banner['id'].'?location=')?>'+S(response.data[i].location).escapeHTML().s+'">'+
									S(response.data[i].location).escapeHTML().s+'</a>',
								number_format(response.data[i].impressions),
								number_format(response.data[i].clicks),
							]);
					}
					start += 20;
					$(".progress1").html($(".progress1").html()+'.');
					getCityOverallStats();
				}else{
					//draw table
					draw_table(data);
					$(".progress1").hide();
				}
			}
		});
}
function draw_table(data){
	$('#tblpercity').dataTable( {
		"aaData": data,
		"aoColumns": [
			{ "sTitle": "Location" },
			{ "sTitle": "Impressions", "sClass": "center", "sType": "numeric-comma"},
			{ "sTitle": "Clicks", "sClass": "center", "sType": "numeric-comma"},

		]
	} );
}
jQuery.extend( jQuery.fn.dataTableExt.oSort, {
    "formatted-num-pre": function ( a ) {
        a = (a === "-" || a === "") ? 0 : a.replace( /[^\d\-\.]/g, "" );
        return parseFloat( a );
    },
 
    "formatted-num-asc": function ( a, b ) {
        return a - b;
    },
 
    "formatted-num-desc": function ( a, b ) {
        return b - a;
    },
     "numeric-comma-pre": function ( a ) {
        var x = (a == "-") ? 0 : a.replace( /,/, "." );
        return parseFloat( x );
    },
 
    "numeric-comma-asc": function ( a, b ) {
        return ((a < b) ? -1 : ((a > b) ? 1 : 0));
    },
 
    "numeric-comma-desc": function ( a, b ) {
        return ((a < b) ? 1 : ((a > b) ? -1 : 0));
    }
} );
getCityOverallStats();
</script>