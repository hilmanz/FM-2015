<h3>
	Ticket Orders
</h3>
<div class="row">
	<a href="<?=$this->Html->url('/merchandises/create')?>" class="button">Add Merchandise</a>
	<a href="<?=$this->Html->url('/merchandises/categories')?>" class="button">Categories</a>
	<a href="<?=$this->Html->url('/merchandises/orders')?>" class="button">Purchase Orders</a>
	<a href="<?=$this->Html->url('/merchandises/ticketorders')?>" class="button">Ticket Orders</a>
</div>
<div class="progress">Loading data..</div>
<div class="row">
	<table 
		width="100%" border="0" cellspacing="0" cellpadding="0" 
		class="dataTable dataTableTeam" 
		id="tbl">

	</table>
</div>

<?php echo $this->Html->script('jquery.dataTables.min');?>
<?php
$pic_dir = Configure::read('avatar_web_url')."merchandise/thumbs/2_";
$view_url = $this->Html->url('/merchandises/view_order_ticket/');
$item_url = $this->Html->url('/merchandises/edit/');
?>
<script>
	var item_url = "<?=$item_url?>";
	var start = 0;
	var data = [];
	var order_status = 0;
	var tbl = $('#tbl').dataTable( {
		"fnDrawCallback":function(){
			//initClickEvents();
		},
		"aoColumns": [
			{ "sTitle": "Date" },
			{ "sTitle": "PO" },
			{ "sTitle": "Voucher Code" },
			
			{ "sTitle": "ItemName" },
			{ "sTitle": "User" },
			{ "sTitle": "Status" },
			{ "sTitle": "Action"}
		]
	} );
	function resetData(){
		start = 0;
		data = [];
	}
	function getdata(n_status){
		$(".progress").show();
		order_status = n_status;

		api_call("<?=$this->Html->url('/merchandises/get_ticket_orders/?start=')?>"+start+'&status='+order_status,
			function(response){
				if(response.status==1){
					if(response.data.length > 0){
						for(var i in response.data){
							var status = 'Pending';
							var item_details = '<a href="<?=$item_url?>'+
												response.data[i].b.id+'">'+
												response.data[i].b.id+' - '+response.data[i].b.id+'</a>';
							console.log(response.data[i].b.data);
							if(response.data[i].b.data!=null){
								item_details = '';
								for(var t in response.data[i].b.data){
									if(response.data[i].a.merchandise_item_id == 
										response.data[i].b.data[t].data.MerchandiseItem.id){
										try{
											item_details+= "<a href='"+
											item_url+
											response.data[i].b.data[t].data.MerchandiseItem.id+"'>"+
											response.data[i].b.data[t].data.MerchandiseItem.id
													+' - '+
											response.data[i].b.data[t].data.MerchandiseItem.name+
													'</a><br/>';
										}catch(e){}
									}
								}
							}

							switch(response.data[i].a.n_status){
								case '1':
									status = 'Downloaded';
								break;
								default:
									status = 'Pending';
								break;
							}
							data.push([
									response.data[i].a.created_dt,
									response.data[i].b.po_number,
									response.data[i].a.voucher_code,
									
									item_details,
									(parseInt(response.data[i].b.game_team_id)+139670)+' - '+response.data[i].b.first_name+' '+response.data[i].b.last_name,
									status,
									'<a href="<?=$view_url?>'+response.data[i].b.id+'">View</a>'
								]);
						}
						start = response.next_offset;
						$(".progress").html($(".progress").html()+'.');
						getdata(order_status);
					}else{
						//draw table
						draw_table();
						$(".progress").hide();
						
					}
				}
			});
	}
	function draw_table(){
		tbl.fnClearTable();
		tbl.fnAddData(data);
		tbl.fnDraw();
	}
	getdata(0);
</script>