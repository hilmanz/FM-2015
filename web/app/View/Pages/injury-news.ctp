<div id="faqPage">
    <div id="thecontent">
        <div class="content">
        	<?php $news = $this->requestAction('/news/getInjuryNews',array());?>
        	<?php foreach($news as $n):?>
        	
            <div class="row">
                <h1><?=$n['a']['post_title']?></h1>
                <p><?=$n['a']['post_content']?></p>
            </div>
        	<?php endforeach;?>
        </div><!-- end .content -->
    </div><!-- end #thecontent -->
</div><!-- end #faqPage -->
