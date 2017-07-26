<?php
App::uses('AppModel', 'Model');
/**
 *  
 */
class Events extends AppModel {
	public $name ='Events';
	public $useTable = "master_events";
	public $useDbConfig = "ffgame";

	
	// paginate and paginateCount implemented on a behavior.
	public function paginate(
									$conditions, 
									$fields, 
									$order, 
									$limit, 
									$page = 1, 
									$recursive = null, 
									$extra = array()) {
		

		//decide the paging
		if($page==1){
			$start = 0;
		}else{
			$start = ($limit * $page) - $limit;
		}
		$rs = $this->query("SELECT * 
							FROM ".$_SESSION['ffgamedb'].".master_events Events
							LIMIT {$start},{$limit}",false);
		return $rs;
	}
	
	public function paginateCount(
										$conditions = null, 
										$recursive = 0, 
										$extra = array()) {

		
		
	    // method body
		$rs = $this->query("SELECT COUNT(id) AS total FROM ".$_SESSION['ffgamedb'].".master_events");
		
		return $rs[0][0]['total'];
	}

}