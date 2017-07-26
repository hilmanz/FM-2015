<?php
/**
* Game Fixtures Monitoring.
*
*/
App::uses('AppController', 'Controller');
App::uses('Sanitize', 'Utility');

class EventsController extends AppController {

/**
 * Controller name
 *
 * @var string
 */
	public $name = 'Events';
	
	public function index(){
		$this->loadModel('Events');
		$this->loadModel('TriggeredEvents');
		$this->paginate = array('limit'=>25);
		$events = $this->paginate('Events');
		$triggered = $this->paginate('TriggeredEvents');
		$this->set('rs',$events);
		$this->set('triggered',$triggered);
	}
	public function create(){
		$this->loadModel('Events');
		$step = intval(@$this->request->data['step']);
		if($step==0){
			$step = 1;
		}
		
		switch($step){
			case 2:
				$register_data = array(
					'event_name'=>$this->request->data['event_name'],
					'event_type'=>$this->request->data['event_type'],

				);
				$this->set('data',$register_data);
				$this->Session->write('register_event_data',$register_data);
			break;
			case 3:
				$register_data = $this->Session->read('register_event_data');
				$register_data['target_type'] = $this->request->data['target_type'];
				$register_data['affected_item'] = $this->request->data['affected_item'];

				
				$this->set('data',$register_data);
				$this->Session->write('register_event_data',$register_data);

				$this->loadModel('TriggeredEvents');
				$triggered = $this->TriggeredEvents->find('all',array('limit'=>'100','order'=>'id DESC'));
				$this->set('triggered',$triggered);
			break;
			case 4:
				$register_data = $this->Session->read('register_event_data');

				if(is_array($this->request->data['targets'])){
					$register_data['target_value'] = json_encode(@$this->request->data['targets']);
				}else{
					$register_data['target_value'] = json_encode(explode(',',@$this->request->data['targets']));
				}
				
				$register_data['amount'] = $this->request->data['amount'];	

				if($register_data['event_type']==1){
					if($register_data['target_type']==4){
						$register_data['target_str'] = $this->getTargetNameForMasterEvent(
													$register_data['target_type'],
													$register_data['target_value']);
					}else if($register_data['target_type']==5){
						$register_data['target_str'] = $this->getTargetNameForMasterEvent(
													$register_data['target_type'],
													$register_data['target_value']);
					}else{
						//string representations of target values in player data
						$register_data['target_str'] = $this->getTargetNameForPlayerEvent(
															$register_data['target_type'],
															$this->request->data['targets']);	
					}
					
				}else{
					//string representations of target values in master data
					$register_data['target_str'] = $this->getTargetNameForMasterEvent(
													$register_data['target_type'],
													$register_data['target_value']);
				}
				if(isset($this->request->data['prequisite_event_id'])
					&& intval($this->request->data['prequisite_event_id'])){
					$register_data['prequisite_event_id'] = intval($this->request->data['prequisite_event_id']);
					$register_data['prequisite_trigger_type'] = 0;
				}
				if(isset($this->request->data['rejected_prequisite_event_id']) 
					&& intval($this->request->data['rejected_prequisite_event_id'])>0){
					$register_data['prequisite_event_id'] = intval($this->request->data['rejected_prequisite_event_id']);
					$register_data['prequisite_trigger_type'] = 1;
				}
				$this->set('data',$register_data);
				$this->Session->write('register_event_data',$register_data);
				$this->set('USE_WYSIWYG',true);
			break;
			case 5:

				$img_name = '';

				if(isset($_FILES['email_img']['name'])){
					if(eregi("\.jpg",$_FILES['email_img']['name'])){
						$img_name = 'events_'.md5($_FILES['email_img']['name']).".jpg";	
					}else if(eregi("\.png",$_FILES['email_img']['name'])){
						$img_name = 'events_'.md5($_FILES['email_img']['name']).".png";	
					}else if(eregi("\.gif",$_FILES['email_img']['name'])){
						$img_name = 'events_'.md5($_FILES['email_img']['name']).".gif";	
					}else{
						$img_name = 'events_'.md5($_FILES['email_img']['name']).".jpg";	
					}
					move_uploaded_file($_FILES['email_img']['tmp_name'], Configure::read('avatar_img_dir').$img_name);
						
					
				}

				$register_data = $this->Session->read('register_event_data');

				$register_data['email_body_img'] = $img_name;
				$register_data['name_appear_on_report'] = $this->request->data['name_appear_on_report'];				
				$register_data['email_subject'] = $this->request->data['email_subject'];
				//html body
				$view = new View($this, false);
				$body = $view->element('html_email',array('subject'=>$this->request->data['email_subject'],'body'=>$this->request->data['email_body_txt']));					
				//$body = mysql_escape_string($body);
				//-->html body			
				$register_data['email_body_txt'] = $body;
				$register_data['email_body_plain'] = strip_tags(str_replace(array("<br/>","<br />"),"\n",$this->request->data['email_body_txt']));
				$register_data['schedule_dt'] = $this->formatScheduleDate($this->request->data['scheduledt']);
				$this->set('data',$register_data);

				$this->Session->write('register_event_data',$register_data);

			break;
			case 6:
				$register_data = $this->Session->read('register_event_data');
				$register_data['created_dt'] = date("Y-m-d H:i:s");
				$this->Events->create();
				$this->Events->save($register_data);
			break;
		}

		//set steps
		$this->set('step',$step);
	}
	public function create2(){
		$this->set('USE_WYSIWYG',true);
		$this->loadModel('TriggeredEvents');
		$step = intval(@$this->request->data['step']);
		if($step==0){
			$step = 1;
		}
		switch($step){
			case 2:
				$register_data = $this->request->data;
				$register_data['schedule_dt'] = $this->formatScheduleDate($register_data['schedule_dt']);
				$register_data['expired_dt'] = $this->formatScheduleDate($register_data['expired_dt']);
				$register_data['email_body_plain'] = strip_tags(str_replace(array("<br/>","<br />"),"\n",$register_data['email_body_txt']));

				

				$this->set('data',$register_data);
				$this->Session->write('register_event_data',$register_data);
				$this->render('create2_step2');

			break;
			case 3:
				
				$this->setupRewards($this->request->data);
				$this->setupTransferOffer($this->request->data);
				$this->setupTeamSelection($this->request->data);
				$this->render('create2_step3');

			break;
			case 4:
				
				$register_data = $this->Session->read('register_event_data');
				
				$this->TriggeredEvents->create();
				$rs = $this->TriggeredEvents->save($register_data);
				
				if($rs){
					
					$offer_url = Configure::read('WWW_URL').'/?email=true&osign='.encrypt_param(serialize(
						array(
						'offer_id'=>$rs['TriggeredEvents']['id'],
						'ts'=>time()
						)
					));

					//html body
					$view = new View($this, false);
					$body = $view->element('html_email_trigger_events',
											array('subject'=>$register_data['email_subject'],
												'body'=>$register_data['email_body_txt'],
												'offer_url'=>$offer_url));					
					//-->html body			
					$this->TriggeredEvents->id = $rs['TriggeredEvents']['id'];
					$this->TriggeredEvents->save(array(
						'offer_url'=>$offer_url,
						'email_body_txt'=>$body
					));

					$this->Session->setFlash('New Triggered Events has been created successfully !');	

				}else{

					$this->Session->setFlash('Sorry ! New Triggered Events cannot be created.');

				}
				
				$this->redirect('/events');
				
			break;
			default:
				$this->render('create2');
			break;
		}
		//set steps
		$this->set('step',$step);
		

	}
	private function setupRewards($data){
		$register_data = $this->Session->read('register_event_data');
		if(isset($data['reward_type'])){
			switch($data['reward_type']){
				case 1:
					$register_data['money_reward'] = $data['money_reward'];
				break;
				case 2:
					$register_data['points_reward'] = $data['points_reward'];
				break;
				case 3:
					$register_data['point_mod_reward'] = $data['point_mod_reward'];
				break;
				default:
				break;
			}
			$this->set('data',$register_data);
			$this->Session->write('register_event_data',$register_data);
		}
	}
	private function setupTeamSelection($data){
		$this->loadModel('Events');
		$register_data = $this->Session->read('register_event_data');
		if(isset($data['the_target'])){
			$register_data['target_team'] = $data['the_target'];
			$register_data['target_team_name'] = $this->getMasterTeamName(json_encode(array(
														$data['the_target']
													)));
		}
		$this->set('data',$register_data);
		$this->Session->write('register_event_data',$register_data);

	}
	private function setupTransferOffer($data){
		$this->loadModel('Events');
		$register_data = $this->Session->read('register_event_data');
		if(isset($data['offered_player_id'])){
			$register_data['offered_player_id'] = $data['offered_player_id'];
			$register_data['offered_player_name'] = $this->getMasterPlayerName(json_encode(array(
														$data['offered_player_id']
													)));
		}
		$this->set('data',$register_data);
		$this->Session->write('register_event_data',$register_data);

	}
	private function formatScheduleDate($dt){
		$a = explode("/",$dt);
		return $a[2].'-'.$a[1].'-'.$a[0];
	}
	private function getTargetNameForMasterEvent($target_type,$targets){

		switch($target_type){
			case 4:
				return $this->getMasterPlayerName($targets);
			break;
			default:
				return $this->getMasterTeamName($targets);
			break;
		}
	}
	private function getTargetNameForPlayerEvent($target_type,$targets){
		switch($target_type){
			case 1:
				return $this->getPlayerTeamNamesByGameTeamId($targets);
			break;
			case 3:
				return 'Tier '.$targets;
			break;
			default:
				return "All Teams";
			break;
		}
	}
	private function getMasterTeamName($team_id){
		$str = '';
		$n=0;
		$team_id = json_decode($team_id,true);
		foreach($team_id as $ids){
			$team = $this->Events->query("SELECT * FROM ".$_SESSION['ffgamedb'].".master_team 
											WHERE uid = '{$ids}' 
										  LIMIT 1;");
			if($n!=0){
				$str.=',';
			}
			$str.= Sanitize::clean($team[0]['master_team']['name']);
			$n++;
		}
		return $str;
	}
	private function getMasterPlayerName($player_id){
		$str = '';
		$n=0;
		$player_id = json_decode($player_id,true);
		foreach($player_id as $ids){
			$player = $this->Events->query("SELECT * FROM ".$_SESSION['ffgamedb'].".master_player 
											WHERE uid = '{$ids}' 
										  LIMIT 1;");
			if($n!=0){
				$str.=',';
			}
			$str.= Sanitize::clean($player[0]['master_player']['name']);
			$n++;
		}
		return $str;
	}
	private function getPlayerTeamNamesByGameTeamId($game_team_id){
		$str = '';
		$n=0;
		foreach($game_team_id as $ids){
			$team = $this->Events->query("SELECT d.team_name
								FROM ".$_SESSION['ffgamedb'].".game_users a
								INNER JOIN ".$_SESSION['ffgamedb'].".game_teams b
								ON a.id = b.user_id
								INNER JOIN ".Configure::read('DB').".users c
								ON c.fb_id = a.fb_id
								INNER JOIN ".Configure::read('DB').".teams d
								ON d.user_id = c.id
								WHERE b.id = {$ids} LIMIT 1;");
			if($n!=0){
				$str.=',';
			}
			$str.= Sanitize::clean($team[0]['d']['team_name']);
			$n++;
		}
		return $str;
	}
	public function master_teams(){
		$this->layout = 'ajax';
		$this->loadModel('Events');
		$start = intval(@$this->request->query['start']);
		$limit = 20;
		
		$rs = $this->Events->query("SELECT * FROM ".$_SESSION['ffgamedb'].".master_team
									LIMIT {$start},{$limit};");
		
		
		$teams = array();
		foreach($rs as $r){
			$teams[] = $r['master_team'];
		}
		
		$this->set('response',array('status'=>1,'data'=>$teams,'next_offset'=>$start+$limit,'rows_per_page'=>$limit));
		$this->render('response');
		
	}
	public function master_players(){
		$this->layout = 'ajax';
		$this->loadModel('Events');
		$start = intval(@$this->request->query['start']);
		$limit = 20;
		
		$rs = $this->Events->query("SELECT a.uid AS id,a.name,a.position,
									a.first_name,a.last_name,a.known_name,b.name AS club,
									a.transfer_value
									FROM ".$_SESSION['ffgamedb'].".master_player a
									INNER JOIN ".$_SESSION['ffgamedb'].".master_team b
									ON a.team_id = b.uid 
									LIMIT {$start},{$limit};");
		
		
		$players = array();
		foreach($rs as $r){
			$t = $r['a'];
			$t['team_name'] = $r['b']['club'];
			$players[] = $t;
		}
		
		$this->set('response',array('status'=>1,'data'=>$players,'next_offset'=>$start+$limit,'rows_per_page'=>$limit));
		$this->render('response');
		
	}
	public function get_teams(){
		$this->layout = 'ajax';
		$this->loadModel('Events');
		$start = intval(@$this->request->query['start']);
		$limit = 20;
		

		$rs = $this->Events->query("SELECT a.fb_id,a.name,a.email,a.phone_number,a.location,
									b.team_name,b.team_id,(c.points+c.extra_points) AS total_points,c.rank
									FROM ".Configure::read('DB').".users a
									INNER JOIN ".Configure::read('DB').".teams b
									ON a.id = b.user_id
									INNER JOIN ".Configure::read('DB').".points c
									ON c.team_id = b.id
									LIMIT {$start},{$limit};");
		
		$this->original = $this->getOriginalTeams();
		$teams = array();
		foreach($rs as $r){
			$t = $r['a'];
			$t['team_name'] = $r['b']['team_name'];
			$t['original_team'] = $this->original[$r['b']['team_id']];
			$t['points'] = $r[0]['total_points'];
			$t['rank'] = $r['c']['rank'];
			$t['game_team_id'] = $this->getGameTeamId($r['a']['fb_id']);
			$teams[] = $t;
		}
		
		$this->set('response',array('status'=>1,'data'=>$teams,'next_offset'=>$start+$limit,'rows_per_page'=>$limit));
		$this->render('response');
		
	}
	private function getGameTeamId($fb_id){
		$rs = $this->Events->query("SELECT b.id AS game_team_id 
										FROM ".$_SESSION['ffgamedb'].".game_users a
										INNER JOIN ".$_SESSION['ffgamedb'].".game_teams b
										ON a.id = b.user_id
										WHERE a.fb_id = '{$fb_id}' LIMIT 1;");
		return $rs[0]['b']['game_team_id'];
	}
	private function getOriginalTeams(){
		$master_team = $this->Events->query("SELECT * FROM ".$_SESSION['ffgamedb'].".master_team t LIMIT 20;");
		$original_teams = array();
		foreach($master_team as $team){
			$original_teams[$team['t']['uid']] = $team['t']['name'];
		}
		$master_team = null;
		unset($master_team);
		return $original_teams;
	}
}