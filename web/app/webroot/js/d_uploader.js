/**
 * multi-file uploader using html5 features
 */
var uploaded_files = [];
var $progressbar;
var uploaderFormId = "";
(function( $ ) {
  $.fn.file_uploader = function(fproc,params) {
  	uploaded_files = [];
  	if(params==null){
  		params = {beforeSend:null,success:null,error:null,onComplete:null};
  	}
  	
  	return this.each(function () {
  		uploaderFormId = $(this).attr('id');
  		try{
			$("#"+uploaderFormId).find(':file').change(function(){
				$(this).hide();
				if($(this).next().attr('id')!='progress'){
					$progressbar = $('<div id="progress">'+
										'<span id="progressbar" style="float:left;width:380px;height:15px;background-color:#cc0000;"></span>'+
										'<span id="percent" style="float:left;padding-left:5px;">100%</span>'+
										'<span id="uploadagain" style="float:left;padding-left:10px;display:none;">Uploading...</span>'+
										'</div>');
					$(this).after($progressbar);
				}else{
					$("#progress").fadeIn();
				}
				$("#progress #progressbar").css('width','0px');
			 	$("#progress #percent").html('0%');
				var formData = new FormData(document.getElementById($(this).parent().attr('id')));
				try{
				    $.ajax({
				        url: fproc,  //server script to process data
				        type: 'POST',
				        xhr: function() {  // custom xhr
				            myXhr = $.ajaxSettings.xhr();
				            if(myXhr.upload){ // check if upload property exists
				                myXhr.upload.addEventListener('progress',d_on_progress, false); // for handling the progress of the upload
				            }
				            return myXhr;
				        },
				        //Ajax events
				        beforeSend: params.beforeSend,
				        success: params.success,
				        error: params.error,
				        // Form data
				        data: formData,
				        //Options to tell JQuery not to process data or worry about content-type
				        cache: false,
				        contentType: false,
				        processData: false
				    });
				 }catch(e){console.log(e.message);}
			});
		}catch(e){
			console.log(e.message);
		}
	});
  };
})( jQuery );
function d_on_progress(e){
	 if(e.lengthComputable){
	 
	 	//console.log("Progress : "+e.loaded+","+e.total);
	 	var progress_width = (e.loaded/e.total)*380;
	 	var percent = Math.ceil((e.loaded/e.total)*100);
	 	$("#progress #progressbar").css('width',progress_width+'px');
	 	$("#progress #percent").html(percent+'%');
	 	if(percent==100){
	 		
	 		$("#"+uploaderFormId).find(':file').show();
	 		$("#progress #progressbar").hide();
	 		if(typeof onComplete !== 'undefined' && onComplete!=null){
	 			onComplete();
	 		}
	 	}
     }
}