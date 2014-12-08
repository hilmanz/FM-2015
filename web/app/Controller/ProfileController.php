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
require_once APP . 'Vendor' . DS. 'Thumbnail.php';

//we use password-hash for dealing with wordpress's password hash
require_once APP.DS.'Vendor'.DS.'password-hash.php';

require_once APP.DS.'Vendor'.DS.'autoload-mailgun.php';
use Mailgun\Mailgun;

require_once APP.DS.'Vendor'.DS.'phpmailer'.DS.'PHPMailerAutoload.php';

/**
 * Static content controller
 *
 * Override this controller by placing a copy in controllers directory of an application
 *
 * @package       app.Controller
 * @link http://book.cakephp.org/2.0/en/controllers/pages-controller.html
 */
class ProfileController extends AppController {

/**
 * Controller name
 *
 * @var string
 */
	public $name = 'Profile';

/**
 * This controller does not use a model
 *
 * @var array
 */
	public $uses = array();
	public $components = array('Captcha');

	public function beforeFilter(){
		parent::beforeFilter();
		$userData = $this->getUserData();
		$this->loadModel('ProfileModel');
		$this->loadModel('Captchacode');
		$this->ProfileModel->setAccessToken($this->getAccessToken());
	}
	public function hasTeam(){
		$userData = $this->getUserData();
		if(is_array($userData['team'])){
			return true;
		}
	}

	public function captcha(){
		$this->autoRender = false;
		$this->layout='ajax';
		if(!isset($this->Captcha)){ //if Component was not loaded throug $components array()
			$this->Captcha = $this->Components->load('Captcha', array(
				'width' => 150,
				'height' => 50,
				'theme' => 'default', //possible values : default, random ; No value means 'default'
			)); //load it
		}
		$this->Captcha->create();
	}

	public function index(){
		if($this->hasTeam()){
			$this->set('avatar_dir',Configure::read('avatar_web_dir'));
			$userData = $this->getUserData();
			$this->loadModel('User');
			//data user
			$user = $this->User->findByFb_id($userData['fb_id']);
			
			$this->set('user',$user['User']);
			$this->set('team',$user['Team']);
			
			//budget
			$budget = $this->Game->getBudget($userData['team']['id']);
			$this->set('team_bugdet',$budget);
			
			//long banner
			$long_banner = $this->getBanners('PROFILE_BANNER',2,true);
			$this->set('long_banner',$long_banner);
			
			$this->render('details');

			
		}else{
			$this->redirect('/');
		}
		
	}

	public function booster()
	{
		$userData = $this->getUserData();

		//long banner
		$long_banner = $this->getBanners('PROFILE_BANNER',2,true);
		$this->set('long_banner',$long_banner);

		//budget
		$budget = $this->Game->getBudget($userData['team']['id']);
		$this->set('team_bugdet',$budget);

		$booster = $this->Game->getPoinBooster($userData['team']['id']);
		$this->set('booster', $booster);
	}

	public function create_password()
	{
		$userData = $this->getUserData();
		$this->loadModel('User');
		if($this->request->is('post'))
		{
			$password 			= trim(Sanitize::clean($this->request->data['password']));
			$password_repeat 	= trim(Sanitize::clean($this->request->data['password_repeat']));
			$secret				= md5(date("Ymdhis"));
			if($password != $password_repeat)
			{
				$this->Session->setFlash("Maaf, Password tidak sama");
			}
			else if(strlen($password) < 6)
			{
				$this->Session->setFlash("Password harus lebih dari 6 karakter");
			}
			else
			{
				$passHasher = new PasswordHash(8, true);
				$user_pass = $passHasher->HashPassword($password.$secret);
				
				//update table user by fb_id
				$fb_id = intval($userData['fb_id']);
				try{
					$this->User->query("UPDATE users 
										SET password='{$user_pass}', secret = '{$secret}' 
										WHERE fb_id = '{$fb_id}'");
					$rs_user = $this->User->findByFb_id($fb_id);

					if($rs_user['User']['n_status'] == 0){
						if($this->send_mail($rs_user['User'])){
							$this->redirect('/profile/activation');
						}
						else
						{
							Cakelog::write('error', 'profile.create_password email error :'.json_encode($rs_user['User']));
							$this->Session->setFlash("Email yang loe masukan gak valid !");
							$this->redirect("/profile/send_activation");
						}
					}
					else if($rs_user['User']['register_completed'] == 0)
					{
						$this->Session->write('Userlogin.is_login', true);
						$this->redirect('/profile/register_team');
					}
					else
					{
						$this->Session->write('Userlogin.is_login', true);
						$this->redirect('/manage/team');
					}
				}catch(Exception $e){
					$this->Session->setFlash("Terjadi Kesalahan, silahkan coba lagi");
					Cakelog::write('error', "ProfileController.create_password message ".$e->getMessage());
				}
				
			}
		}
	}

	public function update(){
		if(strlen($this->request->data['email'])>0){
			$data = array(
				'name'=>$this->request->data['name'],
				'email'=>$this->request->data['email'],
				'phone_number'=>$_POST['phone_number'],
				'location'=>$this->request->data['location']
			);
			
			$userData = $this->getUserData();
			$this->loadModel('User');
			$user = $this->User->findByFb_id($userData['fb_id']);
			$this->User->id = $user['User']['id'];
			//update user profile
			$rs = $this->User->save($data);

			//update team name
			$this->loadModel('Team');
			$this->Team->id = intval($user['Team']['id']);
			$this->Team->save(array(
					'team_name' => $this->request->data['team_name']
				));
			if(isset($rs)){

				$this->Session->setFlash('Profil Anda telah berhasil diubah!');
				$this->redirect('/profile/success');
			}else{
				$this->Session->setFlash('Gagal menyimpan perubahan, coba beberapa saat lagi!');
				$this->redirect('/profile/error');
			}
		}else{
			$this->Session->setFlash('Mohon maaf, email wajib diisi !');
				$this->redirect('/profile/error?attempt=1');
		}
		
	
		die();
	}
	private function getBudget($team_id){
		/*Budget*/
		
		$team_id = 1; // sample
		$this->set('team_bugdet',$this->ProfileModel->getBudget($team_id));
	}
	public function details(){
		$this->getBudget($team_id);

		/*facebook detail*/
		$userData = $this->getUserData();
		$this->set('avatar_dir',Configure::read('avatar_img_dir'));
		if($_POST['save']==1){
			$data = array(
				'fb_id'=>$userData['fb_id'],
				'name'=>$_POST['name'],
				'email'=>$_POST['email'],
				'phone'=>$_POST['phone_number'],
				'phone'=>$_POST['city']
			);
			$response = $this->ProfileModel->setProfile($data);
			if($response){
				$this->redirect("/profile/teams");
			}
		}
	}
	
	public function register_team(){
		$userData = $this->getUserData();
		CakeLog::write('create_team',' ['.$_SESSION['league'].'] - register_team');
		if(@$userData['register_completed']!=1 || $userData['team']==null){
			$team = $this->Session->read('TeamRegister');
			$this->set('previous_team',$team);

			if($userData==null){
				$this->redirect('/login/expired');
			}
			if($this->request->is('post')){
				if(strlen($this->request->data['team_name']) > 0
					&& strlen($this->request->data['team_id']) > 0
					&& strlen($this->request->data['fb_id']) > 0){
					$this->Session->write('TeamRegister',$this->request->data);
					$this->redirect('/profile/select_player');
				}else{
					$this->Session->setFlash('Kamu harus memilih salah satu team terlebih dahulu !');
					$this->redirect('/profile/team_error');
				}
				
			}else{
				$teams = $this->Game->getTeams();
				$this->set('team_list',$teams);
				$this->set('INITIAL_BUDGET',Configure::read('INITIAL_BUDGET'));
			}
		}else{
			$this->redirect("/");
		}
	}

	public function activation()
	{
		$user_fb = $this->Session->read('Userlogin.info');

		Cakelog::write('debug', 'profile.activation user_fb:'.json_encode($user_fb));

		$this->loadModel('User');
		$rs_user = $this->User->findByFb_id($user_fb['fb_id']);

		$this->set('user_data', $rs_user['User']);

		if($this->request->is("post"))
		{
			$act_code = trim(Sanitize::clean($this->request->data['act_code']));

			if($act_code == $rs_user['User']['activation_code'])
			{
				try{
					$rs = $this->User->updateAll(
	                                                array('n_status' => 1),
	                                                array(
	                                                                'fb_id' => $user_fb['fb_id']
	                                                        )
	                                                );
	                Cakelog::write('debug','profile.activation rs:'.json_encode($rs));
					Cakelog::write('debug','profile.activation rs_user:'.json_encode($rs_user));
					
					if($rs_user['User']['password'] == ''){
						$this->Session->write('Userlogin.is_login', false);
						$this->redirect('/profile/create_password');
					}else{
						$this->Session->write('Userlogin.is_login', true);
						$this->redirect('/profile/register_team');
					}
				}catch(Exception $e){
					Cakelog::write('error', 'profile.activation message:'.$e->getMessage());
				}
			}
			else
			{
				$this->Session->setFlash("Kode Aktivasi Salah");
			}
		}
	}

	public function create_team(){
		$userData = $this->getUserData();
		CakeLog::write('create_team',' ['.$_SESSION['league'].'] - create_team');
		CakeLog::write('create_team',' ['.$this->Session->read('league').'] - create_team');
		if(@$userData['register_completed']!=1 || $userData['team']==null){
			$team = $this->Session->read('TeamRegister');
			$players = explode(',',$this->request->data['players']);
			$data = array(
				'team_id'=>Sanitize::paranoid($team['team_id']),
				'fb_id'=>Sanitize::paranoid($userData['fb_id'])
			);
			
			$players_selected = $this->Game->getMasterTeam($team['team_id']);
			$players = array();
			foreach($players_selected as $p){
				$players[] = $p['uid'];
			}

			$data['players'] = json_encode($players);


			$result = $this->Game->create_team($data);
			CakeLog::write('create_team',json_encode($data).' - result : '.json_encode($result));
			$this->loadModel('User');			
			$user = $this->User->findByFb_id($userData['fb_id']);
			
			$step1_ok = false;
			$step2_ok = false;

			$all_ok = false;
			
			if(isset($result['error'])){
				CakeLog::write('create_team',$data['fb_id'].'-failed creating game_user and game_team');
				/*$this->User->id = $user['User']['id'];
				$this->User->set('register_completed',1);
				$rs = $this->User->save();*/
				
				$this->Session->setFlash('Maaf, Anda tidak dapat membentuk tim lagi. Nampaknya Anda sudah melakukan pembentukan tim sebelumnya.');
				$this->redirect('/profile/team_error');
			}else{
				CakeLog::write('create_team',$data['fb_id'].' ['.$this->Session->read('league').'] - success creating game_user and game_team');
				$step1_ok = true;
				$userData['team'] = $this->Game->getTeam(Sanitize::paranoid($userData['fb_id']));
				$this->loadModel('Team');
				$this->Team->create();
				$InsertTeam = $this->Team->save(array(
					'user_id'=>$user['User']['id'],
					'team_id'=>Sanitize::paranoid($team['team_id']),
					'team_name'=>Sanitize::clean($team['team_name']),
					'league'=>$this->Session->read('league')
				));
				CakeLog::write('create_team','InsertTeam->'.json_encode($InsertTeam));
				if($InsertTeam){
					CakeLog::write('create_team','InsertTeam->'.json_encode($InsertTeam));
					$check_team = $this->Team->findByUser_id($user['User']['id']);
					if($check_team['Team']['user_id']==$user['User']['id']){
						CakeLog::write('create_team',$data['fb_id'].'-success creating fantasy.teams '.json_encode($InsertTeam));
						$step2_ok = true;
					}else{
						CakeLog::write('create_team',$data['fb_id'].'- data not exists in fantasy.teams '.json_encode($check_team));
					}
					
				}else{
					CakeLog::write('create_team','InsertTeam->'.json_encode($InsertTeam));
				}
				
			}
			
			if($step1_ok == true && $step2_ok==true){
				$all_ok = true;
			}

			if($all_ok){
				$this->User->id = $user['User']['id'];
				$this->User->set('register_completed',1);
				$rs = $this->User->save();
				$this->Session->write('Userlogin.info',$userData);
				$this->Session->write('TeamRegister',null);
				$this->Session->setFlash('Congratulations, Your team is ready !');
				$this->redirect('/profile/register_staff');

			}else{
				$this->Session->setFlash('Maaf, Anda tidak dapat membentuk tim. Silahkan coba kembali nanti.');
				CakeLog::write('create_team',$data['fb_id'].'- failed to save data to all tables ');
				$this->redirect('/profile/team_error');
			}
		}
	}
	/**
	* @todo harus pastiin bahwa halaman ini hanya bisa 
	* diakses kalo user uda ada register
	*/
	public function select_player(){
		if(@$userData['register_completed']!=1 || $userData['team']==null){

			$userData = $this->getUserData();
			$selected_team = $this->Session->read('TeamRegister');
			
			if(is_array($this->Session->read('TeamRegister'))){
				
				$userData = $this->getUserData();
				$this->set('INITIAL_BUDGET',Configure::read('INITIAL_BUDGET'));
				$teams = $this->Game->getTeams();
				//get players of selected team.
				$players_selected = $this->Game->getMasterTeam($selected_team['team_id']);
				$this->set('player_selected',json_encode($players_selected));


				$this->set('team_list',$teams);
				$this->set('selected_team',$selected_team);
				$original = $this->Game->getClub($selected_team['team_id']);
				$this->set('original',$original);

			}else{
				$this->redirect('/profile/register_team');
			}

		}else{
			$this->redirect('/');
		}
	}
	public function register_staff(){
		if(@$this->userData['register_completed']==1 || $userData['team']==null){
			$this->redirect('/');
		}	
		$userData = $this->getUserData();

		$user = $this->User->findByFb_id($userData['fb_id']);
				
		$this->User->id = $user['User']['id'];
		$this->User->set('register_completed',1);
		$rs = $this->User->save();

		//set register_completed ==1 in session
		$this->userData['register_completed'] = 1;
		$this->Session->write('Userlogin.info',$this->userData);
		if($rs){
			$this->Session->write('first_time',true);
			$this->redirect('/manage/team');
		}else{
			$this->redirect('/profile/error');
		}
				
		/*
		if($this->request->is('post')){
			$this->loadModel('User');
			if($this->request->data['complete_registration']==1){
				$user = $this->User->findByFb_id($userData['fb_id']);
				
				$this->User->id = $user['User']['id'];
				$this->User->set('register_completed',1);
				$rs = $this->User->save();

				//set register_completed ==1 in session
				$this->userData['register_completed'] = 1;
				$this->Session->write('Userlogin.info',$this->userData);
				if($rs){
					$this->Session->write('first_time',true);
					$this->redirect('/manage/team');
				}else{
					$this->redirect('/profile/error');
				}
			}
		}else{
			
			//budget
			$budget = $this->Game->getBudget($userData['team']['id']);
			$this->set('team_bugdet',$budget);

			//get officials
			$officials = $this->Game->getAvailableOfficials($userData['team']['id']);
			

			//estimated costs
			$total_weekly_salary = 0;

			//current staff's salary (if exists)
			foreach($officials as $official){
				if(isset($official['hired'])){
					$total_weekly_salary += intval($official['salary']);
				}
			}

			//player's salary
			$players = $this->Game->get_team_players($userData['fb_id']);

			foreach($players as $player){
				$total_weekly_salary += intval($player['salary']);
			}

			$this->set('officials',$officials);
			$this->set('weekly_salaries',$total_weekly_salary);
		}
		*/
	}
	
	public function register(){

		if($this->request->is('post'))
		{
			$this->Captchacode->set($this->request->data);
			$this->Captchacode->setCaptcha($this->Captcha->getVerCode());

			if(!$this->Captchacode->validates())
			{
				$this->Session->setFlash('Wrong Captcha Input');
				$this->redirect('/profile/register');
			}
		}

		if(@$this->userData['register_completed']!=1){
			$this->loadModel('User');
		
			$this->set('INITIAL_BUDGET',Configure::read('INITIAL_BUDGET'));
			$user_fb = $this->Session->read('Userlogin.info');
			$this->set('user',$user_fb);
			$this->set('phone_empty',false);
			Cakelog::write('debug', 'profile.register user_fb:'.json_encode($user_fb));

			if($user_fb['fb_id']==null){
				$this->Session->setFlash('Mohon maaf, tidak berhasil login menggunakan akun facebook kamu. 
															Silahkan coba kembali beberapa saat lagi!');
								$this->redirect('/profile/error');
			}else{
				if($this->request->is('post') && $this->request->data['phone_number']!=null 
					&& strlen($this->request->data['name']) > 0 
					&& strlen($this->request->data['email']) > 0 
					&& strlen($this->request->data['password']) > 0
					&& strlen($this->request->data['password']) > 5
					&& $this->request->data['password'] == $this->request->data['password_repeat']){
					$this->request->data['hearffl'] = (isset($this->request->data['hearffl'])) ? $this->request->data['hearffl'] : 0;
					$this->request->data['daylyemail'] = (isset($this->request->data['daylyemail'])) ? $this->request->data['daylyemail'] : 0;
					$this->request->data['daylysms'] = (isset($this->request->data['daylysms'])) ? $this->request->data['daylysms'] : 0;
					$this->request->data['firstime'] = (isset($this->request->data['firstime'])) ? $this->request->data['firstime'] : 0;
					$birthdate = intval($this->request->data['bod_yr']).'-'.intval($this->request->data['bod_mt']).'-'.intval($this->request->data['bod_dt']);
					$secret		= md5(date("Ymdhis"));
					$passHasher = new PasswordHash(8, true);
					$user_pass 	= $passHasher->HashPassword($this->request->data['password'].$secret);
					$fb_id_ori = $user_fb['fb_id'];
					if(isset($this->request->data['not_facebook'])){
						$fb_id_ori = NULL;
					}

					$data = array('fb_id_ori'=>$fb_id_ori,
								  'fb_id'=>$user_fb['fb_id'],
								  'name'=>$this->request->data['name'],
								  'email'=>$this->request->data['email'],
								  'password'=>$user_pass,
								  'secret'=>$secret,
								  'location'=>$this->request->data['city'],
								  'phone_number'=>$this->request->data['phone_number'],
								  'register_date'=>date("Y-m-d H:i:s"),
								  'survey_about'=>$this->request->data['hearffl'],
								  'survey_daily_email'=>$this->request->data['daylyemail'],
								  'survey_daily_sms'=>$this->request->data['daylysms'],
								  'survey_has_play'=>$this->request->data['firstime'],
								  'faveclub'=>Sanitize::clean($this->request->data['faveclub']),
								  'birthdate'=>$birthdate,
								  'n_status'=>0,
								  'register_completed'=>0,
								  'activation_code' => date("Ymdhis").rand(100, 999)
								  );

					$this->Session->write('Userlogin.info',array('fb_id'=>$user_fb['fb_id'],
											'username'=>'',
											'name'=>$this->request->data['name'],
											'role'=>1,
											'access_token'=>$this->getAccessToken()));

					//make sure that the fb_id is unregistered
					$check = $this->User->findByFb_id($user_fb['fb_id']);
					//make sure that the email is not registered yet.
					$check2 = $this->User->findByEmail($this->request->data['email']);

					$user_data = @$check2['User'];
					if(isset($check['User']) && $check2['User']['register_completed'] != 0){
						Cakelog::write('error', 'Mohon maaf, akun kamu sudah terdaftar sebelumnya. !');

						$user_data = $check['User'];
						$this->Session->destroy();
						$this->Session->setFlash('Mohon maaf, akun kamu sudah terdaftar sebelumnya. !');
						//$this->redirect('/profile/error');
					}else if(isset($check2['User']) 
								&& $check2['User']['email'] == $this->request->data['email']
								&& $check2['User']['register_completed'] != 0){

						Cakelog::write('error', 'Mohon maaf, akun email ini `'.Sanitize::html($this->request->data['email']).'` 
										sudah terdaftar sebelumnya. Silahkan menggunakan alamat email yang lain !');

						$this->Session->setFlash('Mohon maaf, akun email ini `'.Sanitize::html($this->request->data['email']).'` 
							sudah terdaftar sebelumnya. Silahkan menggunakan alamat email yang lain !');
						//$this->redirect('/profile/error');
					}else{

						if(!isset($check2['User'])){
							$this->User->create();
							$rs = $this->User->save($data);
							$user_data = $rs['User'];
						}
						
						if(isset($rs['User']) || isset($check2['User'])){

							//register user into gameAPI.
							$response = $this->ProfileModel->setProfile($data);


							if($response['status']==1 || $check2['User']['register_completed'] == 0){
								//send info
								$msg = "@p1_".$user_data['id']." sudah terdaftar dalam fantasy football.";
								$this->Info->write('new player',$msg);

								if(@$rs['User']['n_status'] == 0 || $user_data['n_status'] == 0){
									
									if($this->send_mail($user_data))
									{
										$this->redirect("/profile/activation");
									}
									else
									{
										Cakelog::write('error', 'profile.register email error :'.json_encode($user_data));
										$this->Session->setFlash("Email yang loe masukan gak valid !");
										$this->redirect("/profile/send_activation");
									}
								}else{
									$this->redirect("/profile/register_team");
								}

								//$this->redirect("/profile/register_team");
							}else{
								$this->User->delete($this->User->id);
								$this->Session->setFlash('Mohon maaf, tidak berhasil mendaftarkan akun kamu. 
															Silahkan coba kembali beberapa saat lagi!');
								$this->redirect('/profile/error');
							}
						}
					}
				}else if($this->request->is('post') && $this->request->data['phone_number']==null){
					$this->Session->setFlash('Harap mengisi nomor mobile phone terlebih dahulu !');
					$this->set('phone_empty',true);
				}else if($this->request->is('post') && strlen($this->request->data['email']) == 0){
					$this->Session->setFlash('Harap mengisi email terlebih dahulu !');
					$this->set('email_empty',true);
				}else if($this->request->is('post') && strlen($this->request->data['password']) == 0){
					$this->Session->setFlash('Harap mengisi password terlebih dahulu !');
					$this->set('password',true);
				}else if($this->request->is('post') && strlen($this->request->data['password']) < 6){
					$this->Session->setFlash('Password harus 6 karakter atau lebih');
				}else if($this->request->is('post') 
					&& $this->request->data['password'] != $this->request->data['password_repeat']){
					$this->Session->setFlash('Maaf, Password tidak sama');
					$this->set('password_repeat',true);
				}else if($this->request->is('post') && strlen($this->request->data['name']) == 0){
					$this->Session->setFlash('Harap mengisi nama terlebih dahulu !');
				}
			}
			
		}else{
			$this->redirect('/manage/team');
		}
	}
	public function error(){
		if(isset($this->request->query['attempt'])){
			$this->set('attempt',1);
		}
		$this->render('error');
	}
	public function team_error(){
		$this->set('error_type','team');
		$this->render('error');
	}
	public function success(){

		$user = $this->User->findByFb_id($this->userData['fb_id']);

		$this->set('user',$user['User']);
		$this->render('success');
	}

	public function logout(){
		$this->Session->destroy();
		$this->redirect('/login/thankyou');
	}

	public function upload_image(){
		$this->layout = 'ajax';
		$_FILES['file']['name'] = str_replace(array(' ','\''),"_",$_FILES['file']['name']);
		if(move_uploaded_file($_FILES['file']['tmp_name'],
				Configure::read('avatar_img_dir').$_FILES['file']['name'])){
			//resize to 120x120 pixels
			$thumb = new Thumbnail();
			$thumb->resizeImage('resizeCrop', $_FILES['file']['name'], 
							Configure::read('avatar_img_dir'), 
							'120x120_'.$_FILES['file']['name'], 
							120, 
							120, 
							100);
			//save to db
			$data = array(
				'avatar_img'=>$_FILES['file']['name']
			);
			$userData = $this->getUserData();
			$this->loadModel('User');
			$user = $this->User->findByFb_id($userData['fb_id']);
			$this->User->id = $user['User']['id'];
			$rs = $this->User->save($data);
			print json_encode(array('status'=>1,'files'=>$_FILES['file']['name']));
		}else{
			print json_encode(array('status'=>0));
		}
		die();
	}

	public function send_activation()
	{
		$user_fb = $this->Session->read('Userlogin.info');

		$this->loadModel('User');

		$rs_user = $this->User->findByFb_id($user_fb['fb_id']);

		$this->set('user_data', $rs_user['User']);

		if($this->request->is("post"))
		{
			try{
				$this->Captchacode->set($this->request->data);
				$this->Captchacode->setCaptcha($this->Captcha->getVerCode());
				$trxsess = $this->Session->read('trxsess_'.$this->request->data['trxsess']);
				if(!$this->Captchacode->validates())
				{
					throw new Exception("Error Captcha Input");
				}
				
				$data['email'] = trim(Sanitize::clean($this->request->data['email']));

				$this->User->id = $rs_user['User']['id'];
				$rs = $this->User->save($data);
				$rs_user = $this->User->findByFb_id($user_fb['fb_id']);

				if($trxsess != 1){
					if(!$this->send_mail($rs_user['User'])){
						Cakelog::write('error', 'profile.send_activation email error :'.json_encode($rs_user['User']));
						$this->Session->setFlash("Email yang loe masukan gak valid !");
						$this->redirect("/profile/send_activation");
					}
				}

				$this->Session->write('trxsess_'.$this->request->data['trxsess'],1);

				$this->set('user_data', $rs_user['User']);
				$this->render('activation');

			}catch(Exception $e){
				Cakelog::write('error', 
						'profile.send_activation message : '.$e->getMessage().' data :'.json_encode($user_fb));
				$this->Session->setFlash("Terjadi Kesalahan Silahkan Coba Lagi");
			}
		}
	}

	public function forgot_password()
	{
		if($this->request->is("post"))
		{
			try{
				$this->loadModel("User");
				$this->Captchacode->set($this->request->data);
				$this->Captchacode->setCaptcha($this->Captcha->getVerCode());
				$trxsess = $this->Session->read('trxsess_'.$this->request->data['trxsess']);
				if(!$this->Captchacode->validates())
				{
					throw new Exception("Error Captcha Input");
				}
				
				$email = trim(Sanitize::clean($this->request->data['email']));

				$rs_user = $this->User->findByEmail($email);

				if(count($rs_user) == 0)
				{
					throw new Exception("Akun Tersebut Belum Terdaftar");
				}

				//generate link activation
				$data_user = array(
								'fb_id' => $rs_user['User']['fb_id'],
								'email' => $rs_user['User']['email'],
								'secret' => $rs_user['User']['secret']
							);

				$params = encrypt_param(serialize($data_user));
				$url = 'http://'.Configure::read('DOMAIN').'/profile/reset_password/?params='.$params;

				$data = array(
							'email' => $rs_user['User']['email'],
							'url' => $url
							);

				if($trxsess != 1){
					if(!$this->send_reset_password($data))
					{
						$this->redirect("email_not_valid");
					}
				}

				$this->Session->write('trxsess_'.$this->request->data['trxsess'],1);

				$this->set('user_data', $rs_user['User']);
				$this->render('notice_resetpassword');

			}catch(Exception $e){
				Cakelog::write('error', 
						'login.forgot_password message : '.$e->getMessage().' data :');
				$this->Session->setFlash($e->getMessage());
			}
		}
	}

	public function reset_password()
	{
		$params = @$this->request->query['params'];
		$this->set('trxcode', $params);

		if($this->request->is("post"))
		{
			try{
				$params = $this->request->data['trxcode'];
				$this->set('trxcode', $params);

				$data = unserialize(decrypt_param($params));
				$password = $this->request->data['password'];
				$password_repeat = $this->request->data['password_repeat'];

				if(strlen($password) > 5 && $password == $password_repeat)
				{
					$this->loadModel('User');
					$passHasher = new PasswordHash(8, true);
					$user_pass = $passHasher->HashPassword($password.$data['secret']);
					$this->User->query("UPDATE users SET password='{$user_pass}' WHERE email='{$data['email']}'");
					$this->render('reset_password_success');
				}
				else
				{
					throw new Exception("Terjadi Kesalahan, Silahkan Coba Kembali");
					
				}
			}catch(Exception $e){
				$this->Session->setFlash($e->getMessage());
			}
		}
	}

	public function send_reset_password($data)
	{
		$view = new View($this, false);

		if(isset($this->request->data_request))
		{
			$data = $this->request->data_request;
		}

		$body = $view->element('email_reset_password',array(
										'url'=> $data['url'],
									));

		$Email = new CakeEmail('smtp');
		$mail = new PHPMailer;

		$mail->isSMTP();
		$mail->Host = $Email->config()['host'];
		$mail->SMTPAuth = true;
		$mail->Username = $Email->config()['username'];
		$mail->Password = $Email->config()['password'];
		$mail->SMTPSecure = ($Email->config()['tls']) ? 'tls' : '';
		$mail->Port = $Email->config()['port'];                     

		$mail->From = $Email->config()['from'];
		$mail->addAddress(trim($data['email']));            
		$mail->isHTML(true);

		$mail->Subject = 'Reset Password';
		$mail->Body    = $body;

		if($mail->send())
		{
			return true;
		}

		return false;
	}

	public function send_mail($data = array()){
		$view = new View($this, false);

		if(isset($this->request->data_request))
		{
			$data = $this->request->data_request;
		}

		$body = $view->element('email_activation',array(
										'activation_code'=> $data['activation_code'],
									));

		$Email = new CakeEmail('smtp');
		
		$mail = new PHPMailer;

		$mail->isSMTP();
		$mail->Host = $Email->config()['host'];
		$mail->SMTPAuth = true;
		$mail->Username = $Email->config()['username'];
		$mail->Password = $Email->config()['password'];
		$mail->SMTPSecure = ($Email->config()['tls']) ? 'tls' : '';
		$mail->Port = $Email->config()['port'];                     

		$mail->From = $Email->config()['from'];
		$mail->addAddress(trim($data['email']));            
		$mail->isHTML(true);

		$mail->Subject = 'Kode Aktivasi';
		$mail->Body    = $body;

		if($mail->send())
		{
			return true;
		}

		return false;
	}

	public function email_not_valid()
	{
		
	}

	public function send_link_activation($data = array())
	{
		$view = new View($this, false);

		if(isset($this->request->data_request))
		{
			$data = $this->request->data_request;
		}

		$body = $view->element('email_link_activation',array(
										'url'=> $data['url'],
									));

		$Email = new CakeEmail('smtp');

		$mail = new PHPMailer;

		$mail->isSMTP();
		$mail->Host = $Email->config()['host'];
		$mail->SMTPAuth = true;
		$mail->Username = $Email->config()['username'];
		$mail->Password = $Email->config()['password'];
		$mail->SMTPSecure = ($Email->config()['tls']) ? 'tls' : '';
		$mail->Port = $Email->config()['port'];                     

		$mail->From = $Email->config()['from'];
		$mail->addAddress(trim($data['email']));            
		$mail->isHTML(true);

		$mail->Subject = 'Kode Aktivasi';
		$mail->Body    = $body;

		if($mail->send())
		{
			return true;
		}

		return false;
	}

}