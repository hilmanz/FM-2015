<?php
App::uses('AppModel', 'Model');
/**
 * Matches Model

 */
class Booking extends AppModel {
	public $useTable = 'bookings'; //kita gak pake table database, karena nembak API langsung.
	public $useDbConfig = 'opta';

	
}