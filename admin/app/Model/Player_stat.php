<?php
App::uses('AppModel', 'Model');
/**
 * Matches Model

 */
class Player_stat extends AppModel {
	public $useTable = 'player_stats'; //kita gak pake table database, karena nembak API langsung.
	public $useDbConfig = 'opta';

	
}