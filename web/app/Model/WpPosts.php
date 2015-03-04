<?php
App::uses('AppModel', 'Model');

class WpPosts extends AppModel {
	public $name ='WpPosts';
	public $useTable = "wp_posts";
	public $useDbConfig = "wordpress";
	public function getInjuryNews(){
		$sql = "SELECT a.* FROM wp_posts a
				 INNER JOIN wp_term_relationships b
				 ON a.ID = b.object_id
				 INNER JOIN wp_term_taxonomy c
				 ON c.term_taxonomy_id = b.term_taxonomy_id
				 INNER JOIN wp_terms d
				 ON d.term_id = c.term_id
				 WHERE d.slug = 'fm-injury-news' ORDER BY a.ID DESC LIMIT 1;";
		$rs = $this->query($sql,false);
		return $rs[0]['a'];
	}
	public function getTopPlayerNews(){
		$sql = "SELECT a.* FROM wp_posts a
				 INNER JOIN wp_term_relationships b
				 ON a.ID = b.object_id
				 INNER JOIN wp_term_taxonomy c
				 ON c.term_taxonomy_id = b.term_taxonomy_id
				 INNER JOIN wp_terms d
				 ON d.term_id = c.term_id
				 WHERE d.slug = 'fm-top-player-news' ORDER BY a.ID DESC LIMIT 1;";
		$rs = $this->query($sql,false);
		return $rs[0]['a'];
	}
	public function readNews($post_id){
		$post_id = intval($post_id);
		$rs = $this->query("SELECT * FROM wp_posts WHERE ID = {$post_id} LIMIT 1");
		return $rs[0]['wp_posts'];
	}
}
