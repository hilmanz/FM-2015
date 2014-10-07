<div id="fillDetailsPage">
      <div class="rowd">
     	 <?php echo $this->element('infobar'); ?>
      </div>
      <div class="rowd">
       
    <div id="thecontent">
        <div id="content">
        	<div class="content">
            	<div class="row-2">
                    <h1 class="red">Invite Friend by Email</h1>
                    <p>Ayo undang teman - teman lo. Buktikan siapa yang terbaik di antara kalian. (maksimal 30)</p>
                    <a id="add_email" href="javascript:;" class="fr yellow">
                      Tambah email
                    </a>
    			</div><!-- end .row-2 -->
                <form class="theForm" action="<?=$this->Html->url('/privateleague/invite')?>" 
                  enctype="multipart/form-data" method="post">
                    <div class="row">
                        <label>Email</label>
                        <input id="email" type="text" name="email[]" value="" maxlength="30"/>
                    </div><!-- end .row -->
                    <div id="t_email">
                    </div>
                    <div class="row">
                        <a id="submit_invite" class="button" href="javascript:;">Invite</a>
                        <span id="message"></span>
                    </div><!-- end .row -->
                </form>
			</div><!-- end .content -->
        </div><!-- end #content -->
    </div><!-- end #thecontent -->
</div><!-- end #fillDetailsPage -->
<script type="text/javascript">
  
  var limit = <?=$limit?>;
  var count = 2;
  $('#add_email').on('click', function(e){
    if(count <= limit)
    {
      $('#t_email').append('<div class="row">\
                        <label>Email</label>\
                        <input id="email" type="text" name="email[]" value="" maxlength="30"/>\
                    </div>');
    }
    count++;
  });
  $('#submit_invite').on('click', function(e){
      var email_array = $('input[name="email[]"]').serializeArray();
      var submit_form = true;
      $.each(email_array, function(index, email){
        if(email.value == "")
        {
          $('#message').html('Email tidak boleh kosong');
          submit_form = false;
        }
      });
      
      if(submit_form)
      {
        $('.theForm').submit();
      }
  });
</script>