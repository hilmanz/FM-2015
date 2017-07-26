<?php
App::uses('AppModel', 'Model');
/**
 * Matches Model
 * 
 */
class AddFundHistory extends AppModel {
	public $useTable = 'add_fund_history'; //kita gak pake table database, karena nembak API langsung.
	public $useDbConfig = "ffgame";

	
}