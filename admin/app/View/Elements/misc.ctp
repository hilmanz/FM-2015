<!--MISC-->
<?php
if(isset($USE_WYSIWYG)):
?>
	<script type="text/javascript">
	$(document).ready(function(){
		$("textarea.wysiwyg").tinymce({
		        // General options
		        mode : "textareas",
		        theme : "modern",
		        plugins : "contextmenu,table,ccake_filemanager,autolink,image,link,media,code",
		        relative_urls : false,
				remove_script_host : false,
		        theme_advanced_toolbar_location : "top",
		        theme_advanced_toolbar_align : "left",
		        theme_advanced_statusbar_location : "bottom",
		        theme_advanced_resizing : true,
		        invalid_elements : "html,head,body",
		        width:'700px',
		        // Example content CSS (should be your site CSS)
		        //content_css : "css/example.css",

		        // Drop lists for link/image/media/template dialogs
		        template_external_list_url : "<?=$this->Html->url('js/template_list.js')?>",
		        external_link_list_url : "<?=$this->Html->url('js/link_list.js')?>",
		        external_image_list_url : "<?=$this->Html->url('js/image_list.js')?>",
		        media_external_list_url : "<?=$this->Html->url('js/media_list.js')?>",
		});
	});
	</script>
<?php
endif;
?>
<!-- END OF MISC-->