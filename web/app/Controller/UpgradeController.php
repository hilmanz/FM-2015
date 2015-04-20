<?php
require_once APP . 'Vendor' . DS. 'lib/Predis/Autoloader.php';
App::uses('AppController', 'Controller');
App::uses('Sanitize', 'Utility');


class UpgradeController extends AppController {

	/**
	 * Controller name
	 *
	 * @var string
	 */

	public $name = 'Upgrade';
	public $charge = 10000;
	protected $redisClient;

	public function beforeFilter()
	{
		parent::beforeFilter();
		$this->loadModel('Game');
		$this->loadModel('User');
		$this->loadModel('MembershipTransactions');
		$this->loadModel('Team');
		
		if(!isset($this->userDetail['User'])){
			$this->redirect('/login/expired');
		}
		if(!$this->hasTeam()
			&&$this->request->params['action']!='plan'
			&&$this->request->params['action']!='pay'
			&&$this->request->params['action']!='payment_success'
			&&$this->request->params['action']!='payment_error'
			&&$this->request->params['action']!='payment_pending'
			&&$this->request->params['action']!='member_success'){
			$this->redirect('/login/expired');
		}

		Predis\Autoloader::register();
		$this->redisClient = new Predis\Client(array(
											    'host'     => Configure::read('REDIS.Host'),
											    'port'     => Configure::read('REDIS.Port'),
											    'database' => Configure::read('REDIS.Database')
											));
	}

	public function hasTeam(){
		$userData = $this->userData;
		if(is_array($userData['team'])){
			return true;
		}
	}
	public function pay(){
		if($this->request->data['payment_method']=='bank_transfer' || 
			$this->request->data['payment_method']=='kartu_kredit')
		{
			$data = $this->pay_with_doku();
			$doku_api = Configure::read('DOKU_API');
			$this->set('doku_api', $doku_api);
			$this->set('params',$data);
			$this->render('doku');
		}
		else
		{
			$this->pay_with_ecash();
			$this->render('ecash');
		}
	}
	
	public function index()
	{
		$this->redirect('/profile');
	}

	public function plan($choice=''){
		
		if($choice!=''){
			if($choice=='free'){
				$this->subscribe_free();
			}else{
				$this->subscribe($choice);
			}
			
		}else{
			$this->render('plan');
		}
	}
	private function subscribe_free(){
		$userData = $this->userData;
		$this->User->query("UPDATE users SET paid_member=0,paid_member_status=0,paid_plan='free' 
										WHERE fb_id='{$userData['fb_id']}' AND paid_member='-1'");
		$this->redirect('/profile/register_team');
	}
	private function subscribe($plan){
		$settings = Configure::read('SUBSCRIPTION_PLAN');
		$enable_ecash = Configure::read('ENABLE_ECASH');
		$this->set('enable_ecash', $enable_ecash);
		
		$po_number = $this->userDetail['User']['id'].'-'.date("YmdHis").'-99'.rand(10,99);
		$plan_setting = $settings[$plan];
		$this->set('plan_setting',$plan_setting);
		$this->set('plan',$plan);
		$this->set('po_number',$po_number);
		$this->set('trxinfo',encrypt_param(serialize(array(
			'user_id'=>$this->userDetail['User']['id'],
			'po_number'=>$po_number,
			'new_membership'=>1,
			'plan'=>$plan,
			'price'=>$plan_setting['price']+$plan_setting['admin_fee'],
			'name'=>$plan_setting['name']
		))));
		$this->render('subscribe');
	}
	public function member()
	{

		$userData = $this->userData;
		
		$this->set('plan',$this->userDetail['User']['paid_plan']);
		$rs_user = $this->User->findByFb_id($userData['fb_id']);
		$transaction_id = intval($rs_user['User']['id']).'-'.date("YmdHis").'-'.rand(0,999);
		$description = 'Purchase Order #'.$transaction_id;
		Cakelog::write('debug', json_encode($rs_user));
		$amount = 0;

		$can_upgrade = true;
		if($rs_user['User']['paid_member'] == 1 && $rs_user['User']['paid_member_status'] == 1)
		{
			$can_upgrade = false;
		}
		else if($rs_user['User']['paid_member'] == 1 && $rs_user['User']['paid_member_status'] == 0)
		{
			$can_upgrade = true;
		}

		if($can_upgrade)
		{
			$this->plan();
		}
		else
		{
			$this->redirect('/profile');
		}
	}

	public function member_success()
	{

		$id = $this->request->query['id'];

		$rs = $this->Game->EcashValidate($id);

		if(isset($rs['data']) && $rs['data'] != '')
		{
			$data = explode(',', $rs['data']);
			if(isset($data[4]) && trim($data[4]) == "SUCCESS")
			{
				$userData = $this->userData;
				$transaction_name = 'Purchase Order #'.$data[3];
				$detail = json_encode($rs['data']);

				try{
					$amount = 0;
					$dataSource = $this->MembershipTransactions->getDataSource();
					$dataSource->begin();

					$redis_content = unserialize($this->redisClient->get($data[3]));
					$trxinfo = $redis_content['trxinfo'];
					$rs_user = $this->User->findByFb_id($redis_content['fb_id']);
					$url_mobile_notif = Configure::read('URL_MOBILE_NOTIF').'fm_payment_notification';
					$save_data= array(
									'fb_id' => $redis_content['fb_id'],
									'transaction_dt' => date("Y-m-d H:i:s"),
									'po_number' => $data[3],
									'transaction_name' => $transaction_name,
									'transaction_type' => 'SUBSCRIPTION',
									'amount' => $trxinfo['price'],
									'details' => $detail,
									'payment_method' => 'ecash',
									'league' => '',
									'n_status' => 1
								);

					$this->MembershipTransactions->save($save_data);
					

					$this->MembershipTransactions->query("INSERT INTO member_billings
												(fb_id,log_dt,expire)
												VALUES('{$userData['fb_id']}',
														NOW(), NOW() + INTERVAL 1 MONTH) ON DUPLICATE KEY 
													UPDATE log_dt = NOW(), 
													expire=NOW() + INTERVAL 1 MONTH,
													is_sevendays_notif=0,is_threedays_notif=0");

					$this->User->query("UPDATE users SET paid_member=1, paid_member_status=1,
										paid_plan='{$trxinfo['plan']}' 
										WHERE fb_id='{$redis_content['fb_id']}'");

					$game_team_id_epl = $this->get_game_team_id($redis_content['fb_id'], 'epl');
					$game_team_id_ita = $this->get_game_team_id($redis_content['fb_id'], 'ita');

					if($trxinfo['plan']=='pro2'){
						$this->MembershipTransactions->query("
											INSERT IGNORE INTO game_transactions
											(fb_id,transaction_dt,transaction_name,amount,details)
											VALUES('{$redis_content['fb_id']}',NOW(),'PRO_BONUS',7000,'PRO_BONUS')");
						
						$this->MembershipTransactions->query("INSERT INTO game_team_cash
																(fb_id,cash)
																SELECT fb_id,SUM(amount) AS cash 
																FROM game_transactions
																WHERE fb_id = '{$redis_content['fb_id']}'
																GROUP BY fb_id
																ON DUPLICATE KEY UPDATE
																cash = VALUES(cash);");

						//give ss$50000000
						if($game_team_id_epl != NULL){
							$this->Game->addTeamExpendituresByLeague(
													intval($game_team_id_epl),
													'PRO_LEAGUE_2',
													1,
													50000000,
													'',
													0,
													1,
													1,
													'epl');
						}

						if($game_team_id_ita != NULL){
							$this->Game->addTeamExpendituresByLeague(
													intval($game_team_id_ita),
													'PRO_LEAGUE_2',
													1,
													50000000,
													'',
													0,
													1,
													1,
													'ita');
						}

						$user_data = array(
											'fb_id' => $redis_content['fb_id'], 
											'trx_type' => 'PRO_LEAGUE_2',
											'user_id' => $rs_user['User']['id']
										);

						$result_mobile = curlPost($url_mobile_notif, $user_data);
						$result_mobile = json_decode($result_mobile, TRUE);
					}else if($trxinfo['plan']=='pro1'){

						//give ss$15000000
						if($game_team_id_epl != NULL){
							$this->Game->addTeamExpendituresByLeague(
													intval($game_team_id_epl),
													'PRO_LEAGUE',
													1,
													15000000,
													'',
													0,
													1,
													1,
													'epl');
						}

						if($game_team_id_ita != NULL){
							$this->Game->addTeamExpendituresByLeague(
													intval($game_team_id_ita),
													'PRO_LEAGUE',
													1,
													15000000,
													'',
													0,
													1,
													1,
													'ita');
						}

						$user_data = array(
											'fb_id' => $redis_content['fb_id'],
											'trx_type' => 'PRO_LEAGUE',
											'user_id' => $rs_user['User']['id']
											);

						$result_mobile = curlPost($url_mobile_notif, $user_data);
						$result_mobile = json_decode($result_mobile, TRUE);
					}

					$this->redisClient->del($data[3]);
					$dataSource->commit();
				}catch(Exception $e){
					$dataSource->rollback();
					Cakelog::write('error', 'Upgrade.member_success 
						id='.$id.' data:'.json_encode($data).' message:'.$e->getMessage());
					$this->render('error');
				}
			}
			else
			{
				Cakelog::write('error', 'Upgrade.member_success id='.$id.' data:'.json_encode($data));
				$this->render('error');
			}
		}
		else
		{
			Cakelog::write('error', 'Upgrade.member_success '.$id.'Not Found');
			$this->render('error');
		}
	}

	private function  get_game_team_id($fb_id, $league='epl')
	{
		$this->loadModel('Game');
		$game_team_id = $this->Game->query("SELECT b.id FROM ffgame.game_users a 
											INNER JOIN ffgame.game_teams b 
											ON a.id = b.user_id WHERE a.fb_id=".$fb_id);
		if($league == 'ita')
		{
			$game_team_id = $this->Game->query("SELECT b.id FROM ffgame_ita.game_users a 
											INNER JOIN ffgame_ita.game_teams b 
											ON a.id = b.user_id WHERE a.fb_id=".$fb_id);
		}

		return @$game_team_id[0]['b']['id'];
	}


	public function paymonthly()
	{
		$this->plan();
	}

	public function renewal()
	{
		
	}

	private function epl_charge($fb_id)
	{
		//sementara di hardcode
		$epl_interval = $this->Game->query("SELECT 
									PERIOD_DIFF(DATE_FORMAT(CURDATE() + INTERVAL 1 MONTH, '%Y%m'), 
									DATE_FORMAT(register_date, '%Y%m')) AS bulan
									FROM ffgame.game_users
									WHERE fb_id='{$fb_id}'
									LIMIT 1");

		return intval(@$epl_interval[0][0]['bulan'])*$this->charge;
	}

	private function ita_charge($fb_id)
	{
		//sementara di hardcode
		$ita_interval = $this->Game->query("SELECT 
									PERIOD_DIFF(DATE_FORMAT(CURDATE() + INTERVAL 1 MONTH, '%Y%m'), 
									DATE_FORMAT(register_date, '%Y%m')) AS bulan
									FROM ffgame_ita.game_users
									WHERE fb_id='{$fb_id}'
									LIMIT 1");

		return intval(@$ita_interval[0][0]['bulan'])*$this->charge;
	}

	private function bill_interval($fb_id, $league)
	{
		$bill_interval = $this->MembershipTransactions->query("SELECT 
											PERIOD_DIFF(DATE_FORMAT(CURDATE(), '%Y%m'), 
											DATE_FORMAT(transaction_dt, '%Y%m')) AS bulan
											FROM membership_transactions
											WHERE fb_id='{$fb_id}'
											AND transaction_type IN ('UPGRADE MEMBER', 'RENEWAL MEMBER')
											AND league = '{$league}'
											ORDER BY transaction_dt DESC
											LIMIT 1");

		return intval(@$bill_interval[0][0]['bulan']);
	}

	private function checkTotalTeam()
	{
		$userData 	= $this->userData;
		$rs_user 	= $this->User->findByFb_id($userData['fb_id']);
		$total_team = $this->Team->find('count', array(
	        'conditions' => array('Team.user_id' => $rs_user['User']['id'])
	    ));

	    return $total_team;
	}

	private function checkRegisterGameUser($fb_id, $league)
	{
		if($league == 'ita')
		{
			$date = $this->MembershipTransactions->query("SELECT 
									DATE_FORMAT(register_date, '%d-%m-%Y') AS tanggal, 
									PERIOD_DIFF(DATE_FORMAT(CURDATE() + INTERVAL 1 MONTH, '%Y%m'), 
									DATE_FORMAT(register_date, '%Y%m')) AS period
									FROM ffgame_ita.game_users
									WHERE fb_id='{$fb_id}'
									LIMIT 1");
		}
		else
		{
			$date = $this->MembershipTransactions->query("SELECT 
									DATE_FORMAT(register_date, '%d-%m-%Y') AS tanggal,
									PERIOD_DIFF(DATE_FORMAT(CURDATE() + INTERVAL 1 MONTH, '%Y%m'), 
									DATE_FORMAT(register_date, '%Y%m')) AS period
									FROM ffgame.game_users
									WHERE fb_id='{$fb_id}'
									LIMIT 1");
		}
		return $date;
	}
	private function pay_with_doku(){
		
		$this->loadModel('Doku');

		$po_number = $this->request->data['po_number'];
		$trxinfo = unserialize(decrypt_param($this->request->data['trxinfo']));

		$result = array('is_transaction_ok'=>false,
						'no_fund'=>false);

		$payment_channel = '01';
		if($this->request->data['payment_method'] == "bank_transfer")
		{
			$payment_channel = '05';
		}


		$transaction_id = $po_number;
		
		$this->set('transaction_id',$transaction_id);

		$description = 'MEMBERSHIP  #'.$transaction_id;
		$user_login = $this->Session->read('Userlogin');
		$fb_id = $user_login['info']['fb_id'];

		//get total money to be spent.
		$total_price = $trxinfo['price'];
		
		$basket = "Subscription ".$trxinfo['name'].",".$trxinfo['price'].",1,".$trxinfo['price'].";";


		$doku_mid = Configure::read('DOKU_MALLID');
		$doku_sharedkey = Configure::read('DOKU_SHAREDKEY');
		$hash_words = sha1(number_format($trxinfo['price'],2,'.','').
						  $doku_mid.
						  $doku_sharedkey.
						  str_replace('-', '', $transaction_id));


		$trx_session_id = sha1(time());
		
		$name_chunk = explode(" ",$this->userDetail['User']['name']);
		$first_name = array_shift($name_chunk);
		$last_name = implode(" ",$name_chunk);
		
		$additionaldata = 'fm-subscribe';
		$data = array('MALLID'=>$doku_mid,
						'CHAINMERCHANT'=>'NA',
						'AMOUNT'=>number_format($total_price,2,'.',''),
						'PURCHASEAMOUNT'=>number_format($total_price,2,'.',''),
						'TRANSIDMERCHANT'=>str_replace('-', '', $transaction_id),
						'WORDS'=>$hash_words,
						'REQUESTDATETIME'=>date("YmdHis"),
						'CURRENCY'=>'360',
						'PURCHASECURRENCY'=>'360',
						'SESSIONID'=>$trx_session_id,
						'NAME'=>Sanitize::clean($first_name).' '.Sanitize::clean($last_name),
						'EMAIL'=>$this->userDetail['User']['email'],
						'ADDITIONALDATA'=>$additionaldata,
						'PAYMENTCHANNEL'=>$payment_channel,
						'BASKET'=>$basket
						);
		

			$rs_order = $this->MembershipTransactions->findByPo_number($transaction_id);
			

			$payment_method = 'cc';
			if($payment_channel != '01'){
				$payment_method = 'va';
			}
			if(count($rs_order) == 0){
				
			

				$userData = $this->userData;

				$transaction_name = 'Purchase Order #'.$transaction_id;
				$detail = json_encode($data);

				try{
					$amount = 0;
					$dataSource = $this->MembershipTransactions->getDataSource();
					$dataSource->begin();
					
					$save_data = array(
										'fb_id' => $userData['fb_id'],
										'transaction_dt' => date("Y-m-d H:i:s"),
										'transaction_name' => $transaction_name,
										'po_number' => $transaction_id,
										'transaction_type' => 'SUBSCRIPTION',
										'amount' => $total_price,
										'payment_method' => $payment_method,
										'details' => $detail,
										'league' => $_SESSION['league'],
										'n_status'=>0
									);

					
					$this->MembershipTransactions->save($save_data);
					
					/*
					$this->MembershipTransactions->query("INSERT INTO member_billings
												(fb_id,log_dt,expire)
												VALUES('{$userData['fb_id']}',
														NOW(), NOW() + INTERVAL 1 MONTH)");
					*/
					/*$this->User->query("UPDATE users SET paid_member=1,paid_member_status=1
										WHERE fb_id='{$userData['fb_id']}'");
										*/
								
					$this->User->query("UPDATE users SET paid_plan='{$trxinfo['plan']}'
										WHERE fb_id='{$userData['fb_id']}'");

					$dataSource->commit();

				}catch(Exception $e){
					$dataSource->rollback();
					Cakelog::write('error', 'Upgrade.pay_with_doku 
						id='.$id.' data:'.json_encode($data).' message:'.$e->getMessage());
					$this->render('error');
				}
				

				try{
					$this->Doku->create();
					$rs_doku = $this->Doku->save(array(
							'catalog_order_id'=>0,
							'po_number'=>$transaction_id,
							'transidmerchant'=>str_replace('-', '', $transaction_id),
							'totalamount'=>number_format($total_price,2,'.',''),
							'words'=>$hash_words,
							'statustype'=>'',
							'response_code'=>'',
							'approvalcode'=>'',
							'trxstatus'=>'Requested',
							'payment_channel'=>$payment_channel,
							'paymentcode'=>'',
							'session_id'=>$trx_session_id,
							'bank_issuer'=>'',
							'creditcard'=>'',
							'payment_date_time'=>date("Y-m-d H:i:s"),
							'verifyid'=>'',
							'verifyscore'=>'',
							'verifystatus'=>'',
							'additionaldata'=>$additionaldata
					));
					CakeLog::write('doku_pro',date("Y-m-d H:i:s").' - [request] create doku entry '.json_encode($rs_doku));
					$this->set('order_doku',$data);
					return $data;
				}catch(Exception $e){
					CakeLog::write('doku_pro',date("Y-m-d H:i:s").' - [request] failed doku entry '.json_encode(@$rs_doku).' '.$e->getMessage());
				}
			}else{
				//transaction exist, so we use the old po
				if($rs_order['MembershipTransactions']['n_status']==0){
					$rs_doku = $this->Doku->findByPo_number($transaction_id);

					if($rs_doku['Doku']['po_number']==$transaction_id){
						if(strlen($rs_doku['Doku']['session_id']) > 0) {
							$data['SESSIONID'] = $rs_doku['Doku']['session_id'];
						}
						$this->set('order_doku',$data);
						return $data;
					}
				}
			}

		

	}

	private function pay_with_ecash(){
		$userData = $this->userData;

		$transaction_id = $this->request->data['po_number'];
		$trxinfo = unserialize(decrypt_param($this->request->data['trxinfo']));

		$data_redis = array(
								'fb_id' => $userData['fb_id'],
								'trxinfo' => $trxinfo,
								'transaction_id' => $transaction_id
							);

		$this->redisClient->set($transaction_id, serialize($data_redis));
		$this->redisClient->expire($transaction_id, 24*60*60);//expires in 1 day

		$rs = $this->Game->getEcashUrl(array(
			'transaction_id'=>$transaction_id,
			'amount'=>$trxinfo['price'],
			'clientIpAddress'=>$this->request->clientIp(),
			'description'=>$trxinfo['name'],
			'source'=>'FMUPGRADE'
		));

		$this->set('transaction_id',$transaction_id);
		$this->set('ecash_url',$rs['data']);
	}

	public function payment_success(){
		
	}
	public function payment_error(){
				
	}
	public function payment_pending(){
		
		$paymentcode = $this->request->query['trx'];
		
		$this->set('paymentcode',decrypt_param($paymentcode));
	}
}