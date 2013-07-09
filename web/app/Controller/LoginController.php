<?php
/**
 * Static content controller.
 *
 * This file will render views from views/pages/
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
 * @package       app.Controller
 * @since         CakePHP(tm) v 0.2.9
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
App::uses('AppController', 'Controller');

/**
 * Static content controller
 *
 * Override this controller by placing a copy in controllers directory of an application
 *
 * @package       app.Controller
 * @link http://book.cakephp.org/2.0/en/controllers/pages-controller.html
 */
class LoginController extends AppController {

/**
 * Controller name
 *
 * @var string
 */
	public $name = 'Login';

/**
 * This controller does not use a model
 *
 * @var array
 */
	public $uses = array();

	public function index(){
		App::import("Vendor", "facebook/facebook");
		$fb = new Facebook(array(
				  'appId'  => $this->FB_APP_ID,
				  'secret' => $this->FB_SECRET)
				  );

		try{
			$fb_id = $fb->getUser();
			if(intval($fb_id) > 0){
				$this->Session->write('UserFBDetail',$fb->api('/me'));
				$this->Session->write('Userlogin.is_login', true);
				$this->Session->write('Userlogin.info',array('fb_id'=>$fb_id,
											'username'=>'',
											'name'=>'',
											'role'=>1,
											'access_token'=>$this->getAccessToken()));
				$this->afterLogin();
			}else{
				$this->redirect("/login/error");
			}
		}catch(Exception $e){
			pr($e->getMessage());
			$this->redirect("/login/error?e=1");	
		}
		die();
	}
	private function afterLogin(){
		$this->loadModel('User');
		
		$user_session = $this->Session->read('Userlogin.info');
		//1. check if the user is already registered in database
		$rs = $this->User->findByFb_id($user_session['fb_id']);
		if($rs['User']['fb_id']==$user_session['fb_id']){
			$user_session['fb_id'] = $rs['User']['fb_id'];
			$user_session['username'] = $rs['User']['name'];
			$user_session['name'] = $rs['User']['name'];
			
			//get team 
			$user_session['team'] = $this->Game->getTeam($user_session['fb_id']);
			$this->Session->write('Userlogin.info',$user_session);
			
			if($user_session['team']==null){
				$this->redirect('/profile/register_team');
			}else{
				$this->redirect('/profile');
			}
		}else{
			$this->redirect('/profile/register');
		}
	}
	
	public function success(){
		$access_token = $this->getAccessToken();
	}
	public function error(){
		
	}
	public function service_unavailable(){
		
	}

}