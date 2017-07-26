<?php
App::uses('AppModel', 'Model');
/**
 * Schedule Model
 *
 * @property status_types $status_types
 * @property document_types $document_types
 * @property topics $topics
 */
class Schedule extends AppModel {
	public $useTable = false; //kita gak pake table database, karena nembak API langsung.
	
}