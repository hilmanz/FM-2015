<?php
App::uses('AppModel', 'Model');
/**
 * Matches Model

 */
class Goal extends AppModel {
	public $useTable = 'goals'; //kita gak pake table database, karena nembak API langsung.
	public $useDbConfig = 'opta';

	
}