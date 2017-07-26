<?php
//tokenized staff list
$staff_token = array();
$total_expenses = 0;
$total_expenses+= intval(@$finance['operating_cost']);
$total_expenses+= intval(@$finance['player_salaries']);
$total_expenses+= intval(@$finance['commercial_director']);
$total_expenses+= intval(@$finance['marketing_manager']);
$total_expenses+= intval(@$finance['public_relation_officer']);
$total_expenses+= intval(@$finance['head_of_security']);
$total_expenses+= intval(@$finance['football_director']);
$total_expenses+= intval(@$finance['chief_scout']);
$total_expenses+= intval(@$finance['general_scout']);
$total_expenses+= intval(@$finance['finance_director']);
$total_expenses+= intval(@$finance['tax_consultant']);
$total_expenses+= intval(@$finance['accountant']);
$total_expenses+= intval(@$finance['buy_player']);

$total_expenses+= intval(@$finance['compensation_fee']);
$total_expenses+= intval(@$finance['ticket_sold_penalty']);
$total_expenses+= intval(@$finance['security_overtime_fee']);


$sponsor = 0;
$sponsor += intval(@$finance['Joining_Bonus']);
$sponsor += intval(@$finance['sponsorship']);


//income from other events
$other = 0;

if(is_array($finance)){
  foreach($finance as $item_name => $item_value){
    if($item_value > 0 && @eregi('other_',$item_name)){
      $other += $item_value;
    }
    if($item_value > 0 && @eregi('event',$item_name)){
      $other += $item_value;
    }
    if($item_value > 0 && @eregi('perk',$item_name)){
      $other += $item_value;
    }
  }

}

//expenses from other events
$other_expenses = 0;
if(is_array($finance)){
  foreach($finance as $item_name => $item_value){
    if($item_value < 0 && @eregi('other_',$item_name)){
      $other_expenses += abs($item_value);
    }
    if($item_value < 0 && @eregi('transaction_fee',$item_name)){
      $other_expenses += abs($item_value);
    }
    if($item_value < 0 && @eregi('perk',$item_name)){
      $other_expenses += $item_value;
    }
  }
}

$total_expenses -= abs($other_expenses);



$finance['total_earnings'] += $sponsor;
$finance['total_earnings'] += $other;

/*
$penalty_expenses = intval(@$finance['compensation_fee']) + 
                    intval(@$finance['ticket_sold_penalty']) + 
                    intval(@$finance['security_overtime_fee']);

*/
$first_week = $weekly_balances[0];
$my_balance = $weekly_balances;
$previous_balances = array();
for($i=1;$i<$first_week['week'];$i++){
  $previous_balances[] = array('week'=>$i,
                              'balance'=>intval(@$starting_budget));
}
if(is_array($weekly_balances)){
  $weekly_balances = array_merge($previous_balances,$weekly_balances);  
}else{
  $weekly_balances = $previous_balances;
}


if($week<=1){
  $starting_balance = intval(@$starting_budget);
}else{
  $starting_balance = $weekly_balances[$week-2]['balance'];

}
if($week==0){
  $running_balance = intval(@$weekly_balances[sizeof($weekly_balances)-1]['balance']);
}else{
  $running_balance = intval(@$weekly_balances[$week-1]['balance']);  
}
foreach($staffs as $staff){
  $staff_token[] = str_replace(" ","_",strtolower($staff['name']));
}
function isStaffExist($staff_token,$name){ 
  foreach($staff_token as $token){
    if($token==$name){
      return true;
    }
  }
}
?>
<div id="klabPage">
	<h1 class="fake-title">Mengelola Klab</h1>
    <div id="thecontent">
      <div class="rows">
     	 <?php echo $this->element('infobar'); ?>
      </div>
      <div class="rows">
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
                            <!--<span>Gaji Mingguan: <strong>ss$ <?=number_format($weekly_salaries)?></strong></span>-->
                        </td>
                        <!--<td colspan="2" class="pendapatan">
                        	
                            <p title="Pendapatan Minggu Lalu">
                              <span class="ico icon-plus-alt">&nbsp;</span>
                            	<strong class="amounts">ss$ <?=number_format($last_earning)?></strong></p>
                            <p title="Pengeluaran Minggu Lalu"><span class="ico icon-minus-alt">&nbsp;</span>
                            	<strong class="amounts">ss$ <?=number_format($last_expenses)?></strong></p>
                        </td>-->
                      </tr>
                    </table>
                </div><!-- end .entry -->
            </div><!-- end .widget -->
        </div><!-- end .col2 -->
        <div class="col2">
            <div class="mediumBanner">
                <?=$this->element('sponsor_banner',array('slot'=>'MY_CLUB_LONG','game_team_id'=>$game_team_id));?>
            </div><!-- end .mediumBanner -->
        </div><!-- end .col2 -->
      </div><!-- end .rows -->
    	<div class="row">
			<div id="clubtabs">
              <ul class="tabLeft" style="display:none;">
                <li><a href="#tabs-Info">Info</a></li>
                <li><a href="#tabs-Money">Keuangan</a></li>
                <li><a href="#tabs-Players">Pemain</a></li>
                
              </ul>
              <div id="tabs-Info">

                <div class="row">
					<div class="col3 fl">
            <div class="banner250x250">
          
                <a 
                href="<?=$this->Html->url('/sponsors/jump/1/'.$info_banner[0]['Banners']['id'])?>" 
                    target="_blank">
                    <img src="<?=$this->Html->url(Configure::read('avatar_web_url').
                                $info_banner[0]['Banners']['banner_file'])?>" />
                </a>
            </div>
					</div><!-- end .col3 -->
					<div class="col3 fl">
						<div class="widget PergerakanRanking">
							<h3>Pergerakan Poin</h3>
							<div class="entry tr">
								<div id="chart_ranking" class="chartbox">
									
								</div>
							</div><!-- end .entry -->
						</div><!-- end .widget -->
					</div><!-- end .col3 -->
					<div class="col3 fl">
						<div class="widget PergerakanKeuangan">
							<h3>Pergerakan Keuangan</h3>
							<div class="entry tr">
								<div id="chart_keuangan" class="chartbox">
									
								</div>
							</div>
						</div>
					</div> 
				</div><!-- end .row -->
				<div class="row">
					<div class="col3 fl">
						<div class="widget PertandinganLalu">
							<h3>Hasil Pertandingan Lalu</h3>
							<div class="entry tr">
									<table width="100%" border="0" cellspacing="0" cellpadding="0" id="shorTable">
								  <thead>
									<tr>
									  <th width="50" align="center">Game</th>
									  <th class="aligncenter" width="1" align="center">Points</th>
									 
									</tr>
								  </thead>
								  <tbody id="myplayerlist">
								  	<?php if(isset($matches)):foreach($matches as $m):?>
								  	<?php
                      $is_home_game = true;
                      $hidden_params = encrypt_param(serialize($m));
                      extract($m);
                      if($home_id==$club['team_id']){
                        $versus = $away_name;
                        $versus_id = str_replace("t","",$away_id);
                        $is_home_game = true;
                      }else{
                        $versus = $home_name;
                        $versus_id = str_replace("t","",$home_id);
                        $is_home_game = false;
                      }
                    ?>
									<tr id="p50004" class="odd">
									  <td><a class="thumbClub" href="<?=$this->Html->url('/manage/matchinfo?game_id='.$game_id).'&r='.$hidden_params?>" title="<?=h($versus)?>"><img src="http://widgets-images.s3.amazonaws.com/football/team/badges_65/<?=$versus_id?>.png"/></a></td>
									  <td class="aligncenter"><?=ceil($points)?></td>
									 
									</tr>
									<?php endforeach;endif;?>
		                          </tbody>
		                       	</table>
							</div><!-- end .entry -->
						</div><!-- end .widget -->
					</div><!-- end .col3 -->
					<div class="col-content fl">
						<div class="widget PemainTerbaik">
							<h3>Pemain Terbaik</h3>
							<div class="entry tr">
								<table width="100%" border="0" cellspacing="0" cellpadding="0" id="shorTable2">
								  <thead>
									<tr>
									  <th width="50"></th>
									  <th width="210">Nama</th>
									  <th class="aligncenter" width="5">Umur</th>
									  <th width="70">Posisi</th>
									 
									  <th width="5">Poin</th>
									  <th class="alignright" width="120">Nilai</th>
									 
									</tr>
								  </thead>
								  <tbody id="myplayerlist">
									
									 <?php $n_best = 0;?>				  	
									  <?php foreach($best_players as $player):?>
					                  <?php
					                    switch($player['position']){
					                      case 'Goalkeeper':
					                        $player_pos = "Goalkeeper";
					                        $color = "grey";
					                      break;
					                      case 'Midfielder':
					                        $player_pos = "Midfielder";
					                        $color = "yellow";
					                      break;
					                      case 'Forward':
					                        $player_pos = "Forward";
					                        $color  = "red";
					                      break;
					                      default:
					                        $player_pos = "Defender";
					                        $color = "blue";
					                      break;
					                    }
					                    
					                   
					                  ?>
					                  <tr id="<?=$player['uid']?>">
					                    <td>
					                     <a class="thumbPlayers" href="<?=$this->Html->url('/manage/player/'.$player['uid'])?>"> <img src="http://omo.akamai.opta.net/image.php?custID=c8bb60c8f6d0184c33a87e6f3041b9cc&sport=football&entity=player&description=<?=str_replace('t','',$player['team_id'])?>&dimensions=103x155&id=<?=str_replace('p','',$player['uid'])?>"/></a>
					                    </td>
					                    <td>
					                      <a class="yellow" href="<?=$this->Html->url('/manage/player/'.$player['uid'])?>"><?=h($player['name'])?></a></td>
					                    
					                    <td class="aligncenter"><?=round((time()-strtotime($player['birth_date']))/(24*60*60*365))?></td>
					                   
					                    <td><?=$player_pos?></td>
					                   
					                    
					                    <?php
                                if($player['points']!=0){
                                  $last_performance = floatval($player['last_performance']);
                                  $performance_bonus = getTransferValueBonus($last_performance,intval($player['transfer_value']));
                                }else{
                                  $performance_bonus = 0;
                                }
                              ?>
					                    <td class="aligncenter"><?=(($player['points']))?></td>
					                    <td class="alignright">ss$ <?=number_format(intval($player['transfer_value'])+$performance_bonus)?></td>
					                    
					                  </tr>
					                  <?php if($n_best==4){break;}else{$n_best++;}?>
					                  <?php endforeach;?>
								  </tbody>
								</table>


							</div><!-- end .entry -->
						</div><!-- end .widget -->
					</div><!-- end .col-content -->
				</div><!-- end .row -->
              </div><!-- end #Info -->
              <div id="tabs-Money">
              	 <div class="col-content">
                    <div class="fr" style="height:40px;">
                      <select name="finance_week" class="styled">
                        <?php
                          if($week == 0){
                            $default_week = "selected='selected'";
                          }else{
                            $default_week = "";
                          }
                        ?>
                        <option value='0' <?=$default_week?>>Keseluruhan</option>
                       
                        <?php 
                            for($i=0;$i<sizeof($weeks);$i++):?>
                          <?php
                            $selected = "";
                            if($week==($weeks[$i])){
                              $selected = "selected='selected'";
                            }
                          ?>
                          <option value='<?=($weeks[$i])?>' <?=$selected?>>Minggu <?=($weeks[$i])?></option>
                        <?php endfor;?>
                      </select>
                    </div>
                    <table cellspacing="0" cellpadding="0" width="100%">
                      <tr class="head">
                        <td colspan="2">Neraca Minggu Lalu</td>
                        <td align="right" class="prevbalance">ss$ <?=number_format($starting_balance)?></td>
                      </tr>
                      <tr>
                        <td>Tiket</td>
                        <td></td>
                        <td class="alignright">Total Perolehan</td>
                      </tr>
                      <?php if(@$finance['tickets_sold']>0):?>
                      <tr>
                        <td>Tiket Terjual</td>
                        <td>ss$<?=round($finance['tickets_sold']/$total_items['tickets_sold'],2)?> x <?=number_format(@$total_items['tickets_sold'])?></td>
                        <td align="right">ss$ <?=number_format(@$finance['tickets_sold'])?></td>
                      </tr>
                      <?php else:?>
                      <tr>
                        <td>Tiket Terjual</td>
                        <td>-</td>
                        <td align="right">ss$ <?=number_format(@$finance['tickets_sold'])?></td>
                      </tr>
                      <?php endif;?>
                      <tr>
                        <td>Pemasukan Tambahan</td>
                        <td>&nbsp;</td>
                        <td>&nbsp;</td>
                      </tr>
                      <?php
                      if(isStaffExist($staff_token,'commercial_director')):
                      ?>
                      <tr>
                        <td>&nbsp;</td>
                        <td>Bonus Commercial Director</td>
                        <td align="right">ss$ <?=number_format(abs(@$finance['commercial_director_bonus']))?></td>
                      </tr>
                      <?php endif;?>
                      <?php
                      if(isStaffExist($staff_token,'marketing_manager')):
                      ?>
                      <tr>
                        <td>&nbsp;</td>
                        <td>Bonus Marketing Manager</td>
                        <td align="right">ss$ <?=number_format(abs(@$finance['marketing_manager_bonus']))?></td>
                      </tr>
                      <?php endif;?>
                       <?php
                      if(isStaffExist($staff_token,'public_relation_officer')):
                      ?>
                      <tr>
                        <td>&nbsp;</td>
                        <td>Bonus Public Relations</td>
                        <td align="right">ss$ <?=number_format(abs(@$finance['public_relation_officer_bonus']))?></td>
                      </tr>
                     <?php endif;?>
                      <tr>
                        <td>Sponsor</td>
                        <td>&nbsp;</td>
                        <td align="right">ss$ <?=number_format(abs(@$sponsor))?></td>
                      </tr>
                      <?php
                        if(isset($finance['player_sold'])):
                      ?>

                      <tr>
                        <td>Penjualan Pemain</td>
                        <td>&nbsp;</td>
                        <td align="right">ss$ <?=number_format(abs(@$finance['player_sold']))?></td>
                      </tr>
                      <?php
                      endif;
                      ?>
                      <?php if(isset($finance['win_bonus'])):?>
                      <tr>
                        <td>Bonus</td>
                        <td>Kemenangan</td>
                        <td align="right">ss$ <?=number_format(abs(@$finance['win_bonus']))?></td>
                      </tr>
                      <?php endif;?>
                      <?php if($other > 0):?>
                      <tr>
                        <td>Bonus</td>
                        <td>Lain - Lain</td>
                        <td align="right">ss$ <?=number_format(abs(@$other))?></td>
                      </tr>
                      <?php endif;?>
                      <tr class="head">
                        <td colspan="2">Total Perolehan</td>
                        <td align="right">ss$ <?=number_format(abs(@$finance['total_earnings']))?></td>
                      </tr>
                      <tr>
                        <td>Biaya Operasional</td>
                        <td>&nbsp;</td>
                        <td align="right">ss$ <?=number_format(abs(@$finance['operating_cost']))?></td>
                      </tr>
                      <tr class="head">
                        <td colspan="3">Gaji</td>
                      </tr>
                      <tr>
                        <td>Gaji Pemain</td>
                        <td align="right"></td>
                        <td align="right">ss$ <?=number_format(abs(@$finance['player_salaries']))?></td>
                      </tr>
                      <?php if(isset($finance['compensation_fee'])):?>
                      <tr>
                        <td>Biaya Kompesansi</td>
                        <td align="right"></td>
                        <td align="right">ss$ <?=number_format(abs(@$finance['compensation_fee']))?></td>
                      </tr>
                      <?php endif;?>
                      <?php if(isset($finance['ticket_sold_penalty'])):?>
                      <tr>
                        <td>Pinalti hasil penjualan tiket</td>
                        <td align="right"></td>
                        <td align="right">ss$ <?=number_format(abs(@$finance['ticket_sold_penalty']))?></td>
                      </tr>
                      <?php endif;?>
                      <?php if(isset($finance['security_overtime_fee'])):?>
                      <tr>
                        <td>Biaya Overtime Sekuriti</td>
                        <td align="right"></td>
                        <td align="right">ss$ <?=number_format(abs(@$finance['security_overtime_fee']))?></td>
                      </tr>
                      <?php endif;?>
                       <?php
                      if(isStaffExist($staff_token,'commercial_director')):
                      ?>
                      <tr>
                        <td>Commercial Director</td>
                        <td align="right"></td>
                        <td align="right">ss$ <?=number_format(abs(@$finance['commercial_director']))?></td>
                      </tr>
                       
                      <?php endif;?>
                        <?php
                      if(isStaffExist($staff_token,'marketing_manager')):
                      ?>
                      <tr>
                        <td>Marketing Manager</td>
                        <td align="right"></td>
                        <td align="right">ss$ <?=number_format(abs(@$finance['marketing_manager']))?></td>
                      </tr>
                       
                       <?php endif;?>
                       

                        <?php
                      if(isStaffExist($staff_token,'public_relation_officer')):
                      ?>
                      <tr>
                        <td>Public Relations</td>
                        <td align="right"></td>
                        <td align="right">ss$ <?=number_format(abs(@$finance['public_relation_officer']))?></td>
                      </tr>
                      
                       <?php endif;?>
                        <?php
                      if(isStaffExist($staff_token,'head_of_security')):
                      ?>
                      <tr>
                        <td>Head of Security</td>
                        <td align="right"></td>
                        <td align="right">ss$ <?=number_format(abs(@$finance['head_of_security']))?></td>
                      </tr>
                        
                       <?php endif;?>
                        <?php
                      if(isStaffExist($staff_token,'football_director')):
                      ?>
                      <tr>
                        <td>Footbal Director</td>
                        <td align="right"></td>
                        <td align="right">ss$ <?=number_format(abs(@$finance['football_director']))?></td>
                      </tr>
                        
                       <?php endif;?>
                        <?php
                      if(isStaffExist($staff_token,'chief_scout')):
                      ?>
                      <tr>
                        <td>Chief Scout</td>
                        <td align="right"></td>
                        <td align="right">ss$ <?=number_format(abs(@$finance['chief_scout']))?></td>
                      </tr>
                        
                       <?php endif;?>
                        <?php
                      if(isStaffExist($staff_token,'general_scout')):
                      ?>
                      <tr>
                        <td>General Scout</td>
                        <td align="right"></td>
                        <td align="right">ss$ <?=number_format(abs(@$finance['general_scout']))?></td>
                      </tr>
                        
                       <?php endif;?>
                        <?php
                      if(isStaffExist($staff_token,'finance_director')):
                      ?>
                      <tr>
                        <td>Finance Director</td>
                        <td align="right"></td>
                        <td align="right">ss$ <?=number_format(abs(@$finance['finance_director']))?></td>
                      </tr>
                       
                       <?php endif;?>
                        <?php
                      if(isStaffExist($staff_token,'tax_consultant')):
                      ?>
                      <tr>
                        <td>Tax Consultant</td>
                        <td align="right"></td>
                        <td align="right">ss$ <?=number_format(abs(@$finance['tax_consultant']))?></td>
                      </tr>
                       
                       <?php endif;?>
                        <?php
                      if(isStaffExist($staff_token,'accountant')):
                      ?>
                      <tr>
                        <td>Accountant</td>
                        <td align="right"></td>
                        <td align="right">ss$ <?=number_format(abs(@$finance['accountant']))?></td>
                      </tr>
                        
                       <?php endif;?>
                       <?php
                        if(isset($finance['buy_player'])):
                      ?>

                      <tr>
                        <td>Pembelian Pemain</td>
                        <td>&nbsp;</td>
                        <td align="right">ss$ <?=number_format(abs(@$finance['buy_player']))?></td>
                      </tr>
                      <?php
                      endif;
                      ?>
                      <?php if(abs(@$other_expenses) > 0):?>
                      <tr>

                        <td>Pengeluaran Lainnya</td>
                        <td></td>
                        <td align="right">ss$ <?=number_format(abs(@$other_expenses))?></td>
                      </tr>
                      <?php endif;?>
                      <tr class="head">
                        <td colspan="2">Total Pengeluaran</td>
                        <td align="right">ss$ <?=number_format(@$total_expenses)?></td>
                      </tr>
                      <tr class="head">
                        <td colspan="2">Neraca Berjalan</td>
                        <td align="right">ss$ <?=number_format(@$running_balance)?></td>
                    </tr>
                   </table>
                   <span>*Neraca klab di update mingguan setelah pertandingan.</span>
					</div><!-- end .col-content -->
                    <div class="col3">
                    	<div class="fr">
                            <?php for($i=0;$i<sizeof($sidebar_banner);$i++):?>
                            <div class="banner250x250">
                

                               <a 
                href="<?=$this->Html->url('/sponsors/jump/1/'.$sidebar_banner[$i]['Banners']['id'])?>" 
                    target="_blank">
                                      <img src="<?=$this->Html->url(Configure::read('avatar_web_url').
                                                  $sidebar_banner[$i]['Banners']['banner_file'])?>" />
                                  </a>
                              </div>
                            <?php endfor;?>
                        </div><!-- end .fr -->
                    </div><!-- end .col3 -->
              </div><!-- end #tabs-Keuagan -->
              <div id="tabs-Players">
                <div class="player-list">
                <table width="100%" border="0" cellspacing="0" cellpadding="0" class="footable">
                <thead>
                  <tr>
                    <th data-class="expand"></th>
                    <th width="210">Nama</th>
                    <th data-hide="phone,tablet" class="aligncenter" width="5">Umur</th>
                    <th data-hide="phone,tablet" width="64">Negara</th>
                    <th data-hide="phone,tablet" width="70">Posisi</th>
                   
                    <th data-hide="phone,tablet" class="alignright" width="120">Gaji* (ss$)</th>
                    <th data-hide="phone,tablet" class="aligncenter" width="5">Poin</th>
                    <th data-hide="phone,tablet" class="alignright" width="120">Nilai</th>
                    <th data-hide="phone,tablet" style="text-align:center;">Tindakan</th>
                  </tr>
                 </thead>
                 <tbody  id="myplayerlist">
                  <?php foreach($players as $player):?>
                  <?php
                    switch($player['position']){
                      case 'Goalkeeper':
                        $player_pos = "Goalkeeper";
                        $color = "grey";
                      break;
                      case 'Midfielder':
                        $player_pos = "Midfielder";
                        $color = "yellow";
                      break;
                      case 'Forward':
                        $player_pos = "Forward";
                        $color  = "red";
                      break;
                      default:
                        $player_pos = "Defender";
                        $color = "blue";
                      break;
                    }
                  ?>
                  <tr id="<?=$player['uid']?>">
                    <td>
                     <a class="thumbPlayers" href="<?=$this->Html->url('/manage/player/'.$player['uid'])?>"> <img src="http://omo.akamai.opta.net/image.php?custID=c8bb60c8f6d0184c33a87e6f3041b9cc&sport=football&entity=player&description=<?=str_replace('t','',$player['team_id'])?>&dimensions=103x155&id=<?=str_replace('p','',$player['uid'])?>"/></a>
                    </td>
                    <td>
                      <a class="yellow" href="<?=$this->Html->url('/manage/player/'.$player['uid'])?>"><?=h($player['name'])?></a></td>
                    
                    <td class="aligncenter"><?=round((time()-strtotime($player['birth_date']))/(24*60*60*365))?></td>
                    <td><?=h($player['country'])?></td>
                    <td><?=$player_pos?></td>
                   
                    <td class="alignright">ss$ <?=number_format($player['player_salary'])?></td>
                    <?php
                      if($player['points']!=0){
                        $last_performance = floatval($player['last_performance']);
                        $performance_bonus = getTransferValueBonus($last_performance,intval($player['transfer_value']));
                      }else{
                        $performance_bonus = 0;
                      }
                    ?>
                    <td class="aligncenter"><?=(($player['points']))?></td>
                    <td class="alignright">ss$ <?=number_format(intval($player['transfer_value'])+$performance_bonus)?></td>
                    <td width="10"><a data-team-name="<?=h($club['team_name'])?>" data-player-name="<?=$player['name']?>" data-price="<?=number_format(intval($player['transfer_value'])+$performance_bonus)?>" data-team="<?=$player['team_id']?>" data-player="<?=$player['uid']?>" id="btnSale" class="buttons" href="<?=$this->Html->url('/manage/player/'.$player['uid'])?>"><span>LIHAT</span></a></td>
                  </tr>
                  <?php endforeach;?>
                 </tbody>
                </table>
                </div><!-- end .player-list -->
                <div class="rows">
                  <?php for($i=0;$i<sizeof($long_banner);$i++):?>
                	<div class="col2">
                        <div class="mediumBanner">
                             <a 
                href="<?=$this->Html->url('/sponsors/jump/1/'.$long_banner[$i]['Banners']['id'])?>" 
                    target="_blank">


                              <img src="<?=$this->Html->url(Configure::read('avatar_web_url').
                                $long_banner[$i]['Banners']['banner_file'])?>" />
                          </a>
                        </div><!-- end .mediumBanner -->
                    </div><!-- end .col2 -->
                	<?php endfor;?>
                </div><!-- end .rows -->
              </div><!-- end #tabs-Squad -->
              
            </div><!-- end #clubtabs -->
		</div>
		
		
    </div><!-- end #thecontent -->
</div><!-- end #faqPage -->

<!--popups-->
<div class="popup">
    <div class="popupContainer popup-small" id="popup-messages">
        <div class="popupHeader">
        </div><!-- END .popupHeader -->
        <div class="popupContent">
            <div class="entry-popup">
                yellow
            </div><!--END .entry-popup-->
        </div><!-- END .popupContent -->
    </div><!-- END .popupContainer -->
</div><!-- END .popup --> 


<script>
$("#btnSale").fancybox({
    beforeLoad : function(){
      $("#popup-messages .popupContent .entry-popup").html('');
      $('.saving').hide();
      $('.confirm').show();
      $('.success').hide();
      $('.failure').hide();
      render_view(tplsale,"#popup-messages .popupContent .entry-popup",{
        player_id:$(this.element).data('player'),
        team_id:$(this.element).data('team'),
        player_name:$(this.element).data('player-name'),
        team_name:$(this.element).data('team-name'),
        transfer_value:$(this.element).data('price')
      });
      $jqOpta.widgetStart(_optaParams);
    },
});
</script>
<script type="text/template" id="tplsale">
    <%
      var uid = player_id.replace('p','');
      var team = team_id.replace('t','');
    %>
    <div class="confirm">
        <h1>Apakah kamu ingin menjual pemain ini?</h1>
        <h3>Pemain yang sudah dijual akan hilang dari lineup dan tidak dapat di undo</h3>
        <h4>ss$ <%=transfer_value%></h4>
        <opta widget="playerprofile" sport="football" competition="8" season="2013" team="<%=team%>" 
          player="<%=uid%>" show_image="true" show_nationality="true" opta_logo="false" 
          narrow_limit="400"></opta>
        <div><a href="#/sale/<%=player_id%>/0" class="button">Jual</a>
            <a href="#" class="button" onclick="$.fancybox.close();return false;">Batal</a></div>
    </div>
    <div class="saving" style="display:none;">
        <h1>Menjual Pemain.</h1>
        <h3>Harap tunggu sebentar..</h3>
        <p><img src="<?=$this->Html->url('/css/fancybox/fancybox_loading@2x.gif')?>"/></p>
    </div>
    <div class="success" style="display:none;">
        <h1>Penjualan Berhasil</h1>
        <h3><%=player_name%> sudah dijual dari <%=team_name%></h3>
    </div>
    <div class="failure" style="display:none;">
        <h1>Penjualan Tidak Berhasil</h1>
        <h3>Silahkan coba kembali !</h3>
    </div>
</script>

<?php if(isset($tab)):?>
<script>
$( "#clubtabs" ).tabs({active:<?=intval($tab)?>});
</script>
<?php endif;?>

<?=$this->Html->script(array('highcharts'))?>

<script>
var stats = <?=json_encode($weekly_points)?>;

var categories = [];
var data = [];
$.each(stats,function(k,v){
  categories.push(v.matchday);
  data.push(parseFloat(v.points));
});


$('#chart_ranking').highcharts({
	colors: ['#c00', '#e12626', '#999999'],
    chart: {
        type: 'area',
        backgroundColor:'#ccc',
        style: {
            color: "#000"
        },
    },
    title: {
        text: '',
        style: {
          color: '#000'
        }
    },
   
    xAxis: {
        
        categories: categories,
       	title: {
            text: '',
            style:{
              color:'#000'
            }
        },
    },
    yAxis: {
        title: {
            text: '',
            style:{
              color:'#000'
            }
        },

    },
    tooltip: {
        enabled: true,
        formatter: function() {
            return '<strong>Minggu '+this.x+'</strong><br/>'+
                    this.series.name+': '+ Highcharts.numberFormat(this.y,1) +'';
        }
    },
    plotOptions: {
        area: {
            stacking: 'normal',
            lineColor: '#666666',
            lineWidth: 1,
            marker: {
                lineWidth: 1,
                lineColor: '#666666'
            }
        }
    },
    legend: {
      enabled: false
    },
    credits:false,
    series: [
        {
            name: 'Poin',
            data: data
        },
    ]
});

var stats = <?=json_encode($my_balance)?>;

categories = [];
data = [];
$.each(stats,function(k,v){
  categories.push(v.week);
  data.push(parseFloat(v.balance));
});

$('#chart_keuangan').highcharts({
	colors: ['#c00', '#e12626', '#999999'],
    chart: {
        type: 'area',
        backgroundColor:'#ccc',
        style: {
            color: "#000"
        },
    },
    title: {
        text: '',
        style: {
          color: '#000'
        }
    },
   
    xAxis: {
        
        categories: categories,
       	title: {
            text: '',
            style:{
              color:'#000'
            }
        },
    },
    yAxis: {
        title: {
            text: '',
            style:{
              color:'#000'
            }
        },

    },
    legend: {
      enabled: false
    },
    tooltip: {
        enabled: true,
        formatter: function() {
            return '<strong>Minggu '+this.x+'</strong><br/>'+
                    ': ss$'+ Highcharts.numberFormat(this.y,0) +'';
        }
    },
    plotOptions: {
        area: {
            stacking: 'normal',
            lineColor: '#666666',
            lineWidth: 1,
            marker: {
                lineWidth: 1,
                lineColor: '#666666'
            }
        }
    },
    credits:false,
    series: [
        {
            name: 'Saldo',
            data: data
        },
    ]
});

</script>

<script>
$('#mainNav li a').click(function(e){
  var hash = window.location.hash;
  if(hash!=null){
    $("#tabs-Info").hide();
    $("#tabs-Players").hide();
    $("#tabs-Money").hide();
    var tag = $(this).attr('href').split('#');
    $('#'+tag[1]).show();
    $(window).scrollTop(0);
  } 
});




$(function() {
    $( "#clubtabs" ).tabs({
        active:<?=intval(@$active_tab)?>
    });
  });
</script>

<script>
$('select[name=finance_week]').change(function(e){
  document.location = "<?=$this->Html->url('/manage/club?week=')?>"+parseInt($(this).val());
});
</script>