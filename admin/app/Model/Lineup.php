<?php
App::uses('AppModel', 'Model');
/**
 * Matches Model

 */
class Lineup extends AppModel {
	public $useTable = 'lineup'; //kita gak pake table database, karena nembak API langsung.
	public $useDbConfig = 'opta';

	
}