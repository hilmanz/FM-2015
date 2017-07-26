<?php
App::uses('AppModel', 'Model');
/**
 * Profile Model
 *
 * @property status_types $status_types
 * @property document_types $document_types
 * @property topics $topics
 */


class User extends AppModel {
	public $hasOne = array('Team');

	public function checkLogin($user_login,$user_pass){
		$rs = $this->findByUser_login($user_login);
		if(isset($rs['WpUser'])){
			return $this->validateLogin($rs,$user_login,$user_pass);	
		}

		$rs = $this->findByUser_nicename($user_login);
		if(isset($rs['WpUser'])){
			return $this->validateLogin($rs,$user_login,$user_pass);	
		}

		$rs = $this->findByUser_email($user_login);
		if(isset($rs['WpUser'])){
			return $this->validateLogin($rs,$user_login,$user_pass);	
		}

		
	}

	private function validateLogin($user_data,$user_login,$user_pass){
		//$password = 'imelda99';
		$passHasher = new PasswordHash(8, true);
		
		$check = $passHasher->CheckPassword($user_pass,$user_data['WpUser']['user_pass']);
		if($check == 1){
			return true;
		}
	}
}