//ROUTER
var tmp = {};
var App = Backbone.Router.extend({
  routes:{
    "selectTeam/:team_id":"selectTeam",    
    "select_player/:player_id":"select_player",
    "unselect_player/:player_id":"unselect_player",
    "save_formation":"save_formation",
    "hire/:staff_id":"hire",
    "dismiss/:staff_id":"dismiss",
  },
  hire:hire,
  dismiss:dismiss,
  selectTeam:selectTeam,
  select_player:select_player,
  unselect_player:unselect_player,
  save_formation:save_formation
});
function getStaffSalary(id){
	for(var i in staffs){
		if(staffs[i].id==id){
			return staffs[i].salary;
		}
	}
}
function hire(staff_id){
	$("#staff-"+staff_id).html('<img class="hire-loading" src="'+base_url+'css/fancybox/fancybox_loading.gif"/>');
	api_post(api_url+'game/hire_staff',{id:staff_id},function(response){
			if(typeof response.status!=='undefined' && response.status == 1){
				$("#staff-"+staff_id).html('Hired');
				$("#staff-"+staff_id).attr('href','#/dismiss/'+staff_id);
				est_expenses += getStaffSalary(staff_id);
				
				$("h1.expenses").html('EUR '+number_format(parseInt(est_expenses)));
			}else{
				$("#staff-"+staff_id).html('Select');
			}
			document.location = "#/";
	});
}
function dismiss(staff_id){
	
	$("#staff-"+staff_id).html('<img class="hire-loading" src="'+base_url+'css/fancybox/fancybox_loading.gif"/>');
	
	api_post(api_url+'game/dismiss_staff',{id:staff_id},function(response){
			if(typeof response.status!=='undefined' && response.status == 1){
				$("#staff-"+staff_id).html('Select');
				$("#staff-"+staff_id).attr('href','#/hire/'+staff_id);
				if(est_expenses>0){
					est_expenses -= getStaffSalary(staff_id);
					if(est_expenses<0){est_expenses = 0;}
					$("h1.expenses").html('EUR '+number_format(parseInt(est_expenses)));
				}
			}else{
				$("#staff-"+staff_id).html('Hired');
			}
			document.location = "#/";
	});
}
function save_formation(){
	
		var formation = selectedVal['formations'].value;
		console.log(formation);
		//make sure that all the lineup is consist of 11 players. + 5 subs
		getLineups(function(total,lineup){
			console.log(total,lineup);
			if(total==16){
				//save the lineup
				var data = {};
				data.formation = formation;
				for(var i in lineup){
					data[lineup[i].name] = parseInt(lineup[i].value);
				}
				
				if(typeof api_url !== 'undefined'){
					$('.saving').show();
					$('.confirm').hide();
					api_post(api_url+'game/save_lineup',data,function(response){
						console.log(response);
						$.fancybox.close();
					});
				}
			}else{
				$("#popup-messages .popupContent .entry-popup").html('');
				console.log('total',total);
				render_view(tplmsg,'#popup-messages .popupContent .entry-popup',
							{title:'Oops !',result:'Your lineup should consist 11 players and 5 substitutions !'});
			}
		});
	
	document.location="#";
}
function getLineups(callback){
	var n_player = 0;
	var players = [];
	if($("#the-formation").children().length>=16){
		$.each($("#the-formation").children(),function(k,player){
		    //console.log(player);
		   
		    if($(player).find('a').attr('no')!=undefined){
		    	players.push({
		    		name:'player-'+$(player).find('a').attr('no'),
		    		value: $(player).attr('id').replace('p','')
		    	});
		        n_player++;
		    }
		    if(n_player == 16){
		        callback(n_player,players);
		        return true;
		    }
		    if((k+1)==$("#the-formation").children().length){
		    	callback(n_player,players);	
		    	return true;
		    }
		});
	}else{
		callback(0,[]);
	}
}
function selectTeam(team_id,teams){
	$("input[name='team_id']").val(team_id);
	var team_name = "";
	$('.teamBox').removeClass('selected');
	//highlight selected
	$.each($('.teamBox'),function(k,v){
	    if($(v).attr('no')==team_id){
	        $(v).addClass('selected');
	    }
	});
	$.each(team_list,function(k,v){
		if(v.uid==team_id){
			return true;
		}
	});
}
function select_player(player_id){
	isPlayerSelected(player_id,function(check){
		if(!check){
			$.each(tmp['available_teams'],function(k,v){
			 	if(v.uid == player_id){
			 		append_view(player_selected,'#selected',v);
			 		est_expenses += v.salary;
			 		$("span.expense").html(number_format(parseInt(est_expenses)));
			 	}
			 });
		}
	});
	 
}
function populate_selected(callback){
	var s = "";
	var n = $("#available").children().length;
	$.each($("#available").children(),function(k,v){
			if(k>0){
				s += ",";
			}
			s += $(v).find('a').attr('id');
			if(n-1 == k){
				callback(s);
			}
	});
}
function isPlayerSelected(player_id,callback){
	var n = $("#selected").children().length;
	var isSelected = false;
	if(n>0){
		$.each($("#selected").children(),function(k,v){
			if($(v).find('a').attr('id') == player_id){
				isSelected = true;
			}
			if(n == k+1){
				callback(isSelected);
			}
		});
	}else{
		callback(isSelected);
	}
}
function unselect_player(player_id){
	//$.each($("#selected").children(),function(k,v))
	$.each($("#selected").children(),function(k,v){
		if($(v).find('a').attr('id') == player_id){
			$(v).remove();
			return true;
		}
	});
}
$(document).ready(function(){
  var app = new App();
  Backbone.history.start();
});

function check_team_name(team_name,callback){
	api_call(api_url+'game/check_team_name?name='+team_name,function(response){
		if(response.status==1){
			tmp.team_name_taken = true;
		}else{
			tmp.team_name_taken = false;
		}
		callback(tmp.team_name_taken);
	});
}

//other functions
function api_call(u,c){
	$.ajax({
		  url: u,
		  dataType: 'json',
		  success: c
		}
	);
}
function api_post(u,d,c){
	$.ajax({
	  url: u,
	  dataType: 'json',
	  type:'POST',
	  data:d,
	  success: c});	
}
function render_view(tpl_source,target,data){
	try{
		var View = Backbone.View.extend({
	        initialize: function(){
	            this.render();
	        },
	        render: function(){
	            var variables = data;
	            var template = _.template($(tpl_source).html(),variables);
	            this.$el.html(template);
	        }
	    });
	    var view = new View({el:$(target)});
	    
   }catch(error){
   		console.log(error.message);
   }
}
function prepend_view(tpl_source,target,data){
	try{
		var View = Backbone.View.extend({
	        initialize: function(){
	            this.render();
	        },
	        render: function(){
	            var variables = data;
	            var template = _.template($(tpl_source).html(),variables);
	            this.$el.prepend(template);
	        }
	    });
	    var view = new View({el:$(target)});
   }catch(error){
   	 	
   }
}
function append_view(tpl_source,target,data){
	try{
		var View = Backbone.View.extend({
	        initialize: function(){
	            this.render();
	        },
	        render: function(){
	            var variables = data;
	            var template = _.template($(tpl_source).html(),variables);
	            this.$el.append(template);
	            this.$el.css('display','none');
	            this.$el.fadeIn();
	        }
	    });
	    var view = new View({el:$(target)});
	    
   }catch(error){
   	 
   }
}

// http://kevin.vanzonneveld.net
// Strip all characters but numerical ones.
function number_format (number, decimals, dec_point, thousands_sep) {
  
  number = (number + '').replace(/[^0-9+\-Ee.]/g, '');
  var n = !isFinite(+number) ? 0 : +number,
    prec = !isFinite(+decimals) ? 0 : Math.abs(decimals),
    sep = (typeof thousands_sep === 'undefined') ? ',' : thousands_sep,
    dec = (typeof dec_point === 'undefined') ? '.' : dec_point,
    s = '',
    toFixedFix = function (n, prec) {
      var k = Math.pow(10, prec);
      return '' + Math.round(n * k) / k;
    };
  // Fix for IE parseFloat(0.55).toFixed(0) = 0;
  s = (prec ? toFixedFix(n, prec) : '' + Math.round(n)).split('.');
  if (s[0].length > 3) {
    s[0] = s[0].replace(/\B(?=(?:\d{3})+(?!\d))/g, sep);
  }
  if ((s[1] || '').length < prec) {
    s[1] = s[1] || '';
    s[1] += new Array(prec - s[1].length + 1).join('0');
  }
  return s.join(dec);
}