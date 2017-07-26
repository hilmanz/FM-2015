<?php echo $this->element('stats_navigation'); ?>
<!--
BPL Leaders Table
-->

<div>
	<h3>BPL Leaders Table</h3>
</div>
<div style="background-color:black;">
<table width="100%" border="0" cellspacing="0" cellpadding="0" class="blacktable">
    <thead>
          <tr>
            <th colspan="2"><h4>Team</h4></th>
            <th><h5>Games Played</h5></th>
            <th><h5>Games Won</h5></th>
            <th><h5>Games Drawn</h5></th>
            <th><h5>Games Lost</h5></th>
            <th><h5>Goals Scored</h5></th>
            <th><h5>Goals Conceded</h5></th>
            <th><h5>Points Earned</h5></th>
            <th><h5>Top Scorer</h5></th>
            <th><h5>Top Assist</h5></th>
          </tr>
      </thead>
      <tbody>
        <?php
          foreach($data as $d):
            $stats = $d['stats'];
        ?>
          <tr>
            <td><a href="<?=$this->Html->url('/stats/team/?team_id='.$d['team_id'])?>"><img style="height:46px" src="http://widgets-images.s3.amazonaws.com/football/team/badges_65/<?=str_replace('t','',$d['team_id'])?>.png"/></a></td>
            <td><a href="<?=$this->Html->url('/stats/team/?team_id='.$d['team_id'])?>"><?=h($d['name'])?></a></td>
            <td>
              <a class="red-arrow">
                  <?=number_format($stats['games_played'])?>
              </a>
            </td>
            <td>
              <a class="red-arrow">
                  <?=number_format($stats['games_won'])?>
              </a>
            </td>
            <td>
              <a class="red-arrow">
                  <?=number_format($stats['games_drawn'])?>
              </a>
            </td>
            <td>
              <a class="red-arrow">
                  <?=number_format($stats['games_lost'])?>
              </a>
            </td>
            <td>
              <a class="red-arrow">
                  <?=number_format($stats['goals'])?>
              </a>
            </td>
            <td>
              <a class="red-arrow">
                  <?=number_format($stats['goals_conceded'])?>
              </a>
            </td>
            <td>
              <a class="red-arrow">
                  <?=number_format($stats['points_earned'])?>
              </a>
            </td>
            <td>
              <a class="red-arrow">
                 <?php
                  if(is_array($stats['top_scorer'])):
                 ?>
                
                <?=@$stats['top_scorer']['name']?>
                <?php endif;?>
              </a>
            </td>
            <td>
              <a class="red-arrow">
                 <?php
                  if(is_array($stats['top_scorer'])):
                 ?>
                
                <?=@$stats['top_assist']['name']?>
                <?php endif;?>
              </a>
            </td>
          </tr>
        <?php endforeach;?>
      </tbody>                    
    </table>
</div>