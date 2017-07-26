<?php
App::uses('AppModel', 'Model');
/**
 * Profile Model
 *
 * @property status_types $status_types
 * @property document_types $document_types
 * @property topics $topics
 */
class ProfileModel extends AppModel {
	public $useTable = false; //kita gak pake table database, karena nembak API langsung.
	
	public function getBudget($team_id){
		$response = $this->api_call('/team/budget/'.$team_id,array());
		return $response['budget'];
	}

	public function setProfile($data){ /*save profile*/
		$response = $this->api_post('/user/register',$data);
		return $response;
	}

	public function getTeams(){ /*load team list*/
		$response = $this->api_call('/team');
		return $response;
	}
	public function getUser($fbId){
		$response = $this->api_call('');
	}
}