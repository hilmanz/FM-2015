<div id="loginContainer">
    <div class="container">
      <h3>Pilih Liga yang Ingin Lo Mainkan!</h3>
      <h4 class="yellow">
      	Sekarang, lo punya 2 pilihan liga untuk dimainkan: English Premier League dan Italian Serie A. Siap untuk tantangan baru ini? Tentukan pilihan lo sekarang.
      </h4>
    </div>
    <div  id="pilih-liga" class="widgets tr">
    	<div>
	       <a href="<?=$this->Html->url('/login/?league=epl')?>">
	       	<img src="<?=$this->Html->url('/images/epl.jpg')?>"/>
	       </a>
   		</div>
   		<div>
	       <a href="<?=$this->Html->url('/login/?league=epl')?>" class="button">
	       	LOGIN KE LIGA PREMIER
	       </a>
   		</div>	
    </div>
    <div  id="pilih-liga"  class="widgets tr">
      <div>

	       <a href="<?=$this->Html->url('/login/?league=ita')?>">
	       	<img src="<?=$this->Html->url('/images/italy.jpg')?>"/>
	       </a>
   		</div>
   		<div>
	       <a href="<?=$this->Html->url('/login/?league=ita')?>" class="button">
	         LOGIN KE SERIE A
	       </a>
   		</div>	
    </div>
</div>
<!-- Conversion: Supersoccer_activity_choose_league_page -->
<img src="http://avn.innity.com/conversion/?cb=25431&conversion=709&value=[VALUE]" width="1" height="1" border="0"/>
