<?php
App::uses('AppModel', 'Model');

class TriggeredEvents extends AppModel {
	public $name ='TriggeredEvents';
	public $useTable = "master_triggered_events";
	public $useDbConfig = "ffgame";
}