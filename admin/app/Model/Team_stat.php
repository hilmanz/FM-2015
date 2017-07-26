<?php
App::uses('AppModel', 'Model');
/**
 * Matches Model

 */
class Team_stat extends AppModel {
	public $useTable = 'team_stats'; //kita gak pake table database, karena nembak API langsung.
	public $useDbConfig = 'opta';

	
}