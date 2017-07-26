<?php
App::uses('AppController', 'Controller');


class DashboardController extends AppController {

/**
 * Controller name
 *
 * @var string
 */
	public $name = 'Dashboard';
	public function index(){
		//news tickers
		$this->loadModel('Ticker');
		$tickers = $this->Ticker->find('all',array('limit'=>1000));
		$this->set('tickers',$tickers);


		//notifications
		$this->loadModel('Notification');
		$tickers = $this->Notification->find('all',array('conditions'=>array('game_team_id'=>0),
														 'limit'=>100));
		$this->set('notifications',$tickers);
	}
	public function daily_logins(){
		$sql = "SELECT DATE(log_dt) AS dt,COUNT(id) AS total FROM activity_logs WHERE log_type='LOGIN' GROUP BY DATE(log_dt) LIMIT 30";
	}
	public function add_ticker(){
		$this->loadModel('Ticker');
		$this->Ticker->create();
		$rs = $this->Ticker->save(array(
			'content'=>$this->request->data['content'],
			'url'=>$this->request->data['url'],
			'post_dt'=>date("Y-m-d H:i:s")
		));
		if($rs) $this->Session->setFlash("Ticker added !");
		else $this->Session->setFlash("Cannot add news, please try again later !");
		$this->redirect('/dashboard');
	}
	public function delete_tickers(){
		$id = intval($this->request->query['id']);
		$this->loadModel('Ticker');
		$this->Ticker->delete($id);
		$this->redirect('/dashboard');
	}


	public function add_notification(){
		$this->loadModel('Notification');
		$this->Notification->create();
		$rs = $this->Notification->save(array(
			'content'=>$this->request->data['content'],
			'url'=>$this->request->data['url'],
			'dt'=>date("Y-m-d H:i:s")
		));
		if($rs) $this->Session->setFlash("New Notification added !");
		else $this->Session->setFlash("Cannot add notification, please try again later !");
		$this->redirect('/dashboard');
	}
	public function delete_notification(){
		$id = intval($this->request->query['id']);
		$this->loadModel('Notification');
		$this->Notification->delete($id);
		$this->redirect('/dashboard');
	}
	public function users(){
		$this->loadModel('Admin');
		$this->paginate = array('limit'=>20);
		$rs = $this->paginate('Admin');
		$this->set('rs',$rs);
	}
	public function change_password($id){
		$this->loadModel('Admin');
		$rs = $this->Admin->findById($id);
		$this->set('rs',$rs);
		
		if($this->request->is('post')){
			
			$username = $rs['Admin']['username'];
			$password = $this->request->data['password'];
			$secret = md5('booyah');
			$hash = Security::hash($password.$secret);
			$this->Admin->id = $rs['Admin']['id'];
			$this->Admin->save(array(
				'password'=>$hash,
				'secret'=>$secret
			));
			$this->Session->setFlash('Password has changed successfully !');
			$this->redirect('users');
		}
	}

	
}
