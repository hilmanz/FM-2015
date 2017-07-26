<?php
App::uses('AppModel', 'Model');
/**
 * Matches Model

 */
class Matches extends AppModel {
	public $useTable = 'matchinfo'; //kita gak pake table database, karena nembak API langsung.
	public $useDbConfig = 'opta';

	public function getTeam($team_id){
		$rs = $this->query("SELECT * FROM master_team WHERE uid = '{$team_id}' LIMIT 1");
		
		return $rs[0]['master_team'];
	}
}