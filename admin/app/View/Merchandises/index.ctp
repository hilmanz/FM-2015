<h3>
	Merchandises
</h3>
<div class="row">
	<a href="<?=$this->Html->url('/merchandises/create')?>" class="button">Add Merchandise</a>
	<a href="<?=$this->Html->url('/merchandises/categories')?>" class="button">Categories</a>
	<a href="<?=$this->Html->url('/merchandises/orders')?>" class="button">Purchase Orders</a>
	<a href="<?=$this->Html->url('/merchandises/ticketorders')?>" class="button">Ticket Orders</a>
	<a href="<?=$this->Html->url('/merchandises/agent')?>" class="button">Ticket Agent</a>
	<a href="<?=$this->Html->url('/merchandises/ongkir')?>" class="button">Ongkos Kirim</a>
	<a href="<?=$this->Html->url('/merchandises/report')?>" class="button">Report CSV</a>
</div>

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
$edit_url = $this->Html->url('/merchandises/edit/');
?>
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
									response.data[i].MerchandiseItem.id,
									'<img src="<?=$pic_dir?>'+response.data[i].MerchandiseItem.pic+'"/>',
									response.data[i].MerchandiseItem.name,
									response.data[i].MerchandiseCategory.name,
									response.data[i].MerchandiseItem.price_currency,
									response.data[i].MerchandiseItem.price_credit,
									response.data[i].MerchandiseItem.price_money,
									response.data[i].stock,
									response.data[i].MerchandiseItem.n_status,
									'<a href="<?=$edit_url?>'+response.data[i].MerchandiseItem.id+'">Edit</a>&nbsp;\
									<a href="javascript:;" class="delete_rows" data-id="'+response.data[i].MerchandiseItem.id+'">Delete</a>',
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
				{ "sTitle": "" },
				{ "sTitle": "Name" },
				{ "sTitle": "Category" },
				{ "sTitle": "ss$" },
				{ "sTitle": "Credit" },
				{ "sTitle": "IDR"},
				{ "sTitle": "stock"},
				{ "sTitle": "status"},
				{ "sTitle": "Action"}
			]
		} );

		init_event();
	}
	getdata();
	function init_event()
	{
		$('a.delete_rows').on('click', function(){
			var id = $(this).attr('data-id');
			var target_element = $(this).parent().parent();
			api_call("<?=$this->Html->url('/merchandises/remove_items/?id=')?>"+id,
			function(response){
				if(response.status==1){
					$(target_element).hide();
				}
			});
		});
		$('a.paginate_enabled_previous').on('click', function(){
			init_event();
		});

		$('a.paginate_enabled_next').on('click', function(){
			init_event();
		});

		$('.dataTables_filter').on('change', function(){
			init_event();
		});
	}
</script>