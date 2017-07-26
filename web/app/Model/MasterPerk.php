<?php
App::uses('AppModel', 'Model');

class MasterPerk extends AppModel {
	public $name ='MasterPerk';
	public $useTable = "master_perks";
	public $useDbConfig = "ffgame";
}