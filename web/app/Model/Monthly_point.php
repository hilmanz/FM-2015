<?php
App::uses('AppModel', 'Model');
/**
 */
class Monthly_point extends AppModel {
	public $belongsTo = array(
		'Team' => array(
			'type'=>'INNER'
		),
	);
	
}