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
App::uses('Sanitize', 'Utility');

/**
 * Static content controller
 *
 * Override this controller by placing a copy in controllers directory of an application
 *
 * @package       app.Controller
 * @link http://book.cakephp.org/2.0/en/controllers/pages-controller.html
 */

//we use password-hash for dealing with wordpress's password hash
require_once APP.DS.'Vendor'.DS.'password-hash.php';

class LoginController extends AppController {

/**
 * Controller name
 *
 * @var string
 */
	public $name = 'Login';
	public $components = array('ActivityLog', 'Captcha');
/**
 * This controller does not use a model
 *
 * @var array
 */
	public $uses = array();

	public function index()
	{
		//clear session
		$this->Session->delete('Userlogin.info');
		$this->Session->delete('TeamRegister');
		$this->Session->delete('UserFBDetail');
  		$this->Session->delete('Userlogin.is_login');

		if($this->request->is("post"))
		{
			$this->loadModel('User');

			$email 		= trim(Sanitize::clean($this->request->data['email']));
			$password 	= trim(Sanitize::clean($this->request->data['password']));

			try{
				$rs = $this->User->query("SELECT * FROM users WHERE email='{$email}' LIMIT 1");
				if(count($rs) > 0)
				{
					if($this->validateLogin($rs, $password.$rs[0]['users']['secret']))
					{
						$this->Session->write('Userlogin.is_login', true);
						$this->Session->write('Userlogin.info',array('fb_id'=>$rs[0]['users']['fb_id'],
													'username'=>'',
													'name'=>$rs[0]['users']['name'],
													'role'=>1,
													'email'=> $email,
													'access_token'=>$this->getAccessToken()));
						$this->afterLogin();
					}
					else
					{
						throw new Exception("Did not match");
					}
				}
				else
				{
					throw new Exception("error sql");
				}
			}catch(Exception $e){
				Cakelog::write('error', 'login.index message:'.$e->getMessage());
				$this->Session->setFlash("Username atau Password Salah !");
			}
		}
	}

	public function register()
	{
		if($this->request->is('post')){

			$this->loadModel('Captchacode');
			$this->loadModel('User');

			$this->Captchacode->set($this->request->data);
			$this->Captchacode->setCaptcha($this->Captcha->getVerCode());
			$data_post = $this->request->data;
			$this->set('data_post', $data_post);
			if(!$this->Captchacode->validates())
			{
				$this->Session->setFlash('Wrong Captcha Input');
				$this->render('register');
			}
			else
			{
				$email = trim(Sanitize::clean($this->request->data['email']));
				$rs_user = $this->User->findByEmail($email);

				$email_domain = explode('@', $email);
				$email_domain = explode('.', $email_domain[1]);
				$blocked_email = Configure::read('BLOCKED_EMAIL');
				
				if(in_array($email_domain[0], $blocked_email))
				{
					$this->Session->destroy();
					$this->redirect('/login/error_email');
				}


				$fb_id = date("Ymdhis").rand(1000, 9999);
				if(isset($rs_user['User']['email'])){
					$fb_id = $rs_user['User']['fb_id'];
				}

				$birthday = $this->request->data['bod_dt'].'/'.$this->request->data['bod_mt']
							.'/'.$this->request->data['bod_yr'];

				$me = array(
						'id' => $fb_id,
						'email' => $this->request->data['email']
					);

				$this->Session->write('UserFBDetail',$me);
				$this->Session->write('Userlogin.is_login', true);
				$this->Session->write('Userlogin.info',array('fb_id'=>$fb_id,
											'username'=>'',
											'name'=>'',
											'role'=>1,
											'access_token'=>$this->getAccessToken()));
				
				$this->requestAction('/profile/register');
			}
			
		}
		else
		{
			$ref_code = $this->request->query('ref_code');
			if($ref_code != NULL || $ref_code != "")
			{
				$this->Session->write('REF_CODE', $ref_code);
				$this->set('ref_code', $ref_code);
			}
		}
	}

	function error_email()
	{

	}

	public function facebook_auth()
	{
		//App::import("Vendor", "facebook/facebook");
		require_once APP . 'Vendor' .DS. 'facebook-5.0/src/Facebook/autoload.php';
		$fb = new Facebook\Facebook(array(
				  'app_id' => $this->FB_APP_ID,
				  'app_secret' =>  $this->FB_SECRET,
				  'default_graph_version' => 'v2.2',
				  ));
		/*$fb = new Facebook(array(
				  'appId'  => $this->FB_APP_ID,
				  'secret' => $this->FB_SECRET,
				  'cookie' => true)

				  );*/
		$helper = $fb->getJavascriptHelper();
		try {
			
		  $accessToken = $helper->getAccessToken();
		
		 
		} catch(Facebook\Exceptions\FacebookResponseException $e) {
		 	session_destroy();
		 	// unset cookies
			if (isset($_SERVER['HTTP_COOKIE'])) {
			    $cookies = explode(';', $_SERVER['HTTP_COOKIE']);
			    foreach($cookies as $cookie) {
			        $parts = explode('=', $cookie);
			        $name = trim($parts[0]);
			        setcookie($name, '', time()-1000);
			        setcookie($name, '', time()-1000, '/');
			    }
			}
		  Cakelog::write('error', 'login.facebook_auth Error facebook :'.$e->getMessage());
			$this->redirect("/login");
		} catch(Facebook\Exceptions\FacebookSDKException $e) {
		  // When validation fails or other local issues
		 Cakelog::write('error', 'login.facebook_auth Error facebook :'.$e->getMessage());
			$this->redirect("/login/error?e=3");
		}

		if (! isset($accessToken)) {
		  $this->redirect("/login/error?e=2");
		  exit;
		}

		
		try{

			//$fb_id = $fb->getUser();
			
			//if(intval($fb_id) > 0){
				$this->Session->write('fb_access_token',(string) $accessToken);

				$response = $fb->get('/me',$accessToken);
				$me = $response->getGraphUser();
				
				if(isset($me['id'])){
					if(isset($me['birthday'])){
						$birthday = date("Y/m/d",$me['birthday']->getTimestamp());
					}else{
						$birthday = "";
					}
					if(isset($me['location'])){
						$location = $me['location']['name'];
					}else{
						$location = '';
					}
					$this->Session->write('UserFBDetail',$me);
					$this->Session->write('Userlogin.is_login', true);
					$this->Session->write('Userlogin.info',array('fb_id'=>$me['id'],
												'username'=>'',
												'name'=>$me['name'],
												'birthday'=>$birthday,
												'email'=>@$me['email'],
												'location'=> array('name', $location),
												'role'=>1,
												'access_token'=>$this->getAccessToken()));
					$this->afterLogin();
				}else{
					Cakelog::write('error', 'unverified fb account : '.$me['id']);
					$this->redirect("/login/error?e=-1");
				}
				
			//}else{

				//$this->redirect($fb->getLoginUrl());
			//}
		}catch(Exception $e){
			Cakelog::write('error', 'login.facebook_auth Error facebook :'.$e->getMessage());
			$this->redirect("/login/error?e=0");
		}
		die();
	}

	/*
	public function loginAsThorfin(){
		if(Configure::read('STAGING')){
			$fb_id = '100001023465395';
			$name = 'Thorfinn Karlsefni';
			$this->Session->write('UserFBDetail',array('name'=>$name,'fb_id'=>$fb_id));
			$this->Session->write('Userlogin.is_login', true);
			$this->Session->write('Userlogin.info',array('fb_id'=>$fb_id,
										'username'=>'',
										'name'=>$name,
										'role'=>1,
										'access_token'=>$this->getAccessToken()));
			$this->afterLogin();

		}
		$this->render('error');
	}*/

	private function validateLogin($user_data, $user_pass)
	{
		$passHasher = new PasswordHash(8, true);
		
		$check = $passHasher->CheckPassword($user_pass,$user_data[0]['users']['password']);
		if($check == 1){
			return true;
		}
	}

	public function afterLogin(){
		
		$this->loadModel('User');
		
		$user_session = $this->Session->read('Userlogin.info');
		
		//pasang external tracking code here
		curlGet('http://avn.innity.com/conversion/?cb=26575&conversion=719&value=[VALUE]',null);
		//---->
		//1. check if the user is already registered in database
		$rs = $this->User->findByFb_id($user_session['fb_id']);
		
		
		if(@$rs['User']['fb_id']==$user_session['fb_id']&&$user_session['fb_id']>0){
		

			$user_session['fb_id'] = $rs['User']['fb_id'];
			$user_session['username'] = $rs['User']['name'];
			$user_session['name'] = $rs['User']['name'];
			$user_session['register_completed'] = $rs['User']['register_completed'];

			//get team 
			$user_session['team'] = $this->Game->getTeam($user_session['fb_id']);
			
			$this->Session->write('Userlogin.info',$user_session);
			
			//log time
			$this->ActivityLog->logTime($rs['User']['id'],$this->Session,true);
			
			if($rs['User']['n_status'] == 0){
				$this->Session->write('Userlogin.is_login', false);
				$this->redirect('/profile/send_activation');
			}else if($rs['User']['password'] == ""){
				$this->Session->write('Userlogin.is_login', false);
				$this->redirect('/profile/create_password');
			}else if($rs['User']['paid_member']==-1){
				$this->redirect('/upgrade/plan');
			}else if($user_session['team']==null){
				
				
				$this->redirect('/profile/register_team');
			}else if($user_session['team']!=null&&$user_session['register_completed']==0){
				$this->redirect('/profile/register_staff');
			}else{
				//check if there's pending redirect url		
				$this->ActivityLog->writeLog($rs['User']['id'],'LOGIN');

				if($this->Session->read('pending_redirect')!=null){
					//prepare the redirect_url from session
					$redirect_url = $this->Session->read('pending_redirect');
					//we no longer need it
					$this->Session->write('pending_redirect',null); 
					//redirect the page
					$this->redirect($redirect_url);
				}else{
					
					$this->redirect('/manage/team');
				}
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
	public function expired(){
		
		$this->set('USER_IS_LOGIN',false);
	}
	public function thankyou(){
		$this->render('logout');
	}

}
