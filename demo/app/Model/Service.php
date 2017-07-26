<?php
App::uses('AppModel', 'Model');
/**
 * Service Model
 *
 * @property status_types $status_types
 * @property document_types $document_types
 * @property topics $topics
 */
class Service extends AppModel {

	public $useTable = false; //kita gak pake table database, karena nembak API langsung.
	public function request($strReq,$timeout = 15){
		App::import("Vendor","common");
		$stats_service_url = Configure::read('stats_service_url');
		return json_decode(curlGet($stats_service_url.$strReq,array(),null,$timeout),true);
	}
}