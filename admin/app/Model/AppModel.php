<?php
/**
 * Application model for Cake.
 *
 * This file is application-wide model file. You can put all
 * application-wide model-related methods here.
 *
 * PHP 5
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @package       app.Model
 * @since         CakePHP(tm) v 0.2.9
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */

App::uses('Model', 'Model');

/**
 * Application model for Cake.
 *
 * Add your application-wide methods in the class below, your models
 * will inherit them.
 *
 * @package       app.Model
 */
class AppModel extends Model {
	protected $access_token = '';
	public function api_post($uri,$params=null,$cookie_file='',$timeout=15){
		if($params==null){
			$params = array();
		}
		App::import("Vendor","common");
		if($this->getAccessToken()!=null){
			$params['access_token'] = $this->getAccessToken();
		}
		$params['api_key'] = $this->getAPIKey();
		$response = json_decode(curlPost($this->getAPIUrl().$uri,$params,$cookie_file,$timeout),true);
		return $response;
	}
	public function api_call($uri,$params=null,$cookie_file='',$timeout=15){
		if($params==null){
			$params = array();
		}
		App::import("Vendor","common");
		if($this->getAccessToken()!=null){
			$params['access_token'] = $this->getAccessToken();
		}
		$params['api_key'] = $this->getAPIKey();
		$response = json_decode(curlGet($this->getAPIUrl().$uri,$params,$cookie_file,$timeout),true);
		return $response;
	}
	public function getAccessToken(){
		return $this->access_token;
	}
	public function setAccessToken($access_token){
		$this->access_token = $access_token;
	}
	public function getAPIUrl(){
		App::uses('CakeSession', 'Model/Datasource');
		$in_session = CakeSession::read('API_URL');
		
		if(isset($in_session)){
			return $in_session;
		}else{
			$API_URL = Configure::read('API_URL');
			if(is_array($API_URL)){
				$n = sizeof($API_URL);
				$API_URL = $API_URL[rand(0,($n-1))];
			}
			CakeSession::write('API_URL',$API_URL);
			
			return $API_URL;
		}

		
	}
	public function getAPIKey(){
		return Configure::read('API_KEY');
	}
}
