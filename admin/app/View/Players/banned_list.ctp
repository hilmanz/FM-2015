<h3>
	Banned User
</h3>

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
$edit_url = $this->Html->url('/players/banned_list/view/');
?>
<script>
	var start = 0;
	var data = [];
	function getdata(){
		api_call("<?=$this->Html->url('/players/get_banned_user/?start=')?>"+start,
			function(response){
				if(response.status==1){
					if(response.data.length > 0){
						for(var i in response.data){
							console.log(response.data);
							var j = parseInt(i)+1;
							data.push([
									j,
									'<img src="http://graph.facebook.com/'+response.data[i].b.fb_id+'/picture"/>',
									response.data[i].b.name+'<br />'+response.data[i].b.fb_id+'<br />'+response.data[i].b.email,
									response.data[i].b.name,
									response.data[i].c.team_name+' ('+response.data[i].f.name+')',
									response.data[i].b.phone_number,
									response.data[i].b.register_date,
									'<a href="<?=$edit_url?>'+response.data[i].a.user_id+'">View</a>'
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
				{ "sTitle": "No" },
				{ "sTitle": "" },
				{ "sTitle": "User" },
				{ "sTitle": "Name" },
				{ "sTitle": "Original Team" },
				{ "sTitle": "Phone Number" },
				{ "sTitle": "Joined" },
				{ "sTitle": "Action"}
			]
		} );
	}
	getdata();
</script>