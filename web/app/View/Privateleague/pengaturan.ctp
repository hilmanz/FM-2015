<div id="fillDetailsPage">
      <div class="rowd">
     	 <?php echo $this->element('infobar'); ?>
      </div>
      <div class="rowd">
      <div class="fl">
        <a href="<?=$this->Html->url('/privateleague')?>" class="button">
          KEMBALI
        </a>
      </div>
       
    <div id="thecontent">
        <div id="content">
        	<div class="content">
            	<div class="row-2">
                    <h1 class="red">Pengaturan Liga</h1>
                    <?php if(count($rs_joined) > 0): ?>
                    <p>Joined</p>
                    <table class="theTable footable" width="100%">
                      <thead>
                          <tr>
                              <th data-class="expand">No.</th>
                              <th>Email</th>
                          </tr>
                      </thead>
                      <tbody>
                        <?php $i=1; foreach($rs_joined as $value): ?>
                        <tr>
                          <td><?=$i?></td>
                          <td><?=$value['leagueInvitation']['email']?></td>
                        </tr>
                        <?php $i++; endforeach; ?>
                      </tbody>
                    </table>
                    <?php endif; ?>

                    <?php if(count($rs_invited) > 0): ?>
                    <p>Waiting Confirmation</p>
                    <table class="theTable footable" width="100%">
                      <thead>
                          <tr>
                              <th data-class="expand">No.</th>
                              <th>Email</th>
                              <th data-hide="phone,tablet">&nbsp;</th>
                          </tr>
                      </thead>
                      <tbody>
                        <?php $i=1; foreach($rs_invited as $value): ?>
                        <tr>
                          <td><?=$i?></td>
                          <td><?=$value['leagueInvitation']['email']?></td>
                          <td>
                            <a id="copylink" class="button" href="javascript:;" 
                            data-trx="<?=$value['leagueInvitation']['trx_code']?>">
                              Copy Link
                            </a>
                          </td>
                        </tr>
                        <?php $i++; endforeach; ?>
                      </tbody>
                    </table>
                    <?php endif; ?>
    			</div><!-- end .row-2 -->
              <form class="theForm" action="<?=$this->Html->url('/privateleague/invite')?>" 
                enctype="multipart/form-data" method="post">
                  <div class="row">
                      <label>Email Teman</label>
                      <input type="hidden" name="league_trx" value="<?=$league_trx?>" />
                      <input id="email" type="text" name="email[]" value="" maxlength="30"/>
                      <a id="add_email" href="javascript:;" class="fr yellow">
                        Tambah email
                      </a>
                  </div><!-- end .row -->
                  <div id="t_email">
                  </div>
                  <div class="row">
                      <a id="submit_invite" class="button" href="javascript:;">Kirim Undangan</a>
                      <span id="message"></span>
                  </div><!-- end .row -->
              </form>
			</div><!-- end .content -->
        </div><!-- end #content -->
    </div><!-- end #thecontent -->
</div><!-- end #fillDetailsPage -->

<div id="bgPopup" style="display:none;"></div>
<div id="popupWelcome" style="display:none;">
  <a href="#" class="closebtn"><span class="icon-close"></span></a>
  <div class="popup-content">
     <h3>Share link dibawah ini supaya temen loe join di private league</h3>
     <div class="howto-text">
        <p id="t_link"></p>
     </div><!-- end .howto-text -->
  </div><!-- end .popup-content -->
</div><!-- end #popupWelcome -->

<script type="text/javascript">
$(document).ready(function(){
        
    $("a.closebtn").click(function(){
      $("#bgPopup").fadeOut();
      $("#popupWelcome").fadeOut();
    });
});
  
  $('a#copylink').on('click', function(e){
    var link = "<?=Configure::read('DOMAIN');?>/privateleague/linkjoin/?trx="+$(this).attr('data-trx');
    $('#t_link').text(link);

    $("#bgPopup").fadeIn();
    $("#popupWelcome").fadeIn();
  });

  var limit = <?=$limit?>;
  var count = 2;
  $('#add_email').on('click', function(e){
    if(count <= limit)
    {
      $('#t_email').append('<div class="row">\
                        <label>Email Teman</label>\
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