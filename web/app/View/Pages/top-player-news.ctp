<div id="faqPage">
    <div id="thecontent">
        <div class="content">
            <div class="row">
                <?php $news = $this->requestAction('/news/getTopPlayerNews',array());?>
                
                <h1><?=$news['post_title']?></h1>
                <p><?=$news['post_content']?></p>
            </div>
        </div><!-- end .content -->
    </div><!-- end #thecontent -->
</div><!-- end #faqPage -->
