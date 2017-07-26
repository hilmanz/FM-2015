// JAVASCRIPT FFL
// ACIT JAZZ v.1.2
	
$(document).ready(function() {
	$("body").addClass(BrowserDetect.browser); 
	$("table tbody tr:nth-child(odd)").addClass("odd");
	$("table tbody tr:nth-child(even)").addClass("even");
	// Popup
	//$('.showPopup').fancybox();
	
	// Drop Down Menu
	$('ul#topnav,#mainNav').superfish({ 
        delay:       600,
        animation:   {opacity:'show',height:'show'},
        speed:       'fast',
        autoArrows:  true,
        dropShadows: false
    });
	$( "#bantuanTab").tabs();
	
});

// SLIDER
$(window).load(function(){
  $('.bannerslider').flexslider({
	animation: "slide",
	controlNav: false,               
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
	controlNav: false,               
	directionNav: false, 
	start: function(slider){
	  $('body').removeClass('loading');
	}
  });
  $('#starterTeam').flexslider({
		animation: "slide",
		animationLoop: false,
		slideshow: false,
		itemWidth: 210,
		itemMargin: 0,
		minItems: 2,
		move: 40,   
		direction: "vertical",
		pauseOnHover: true
  });

			$(".flex-prev").mouseover(function(e){
			   	$("#draggable").hide(); 
			});
			$(".flex-next").mouseover(function(e){
				$("#draggable").hide(); 
			});
 
});
