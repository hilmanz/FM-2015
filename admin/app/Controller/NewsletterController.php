<?php

App::uses('AppController', 'Controller');


class NewsletterController extends AppController {

/**
 * Controller name
 *
 * @var string
 */
	public $name = 'Newsletter';


	public function index(){
		$this->paginate = array('limit'=>20,
								'order'=>array('Newsletter.id'=>'desc')
								);
		$this->set('data',$this->Paginate('Newsletter'));
	}

	public function create(){
		if($this->request->is('post')){
			$this->request->data['Newsletter']['created_dt'] = date("Y-m-d H:i:s");
			$this->Newsletter->create();
			$rs = $this->Newsletter->save($this->request->data);
			if(isset($rs['Newsletter'])){
				$this->Session->setFlash('New newsletter has been created successfully !');
			}else{
				$this->Session->setFlash('Cannot create the newsletter, please try again later !');
			}
			$this->redirect('/newsletter');
		}

		$this->set('USE_WYSIWYG',true);
	}
	public function edit($id=0){
		if($this->request->is('post')){
			
			$this->Newsletter->id = $id;
			$rs = $this->Newsletter->save($this->request->data);
			if(isset($rs['Newsletter'])){
				$this->Session->setFlash('New newsletter has been updated successfully !');
			}else{
				$this->Session->setFlash('Cannot update the newsletter, please try again later !');
			}
			$this->redirect('/newsletter');
		}
		$rs = $this->Newsletter->findById($id);
		$this->set('rs',$rs['Newsletter']);
		$this->set('USE_WYSIWYG',true);
	}

	public function sending($id=0,$step=1){
		$rs = $this->Newsletter->findById($id);
		$this->set('rs',$rs);
		switch($step){
			case 2:
				if($this->request->query['recipient_type']==1){
					$this->send_newsletter($id,false);
				}else{
					$this->render('sending_newsletter');	
				}
				
			break;
			default:
				$this->render('sending');
			break;
		}
	}

	private function send_newsletter($id,$target_team_id){
		//$this->Game->query("");
		$rs = $this->Newsletter->findById($id);
		$view = new View($this, false);
		$body = $view->element('html_email',array('subject'=>$rs['Newsletter']['subject'],
													'body'=>$rs['Newsletter']['content']));
		
		$body = mysql_escape_string($body);
		$rs = $this->Game->query("INSERT IGNORE INTO ".$_SESSION['ffgamedb'].".email_queue
							(SUBJECT,email,plain_txt,html_text,queue_dt,n_status)
							SELECT 
							'{$rs['Newsletter']['subject']}',
							email,'{$body}','{$body}',NOW(),0 
							FROM users;");
		$this->Session->setFlash('the email has been queued successfully !');
		$this->redirect('/newsletter');
	}
}
