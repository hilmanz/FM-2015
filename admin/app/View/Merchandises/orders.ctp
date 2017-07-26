<h3>
	Merchandises
</h3>
<div class="row">
	<a href="<?=$this->Html->url('/merchandises/create')?>" class="button">Add Merchandise</a>
	<a href="<?=$this->Html->url('/merchandises/categories')?>" class="button">Categories</a>
	<a href="<?=$this->Html->url('/merchandises/orders')?>" class="button">Purchase Orders</a>
	<a href="<?=$this->Html->url('/merchandises/ticketorders')?>" class="button">Ticket Orders</a>
</div>
<div class="row">
<a href="javascript:;" class="button" onclick="resetData();getdata(0);">Pending</a>
<a href="javascript:;" class="button" onclick="resetData();getdata(1);">Order Accepted, Ready to Ship</a>
<a href="javascript:;" class="button" onclick="resetData();getdata(2);">Delivered</a>
<a href="javascript:;" class="button" onclick="resetData();getdata(3);">Closed</a>
<a href="javascript:;" class="button" onclick="resetData();getdata(4);">Canceled</a>
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
$view_url = $this->Html->url('/merchandises/view_order/');
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
		api_call("<?=$this->Html->url('/merchandises/get_orders/?start=')?>"+start+'&status='+order_status,
			function(response){
				if(response.status==1){
					if(response.data.length > 0){
						for(var i in response.data){
							var status = 'Pending';
							var item_details = '<a href="<?=$item_url?>'+
												response.data[i].MerchandiseItem.id+'">'+
												response.data[i].MerchandiseItem.id+' - '+response.data[i].MerchandiseItem.name+'</a>';
							
							if(response.data[i].MerchandiseOrder.data!=null){
								item_details = '';
								for(var t in response.data[i].MerchandiseOrder.data){
									console.log(response.data[i].MerchandiseOrder.data[t]);
									try{
										item_details+= "<a href='"+
										item_url+
										response.data[i].MerchandiseOrder.data[t].data.MerchandiseItem.id+"'>"+
										response.data[i].MerchandiseOrder.data[t].data.MerchandiseItem.id
												+' - '+
										response.data[i].MerchandiseOrder.data[t].data.MerchandiseItem.name+
												' x '+
												response.data[i].MerchandiseOrder.data[t].qty+'</a><br/>';
									}catch(e){}
									
								}
							}
							switch(response.data[i].MerchandiseOrder.n_status){
								case '1':
									status = 'Order Accepted, waiting for shipping';
								break;
								case '2':
									status = 'Delivered';
								break;
								case '3':
									status = 'Closed';
								break;
								case '4':
									status = 'Canceled';
								break;
								default:
									status = 'Pending';
								break;
							}
							data.push([
									response.data[i].MerchandiseOrder.order_date,
									response.data[i].MerchandiseOrder.po_number,
									
									item_details,
									(parseInt(response.data[i].MerchandiseOrder.game_team_id)+139670)+' - '+response.data[i].MerchandiseOrder.first_name+' '+response.data[i].MerchandiseOrder.last_name,
									status,
									'<a href="<?=$view_url?>'+response.data[i].MerchandiseOrder.id+'">View</a>'
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