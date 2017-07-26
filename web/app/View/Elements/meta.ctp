
	<meta charset="utf-8">
	<title>SuperSoccer Football Manager - Online Football Manager on SuperSoccer</title>

    <meta name="viewport" content = "width = device-width, initial-scale = 1.0, minimum-scale = 1.0, maximum-scale = 1.0, user-scalable = no" />	
	<?php echo $this->Html->meta('icon', '/images/favicon.ico');?>
	
	<meta name="description" content="SuperSoccer Football Manager, football manager online games dari SuperSoccer. Ajang unjuk gigi kemampuan kamu sebagai manager klab liga utama inggris.">
	<meta name='keywords' content="football manager online, online football manager, fm super soccer, football manager supersoccer"/>
	<meta name="author" content="">
	<script>
	var api_url = "<?=$this->Html->url('/')?>";
	var base_url = "<?=$this->Html->url('/')?>";
	var est_expenses = 0; 
	var staffs = [];
	var canResetFormation = false;
		 function resetLineups(){
            $.each($("#the-formation").children(),function(t,l){
                if(typeof $(l).find('a').attr('no') !== 'undefined'){
                    $(l).remove();
                }
            });
            
            $("div.bench").removeClass('playerBoxChoosed');
            $("div.starter").removeClass('playerBoxChoosed');
            $("div.bench").removeClass('playerBoxSelected');
            $("div.starter").removeClass('playerBoxSelected');
            $("#draggable").hide();
            //show slots for subs
            for(var i=0;i<17;i++){
                $("#p"+i+".slot").show();
            }
        }
	</script>

	<?php
	if(isset($ENABLE_OPTA)):
	?>
	<link rel="stylesheet" href="http://widget.cloud.opta.net/2.0/css/widgets.opta.css" type="text/css">
	<!--[if IE 9]>
		<link rel="stylesheet" type="text/css" href="http://widget.cloud.opta.net/2.0/css/ie9.widgets.opta.css" media="screen" />
	<![endif]-->
	<!--[if IE 8]>
		<link rel="stylesheet" type="text/css" href="http://widget.cloud.opta.net/2.0/css/ie8.widgets.opta.css" media="screen" />
	<![endif]-->
	<!--[if IE 7]>
		<link rel="stylesheet" type="text/css" href="http://widget.cloud.opta.net/2.0/css/ie7.widgets.opta.css" media="screen" />
	<![endif]-->
	<script type="text/javascript" src="http://widget.cloud.opta.net/2.0/js/widgets.opta.js"></script>
	<script type="text/javascript">
		var _optaParams = {
			custID:		'<?=$OPTA_CUSTOMER_ID?>',
			language:	'en',
		};

	</script>
	<?php endif;?>
	<!-- Le styles -->
	<?php echo $this->Html->css(
			  array('ffl','superfish','fancybox/jquery.fancybox.css?v=2.1.5','responsive','footable-0.1','jquery.jqplot'),
		null,array('media'=>'all')); 
	?>
	<?php echo $this->Html->script(
	  array('jquery-1.9.1',
			'jquery-ui-1.10.3.custom.min',
			'hoverIntent',
			'superfish',
			'fancybox/jquery.fancybox.js?v=2.1.5',
			'datatables/jquery.dataTables',
			'datatables/DT_bootstrap',
			'datatables/tables',
			'footable',
	  ));
?>
    <script type="text/javascript">
		if ($(window).width() < 960) {
			  $(function() {
				$('table').footable();
			  });
		}
		else {
		}
    </script>
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

	<?php
	if(isset($ENABLE_CHARTS)):
	?>
	<?php echo $this->Html->script(
	  array('jquery.jqplot.min','plugins/jqplot.highlighter.min','plugins/jqplot.cursor.min',
	  			'plugins/jqplot.dateAxisRenderer.min','plugins/jqplot.json2.min'));
	  ?>
	<?php
	endif;
	?>