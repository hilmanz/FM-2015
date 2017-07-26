
function get_widget(url,target,type,manual){
	manual = (typeof type === 'undefined') ? false : manual;
	type = (typeof type === 'undefined') ? 1 : type;
	game_id = (typeof game_id === 'undefined') ? 1 : game_id;
	$.ajax({
	  url:url+'?id='+game_id+'&type='+type+'&r='+Math.random()*999999,
	  dataType: '',
	  success: function(response){
		$(target).html(response);
		if(manual){
			$(target).prepend("<select id='sstogglewidget'><option>1</option>\
								<option>2</option>\
								<option>3</option>\
								<option>4</option>\
								<option>5</option>\
								<option>6</option>\
								<option>7</option>\
								<option>8</option>\
								<option>9</option>\
								<option>10</option>\
								<option>11</option>\
								<option>12</option>\
								<option>13</option>\
								<option>14</option>\
								<option>15</option>\
								<option>16</option>\
								<option>17</option>\
								<option>18</option>\
								<option>19</option>\
								<option>20</option>\
								</select>");
			try{
				document.getElementById('sstogglewidget').value = game_id;
			}catch(e){}
		}
		$("#sstogglewidget").click(function(){
			game_id = $("#sstogglewidget").val();
		});
		$("table tbody tr:nth-child(odd)").addClass("odd");
		$("table tbody tr:nth-child(even)").addClass("even");
	},error:function(e){
		console.log(e);
	}});
	window.setTimeout(function(){
		get_widget(url,target,type,manual);
	},1000*5);
}

function api_call(u,c){
	$.ajax({
		  url: u,
		  dataType: 'json',
		  success: c
		}
	);
}