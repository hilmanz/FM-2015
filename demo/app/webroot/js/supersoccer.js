// JAVASCRIPT FFL
// ACIT JAZZ v.1.2
$(document).ready(function() {
	$("body").addClass(BrowserDetect.browser); 
	$("table tbody tr:nth-child(odd)").addClass("odd");
	$("table tbody tr:nth-child(even)").addClass("even");
	// Popup
	
	$('.showPopup').fancybox();
	
	// Live Channel
	jQuery('#livechannels').jcarousel({
		scroll: 5,
	});
	
	// Drop Down Menu
	$('ul#topnav').superfish({ 
        delay:       600,
        animation:   {opacity:'show',height:'show'},
        speed:       'fast',
        autoArrows:  true,
        dropShadows: false
    });

	
});
// SLIDER
$(window).load(function(){
  $('.bannerslider').flexslider({
	animation: "slide",
	controlNav: true,               
	directionNav: true,           
	prevText: "Previous",          
	nextText: "Next",
	start: function(slider){
	  $('body').removeClass('loading');
	}
  });
  $('#newsticker').flexslider({
	animation: "slide",
	animationSpeed: 1000,
	slideshowSpeed: 3000,
	start: function(slider){
	  $('body').removeClass('loading');
	}
  });
});

$(function() {
$( "#clubtabs" ).tabs();
});



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