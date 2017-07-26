<?php
App::uses('AppModel', 'Model');
/**
 * Matches Model

 */
class Player extends AppModel {
	public $useTable = 'master_player'; //kita gak pake table database, karena nembak API langsung.
	public $useDbConfig = 'opta';

	
}