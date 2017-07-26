<?php
App::uses('AppModel', 'Model');
/**
 * Matches Model

 */
class MerchandiseItemPerks extends AppModel {
	public $useTable = 'merchandise_item_perks';
	public $useDbConfig = 'default';

	public function delete_by_item_id($item_id)
	{
		$this->query("DELETE FROM ".$this->useTable." WHERE merchandise_item_id =".$item_id);
	}
}