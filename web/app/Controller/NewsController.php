<?php
/**
 * Profile Controller
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
App::uses('File', 'Utility');
App::uses('CakeEmail', 'Network/Email');


/**
 * Static content controller
 *
 * Override this controller by placing a copy in controllers directory of an application
 *
 * @package       app.Controller
 * @link http://book.cakephp.org/2.0/en/controllers/pages-controller.html
 */
class NewsController extends AppController {

/**
 * Controller name
 *
 * @var string
 */
	public $name = 'News';

/**
 * This controller does not use a model
 *
 * @var array
 */
	public $uses = array();
	
	public function beforeFilter(){
		parent::beforeFilter();
		$this->loadModel('Team');
		$this->loadModel('User');
		

		
		

		if(time()<strtotime($this->userDetail['User']['register_date'])+(24*60*60)){
			$is_new_user = true;
		}else{
			$is_new_user = false;
		}
		$this->set('is_new_user',$is_new_user);	
		

		$userData = $this->userData;

	
		//user data
		$user = $this->userDetail;
		$this->set('user',$user['User']);

		if($user['User']['paid_member_status']!=1 && $this->request->params['action']!='error'){
			$this->redirect('/news/error');
			die();	
		}
		
	}
	public function getInjuryNews(){
		$this->loadModel('WpPosts');
		return $this->WpPosts->getInjuryNews();
	}
	public function getTopPlayerNews(){
		$this->loadModel('WpPosts');
		return $this->WpPosts->getTopPlayerNews();	
	}

	public function error(){
		return array('post_title'=>'Access Denied','post_content'=>'Mohon maaf, hanya anggota pro-league yang dapat mengakses berita ini !');
	}
	
	
}