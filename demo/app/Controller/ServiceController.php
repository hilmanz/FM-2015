<?php
/**
* OPTA Valde HTTP Push EndPoint Implementation
*/
App::uses('AppController', 'Controller');
App::uses('Folder', 'Utility');
App::uses('File', 'Utility');
require_once APP.DS.'Vendor'.DS.'common.php';
class ServiceController extends AppController {

/**
 * Controller name
 *
 * @var string
 */
	public $name = 'Service';

	public function index(){
		$this->set('response',array('status'=>1,'message'=>'ok'));	
	}
	public function result(){
		$id = $this->request->query('id');
		$this->set('response',array('status'=>1,'data'=>$id));	
	}
}
