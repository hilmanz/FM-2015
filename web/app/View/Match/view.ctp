<?php
$data = $o['data'];
?>
<div id="fillDetailsPage">
     <?php echo $this->element('infobar'); ?>
    <div id="thecontent">
        <div id="contents">
            <div class="content">
                <div class="row-2">
                    <h1 class="red">Livestats</h1>
                    <p></p>
                </div><!-- end .row-2 -->
                <div class="row">
                    <table width="100%">
                        <tr>
                            <td align="center" width="45%">
                            	<img style="height:46px;" src="http://widgets-images.s3.amazonaws.com/football/team/badges_65/<?=str_replace('t','',$data[0]['team_id'])?>.png"/>
                            	<h3><?=$data[0]['name']?> ( <?=$data[0]['score']?> )</h3>
                            </td>
                            
                            <td align="center" width="45%">
                            	<img style="height:46px;" src="http://widgets-images.s3.amazonaws.com/football/team/badges_65/<?=str_replace('t','',$data[1]['team_id'])?>.png"/>
                            	<h3><?=$data[1]['name']?> ( <?=$data[1]['score']?> )</h3>
                            </td>
                        </tr>
                        <tr>
                            <td valign="top">
                                <table width="100%">
                                	<?php foreach($data[0]['player_stats'] as $player_id=>$player_data):?>
                                	<tr>
                                		<td>
                                			 <img class="match-view-img" src="http://omo.akamai.opta.net/image.php?custID=c8bb60c8f6d0184c33a87e6f3041b9cc&sport=football&entity=player&description=<?=str_replace('t','',$data[0]['team_id'])?>&dimensions=103x155&id=<?=str_replace('p','',$player_id)?>"/>
                                			 <div>
                                			 	<?php
                                				echo h($player_data['name']);
                                				?>
                                			 </div>
                                		</td>
                                	
                                		<td>
                                			<div id="chart-<?=$player_id?>" class="chart-player">

                                			</div>
                                		</td>
                                		<td>
                                			<div id="totalpoints-<?=$player_id?>">
                                			0
                                			</div>
                                		</td>
                                	</tr>
                                	<?php endforeach;?>
                                </table>
                            </td>
                           
                            <td valign="top">
                               <table width="100%">
                                	<?php foreach($data[1]['player_stats'] as $player_id=>$player_data):?>
                                	<tr>
                                		<td>
                                			 <img class="match-view-img" src="http://omo.akamai.opta.net/image.php?custID=c8bb60c8f6d0184c33a87e6f3041b9cc&sport=football&entity=player&description=<?=str_replace('t','',$data[1]['team_id'])?>&dimensions=103x155&id=<?=str_replace('p','',$player_id)?>"/>
                                			 <div>
                                			 	<?php
                                				echo h($player_data['name']);
                                				?>
                                			 </div>
                                		</td>
                                		
                                		<td>
                                			<div id="chart-<?=$player_id?>" class="chart-player">

                                			</div>
                                		</td>
                                		<td>
                                			<div id="totalpoints-<?=$player_id?>">
                                			0
                                			</div>
                                		</td>
                                	</tr>
                                	<?php endforeach;?>
                                </table>
                            </td>
                        </tr>
                    </table>
                    <div class="row">
                        <a href="<?=$this->Html->url('/match')?>" class="button">Kembali ke Daftar</a>
                    </div>
                </div>
            </div><!-- end .content -->
        </div><!-- end #content -->
    <div id="sidebar" class="tr">
        
    </div><!-- end #sidebar -->
    </div><!-- end #thecontent -->
</div><!-- end #fillDetailsPage -->

<script>
var plots = {};

function loadStats(){
	$.ajax({
		  url: '<?=$this->Html->url('/match/livestats/'.$game_id)?>',
		  dataType: 'json',
		  success: function(response){
		  	if(response.status==1){
		  		for(var i in response.data){
		  			plotChart(i,response.data[i]);
		  		}
		  		
		  	}
		  }
	});
	setTimeout(function(){
		loadStats();
	},1000*60*2);
}


function plotChart(player_id,data){
	var series = [];
	
	var scores = 0;
	
	for(var i in data){
        var poin = data[i].points;
        if(i>0){
            poin = data[i].points - data[i-1].points;
        }
		series.push([
			date("h:i",data[i].ts),
			poin
		]);
		scores = data[i].points;
	}
	$("#totalpoints-"+player_id).html(scores);
	if(typeof plots[player_id] === 'undefined'){

		plots[player_id] = $.jqplot ('chart-'+player_id, [series],{
							animate: true,

							axesDefaults: {
						   		showTicks:false
							},
							axes:{
							    xaxis:{
							      renderer:$.jqplot.DateAxisRenderer,
							      tickOptions:{
							        formatString:'%H:%M'
							      } 
							    },
							    yaxis:{
							      tickOptions:{
							        formatString:'%.0f'
							        }
							    }
							  },
							highlighter: {
							    show: true,
							    sizeAdjust: 7.5
							}

						});
	}else{

        plots[player_id].replot({data:[series]});
	}
	
}
loadStats();
</script>