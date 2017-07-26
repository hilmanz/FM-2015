
	<meta charset="utf-8">
	<!-- <title>SUPER SOCCER - FANTASY FOOTBALL LEAGUE</title> -->

	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<meta name="description" content="">
	<meta name="author" content="">
	<script>
	var api_url = "<?=$this->Html->url('/')?>";
	var base_url = "<?=$this->Html->url('/')?>";
	var est_expenses = 0; 
	var staffs = [];
	</script>
	<!-- Le styles -->
	<?php echo $this->Html->css(
			  array('ffl','superfish','fancybox/jquery.fancybox.css?v=2.1.5'),
		null,array('media'=>'all')); 
	?>
	<?php echo $this->Html->script(
	  array('jquery-1.9.1',
			'jquery-ui-1.10.3.custom.min',
			'fancybox/jquery.fancybox.js?v=2.1.5',
			
			'hoverIntent',
			'tinymce/jquery.tinymce.min',
          	'tinymce/tinymce.min',
			'superfish',
			'highcharts',
			'underscore-min',
			'backbone-min',
			'string.min',
			'kit'
	  ));
?>
	<!--[if IE 6]>
	<link rel="stylesheet" type="text/css" href="js/slider/themes/default/jquery.slider.ie6.css" />
	<?php echo $this->Html->css(
			  array('slider/themes/default/jquery.slider.ie6'
			  ),
				null,array('media'=>'all')); 
	?>
	<![endif]-->
	<!--[if gte IE 9]>
	  <style type="text/css">
	    .grad {
	       filter: none;
	    }
	  </style>
	<![endif]-->
