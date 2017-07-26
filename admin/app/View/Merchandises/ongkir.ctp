<h3>
	Merchandises - Ongkos Kirim
</h3>
<div class="row">
	<a href="<?=$this->Html->url('/merchandises/create')?>" class="button">Add Merchandise</a>
	<a href="<?=$this->Html->url('/merchandises/categories')?>" class="button">Categories</a>
	<a href="<?=$this->Html->url('/merchandises/orders')?>" class="button">Purchase Orders</a>
	<a href="<?=$this->Html->url('/merchandises/update_ongkir')?>" class="button">Update Ongkir</a>
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

$view_url = $this->Html->url('/merchandises/view_ongkir/');

?>
<script>
	var view_url = "<?=$this->Html->url('/merchandises/edit_ongkir/')?>";
	var start = 0;
	var data = [];
	
	var tbl = $('#tbl').dataTable( {
		"fnDrawCallback":function(){
			//initClickEvents();
		},
		"aoColumns": [
			{ "sTitle": "Lokasi" },
			{ "sTitle": "Harga per kg" },
			{ "sTitle": "Action"}
		]
	} );
	function resetData(){
		start = 0;
		data = [];
	}
	function getdata(n_status){
		$(".progress").show();
		
		api_call("<?=$this->Html->url('/merchandises/get_ongkir/?start=')?>"+start,
			function(response){
				if(response.status==1){
					if(response.data.length > 0){
						for(var i in response.data){
							console.log(response.data[i]);
							data.push([
									response.data[i].Ongkir.kecamatan+' - '+response.data[i].Ongkir.city,
									'Rp.'+number_format(response.data[i].Ongkir.cost),
									'<a href="<?=$view_url?>'+response.data[i].Ongkir.id+'">View</a>'
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
		tbl.fnClearTable();
		tbl.fnAddData(data);
		tbl.fnDraw();
	}
	getdata(0);
</script>