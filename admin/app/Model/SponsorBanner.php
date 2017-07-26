<?php
App::uses('AppModel', 'Model');
/**
 * the player report will returns the following informations : 
 * User | Original Team | Email | Telp | Kota | Import Players Count* | Games* | Passing & Attacking* | Defending* | Goalkeeping* | Mistakes & Errors* | Total Points* | Money*
 */
class SponsorBanner extends AppModel {
	public $name ='User';
	public $useTable = "game_sponsorship_banners";
	public $useDbConfig = "ffgame";
}