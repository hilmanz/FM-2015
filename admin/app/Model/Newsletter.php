<?php
App::uses('AppModel', 'Model');
/**
 * Matches Model

 */
class Newsletter extends AppModel {
	public $useTable = 'newsletter'; //kita gak pake table database, karena nembak API langsung.
	public $useDbConfig = "ffgame";
	
}