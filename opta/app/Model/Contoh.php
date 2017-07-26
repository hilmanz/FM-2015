<?php
App::uses('AppModel', 'Model');
/**
 * Contoh Model
 *
 * @property status_types $status_types
 * @property document_types $document_types
 * @property topics $topics
 */
class Contoh extends AppModel {
	public $useTable = false; //kita gak pake table database, karena nembak API langsung.
	public function getBudget($team_id){
		var_dump($team_id);
		$response = $this->api_call('/team/budget/'.$team_id,array());
		return $response['budget'];
	}
}