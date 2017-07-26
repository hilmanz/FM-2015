//ROUTER
var tmp = {};
var App = Backbone.Router.extend({
  routes:{
    "selectTeam/:team_id":"selectTeam",
  },
  selectTeam:selectTeam,
});

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

$(document).ready(function(){
  var app = new App();
  Backbone.history.start();
});


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