<?php
App::uses('AppModel', 'Model');

class GamePerk extends AppModel {
	public $name ='GamePerk';
	public $useTable = "game_perks";
	public $useDbConfig = "ffgame";
}