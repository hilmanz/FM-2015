<?php
App::uses('AppModel', 'Model');

class WpPosts extends AppModel {
	public $name ='WpPosts';
	public $useTable = "wp_posts";
	public $useDbConfig = "wordpress";
	public function getInjuryNews($league='epl',$plan='pro1'){

		if($league=='ita'){
			
			$sql = "SELECT a.* FROM wp_posts a
				 INNER JOIN wp_term_relationships b
				 ON a.ID = b.object_id
				 INNER JOIN wp_term_taxonomy c
				 ON c.term_taxonomy_id = b.term_taxonomy_id
				 INNER JOIN wp_terms d
				 ON d.term_id = c.term_id
				 WHERE d.slug = 'fm-injury-news-ita' ORDER BY a.ID DESC LIMIT 10;";
			$rs = $this->query($sql,false);
		}else{
			$sql = "SELECT a.* FROM wp_posts a
				 INNER JOIN wp_term_relationships b
				 ON a.ID = b.object_id
				 INNER JOIN wp_term_taxonomy c
				 ON c.term_taxonomy_id = b.term_taxonomy_id
				 INNER JOIN wp_terms d
				 ON d.term_id = c.term_id
				 WHERE d.slug = 'fm-injury-news' ORDER BY a.ID DESC LIMIT 10;";
			$rs = $this->query($sql,false);
		}
		
		return $rs;
	}
	public function getTopPlayerNews($league='epl',$plan='pro1'){
		print "<!-- PRO PLAN : {$plan}-->";
		if($league=='ita'){
			if($plan=='pro2'){
				$sql = "SELECT a.* FROM wp_posts a
					 INNER JOIN wp_term_relationships b
					 ON a.ID = b.object_id
					 INNER JOIN wp_term_taxonomy c
					 ON c.term_taxonomy_id = b.term_taxonomy_id
					 INNER JOIN wp_terms d
					 ON d.term_id = c.term_id
					 WHERE d.slug = 'fm-top-player-news-ita' ORDER BY a.ID DESC LIMIT 1;";
				$rs = $this->query($sql,false);
			}else{
				$sql = "SELECT a.* FROM wp_posts a
					 INNER JOIN wp_term_relationships b
					 ON a.ID = b.object_id
					 INNER JOIN wp_term_taxonomy c
					 ON c.term_taxonomy_id = b.term_taxonomy_id
					 INNER JOIN wp_terms d
					 ON d.term_id = c.term_id
					 WHERE d.slug = 'fm-top-player-news-ita-pro1' ORDER BY a.ID DESC LIMIT 1;";
				$rs = $this->query($sql,false);
			}
			
		}else{
			if($plan=='pro2'){
				$sql = "SELECT a.* FROM wp_posts a
						 INNER JOIN wp_term_relationships b
						 ON a.ID = b.object_id
						 INNER JOIN wp_term_taxonomy c
						 ON c.term_taxonomy_id = b.term_taxonomy_id
						 INNER JOIN wp_terms d
						 ON d.term_id = c.term_id
						 WHERE d.slug = 'fm-top-player-news' ORDER BY a.ID DESC LIMIT 1;";
				$rs = $this->query($sql,false);
			}else{
				$sql = "SELECT a.* FROM wp_posts a
						 INNER JOIN wp_term_relationships b
						 ON a.ID = b.object_id
						 INNER JOIN wp_term_taxonomy c
						 ON c.term_taxonomy_id = b.term_taxonomy_id
						 INNER JOIN wp_terms d
						 ON d.term_id = c.term_id
						 WHERE d.slug = 'fm-top-player-news-pro1' ORDER BY a.ID DESC LIMIT 1;";
				$rs = $this->query($sql,false);
			}
		}
		print "<!--plan : ".$plan."-->";
		return $rs[0]['a'];
	}
	public function readNews($post_id){
		$post_id = intval($post_id);
		$rs = $this->query("SELECT * FROM wp_posts WHERE ID = {$post_id} LIMIT 1");
		return $rs[0]['wp_posts'];
	}
}
