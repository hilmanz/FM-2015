<?php
App::uses('AppModel', 'Model');
/**
 * Matches Model
 * 
 */
class AddCoinHistory extends AppModel {
	public $useTable = 'add_coin_history'; //kita gak pake table database, karena nembak API langsung.
	public $useDbConfig = "ffgame";

	
}