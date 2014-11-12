<?php
App::uses('AppController', 'Controller');
App::uses('Sanitize', 'Utility');


class PrivateleagueController extends AppController {

	/**
	 * Controller name
	 *
	 * @var string
	 */

	public $name = 'Privateleague';
	public $components = array('Thumbnail');
	public $league;

	public function beforeFilter()
	{
		parent::beforeFilter();
		$this->loadModel('League');
		$this->loadModel('leagueInvitation');
		$this->loadModel('User');

		$userData = $this->getUserData();
		$user = $this->userDetail;
		$this->set('user',$user['User']);
		if(!$this->hasTeam()){
			$this->redirect('/login/expired');
		}

		$this->league = $this->Session->read('league');
	}

	public function hasTeam(){
		$userData = $this->getUserData();
		if(is_array($userData['team'])){
			return true;
		}
	}

	public function index()
	{
		$rs_user = $this->userDetail;
		$team_id 	= $rs_user['Team']['id'];
		$join_button = false;

		//check if user has create private league or user has joined private league
		$result = $this->League->checkUser($rs_user['User']['email'], $team_id, $this->league);
		if($result['has_invited'] == 1 && $result['has_joined'] != 1)
		{
			$join_button = true;	
		}

		$this->set('join_button', $join_button);
		$rs = $this->League->getLeague($team_id, $this->league);
		$this->set('rs', $rs);
		$this->set('user_id', $rs_user['User']['id']);
	}

	public function create()
	{
		$this->Session->delete('league_id');

		$rs_user 	= $this->userDetail;
		$user_id 	= $rs_user['User']['id'];
		$email 		= $rs_user['User']['email'];
		$team_id 	= $rs_user['Team']['id'];



		//check if user has create private league or user has joined private league
		$result = $this->League->checkUser($email, $team_id, $this->league);

		if($result['has_invited'] == 1)
		{
			//page join
			$this->redirect('/privateleague/join_confirmation');
		}
		else if($result['has_joined'] == 1)
		{
			//redirect to /privateleague
			$this->Session->setFlash('Sorry, lo sudah bergabung dengan Private League sebelumnya.');
			$this->redirect('/privateleague');
		}

		if($this->request->is("post"))
		{
			$upload_dir	= Configure::read('privateleague_web_dir');

			$league_name = Sanitize::clean($this->request->data['name']);
			$league_logo = $_FILES['logo'];
			if(is_array($league_logo))
			{
				$allow_ext = array('jpeg', 'jpg', 'gif');
				$aFile = explode('.', $league_logo['name']);
				$file_ext = array_pop($aFile);
				$filename = 'privateleague_default.jpg';
				if(in_array($file_ext, $allow_ext))
				{
					$filename = date("ymdhis").'-'.rand(0, 99999).'.'.$file_ext;
					if(move_uploaded_file($league_logo['tmp_name'], $upload_dir.$filename))
					{
						$this->Thumbnail->create($upload_dir.$filename,
														$upload_dir.'thumb_'.$filename,
														150,150);
					}
				}

				$data = array('name' => $league_name,
									'logo' => $filename,
									'type' => 'private_league',
									'user_id' => $user_id,
									'max_player' => 30,
									'date_created' => date("Y-m-d H:i:s"),
									'n_status' => 1,
									'league' => $this->league
										);

				try{
					$dataSource = $this->League->getDataSource();
					$dataSource->begin();

					$this->League->create();
					$this->League->save($data);

					$league_id = $this->League->id;

					$this->League->query("INSERT INTO 
										league_invitations(league_id, email, n_status, league) 
										VALUES('{$league_id}','{$email}',1,'{$this->league}')");

					$this->League->query("INSERT INTO league_member
												(league_id,team_id,join_date,n_status,league) 
												VALUES('{$league_id}','{$team_id}',
													now(),1,'{$this->league}')");

					$dataSource->commit();
					$this->Session->write('league_id', $league_id);
					$this->redirect('/privateleague/invite');

				}catch(Exception $e){
					$dataSource->rollback();
					$this->Session->setFlash('Terjadi kesalahan, silahkan coba lagi');
					$this->redirect('/privateleague/error');
				}
			}
		}
	}

	public function invite()
	{
		$league_id = $this->Session->read('league_id');
		if($league_id == NULL){
			$league_trx = unserialize(decrypt_param($this->request->data['league_trx']));
			$league_id = $league_trx['league_id']; 
			if(!isset($league_id)){
				$this->redirect('/privateleague');
			}
		}

		$rs_league = $this->League->findById($league_id);
		$max_player = $rs_league['League']['max_player'];

		$rs_invited = $this->leagueInvitation->find('count', array(
	        'conditions' => array(
	        						'leagueInvitation.league_id' => $league_id,
	        						'league' => $this->league,
	        						'n_status' => 0
	        					))
	    );

	    $rs_joined = $this->leagueInvitation->find('count', array(
	        'conditions' => array(
	        						'leagueInvitation.league_id' => $league_id,
	        						'league' => $this->league,
	        						'n_status' => 1
	        					))
	    );

	    $limit = $max_player - ($rs_invited + $rs_joined);
	    $this->set('limit', $limit);

		if($this->request->is('post'))
		{
			try{
				$email = $this->request->data['email'];
				
				$i=2;
				foreach ($email as $value)
				{
					$data[] = array('league_id' => $league_id,
									'email' => $value,
									'n_status' => 0,
									'league' => $this->league);
					if($i == $limit){
						break;
					}
					$i++;
				}

				$this->leagueInvitation->saveMany($data);
				
				$this->Session->delete('league_id');
				$this->Session->setFlash('Undangan untuk gabung ke Private League '.$rs_league['League']['name'].' sudah dikirim ke email teman loe.');
				$this->redirect('/privateleague');
			}catch(Exception $e){
				$this->Session->setFlash('Terjadi kesalahan, silahkan coba lagi');
				$this->redirect('/privateleague/error');
			}
		}
	}

	public function join_confirmation()
	{
		$rs_user 	= $this->userDetail;
		$email 		= $rs_user['User']['email'];

		$rs_league = $this->League->query("SELECT * FROM league_invitations a 
											INNER JOIN league b ON a.league_id = b.id 
											INNER JOIN users c ON b.user_id = c.id 
											WHERE a.email = '{$email}' AND a.n_status = 0
											AND a.league='{$this->league}'
											LIMIT 1");
		if(count($rs_league) > 0)
		{
			$data_enc = array(
								'email' => $rs_league[0]['a']['email'], 
								'league_id' => $rs_league[0]['a']['league_id'],
								'league' => $rs_league[0]['a']['league']
								);
			$trx_code = encrypt_param(serialize($data_enc));

			$this->set('trx_code', $trx_code);
			$this->set('rs_league', $rs_league);
		}
		else
		{
			$this->Session->setFlash('Sorry, lo sudah bergabung dengan Private League sebelumnya.');
			$this->redirect('/privateleague');
		}

	}

	public function linkjoin()
	{
		$trx_code = $this->request->query['trx'];
		$param = unserialize(decrypt_param($trx_code));
		$rs_user = $this->userDetail;

		if($param['email'] == $rs_user['User']['email'])
		{
			$rs_league = $this->League->query("SELECT * FROM league_invitations a 
											INNER JOIN league b ON a.league_id = b.id 
											INNER JOIN users c ON b.user_id = c.id 
											WHERE a.email = '{$param['email']}' AND a.n_status = 0
											AND a.league='{$param['league']}' 
											AND league_id='{$param['league_id']}'
											LIMIT 1");

			if(count($rs_league) > 0)
			{
				$this->set('rs_league', $rs_league);
				$this->set('trx_code', $trx_code);
			}
			else
			{
				$this->redirect('/privateleague');
			}
		}
		else
		{
			$this->redirect('/privateleague');
		}

	}

	public function join()
	{
		$rs_user 	= $this->userDetail;
		$email 		= $rs_user['User']['email'];

		$trx_code = $this->request->query['trx'];
		$param = unserialize(decrypt_param($trx_code));
		$frontend_schema = Configure::read('FRONTEND_SCHEMA');

		$rs_team = $this->League->query("SELECT id FROM ".$frontend_schema.".teams 
								WHERE user_id='{$rs_user['User']['id']}' 
								AND league='{$param['league']}'");

		$team_id = $rs_team[0]['teams']['id'];

		if($param['email'] == $email)
		{
			try{
				$dataSource = $this->League->getDataSource();
				$dataSource->begin();

				$this->leagueInvitation->updateAll(
									array('n_status' => 1),
									array(
											'league_id' => $param['league_id'],
											'email' => $param['email'], 
											'n_status' => 0, 
											'league' => $param['league']
										)
									);
				$this->League->query("INSERT INTO league_member(league_id,team_id,join_date,n_status,league)
										VALUES('{$param['league_id']}','{$team_id}',now(),1,'{$param['league']}')");
				$dataSource->commit();
				$this->redirect('/privateleague');
			}catch(Exception $e){
				$dataSource->rollback();
				$this->leagueInvitation->updateAll(
									array('n_status' => 2),
									array(
											'league_id' => $param['league_id'],
											'email' => $param['email'], 
											'n_status' => 0,
											'league' => $param['league']
										));
				$this->Session->setFlash("Loe gak bisa join karena sudah pernah join private league sebelumnya");
				$this->redirect("/privateleague/error");
			}
		}
		else
		{
			$this->redirect("/privateleague");
		}
	}

	public function reject()
	{
		$rs_user 	= $this->userDetail;
		$email 		= $rs_user['User']['email'];

		$trx_code = $this->request->query['trx'];
		$param = unserialize(decrypt_param($trx_code));

		if($param['email'] == $email)
		{
			$this->leagueInvitation->updateAll(
									array('n_status' => 2),
									array(
										'league_id' => $param['league_id'],
										'email' => $param['email'],
										'n_status' => 0,
										'league' => $param['league']
										));
			$this->redirect('/privateleague');
		}
		else
		{
			$this->redirect('/privateleague');
		}

	}

	public function pengaturan()
	{
		$rs_user 	= $this->userDetail;

	    $rs_league = $this->League->query("SELECT * FROM league 
	    									WHERE user_id='{$rs_user['User']['id']}'
	    									AND league='{$this->league}' LIMIT 1");

	    $max_player = $rs_league[0]['league']['max_player'];

	    if(count($rs_league) == 0){
	    	$this->redirect('/privateleague');
	    }

	    $league_id = $rs_league[0]['league']['id'];
	    $league_id_enc = encrypt_param(serialize(array('league_id' => $league_id)));
	    $this->set('league_trx', $league_id_enc);

	    $rs_invited = $this->leagueInvitation->find('all', array(
	    	'limit' => 1000,
	        'conditions' => array(
	        						'leagueInvitation.league_id' => $league_id,
	        						'league' => $this->league,
	        						'n_status' => 0
	        					))
	    );
	    foreach ($rs_invited as $key => $value) {
	    	$data_enc = array(
	    						'league_id' => $value['leagueInvitation']['league_id'],
	    						'email' => $value['leagueInvitation']['email'],
	    						'league' => $value['leagueInvitation']['league']
	    					);
	    	$rs_invited[$key]['leagueInvitation']['trx_code'] = encrypt_param(serialize($data_enc));
	    }

	    //$params = encrypt_param(serialize($data_user));

	    $rs_joined = $this->leagueInvitation->find('all', array(
	    	'limit' => 1000,
	        'conditions' => array(
	        						'leagueInvitation.league_id' => $league_id,
	        						'league' => $this->league,
	        						'n_status' => 1
	        					))
	    );

	    $limit = $max_player - (count($rs_invited) + count($rs_joined));
	    
	    $this->set('limit', $limit);
	    $this->set('rs_invited', $rs_invited);
	    $this->set('rs_joined', $rs_joined);
	    
	}

	public function error()
	{

	}

	public function leaderboard()
	{
		$rs_user 	= $this->userDetail;
		$team_id 	= $rs_user['Team']['id'];

		$matchday = 0;

		$rs_leaguetable = $this->League->query("SELECT matchday FROM league_table
												WHERE league='{$this->league}' 
												ORDER BY id DESC LIMIT 1");

		if(count($rs_leaguetable) > 0){
			$matchday = $rs_leaguetable[0]['league_table']['matchday'];
		}

		$rs_league = $this->League->getLeague($team_id, $this->league);

		$rs_leaderboard = $this->League->query("SELECT c.id,c.team_name,d.name,b.total_points FROM league_member a 
												INNER JOIN league_table b ON a.league_id = b.league_id
												INNER JOIN teams c ON b.team_id = c.id
												INNER JOIN users d ON c.user_id = d.id
												WHERE b.league_id='{$rs_league[0]['a']['league_id']}' 
												AND b.matchday='{$matchday}'
												AND b.league='{$this->league}'
												GROUP BY b.team_id
												ORDER BY b.total_points DESC
												LIMIT 1000");

		

		$this->set('rs_leaderboard', $rs_leaderboard);
	}

}