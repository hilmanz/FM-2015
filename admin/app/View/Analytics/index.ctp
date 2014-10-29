<h3>
	User Analytics
</h3>
<div class="row">
	Total : <?=number_format($rs_epl[0][0]['total'])?>
	<div class="active_players_epl">
	</div>
	<!-- end of unique player daily -->
</div>
<div class="row">
	Total : <?=number_format($rs_ita[0][0]['total'])?>
	<div class="active_players_ita">
	</div>
	<!-- end of unique player daily -->
</div>
<div class="row">
	<!-- Unique Player Daily last 14 days-->
	<div class="unique_daily">
	</div>
	<!-- end of unique player daily -->
</div>
<div class="row">
	<!-- Unique Player Weekly last 4 weeks -->
	<div class="unique_weekly">
	</div>
	<!-- end of unique player Weeky last 12 months-->
</div>
<div class="row">
	<!-- Unique Player Monthly -->
	<div class="unique_monthly">
	</div>
	<!-- end of unique player monthly -->
</div>

<div class="row">
	<h4>
		Team Distribution
	</h4>
	<table width="100%" class="table">
		<?php foreach($team_used as $team):?>
		<tr>
			<td><?=$team['name']?></td><td><?=number_format($team['total'])?></td>
		</tr>
		<?php endforeach;?>
	</table>
</div>
<div class="row">
	<h4>
		Top Player Distribution
	</h4>
	<table width="100%" class="table">
		<?php foreach($player_used as $player):?>
		<tr>
			<td><?=$player['name']?></td><td><?=number_format($player['total'])?></td>
		</tr>
		<?php endforeach;?>
	</table>
</div>
<div class="row">
	<h4>
		Formation Distribution
	</h4>
	<table width="100%" class="table">
		<?php foreach($formation_used as $formation):?>
		<tr>
			<td><?=$formation['formation']?></td><td><?=number_format($formation['total'])?></td>
		</tr>
		<?php endforeach;?>
	</table>
</div>

<div class="row">
	<h4>
		Most Buy
	</h4>
	<div>
		<select name="most_buy">
			<option value="0">Choose Transfer Window</option>
			<?php foreach($transfer_window as $tw):?>
			<option value="<?=h($tw['id'])?>"><?=h($tw['window_name'])?></option>
			<?php endforeach;?>
		</select>
	</div>
	<div class="most-buy-content">

	</div>
</div>
<div class="row">
	<h4>
		Most Sale
	</h4>
	<div>
		<select name="most_sale">
			<option value="0">Choose Transfer Window</option>
			<?php foreach($transfer_window as $tw):?>
			<option value="<?=h($tw['id'])?>"><?=h($tw['window_name'])?></option>
			<?php endforeach;?>
		</select>
	</div>
	<div class="most-sold-content">

	</div>
</div>
<script id="tpl_2col" type="text/template">
<h4><%=window.window_name%></h4>
<table width="100%" class="table">
<% for(var i=0; i < data.length; i++){ %>
	<tr>
		<td><%=data[i].name%></td><td><%=number_format(data[i].total)%></td>
	</tr>
<% } %>
</table>
</script>
<!--charts -->
<script>
$(function () {
	active_players_epl();
	active_players_ita();
	unique_user_daily();
	unique_user_weekly();
	unique_user_monthly();
	//daily_registrations();
	most_buy_player(<?=$transfer_window[0]['id']?>);
	most_sold_player(<?=$transfer_window[0]['id']?>);

	$("select[name='most_buy']").on('change',function(e){
		console.log($(this).val());
		most_buy_player($(this).val());
	});
	$("select[name='most_sale']").on('change',function(e){
		console.log($(this).val());
		most_sold_player($(this).val());
	});
	
});

function most_buy_player(window_id){
	$(".most-buy-content").html('Loading...');
	api_call("<?=$this->Html->url('/analytics/most_buy/')?>"+window_id,
		function(response){
			if(response.status==1){
				render_view(tpl_2col,
							".most-buy-content",
							{window:response.window,data:response.data});
			}
	});
}
function most_sold_player(window_id){
	$(".most-sold-content").html('Loading...');
	api_call("<?=$this->Html->url('/analytics/most_sold/')?>"+window_id,
		function(response){
			if(response.status==1){
				render_view(tpl_2col,
							".most-sold-content",
							{window:response.window,data:response.data});
			}
	});
}
function addChart(options){
		console.log(options);
        $(options.target).highcharts({
            title: {
                text: options.title,
                x: -20 //center
            },
           	zoomType: 'x',
            xAxis: {
                categories: options.categories,
                labels: {
                    rotation: -45,
                    align: 'right',
                    style: {
                        fontSize: '13px',
                        fontFamily: 'Verdana, sans-serif'
                    }
                }
            },
            yAxis: {
                title: {
                    text: options.yText
                },
                plotLines: [{
                    value: 0,
                    width: 1,
                    color: '#808080'
                }]
            },
           
            
            series: [{
                name: options.xText,
                data: options.xValue
            }]
        });
    
}
function unique_user_daily(){
	api_call('<?=$this->Html->url("/analytics/unique_user_daily")?>',
			function(response){
				if(response.status==1){
					addChart({
						target:'.unique_daily',
						categories: response.data.categories,
						xValue: response.data.values,
						xText: "Tanggal",
						yText: "Total",
						title: 'Unique Players Daily'
					});
				}	
			});
}
function unique_user_weekly(){
	api_call('<?=$this->Html->url("/analytics/unique_user_weekly")?>',
			function(response){
				if(response.status==1){
					addChart({
						target:'.unique_weekly',
						categories: response.data.categories,
						xValue: response.data.values,
						xText: "Minggu/Tahun",
						yText: "Total",
						title: 'Unique Players Weekly'
					});
				}	
			});
}
function unique_user_monthly(){
	api_call('<?=$this->Html->url("/analytics/unique_user_monthly")?>',
			function(response){
				if(response.status==1){
					addChart({
						target:'.unique_monthly',
						categories: response.data.categories,
						xValue: response.data.values,
						xText: "Bulan/Tahun",
						yText: "Total",
						title: 'Unique Players Monthly'
					});
				}	
			});
}
function daily_registrations(){
	api_call('<?=$this->Html->url("/analytics/daily_registrations")?>',
			function(response){
				if(response.status==1){
					addChart({
						target:'.daily_registrations',
						categories: response.data.categories,
						xValue: response.data.values,
						xText: "Tanggal",
						yText: "Total",
						title: 'Daily Registrations'
					});
				}	
			});
}

function active_players_epl(){
	api_call('<?=$this->Html->url("/analytics/active_players_epl")?>',
			function(response){
				if(response.status==1){
					addChart({
						target:'.active_players_epl',
						categories: response.data.categories,
						xValue: response.data.values,
						xText: "Tanggal",
						yText: "Total",
						title: 'Daily Registration EPL'
					});
				}	
			});
}
function active_players_ita(){
	api_call('<?=$this->Html->url("/analytics/active_players_ita")?>',
			function(response){
				if(response.status==1){
					addChart({
						target:'.active_players_ita',
						categories: response.data.categories,
						xValue: response.data.values,
						xText: "Tanggal",
						yText: "Total",
						title: 'Daily Registration SERIE A'
					});
				}	
			});
}
</script>
<!-- end of charts -->

<?php echo $this->Html->script('jquery.dataTables.min');?>

<script>
	var start = 0;
	var data = [];
	function getdata(){
		api_call("<?=$this->Html->url('/merchandises/get_items/?start=')?>"+start,
			function(response){
				if(response.status==1){
					if(response.data.length > 0){
						for(var i in response.data){
							data.push([
									'<img src="<?=$pic_dir?>'+response.data[i].MerchandiseItem.pic+'"/>',
									response.data[i].MerchandiseItem.name,
									response.data[i].MerchandiseCategory.name,
									response.data[i].MerchandiseItem.price_currency,
									response.data[i].MerchandiseItem.price_credit,
									response.data[i].MerchandiseItem.price_money,
									response.data[i].MerchandiseItem.stock,
									'<a href="<?=$edit_url?>'+response.data[i].MerchandiseItem.id+'">Edit</a>'
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
				{ "sTitle": "" },
				{ "sTitle": "Name" },
				{ "sTitle": "Category" },
				{ "sTitle": "ss$" },
				{ "sTitle": "Credit" },
				{ "sTitle": "IDR"},
				{ "sTitle": "stock"},
				{ "sTitle": "Action"}
			]
		} );
	}
	//getdata();
</script>