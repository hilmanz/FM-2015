<?php
App::uses('AppModel', 'Model');
/**
 * Profile Model
 *
 * @property status_types $status_types
 * @property document_types $document_types
 * @property topics $topics
 */
class Weekly_point extends AppModel {

	public $belongsTo = array(
		'Team' => array(
			'type'=>'INNER'
		),
	);
	public function getWeeklyPoints($team_id,$league='epl'){
		$frontend_schema = Configure::read('FRONTEND_SCHEMA');
		$poin = $this->query("
	    		SELECT matchday,SUM(points+extra_points) AS total FROM {$frontend_schema}.weekly_points 
				WHERE team_id = {$team_id} AND league='{$league}' 
				GROUP BY matchday;");
		$rs = array();
	   	for($i=0;$i<sizeof($poin);$i++){
	   		$rs[$poin[$i]['weekly_points']['matchday']] = $poin[$i][0]['total'];
	   	}
    	return $rs;
	}
}