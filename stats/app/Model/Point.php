<?php
App::uses('AppModel', 'Model');
/**
 * Profile Model
 *
 * @property status_types $status_types
 * @property document_types $document_types
 * @property topics $topics
 */
class Point extends AppModel {
	public $belongsTo = array(
		'Team' => array(
			'type'=>'INNER'
		)
	);
}