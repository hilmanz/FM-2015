<?php

App::uses('AppController', 'Controller');


class LoginController extends AppController {

/**
 * Controller name
 *
 * @var string
 */
	public $name = 'Login';


	public function login(){

	}
	public function index(){
		if($this->request->is('post')){
			
			$username = $this->request->data['username'];
			$password = $this->request->data['password'];
			$this->loadModel('Admin');
			$user = $this->Admin->findByUsername($username);

			if(isset($user['Admin']) && $user['Admin']['username']==$username && strlen($user['Admin']['username']) >= 5){
				$secret = $user['Admin']['secret'];
				if(strlen($secret)>0){
					$hash = Security::hash($password.$secret);	
					if($hash == $user['Admin']['password']){
						$this->Session->write('AdminLogin',array('id'=>$user['Admin']['id'],
																'username'=>$user['Admin']['username'],
																'login_ts'=>time()));
						$this->redirect('/dashboard');
					}else{
						$this->Session->setFlash('Wrong username and / or password');
					}
				}
			}else{
				$this->Session->setFlash('Wrong username and / or password');
			}
		}
	}
	public function logout(){
		$this->Session->destroy();
		$this->redirect('/login');
	}
	/*
	public function dummy(){
		$username = "admin";
		$password = "11111111";
		$secret = md5('booyah');
		$hash = Security::hash($password.$secret);
		$this->loadModel('Admin');
		$this->Admin->create();
		$this->Admin->save(array(
			'username'=>$username,
			'password'=>$hash,
			'secret'=>$secret
		));
	}*/

}
