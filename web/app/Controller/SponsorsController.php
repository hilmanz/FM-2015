<?php
/**
 * Market Controller

 */
App::uses('AppController', 'Controller');
App::uses('Sanitize', 'Utility');

class SponsorsController extends AppController {

/**
 * Controller name
 *
 * @var string
 */
	public $name = 'Sponsors';

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
		$userData = $this->getUserData();
		$user = $this->userDetail;
		$this->set('user',$user['User']);
		if(!$this->hasTeam()){
			$this->redirect('/login/expired');
		}
	}
	public function hasTeam(){
		$userData = $this->getUserData();
		if(is_array($userData['team'])){
			return true;
		}
	}
	public function index(){
		$this->redirect('/');
	}
	public function banners(){
		$game_team_id = intval($this->request->query['game_team_id']);
		$slot = $this->request->query['slot'];
		//get Sponsorship
		$sponsor = $this->Game->query("SELECT * FROM ".$_SESSION['ffgamedb'].".game_team_sponsors TeamSponsor
										WHERE game_team_id={$game_team_id} LIMIT 1");
		if(sizeof($sponsor)>0){
			$rs = $this->Game->query("SELECT * FROM ".$_SESSION['ffgamedb'].".game_sponsorship_banners Banner
							WHERE sponsor_id={$sponsor[0]['TeamSponsor']['sponsor_id']} AND slot='{$slot}' 
							ORDER BY RAND() LIMIT 10;");
			return $rs;
		}else{
			return array();
		}
		
		
	}
	public function apply(){
		$this->loadModel('GameTeamSponsor');
		$c = Sanitize::clean($this->request->query['c']);
		
		if(strlen($c)>0){
			$data = unserialize(decrypt_param($c));
			$sponsor = $this->Game->query("SELECT * 
								FROM ".$_SESSION['ffgamedb'].".game_sponsorships Sponsor
								WHERE id = ".intval($data['sponsor_id'])." 
								LIMIT 1");
			
			if($this->userData['team']['id'] == $data['game_team_id']){
				$rs = $this->Game->apply_sponsorship($this->nextMatch['match']['game_id'],
													$this->nextMatch['match']['matchday'],
													$data['game_team_id'],
													$data['sponsor_id']);

				
				//reset financial statement
				$this->Session->write('FinancialStatement',null);
				
				if($rs['status']==1){
					//sponsorship accepted
					$this->set('sponsor_name',$sponsor[0]['Sponsor']['name']);
				}else if($rs['status']==2){
					//already has a sponsor
					$this->hasSponsorError();
				}else{
					//denied
					$this->refused();
				}

				//reset financial statement
				$this->Session->write('FinancialStatement',null);

			}else{
				$this->errorCode();
			}
		}else{
			$this->set('title','Ups !');
			$this->set('message','Link yang lo tuju sepertinya salah nih !');
			$this->error();
		}
	}
	private function refused(){
		$this->set('title','Aplikasi Tidak Valid!');
		$this->set('message','Elo sudah pernah mengajukan aplikasi atau sudah memiliki kontrak dengan sponsor ini. ');
		$this->error();
	}
	private function hasSponsorError(){
		$this->set('title','Aplikasi Ditolak!');
		$this->set('message','Elo sudah terikat kontrak dengan sponsor lain. Silahkan tunggu penawaran sponsor berikutnya.');
		$this->error();
	}
	private function errorCode(){
		$this->set('title','Aplikasi Tidak Dapat di Proses! ');
		$this->set('message','Penerimaan sponsorship cuma dapat diperoleh melalui jalur-jalur resmi Supersoccer Football Manager');
		$this->error();
	}
	public function error(){
		$this->render('error');
	}
	public function team_error(){
		$this->set('error_type','team');
		$this->render('error');
	}
	public function success(){
		$this->render('success');
	}
	/**
	* track banner views/clicks
	* $type = 1 -> view
	* $type = 2 -> click
	*/
	public function track($type=1){
		$this->layout = "ajax";
		$banner_id = intval(@$this->request->query['id']);
		$location = Sanitize::clean(strtolower($this->parseLocation($this->userDetail['User']['location'])));
		if($type==2){
			//click
			$t_click = 1;
			$t_view = 0;
		}else{
			$t_click = 0;
			$t_view = 1;
		}
		$sql = "INSERT INTO ".$_SESSION['ffgamedb'].".sponsor_banner_logs
				(banner_id,current_month,location,t_click,t_view)
				VALUES
				({$banner_id},".date('m').",'{$location}',{$t_click},{$t_view})
				ON DUPLICATE KEY UPDATE
				t_click = t_click + VALUES(t_click),
				t_view = t_view + VALUES(t_view)";
				
		$this->Game->query($sql);
		$this->set('response',array('status'=>1,'type'=>$type));
		$this->render('response');
	}
	public function jump($type=1,$banner_id=0){

		$this->layout = "ajax";
		$this->loadModel('Banner');
		$banner = $this->Banner->findById($banner_id);
		$location = Sanitize::clean(strtolower($this->parseLocation($this->userDetail['User']['location'])));
		if($type==2){
			//click
			$t_click = 1;
			$t_view = 0;
		}else{
			$t_click = 0;
			$t_view = 1;
		}
		$sql = "INSERT INTO ".$_SESSION['ffgamedb'].".sponsor_banner_logs
				(banner_id,current_month,location,t_click,t_view)
				VALUES
				({$banner_id},".date('m').",'{$location}',{$t_click},{$t_view})
				ON DUPLICATE KEY UPDATE
				t_click = t_click + VALUES(t_click),
				t_view = t_view + VALUES(t_view)";
				
		$this->Game->query($sql);
		$this->redirect($banner['Banner']['url']);
	}
	private function parseLocation($str){
		$a = explode(",",$str);
		return trim($a[0]);
	}

}
