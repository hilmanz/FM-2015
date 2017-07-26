<div id="fillPlayersPage">
	
    <div id="thecontent">
        <div id="content">
        	<div class="content">
            	<div class="row-2">
                    <div class="col2">
                        <h1 class="lh30">Jajaran Pemain Starter <br/>dan Pemain Cadangan </h1>
                    </div>
                    <div class="col2">
						  <img class="teamlogo" src="http://widgets-images.s3.amazonaws.com/football/team/badges_65/<?=str_replace('t','',$original['uid'])?>.png"/>
                          
                          <h3 class="teamname"> <?=htmlspecialchars(stripslashes($selected_team['team_name']))?></h3>
                    </div>
    			</div><!-- end .row-2 -->
                <form class="theForm" method="post" action="<?=$this->Html->url('/profile/create_team')?>" enctype="multipart/form-data">
                    
                    <div class="row-2">
						<div class="playerlistbox last">
							<div class="titles">
								<h2>Inilah daftar pemain tim elo, sanggup mengurus tim ini ? Kalo siap  segera pencet tombol "LANJUT"</h2>
							</div><!-- end .titles -->
                       		 <!-- available players -->
							<table width="100%" border="0" cellspacing="0" cellpadding="0">
							<thead>
							  <tr>
                                <th></th>
								<th>Nama</th>
								<th>Posisi</th>
                                <th>Tgl.Lahir</th>
                                <th>Usia</th>
                               
                                <th>Gaji</th>
							  </tr>
							 </thead>
							 <tbody  id="available">
							 </tbody>
							</table>
                        </div>
						
                    </div><!-- end .row-2 -->
                    
                    <div class="row-2">
                        <input type="hidden" name="players" value="">
                        
                        <a class="button fl" href="<?=$this->Html->url('/profile/register_team')?>">Pilih Tim Lain</a>
                        <a class="button fr" href="javascript:void();" onclick="create_team();return false;">Simpan & Lanjutkan</a>
                    </div><!-- end .row-2 -->
                </form>
			</div><!-- end .content -->
        </div><!-- end #content -->
	<div id="sidebar" class="tr">
		
	    <div class="widget">
	        <div class="nav-side">
	            <ul>
                   <li><span>Isi data lengkap Anda</span></li>
                   <li><span>Pilih Tim</span></li>
                   <li class="current"><span>Pilih Pemain</span></li>
                   <li><span>Pilih Staff</span></li>
	              
	            </ul>
	        </div><!-- end .nav-side -->
	    </div><!-- end .widget -->
	    <div class="widget">
	        <div class="cash-left">
	            <h3 class="red">SISA UANG</h3>
	            <h1>SS$ <?=number_format($INITIAL_BUDGET)?></h1>
	            <h3 class="red">Est. PENGELUARAN MINGGUAN</h3>
	            <h1>SS$ <span class="expense">0</span></h1>
	        </div>
	    </div><!-- end .widget -->
	</div><!-- end #sidebar -->
    </div><!-- end #thecontent -->
</div><!-- end #fillDetailsPage -->
<script>
function create_team(){
    populate_selected(function(s){
        $("input[name='players']").val(s);
        $("form.theForm").submit();
    });
}
//EVENTS
$("select[name='select_team']").on('custom_select', function(event, data) {
   getPlayers(data.value);
});
function getPlayers(team_id){
      $("#available").html('Please wait...');
     api_call("<?=$this->Html->url('/game/players/')?>"+team_id,function(response){
        $("#available").html('');
        if(response.length>0){
            try{
                tmp['available_teams'] = response;
                var n = response.length;
                $.each(response,function(k,v){
                    if(v!=null){
                        est_expenses += v.salary;
                        v.team_id = team_id;
                        append_view(player,'#available',v);    
                    }
                    if(k==(n-1)){
                        $('.expense').html(number_format(est_expenses));
                    }
                });
            }catch(e){
                $("#available").html('Gagal memuat daftar pemain, <a href="javascript:getPlayers("<?=$selected_team['team_id']?>");" class="button">Coba Lagi</a>');
            }
            
        }else{
            $("#available").html('Gagal memuat daftar pemain, <a href="javascript:getPlayers("<?=$selected_team['team_id']?>");" class="button">Coba Lagi</a>');
        }
    });
}
$(document).ready(function(){
  //getPlayers("<?=$selected_team['team_id']?>");  
  populatePlayers("<?=$selected_team['team_id']?>",<?=$player_selected?>);
});

function populatePlayers(team_id,response){
    $("#available").html('');
        
        if(response.length>0){
            try{
                tmp['available_teams'] = response;
                var n = response.length;
                $.each(response,function(k,v){
                    if(v!=null){
                        est_expenses += v.salary;
                        v.team_id = team_id;
                        append_view(player,'#available',v);    
                    }
                    if(k==(n-1)){
                        $('.expense').html(number_format(est_expenses));
                    }
                });
            }catch(e){
                 $("#available").html('Gagal memuat daftar pemain, '+
                '<a href="javascript:getPlayers(\''+team_id+'\');" class="button">Coba Lagi</a>');
            }
                
        }else{
             $("#available").html('Gagal memuat daftar pemain, '+
                '<a href="javascript:getPlayers(\''+team_id+'\');" class="button">Coba Lagi</a>');
        }
}
</script>
<script type="text/template" id="player">
  <%
        var jersey_color = 'j-red';
        var pos_code = 'F';
        switch(position){
            case 'Goalkeeper':
                pos_code = 'Goalkeeper';
                jersey_color = 'j-grey';
            break;
            case 'Defender':
                pos_code = 'Defender';
                jersey_color = 'j-blue';
            break;
            case 'Midfielder':
                pos_code = 'Midfielder';
                jersey_color = 'j-yellow';
            break;
            case 'Forward':
                pos_code = 'Forward';
                jersey_color = 'j-red';
            break;
            default:
                pos_code = 'Forward';
                jersey_color = 'j-red';
            break;
        }
		$("table tbody tr:nth-child(odd)").addClass("odd");
		$("table tbody tr:nth-child(even)").addClass("even");
        var bod = new Date(birth_date);
        var age = Math.round((new Date().getTime() - bod.getTime()) / (24*60*60*1000*365));
        var birthday = ((bod.getDate()<10)?'0'+bod.getDate():bod.getDate())+'-'+
                        (((bod.getMonth()+1)<10)?'0'+(bod.getMonth()+1):(bod.getMonth()+1))+'-'+bod.getFullYear();
        var custId = uid.replace('p','');
        var n_team_id = team_id.replace('t','');
    %>
	
  <tr id="<%=uid%>"  class="odd">
    <td><a href="" class="thumbPlayers"><img src="http://omo.akamai.opta.net/image.php?custID=c8bb60c8f6d0184c33a87e6f3041b9cc&sport=football&entity=player&description=<%=n_team_id%>&dimensions=103x155&id=<%=custId%>"/></a></td>
    <td><%=name%></td>
    <td><%=pos_code%></td>
    <td><%=birthday%></td>
    <td><%=age%></td>
    
    <td><%=number_format(salary)%></td>
  </tr>
</script>
<script type="text/template" id="player_selected">
<div class="jersey-player ">
<a href="#/unselect_player/<%=uid%>" id="<%=uid%>">
    <%
        var jersey_color = 'j-red';
        var pos_code = 'F';
        switch(position){
            case 'Goalkeeper':
                pos_code = 'G';
                jersey_color = 'j-grey';
            break;
            case 'Defender':
                pos_code = 'D';
                jersey_color = 'j-blue';
            break;
            case 'Midfielder':
                pos_code = 'M';
                jersey_color = 'j-yellow';
            break;
            case 'Forward':
                pos_code = 'F';
                jersey_color = 'j-red';
            break;
            default:
                pos_code = 'F';
                jersey_color = 'j-red';
            break;
        }
    %>
    <div class="jersey <%=jersey_color%>"><%=pos_code%></div>
    <span class="player-name"><%=name%></span>
</a>
</div>
</script>