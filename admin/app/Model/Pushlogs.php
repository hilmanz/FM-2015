<?php
App::uses('AppModel', 'Model');
/**
 * Pushlogs Model

 */
class Pushlogs extends AppModel {
	public $useTable = 'pushlogs'; //kita gak pake table database, karena nembak API langsung.
	public $useDbConfig = 'opta';
}