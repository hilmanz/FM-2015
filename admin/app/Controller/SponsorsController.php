<?php
/**
* Game Fixtures Monitoring.
*
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
	
	public function index(){
		$this->loadModel('Sponsorship');
		$this->paginate = array('limit'=>25);
		$sponsors = $this->paginate('Sponsorship');
		foreach($sponsors as $n=>$v){
			$sponsors[$n]['perks'] = $this->Sponsorship->getPerksBySponsorId($v['Sponsors']['id']);
		}

		$this->set('sponsors',$sponsors);
		
		//attach perks on json
		$perks = $this->Sponsorship->getPerks();
		$this->set('perks',$perks);
	}
	public function stats($id){
		$this->loadModel('Sponsorship');
		$this->loadModel('SponsorBanner');

		
		//load sponsorship details
		$rs = $this->Sponsorship->findById($id);
		$this->set('sponsor',$rs['Sponsorship']);

		//get the list of banners and it's summary
		$banners = $this->Game->query("SELECT a.id,a.banner_name,a.slot,
										SUM(t_click) AS clicks,
										SUM(t_view) AS views 
										FROM 
										".$_SESSION['ffgamedb'].".game_sponsorship_banners a
										INNER JOIN 
										".$_SESSION['ffgamedb'].".sponsor_banner_logs b
										ON a.id = b.banner_id
										WHERE a.sponsor_id = {$id}
										GROUP BY b.banner_id
										LIMIT 100");
		$this->set('banners',$banners);
	}
	public function stats_detail($id,$banner_id){
		$this->loadModel('Sponsorship');
		$this->loadModel('SponsorBanner');

		
		//load sponsorship details
		$rs = $this->Sponsorship->findById($id);
		$this->set('sponsor',$rs['Sponsorship']);

			
		$banner = $this->SponsorBanner->findById($banner_id);
		$this->set('banner',$banner['SponsorBanner']);

		//overall monthly
		$overall_monthly = $this->Game->query("SELECT current_month AS mt,current_year AS yr,
												SUM(t_click) AS clicks,SUM(t_view) AS views 
												FROM ".$_SESSION['ffgamedb'].".sponsor_banner_logs 
												WHERE banner_id={$banner_id} GROUP BY current_month,current_year
												ORDER BY current_year ASC,current_month ASC;");
		$this->set('overall_monthly',$overall_monthly);
	}
	public function citystats($sponsor_id,$banner_id){
		$this->loadModel('Sponsorship');
		$this->loadModel('SponsorBanner');

		$location = (isset($this->request->query['location']))? $this->request->query['location'] : '';
		$location = Sanitize::clean($location);

		$this->set('location',$location);
		
		//load sponsorship details
		$rs = $this->Sponsorship->findById($sponsor_id);
		$this->set('sponsor',$rs['Sponsorship']);

			
		$banner = $this->SponsorBanner->findById($banner_id);
		$this->set('banner',$banner['SponsorBanner']);


		//overall monthly
		$overall_monthly = $this->Game->query("SELECT location,current_month AS mt,current_year AS yr,
												SUM(t_click) AS clicks,SUM(t_view) AS views 
												FROM ".$_SESSION['ffgamedb'].".sponsor_banner_logs 
												WHERE banner_id={$banner_id} AND location='{$location}' 
												GROUP BY current_month,current_year,location
												LIMIT 0,36;");
		$this->set('overall_monthly',$overall_monthly);
	}
	public function banner_per_city($banner_id=0){
		$this->layout="ajax";
		$start = intval(@$this->request->query['start']);
		$sql = "SELECT location,
				SUM(t_click) AS clicks,
				SUM(t_view) AS views 
				FROM ".$_SESSION['ffgamedb'].".sponsor_banner_logs 
				WHERE banner_id=$banner_id
				GROUP BY location
				LIMIT {$start},20;";
			
		$rs = $this->Game->query($sql);
		$stats = array();
		$total_data = sizeof($rs);
		while(sizeof($rs)>0){
			$p = array_shift($rs);
			$stats[] = array('location'=>$p['sponsor_banner_logs']['location'],
							 'impressions'=>$p[0]['views'],
							 'clicks'=>$p[0]['clicks']
							 );
			$start += 20;
		}
		unset($rs);
		$this->set('response',array('status'=>1,'total_data'=>$total_data,'data'=>$stats,'next_offset'=>$start));
		$this->render('response');
	}
	public function add_perk(){
		$this->layout="ajax";
		if($this->request->is('post')){
			$this->loadModel('Sponsorship');
			$rs = $this->Sponsorship->addPerk($this->request->data);
			if($rs){
				$this->set('response',array('status'=>1));	
			}else{
				$this->set('response',array('status'=>0));
			}
		}else{
			$this->set('response',array('status'=>0));
		}
		$this->render('response');
	}

	public function create(){
		$this->loadModel('Sponsorship');
		if($this->request->is('post')){
			$this->Sponsorship->create();
			$rs = $this->Sponsorship->save($this->request->data);
			if($rs){
				$this->Session->setFlash("new sponsorship has been saved successfully !");
				$this->redirect('/sponsors/edit/'.$this->Sponsorship->id);
			}else{
				$this->Session->setFlash("Cannot save the sponsorship, please try again later !");
			}
		}
	}
	public function create_perk(){
		$this->loadModel('Perk');
		if($this->request->is('post')){
			$this->Perk->create();
			$rs = $this->Perk->save($this->request->data);
			if($rs){
				$this->Session->setFlash("new perk has been saved successfully !");
				$this->redirect('/sponsors/perks');
			}else{
				$this->Session->setFlash("Cannot save the perk, please try again later !");
			}
		}
	}
	public function edit_perk($id){
		$this->loadModel('Perk');
		
		
		//posting data (if any)
		if($this->request->is('post')){
			$this->Perk->id = $id;
			$rs = $this->Perk->save($this->request->data);
			if($rs){
				$this->Session->setFlash("The changes has been saved successfully !");
			}else{
				$this->Session->setFlash("Cannot save the changes, please try again later !");
			}
		}//-->

		//load sponsorship details
		$rs = $this->Perk->findById($id);
		
		$this->set('perk',$rs['Perk']);
	}
	public function edit($id){
		$this->loadModel('Sponsorship');
		$this->loadModel('SponsorBanner');

		//attach perks on json
		$perks = $this->Sponsorship->getPerks();
		$this->set('perks',$perks);

		//load banners
		$banners = $this->SponsorBanner->find('all',array('conditions'=>array('sponsor_id'=>$id),
														'limit'=>200));
		$this->set('banners',$banners);

		//posting data (if any)
		if($this->request->is('post')){
			$this->Sponsorship->id = $id;
			$rs = $this->Sponsorship->save($this->request->data);
			if($rs){
				$this->Session->setFlash("The changes has been saved successfully !");
			}else{
				$this->Session->setFlash("Cannot save the changes, please try again later !");
			}
		}//-->
		
		//load sponsorship details
		$rs = $this->Sponsorship->findById($id);
		$rs['Sponsorship']['perks'] = $this->Sponsorship->getPerksBySponsorId($rs['Sponsorship']['id']);
		$this->set('sponsor',$rs['Sponsorship']);
	}
	//upload banner assets
	public function upload($id){
		$_FILES['file']['name'] = str_replace(array(' ','\''),"_",$_FILES['file']['name']);
		$this->set('sponsor_id',$id);
		if(move_uploaded_file($_FILES['file']['tmp_name'],
				Configure::read('avatar_img_dir').$_FILES['file']['name'])){
			
			//save to db
			$data = array(
				'banner_name'=>$this->request->data['name'],
				'url'=>$this->request->data['url'],
				'banner_file'=>$_FILES['file']['name'],
				'slot'=>$this->request->data['slot'],
				'sponsor_id'=>$id,
				'upload_date'=>date("Y-m-d H:i:s")
			);
			
			$this->loadModel('SponsorBanner');
			$rs = $this->SponsorBanner->save($data);
			$this->set('success',1);
		}else{
			$this->set('success',0);
		}
	}
	public function remove_banner(){
		$sponsor_id = $this->request->query['sponsor_id'];
		$banner_id = $this->request->query['id'];
		$this->loadModel('SponsorBanner');
		$rs = $this->SponsorBanner->delete($banner_id);
		$this->redirect('/sponsors/edit/'.$sponsor_id);

	}
	public function perks(){
		$this->loadModel('Perk');
		$rs = $this->Perk->find('all');
		$this->set('rs',$rs);
	}

	public function invite($id){
		$this->loadModel('Sponsorship');
		$this->loadModel('SponsorBanner');
		//load banners
		$banners = $this->SponsorBanner->find('all',array('conditions'=>array('sponsor_id'=>$id),
														'limit'=>200));
		$this->set('banners',$banners);

		//load sponsorship details
		$rs = $this->Sponsorship->findById($id);
		$rs['Sponsorship']['perks'] = $this->Sponsorship->getPerksBySponsorId($rs['Sponsorship']['id']);
		$this->set('sponsor',$rs['Sponsorship']);
	}
	public function queueing(){
		$this->loadModel('Sponsorship');
		$this->layout = 'ajax';
		$filter = $this->request->query['filter'];
		$email_type = $this->request->query['email_type'];
		$sponsor_id = intval($this->request->query['sponsor_id']);
		$start = intval($this->request->query['start']);

		$sponsor = $this->Sponsorship->findById($sponsor_id);
		

		$start_rank = 0;
		$end_rank = 0;

		if($filter=='everyone_once'){
			$rs = $this->Sponsorship->query("SELECT a.fb_id,c.email,b.id AS game_team_id
										FROM ".$_SESSION['ffgamedb'].".game_users a
										INNER JOIN ".$_SESSION['ffgamedb'].".game_teams b
										ON a.id = b.user_id
										INNER JOIN users c
										ON a.fb_id = c.fb_id LIMIT {$start},20;
										");
			$total_scan = sizeof($rs);
			$in_queue = $this->queue_everyone_once($sponsor_id,$rs,$email_type,$sponsor['Sponsorship']);
		}else if($filter=="tier1"){
			$rs = $this->queue_in_tier1_once(1,$start,$sponsor_id,$email_type,$sponsor['Sponsorship']);
			$total_scan = $rs['total'];
			$in_queue = $rs['in_queue'];
			$start_rank = $rs['start_rank'];
			$end_rank = $rs['end_rank'];
		}else if($filter=="tier2"){
			$rs = $this->queue_in_tier1_once(2,$start,$sponsor_id,$email_type,$sponsor['Sponsorship']);
			$total_scan = $rs['total'];
			$in_queue = $rs['in_queue'];
			$start_rank = $rs['start_rank'];
			$end_rank = $rs['end_rank'];
		}else if($filter=="tier3"){
			$rs = $this->queue_in_tier1_once(3,$start,$sponsor_id,$email_type,$sponsor['Sponsorship']);
			$total_scan = $rs['total'];
			$in_queue = $rs['in_queue'];
			$start_rank = $rs['start_rank'];
			$end_rank = $rs['end_rank'];
		}else if($filter=="tier4"){
			$rs = $this->queue_in_tier1_once(4,$start,$sponsor_id,$email_type,$sponsor['Sponsorship']);
			$total_scan = $rs['total'];
			$in_queue = $rs['in_queue'];
			$start_rank = $rs['start_rank'];
			$end_rank = $rs['end_rank'];
		}else{
			$rs = $this->Sponsorship->query("SELECT a.fb_id,c.email,b.id AS game_team_id
										FROM ".$_SESSION['ffgamedb'].".game_users a
										INNER JOIN ".$_SESSION['ffgamedb'].".game_teams b
										ON a.id = b.user_id
										INNER JOIN ffg.users c
										ON a.fb_id = c.fb_id LIMIT {$start},20;
										");
			$total_scan = sizeof($rs);
			$in_queue = $total_scan;
		}
		
		
		$this->set('response',array('status'=>1,
									'total'=>$total_scan,
									'in_queue'=>$in_queue,
									'start_rank'=>$start_rank,
									'end_rank'=>$end_rank));	
		
		
		$this->render('response');
	}
	private function queue_in_tier1_once($tier,$start,$sponsor_id,$email_type,$sponsor){
		//get current users in total.
		$ct = $this->Game->query("SELECT COUNT(a.id) AS total 
								FROM points a
								INNER JOIN teams b
								ON a.team_id = b.id WHERE a.league='{$_SESSION['league']}';");
		$total_users = $ct[0][0]['total'];
		
		switch($tier){
			case 1:
				$start_rank = 0;
				$end_rank 	= floor(0.25 * $total_users);
			break;
			case 2:
				$start_rank = ceil(0.25 * $total_users);
				$end_rank 	= floor(0.5 * $total_users);
			break;
			case 3:
				$start_rank = ceil(0.5 * $total_users);
				$end_rank 	= floor(0.75 * $total_users);
			break;
			case 4:
				$start_rank = ceil(0.75 * $total_users);
				$end_rank 	= floor(1.0 * $total_users);
			break;
			default:
				$start_rank = 0;
				$end_rank 	= 0;
			break;
		}
			$sql = "SELECT b.team_name,c.* FROM points a
			INNER JOIN teams b
			ON a.team_id = b.id
			INNER JOIN users c
			ON b.user_id = c.id 
			WHERE 
			a.league='{$_SESSION['league']}'
			AND
			rank > {$start_rank} 
			AND rank <={$end_rank}
			LIMIT {$start},20;";
			
			$rs = $this->Game->query($sql);

			$total_scan = sizeof($rs);
			$in_queue = 0;

			foreach($rs as $r){
				//get the game_team_id and put it in queue
				$team = $this->Game->query("SELECT a.id AS game_team_id 
						FROM ".$_SESSION['ffgamedb'].".game_teams a
						INNER JOIN ".$_SESSION['ffgamedb'].".game_users b
						ON a.user_id = b.id
						WHERE b.fb_id = '{$r['c']['fb_id']}';");
				
				$game_team_id = intval(@$team[0]['a']['game_team_id']);
				$in_queue += $this->queue_everyone_in_tier($sponsor_id,$game_team_id,$r['c']['email'],$email_type,$sponsor);
			}
			return array('total'=>$total_scan,
						 'in_queue'=>$in_queue,
						 'start_rank'=>$start_rank,
						 'end_rank'=>$end_rank);
			
	}
	private function queue_everyone_in_tier($sponsor_id,$game_team_id,$email,$email_type,$sponsor){
		$in_queue = 0;

		
			$apply_link = $this->generate_apply_link($sponsor_id,
													 $game_team_id,
													 $email,
													 $email_type);
			//put into queue
			if(intval($game_team_id)>0){
				$q = $this->Sponsorship->query("INSERT INTO ".$_SESSION['ffgamedb'].".game_sponsor_emails
										(sponsor_id,game_team_id,email_type,email,apply_link,sent_dt)
										VALUES
										({$sponsor_id},{$game_team_id},'{$email_type}',
										 '{$email}','{$apply_link}',NOW())");

				if(is_array($q)){
					//@TODO - we have to also create HTML version of email body :(
					//queue email
					//invitation_email
					if($email_type=='invitation'){
						$subject = "Proposal Sponsor Klab dari ".$sponsor['name'];
						$plain = str_replace("{{APPLY_LINK}}",Configure::read('WWW_URL').$apply_link,$sponsor['invitation_email']);
					}else{
						$subject = "Selamat, anda mendapatkan bonus dari sponsor !";
						$plain = str_replace("{{APPLY_LINK}}",Configure::read('WWW_URL').$apply_link,$sponsor['win_bonus_email']);
					}
					$view = new View($this, false);
					$body = $view->element('html_email',array('subject'=>$subject,'body'=>$plain));
					
					$body = mysql_escape_string($body);

					//queue email
					$this->Sponsorship->query("INSERT INTO ".$_SESSION['ffgamedb'].".email_queue
												(subject,email,plain_txt,html_text,queue_dt,send_dt,n_status)
												VALUES
												('{$subject}','{$email}','{$body}','{$body}',NOW(),NULL,0);");

					//add to notification
					$notif_msg = mysql_escape_string(str_replace("{{APPLY_LINK}}",
											Configure::read('WWW_URL').$apply_link,
											$sponsor['invitation_email']));

					$this->Game->query("INSERT INTO notifications
												(content,url,dt,game_team_id,league)
												VALUES
												('{$notif_msg}','#',NOW(),{$game_team_id},'{$_SESSION['league']}');");
					return 1;
				}else{
					return 0;
				}
			}else{
				return 0;
			}
			
	}
	private function queue_everyone_once($sponsor_id,$rs,$email_type,$sponsor){
		$in_queue = 0;

		foreach($rs as $n=>$v){
			//check for queue
			$cek = $this->Sponsorship->query("SELECT * FROM ".$_SESSION['ffgamedb'].".game_sponsor_emails 
												WHERE sponsor_id={$sponsor_id} 
												AND game_team_id={$v['b']['game_team_id']}
												AND email_type='{$email_type}' LIMIT 1");
			if(sizeof($cek)==0){
				$in_queue++;
				$apply_link = $this->generate_apply_link($sponsor_id,
														 $v['b']['game_team_id'],
														 $v['c']['email'],
														 $email_type);
				//put into queue
				$q = $this->Sponsorship->query("INSERT INTO ".$_SESSION['ffgamedb'].".game_sponsor_emails
											(sponsor_id,game_team_id,email_type,email,apply_link,sent_dt)
											VALUES
											({$sponsor_id},{$v['b']['game_team_id']},'{$email_type}',
											 '{$v['c']['email']}','{$apply_link}',NOW())");

				if(is_array($q)){
					//@TODO - we have to also create HTML version of email body :(
					//queue email
					//invitation_email
					if($email_type=='invitation'){
						$plain = str_replace("{{APPLY_LINK}}",Configure::read('WWW_URL').$apply_link,$sponsor['invitation_email']);
					}else{
						$plain = str_replace("{{APPLY_LINK}}",Configure::read('WWW_URL').$apply_link,$sponsor['win_bonus_email']);
					}
					//$plain = mysql_escape_string($plain);
					$this->Sponsorship->query("INSERT INTO ".$_SESSION['ffgamedb'].".email_queue
												(email,plain_txt,html_text,queue_dt,send_dt,n_status)
												VALUES
												('{$v['c']['email']}','{$plain}','{$plain}',NOW(),NULL,0);");
				}
			}
		}
		return $in_queue;
	}

	
	private function generate_apply_link($sponsor_id,$game_team_id,$email,$email_type){
		$data = serialize(array('sponsor_id'=>$sponsor_id,
					  'game_team_id'=>$game_team_id,
					  'email'=>$email,
					  'email_type'=>$email_type));

		$hash = encrypt_param($data);
		return "/sponsors/apply/?c=".$hash;
	}
}
