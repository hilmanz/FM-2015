<?php

App::uses('AppController', 'Controller');
App::uses('Sanitize', 'Utility');
App::uses('File', 'Utility');

class BannersController extends AppController {

/**
 * Controller name
 *
 * @var string
 */
	public $name = 'Banners';
	public function index(){
		$this->loadModel('Banners');
		$result = $this->Banners->find('all',array('limit'=>200));
		$this->set('results',$result);
	}
	public function upload(){
		
		$_FILES['file']['name'] = str_replace(array(' ','\''),"_",$_FILES['file']['name']);
		if(move_uploaded_file($_FILES['file']['tmp_name'],
				Configure::read('avatar_img_dir').$_FILES['file']['name'])){
			
			//save to db
			$data = array(
				'banner_name'=>$this->request->data['name'],
				'url'=>$this->request->data['url'],
				'banner_file'=>$_FILES['file']['name'],
				'slot'=>$this->request->data['slot']
			);
			
			$this->loadModel('Banners');
			$rs = $this->Banners->save($data);
			$this->set('success',1);
		}else{
			$this->set('success',0);
		}
	}
	public function remove(){
		$id = $this->request->query['id'];
		$this->loadModel('Banners');
		$banner = $this->Banners->findById($id);
		@unlink(Configure::read('avatar_img_dir').$banner['Banners']['banner_file']);
		if($this->Banners->delete($id)){
			$this->set('success',1);
		}else{
			$this->set('success',0);
		}
	}
}
