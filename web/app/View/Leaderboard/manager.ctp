<?php
$monthly = isset($monthly) ? "selected='selected'":"";
$weekly = isset($weekly) ? "selected='selected'":"";
$overall = isset($overall) ? "selected='selected'":"";
$manager = isset($manager) ? "selected='selected'":"";
$pro_weekly = isset($pro_weekly) ? "selected='selected'":"";
?>
<div id="leaderboardPage">
      <div class="rowd">
     	 <?php echo $this->element('infobar'); ?>
      </div>
      <div class="rowd">
        <div class="col2">
            <div class="widget RingkasanKlab" id="RingkasanKlab">
                <div class="entry tr">
                    <table width="100%" border="0" cellspacing="0" cellpadding="0">
                      <tr>
                        <td align="center">
                        	<a href="#">
							<?php if(strlen(@$user['avatar_img'])==0 || @$user['avatar_img']=='0'):?>
                            <img src="http://widgets-images.s3.amazonaws.com/football/team/badges_65/<?=str_replace('t','',$club['team_id'])?>.png"/>
                            <?php else:?>
                            <img width="65" src="<?=$this->Html->url('/files/120x120_'.@$user['avatar_img'])?>" />
                            <?php endif;?>
       					 </a>
    					</td>
                        <td>
                            <span>Rank: <strong><?=number_format($USER_RANK)?></strong></span>
                            <span>ss$: <strong><?=number_format($team_bugdet)?></strong></span>
                            <span>Point: <strong><?=number_format($USER_POINTS)?></strong></span>
                        </td>
                        <!--<td colspan="2" class="pendapatan">
                        	
                            <p><span class="ico icon-plus-alt">&nbsp;</span>
                            	<strong class="amounts">ss$ <?=number_format($last_earning)?></strong></p>
                            <p><span class="ico icon-minus-alt">&nbsp;</span>
                            	<strong class="amounts">ss$ <?=number_format($last_expenses)?></strong></p>
                        </td>-->
                      </tr>
                    </table>
                </div><!-- end .entry -->
            </div><!-- end .widget -->
        </div><!-- end .col2 -->
       <?php for($i=0;$i<sizeof($long_banner);$i++):?>
                  <div class="col2">
                      <div class="mediumBanner">
                        <a href="javascript:banner_click(<?=$long_banner[$i]['Banners']['id']?>,'<?=$long_banner[$i]['Banners']['url']?>');">
                            <img src="<?=$this->Html->url(Configure::read('avatar_web_url').
                              $long_banner[$i]['Banners']['banner_file'])?>" />
                        </a>
                      </div><!-- end .mediumBanner -->
                  </div><!-- end .col2 -->
            <?php endfor;?>
      </div><!-- end .rowd -->
    <div class="headbar tr">
        <div class="leaderboard-head fl">
         
        	<h1>Papan Peringkat – Manager</h1>
            <p>Daftar urutan manajer berdasarkan poin tertinggi.<br />Diperbaharui secara mingguan. </p>
        </div>
        <div class="leaderboard-rank fr">
            <span>Peringkat Anda:</span>
            <h3><?=number_format($rank)?></h3>
            <span>Tier <?=$tier?></span>
        </div>
    </div><!-- end .headbar -->
    <div class="headbar tr">
     
      <div class="fl">
        <form action="<?=$this->Html->url('/leaderboard')?>" 
          method="get" enctype="application/x-www-form-urlencoded">
          <select name="period" class="styled">
              <option value="weekly" <?=$weekly?>>Mingguan</option>
              <option value="monthly" <?=$monthly?>>Bulanan</option>
              <option value="overall" <?=$overall?>>Keseluruhan</option>
              <option value="pro_weekly" <?=$pro_weekly?>>PROLeague - Mingguan</option>
               <option value="manager" <?=@$manager?>>Manager Standings</option>
          </select>
        </form>
      </div>
      
       
        <div class="fr">
          Matchday : <select name="matchday" class="styled">
            <option value="0">Matchday</option>
            <?php for($i=1;$i<=$matchday;$i++):?>
            <?php if($i==$matchday):?>
              <option value="<?=$i?>" selected='selected'><?=$i?></option>
            <?php else:?>
              <option value="<?=$i?>"><?=$i?></option>
            <?php endif;?>
            <?php endfor;?>
          </select>
        </div>
      
       
    </div>

    <div id="thecontent">
        <div class="contents">
        	<table width="100%" border="0" cellspacing="0" cellpadding="0" class="theTable footable">
                <thead>
                    <tr>
          				      <th></th>
                        <th data-hide="phone,tablet">Manajer</th>
                        <th>Klab</th>
                        
                        <th data-hide="phone" class="alignright">Jumlah Poin</th>
                    </tr>
                </thead>
                <tbody id="tblrows">
                 
                  
                 
                </tbody>
            </table>
            
        </div><!-- end .content -->
        <div class="rows">
            <?php for($i=0;$i<sizeof($long_banner2);$i++):?>
                  <div class="col2">
                      <div class="mediumBanner">
                        <a href="javascript:banner_click(<?=$long_banner2[$i]['Banners']['id']?>,'<?=$long_banner2[$i]['Banners']['url']?>');">
                            <img src="<?=$this->Html->url(Configure::read('avatar_web_url').
                              $long_banner2[$i]['Banners']['banner_file'])?>" />
                        </a>
                      </div><!-- end .mediumBanner -->
                  </div><!-- end .col2 -->
            <?php endfor;?>
        </div><!-- end .rows -->
    </div><!-- end #thecontent -->
</div><!-- end #leaderboardPage -->

<script>
$("select[name='period']").change(function(){
  
  switch($(this).val()){
    case 'monthly':
      document.location="<?=$this->Html->url('/leaderboard/monthly')?>";
    break;
    case 'overall':
      document.location="<?=$this->Html->url('/leaderboard/overall')?>";
    break;
    case 'manager':
      document.location="<?=$this->Html->url('/leaderboard/manager')?>";
    break;
    case 'pro_weekly':
      document.location="<?=$this->Html->url('/leaderboard/pro_weekly')?>";
    break;
    default:
      document.location="<?=$this->Html->url('/leaderboard')?>";
    break;
  }
});
</script>

<script type="text/template" id="manager_list">
  <% for(i=0;i<data.length;i++){
    console.log(data[i]);
  %>

  <% if(data[i].player==true){ %>

  <tr class="playerhighlight">
  
  <% }else if(i==0||i%2==0) { %> 
  <tr class="odd">
  <% }else{ %>
  <tr class="even">
  <%}%>
    <% if(data[i].player == true) { %>
       <td style="width:100px;">
       <?php if(strlen(@$user['avatar_img'])==0 || @$user['avatar_img']=='0'):?>
          <img width="100px" src="http://graph.facebook.com/<?=$USER_DATA['fb_id']?>/picture" />
        <?php else:?>
          <img width="100px" src="<?=$this->Html->url('/files/120x120_'.@$user['avatar_img'])?>" />
        <?php endif;?>
      
       </td>
    <% }else{ %>
    <td style="width:100px;"><img width="100px" src="<?=$this->Html->url('/images/managers/')?><%=data[i].team_id%>"/></td>
    <% } %>
    <td class="l-manager"><%=data[i].manager%></td>
    <td class="l-club"><%=data[i].team%></td>
    <td class="l-points alignright"><%=number_format(data[i].points)%></td>
  </tr>
  <%}%>
</script>

<script>
var matchday = <?=$matchday?>;
var stats = <?=json_encode($rs)?>;
function populate_stats(){
  var data = [];
  for(i=0;i<stats.length;i++){
    if(stats[i].matchday == matchday){
      data.push(stats[i]);
    }
  }
  
  render_view(manager_list,'#tblrows',{data:data});
}
$(document).ready(function(){
  populate_stats();  
  $("select[name='matchday']").change(function(){
    if($(this).val() > 0){
      matchday = $(this).val();  
      populate_stats();
    }
    
  });
});

</script>
