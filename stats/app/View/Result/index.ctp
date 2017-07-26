
<div class="widget">
    <div class="widget-title">
        <h3>Match Stats</h3>
    </div><!-- end .widget-title -->
    <div class="widget-content">
        <table width="100%" border="0" cellspacing="0" cellpadding="0" class="blacktable">
        <thead>
              <tr>
                <th><h4>Overall</h4></th>
                <th class="tcenter"><h5 class="jersey"><img src="http://widgets-images.s3.amazonaws.com/football/team/badges_20/<?=str_replace("t","",@$result['info']['home_team'])?>.png"/></h5></th>
                <th class="tcenter"><h5 class="jersey"><img src="http://widgets-images.s3.amazonaws.com/football/team/badges_20/<?=str_replace("t","",@$result['info']['away_team'])?>.png"/></h5></th>
              </tr>
          </thead>
          <tbody>
              <tr>
                <td><p class="s-title">Goals Scored</p></td>
                <td class="tcenter"><a class="red-arrow"><?=@$result['info']['home_score']?></a></td>
                <td class="tcenter"><a class="red-arrow"><?=@$result['info']['away_score']?></a></td>
              </tr>
              <tr>
                <td><p class="s-title">Possession</p></td>
                <td class="tcenter"><a class="red-arrow"><?=@$result['stats']['home']['possession_percentage']?> %</a></td>
                <td class="tcenter"><a class="red-arrow"><?=@$result['stats']['away']['possession_percentage']?> %</a></td>
              </tr>
             <tr>
                <td><p class="s-title">Corners</p></td>
                <td class="tcenter"><a class="red-arrow"><?=@$result['stats']['home']['total_corners_intobox']?> </a></td>
                <td class="tcenter"><a class="red-arrow"><?=@$result['stats']['away']['total_corners_intobox']?> </a></td>
              </tr>
              <tr>
                <td><p class="s-title">Offsides</p></td>
                <td class="tcenter"><a class="red-arrow"><?=@$result['stats']['home']['total_offside']?> </a></td>
                <td class="tcenter"><a class="red-arrow"><?=@$result['stats']['away']['total_offside']?> </a></td>
              </tr>
               <tr>
                <td><p class="s-title">Accurate Passes</p></td>
                <td class="tcenter"><a class="red-arrow"><?=@$result['stats']['home']['accurate_pass']?> </a></td>
                <td class="tcenter"><a class="red-arrow"><?=@$result['stats']['away']['accurate_pass']?> </a></td>
              </tr>
               <tr>
                <td><p class="s-title">Total Passes</p></td>
                <td class="tcenter"><a class="red-arrow"><?=@$result['stats']['home']['total_pass']?> </a></td>
                <td class="tcenter"><a class="red-arrow"><?=@$result['stats']['away']['total_pass']?> </a></td>
              </tr>
              <tr>
                <td><p class="s-title">Passes Percentage</p></td>
                <td class="tcenter"><a class="red-arrow">
                  <?=@round(@$result['stats']['home']['accurate_pass']/@$result['stats']['home']['total_pass']*100)?> %  </a></td>
                <td class="tcenter"><a class="red-arrow">
                <?=@round(@$result['stats']['away']['accurate_pass']/@$result['stats']['away']['total_pass']*100)?> %  </a></td>
              </tr>
              <tr>
                <td><p class="s-title">Crosses</p></td>
                <td class="tcenter"><a class="red-arrow"><?=@$result['stats']['home']['total_cross']?> </a></td>
                <td class="tcenter"><a class="red-arrow"><?=@$result['stats']['away']['total_cross']?> </a></td>
              </tr>
               <tr>
                <td><p class="s-title">Throws</p></td>
                <td class="tcenter"><a class="red-arrow"><?=@$result['stats']['home']['total_throws']?> </a></td>
                <td class="tcenter"><a class="red-arrow"><?=@$result['stats']['away']['total_throws']?> </a></td>
              </tr>
              <tr>
                <td><p class="s-title">Tackles</p></td>
                <td class="tcenter"><a class="red-arrow"><?=@$result['stats']['home']['total_tackle']?> </a></td>
                <td class="tcenter"><a class="red-arrow"><?=@$result['stats']['away']['total_tackle']?> </a></td>
              </tr>
              <tr>
                <td><p class="s-title">Long Balls</p></td>
                <td class="tcenter"><a class="red-arrow"><?=@$result['stats']['home']['total_long_balls']?> </a></td>
                <td class="tcenter"><a class="red-arrow"><?=@$result['stats']['away']['total_long_balls']?> </a></td>
              </tr>
              <tr>
                <td><p class="s-title">Yellow Card</p></td>
                <td class="tcenter"><a class="red-arrow"><?=@intval($result['stats']['home']['total_yel_card'])?> </a></td>
                <td class="tcenter"><a class="red-arrow"><?=@intval($result['stats']['away']['total_yel_card'])?> </a></td>
              </tr>
              <tr>
                <td><p class="s-title">Red Card</p></td>
                <td class="tcenter"><a class="red-arrow"><?=@intval($result['stats']['home']['total_red_card'])?> </a></td>
                <td class="tcenter"><a class="red-arrow"><?=@intval($result['stats']['away']['total_red_card'])?> </a></td>
              </tr>
          </tbody>                    
        </table>
    </div><!-- end .widget-content -->
</div><!-- end .widget -->
<?php echo $this->element("sql_dump");?>