<?php
/**
 * Application level Controller
 *
 * This file is application-wide controller file. You can put all
 * application-wide controller-related methods here.
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

require_once APP . 'Vendor' . DS. 'lib/Predis/Autoloader.php';
App::uses('AppController', 'Controller');
App::uses('Sanitize', 'Utility');
//require_once APP.DS.'Vendor'.DS.'common.php';

/**
 * Payment Controller
 *
 *
 * @package		payment.Controller
 */
class PaymentController extends AppController {
	public $name = 'Payment';
	protected $redisClient;

	public function beforeFilter()
	{
		parent::beforeFilter();
		$this->loadModel('Game');
		$this->loadModel('User');
		$this->loadModel('MembershipTransactions');
		$this->loadModel('MerchandiseOrder');

		Predis\Autoloader::register();
		$this->redisClient = new Predis\Client(array(
											    'host'     => Configure::read('REDIS.Host'),
											    'port'     => Configure::read('REDIS.Port'),
											    'database' => Configure::read('REDIS.Database')
											));

	}

	//index()
	// 1. check two param fb_id & trx_type
	// 2. save to session fb_id, transaction_id, transaction_type
	// 3. get ecash url then redirect to payment mandiri ecash page
	// 4. check trx_type in charge
	// 5. check if user has purchase before
	public function index()
	{
		$charge = Configure::read('MOBILE_CHARGE');
		$url_scheme = Configure::read('URL_SCHEME');

		if($this->request->query('fb_id') == NULL && $this->request->query('trx_type') == NULL)
		{
			$this->redirect($url_scheme.'fmpayment?status=0&message=Terjadi Kesalahan !');
		}

		$fb_id = trim($this->request->query('fb_id'));
		$trx_type = strtoupper($this->request->query('trx_type'));

		$is_payment = $this->isPayment($fb_id, 'UNLOCK '.$trx_type);

		if($is_payment)
		{
			$this->redirect($url_scheme.'fmpayment?status=0&message=Already, owned !');
		}

		if(array_key_exists($trx_type, $charge))
		{
			$amount = $charge[$trx_type];

			$rs_user = $this->User->findByFb_id($fb_id);
			
			//todo
			//if rs_user empty handle this
			$transaction_id = intval($rs_user['User']['id']).'-'.date("YmdHis").'-'.rand(0,999);
			$description = 'Purchase Order #'.$transaction_id;

			$data_redis = array(
								'fb_id' => $fb_id,
								'payment_type' => $trx_type,
								'transaction_id' => $transaction_id
							);

			$this->redisClient->set($transaction_id, serialize($data_redis));
			$this->redisClient->expire($transaction_id, 24*60*60);//expires in 1 day

			$rs = $this->Game->getEcashUrl(array(
				'transaction_id'=>$transaction_id,
				'description'=>$description,
				'amount'=>$amount,
				'clientIpAddress'=>$this->request->clientIp(),
				'source'=>'FMPAYMENTMOBILE'
			));
			Cakelog::write('debug', 'payment.index rs '.json_encode($rs));

			if($rs['status'] == 1 && $rs['data'] != '')
			{
				$this->redirect($rs['data']);
			}
			else
			{
				$this->redirect($url_scheme.'fmpayment?status=0&message=Saat ini tidak bisa terhubung dengan mandiri ecash, Silahkan coba beberapa saat lagi');
			}
		}
		else
		{
			$this->redirect($url_scheme.'fmpayment?status=0&message=Terjadi Kesalahan !');
		}
		//$this->set('rs', $rs);
	}

	//success()
	// 1. check session
	// 2. compare transaction_id from index method to success method
	// 3. hit url mobile notif
	public function success()
	{
		$charge = Configure::read('MOBILE_CHARGE');
		$url_scheme = Configure::read('URL_SCHEME');
		$url_mobile_notif = Configure::read('URL_MOBILE_NOTIF').'fm_payment_notification';

		$id = $this->request->query['id'];

		$rs = $this->Game->EcashValidate($id);

		$data = explode(',', $rs['data']);
		$redis_content = unserialize($this->redisClient->get($data[3]));

		$fb_id = $redis_content['fb_id'];
		$trx_type = $redis_content['payment_type'];
		$transaction_id = $redis_content['transaction_id'];
		

		$is_payment = $this->isPayment($fb_id, 'UNLOCK '.$trx_type);

		if($is_payment){
			$this->redirect($url_scheme.'fmpayment?status=1&message=Success, Already owned !');
		}

		if(isset($rs['data']) && $rs['data'] != '')
		{
			if(isset($data[4]) && trim($data[4]) == "SUCCESS")
			{
				try{

					$array_session = array('fb_id' => $fb_id, 'trx_type' => $trx_type);

					$transaction_name = 'Purchase Order #'.$data[3];
					$transaction_type = 'UNLOCK '.$trx_type;
					$amount = $charge[$trx_type];
					$detail = json_encode($rs['data']);

					//hit url mobile notification
					$result_mobile = curlPost($url_mobile_notif,$array_session);
					$result_mobile = json_decode($result_mobile, TRUE);

					if($result_mobile['code'] == 1){
						Cakelog::write('debug', 
						'Payment.success result_mobile:'.json_encode($result_mobile).
						' data:'.json_encode($rs).' fb_id:'.$fb_id);
					}else{
						Cakelog::write('error', 
						'Payment.success result_mobile:'.json_encode($result_mobile).
						' data:'.json_encode($rs).' fb_id:'.$fb_id);
					}

					$save_data = array(
									'fb_id' => $fb_id,
									'transaction_dt' => date("Y-m-d H:i:s"),
									'transaction_name' => $transaction_name,
									'transaction_type' => $transaction_type,
									'amount' => $amount,
									'details' => $detail,
									'league' => 'epl'
								);

					$this->MembershipTransactions->save($save_data);

					$this->redisClient->del($data[3]);
					$this->redirect($url_scheme.'fmpayment?status=1&message=success');

				}catch(Exception $e){
					Cakelog::write('error', 'Payment.success 
						id='.$id.' data:'.json_encode($data).' message:'.$e->getMessage());
					$this->redisClient->del($data[3]);
					$this->redirect($url_scheme.'fmpayment?status=0&message=error');
				}
			}
			else
			{
				$this->redirect($url_scheme.'fmpayment?status=0&message=error');
			}
		}
		else
		{
			Cakelog::write('error', 'Payment.success '.$id.' Not Found');
			$this->redirect($url_scheme.'fmpayment?status=0&message=Saat ini tidak bisa terhubung dengan mandiri ecash, Silahkan coba beberapa saat lagi');
		}

	}

	//doku fm payment like trivia, unlock catalog 123, ongkir payment
	public function doku()
	{
		$this->loadModel('Doku');
		$charge = Configure::read('MOBILE_CHARGE');
		$url_scheme = Configure::read('URL_SCHEME');

		if($this->request->query('fb_id') == NULL 
			&& $this->request->query('trx_type') == NULL
			&& $this->request->query('payment_method') == NULL)
		{
			$this->redirect($url_scheme.'fmpayment?status=0&message=Terjadi Kesalahan !');
		}

		$fb_id = trim($this->request->query('fb_id'));
		$trx_type = strtoupper($this->request->query('trx_type'));
		$payment_method = trim($this->request->query('payment_method'));

		$is_payment = $this->isPayment($fb_id, 'UNLOCK '.$trx_type);

		if($is_payment)
		{
			$this->redirect($url_scheme.'fmpayment?status=1&message=Already, owned !');
		}

		if(array_key_exists($trx_type, $charge))
		{
			$amount = $charge[$trx_type];

			$rs_user = $this->User->findByFb_id($fb_id);

			$payment_channel = '01';
			if($payment_method == "va")
			{
				$payment_channel = '05';
			}


			//todo
			//if rs_user empty handle this
			$transaction_id = intval($rs_user['User']['id']).'-'.rand(0,999);
			$transaction_id_merchant = str_replace('-', '', $transaction_id);

			$doku_api = Configure::read('DOKU_API');
			$doku_mid = Configure::read('DOKU_MALLID');
			$doku_sharedkey = Configure::read('DOKU_SHAREDKEY');
			$hash_words = sha1(number_format($amount,2,'.','').
							  $doku_mid.
							  $doku_sharedkey.
							  $transaction_id_merchant);
			$trx_session_id = sha1(time());
			$additionaldata = 'app-purchase';
			$basket = 'UNLOCK '.$trx_type.','.$amount.','.$amount.','.$amount.';';

			$doku_param = array('MALLID'=>$doku_mid,
								'CHAINMERCHANT'=>'NA',
								'AMOUNT'=>number_format($amount,2,'.',''),
								'PURCHASEAMOUNT'=>number_format($amount,2,'.',''),
								'TRANSIDMERCHANT'=>$transaction_id_merchant,
								'WORDS'=>$hash_words,
								'REQUESTDATETIME'=>date("YmdHis"),
								'CURRENCY'=>'360',
								'PURCHASECURRENCY'=>'360',
								'SESSIONID'=>$trx_session_id,
								'NAME'=>$rs_user['User']['name'],
								'EMAIL'=>$rs_user['User']['email'],
								'ADDITIONALDATA'=>$additionaldata,
								'PAYMENTCHANNEL'=>$payment_channel,
								'BASKET'=>$basket,
							);

			$doku_data = array(
							'catalog_order_id'=>NULL,
							'po_number'=>$transaction_id,
							'transidmerchant'=>$transaction_id_merchant,
							'totalamount'=>number_format($amount,2,'.',''),
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
					);

			//save to Doku table
			$this->Doku->create();
			$rs_doku = $this->Doku->save($doku_data);

			$data_redis = array('doku_data' => $doku_data,
								'doku_param' => $doku_param,
								'fb_id' => $fb_id,
								'trx_type' => $trx_type,
								'amount' => $amount
								);



			$this->redisClient->set($transaction_id_merchant, serialize($data_redis));

			$this->redisClient->expire($transaction_id_merchant, 24*60*60);//expires in 1 day
			if($payment_channel == '05')
			{
				$this->redisClient->expire($transaction_id_merchant, 6*60*60);//expires in 6 hours
			}
			
			Cakelog::write('debug', 'payment.doku data_redis '.json_encode($data_redis));

			$this->set('doku_param', $doku_param);
			$this->set('doku_api', $doku_api);
			$this->set('basket', 'UNLOCK '.$trx_type);
		}
		else
		{
			$this->redirect($url_scheme.'fmpayment?status=0&message=Terjadi Kesalahan !');
		}
	}

	public function proleague()
	{
		$this->loadModel('Doku');
		

		if($this->request->query('fb_id') == NULL 
			|| $this->request->query('trx_type') == NULL)
		{
			$this->redirect($url_scheme.'fmpayment?status=0&message=Terjadi Kesalahan !');
		}

		if($this->request->query('payment_method') != NULL)
		{
			$this->pay_with_doku();
		}
		else
		{
			$this->pay_with_ecash();
		}
	}

	private function pay_with_doku()
	{
		$charge = Configure::read('MOBILE_CHARGE');
		$url_scheme = Configure::read('URL_SCHEME');

		$fb_id = trim($this->request->query('fb_id'));
		$trx_type = strtoupper($this->request->query('trx_type'));
		$payment_method = trim($this->request->query('payment_method'));
		
		if(array_key_exists($trx_type, $charge))
		{
			$amount = $charge[$trx_type];

			//add admin fee
			$admin_fee = '5000';
			$amount += $admin_fee;
			$this->set('admin_fee', $admin_fee);


			$rs_user = $this->User->findByFb_id($fb_id);

			$payment_channel = '01';
			if($payment_method == "va")
			{
				$payment_channel = '05';
			}

			//todo
			//if rs_user empty handle this
			$transaction_id = intval($rs_user['User']['id']).'-'.rand(0,999);
			$transaction_id_merchant = str_replace('-', '', $transaction_id);

			$doku_api = Configure::read('DOKU_API');
			$doku_mid = Configure::read('DOKU_MALLID');
			$doku_sharedkey = Configure::read('DOKU_SHAREDKEY');
			$hash_words = sha1(number_format($amount,2,'.','').
							  $doku_mid.
							  $doku_sharedkey.
							  $transaction_id_merchant);
			$trx_session_id = sha1(time());
			$additionaldata = 'app-fm-subscribe';
			$basket = 'UNLOCK '.$trx_type.','.$amount.','.$amount.','.$amount.';';

			$doku_param = array('MALLID'=>$doku_mid,
								'CHAINMERCHANT'=>'NA',
								'AMOUNT'=>number_format($amount,2,'.',''),
								'PURCHASEAMOUNT'=>number_format($amount,2,'.',''),
								'TRANSIDMERCHANT'=>$transaction_id_merchant,
								'WORDS'=>$hash_words,
								'REQUESTDATETIME'=>date("YmdHis"),
								'CURRENCY'=>'360',
								'PURCHASECURRENCY'=>'360',
								'SESSIONID'=>$trx_session_id,
								'NAME'=>Sanitize::clean($rs_user['User']['name']),
								'EMAIL'=>$rs_user['User']['email'],
								'ADDITIONALDATA'=>$additionaldata,
								'PAYMENTCHANNEL'=>$payment_channel,
								'BASKET'=>$basket,
							);

			$doku_data = array(
							'catalog_order_id'=>NULL,
							'po_number'=>$transaction_id,
							'transidmerchant'=>$transaction_id_merchant,
							'totalamount'=>number_format($amount,2,'.',''),
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
					);

			$dataSource = $this->MembershipTransactions->getDataSource();
			$dataSource->begin();
			
			//save to Doku table
			$this->Doku->create();
			$rs_doku = $this->Doku->save($doku_data);

			$transaction_name = 'Purchase Order #'.$transaction_id;
			$save_data = array(
								'fb_id' => $fb_id,
								'transaction_dt' => date("Y-m-d H:i:s"),
								'transaction_name' => $transaction_name,
								'po_number' => $transaction_id,
								'transaction_type' => 'SUBSCRIPTION',
								'amount' => $amount,
								'payment_method' => $payment_method,
								'details' => json_encode($doku_param),
								'league' => $_SESSION['league'],
								'n_status'=>0
							);

			
			$this->MembershipTransactions->save($save_data);
			
			$plan = 'pro1';
			if($trx_type == 'PRO_LEAGUE_2'){
				$plan = 'pro2';
			}

			$this->User->query("UPDATE users SET paid_plan='{$plan}'
								WHERE fb_id='{$fb_id}'");

			$dataSource->commit();

			$this->set('basket', $trx_type.' SUBSCRIPTION');
			//overide trx_type
			$trx_type = 'SUBSCRIPTION';

			$data_redis = array('doku_data' => $doku_data,
								'doku_param' => $doku_param,
								'fb_id' => $fb_id,
								'trx_type' => $trx_type,
								'amount' => $amount
								);


			$this->redisClient->set($transaction_id_merchant, serialize($data_redis));

			$this->redisClient->expire($transaction_id_merchant, 24*60*60);//expires in 1 day
			if($payment_channel == '05')
			{
				$this->redisClient->expire($transaction_id_merchant, 6*60*60);//expires in 6 hours
			}
			
			Cakelog::write('debug', 'payment.doku data_redis '.json_encode($data_redis));

			$this->set('doku_param', $doku_param);
			$this->set('doku_api', $doku_api);
		}
		else
		{
			$this->redirect($url_scheme.'fmpayment?status=0&message=Terjadi Kesalahan !');
		}
	}

	private function pay_with_ecash()
	{
		$url_scheme = Configure::read('URL_SCHEME');

		$fb_id = trim($this->request->query('fb_id'));
		$trx_type = strtoupper($this->request->query('trx_type'));
		$settings = Configure::read('SUBSCRIPTION_PLAN');

		$plan = 'pro2';
		if($trx_type == 'PRO_LEAGUE')
		{
			$plan = 'pro1';
		}

		$rs_user = $this->User->findByFb_id($fb_id);

		$transaction_id = $rs_user['User']['id'].rand(0,9999);
		$trxinfo = $settings[$plan];
		$trxinfo['plan'] = $plan;

		$data_redis = array(
								'fb_id' => $fb_id,
								'trxinfo' => $trxinfo,
								'transaction_id' => $transaction_id
							);

		$this->redisClient->set($transaction_id, serialize($data_redis));
		$this->redisClient->expire($transaction_id, 24*60*60);//expires in 1 day

		$amount = $trxinfo['price'] + $trxinfo['admin_fee'];

		$rs = $this->Game->getEcashUrl(array(
			'transaction_id'=>$transaction_id,
			'amount'=>$amount,
			'clientIpAddress'=>$this->request->clientIp(),
			'description'=>$trxinfo['name'],
			'source'=>'APPFMUPGRADE'
		));

		$this->set('transaction_id',$transaction_id);
		$this->set('ecash_url',$rs['data']);
		$this->render('ecash');
	}

	public function app_proleague()
	{
		$url_scheme = Configure::read('URL_SCHEME');
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
					$this->redirect($url_scheme.'fmcatalogpurchase?status=1&message=Purchase Success !');
				}catch(Exception $e){
					$dataSource->rollback();
					Cakelog::write('error', 'Payment.app_proleague 
						id='.$id.' data:'.json_encode($data).' message:'.$e->getMessage());
					$this->redirect($url_scheme.'fmcatalogpurchase?status=0&message=Terjadi kesalahan !');
				}
			}
			else
			{
				Cakelog::write('error', 'Payment.app_proleague id='.$id.' data:'.json_encode($data));
				$this->redirect($url_scheme.'fmcatalogpurchase?status=0&message=Terjadi kesalahan !');
			}
		}
		else
		{
			Cakelog::write('error', 'Payment.app_proleague '.$id.'Not Found');
			$this->redirect($url_scheme.'fmcatalogpurchase?status=0&message=Terjadi kesalahan !');
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

	public function mobile_ongkir_payment($order_id = "")
	{
		$url_scheme = Configure::read('URL_SCHEME');

		if($order_id == "")
		{
			$this->redirect($url_scheme.'fmcatalogpurchase?status=0&message=Terjadi kesalahan !');
		}

		$rs_order = $this->MerchandiseOrder->find('first', array(
				    	'conditions' => array(
				    			'id' => $order_id,
				    			'payment_method' => 'coins'
				    	)));
		
		if(count($rs_order) > 0)
		{
			$admin_fee_ongkir = $rs_order['MerchandiseOrder']['total_admin_fee'];

			//add suffix -1 to define that its the payment for shipping for these po number.
			$transaction_id =  $rs_order['MerchandiseOrder']['po_number'].'-1';

			$rs_user = $this->User->findByFb_id($rs_order['MerchandiseOrder']['fb_id']);
			$payment_method = $this->request->query('payment_method');
			if($payment_method !== NULL)
			{
				$this->loadModel('Doku');
				$payment_channel = '01';
				if($payment_method == "va")
				{
					$payment_channel = '05';
				}

				$transaction_id_merchant = str_replace('-', '', $transaction_id);
				$amount = $rs_order['MerchandiseOrder']['total_ongkir'] + $admin_fee_ongkir;

				$doku_api = Configure::read('DOKU_API');
				$doku_mid = Configure::read('DOKU_MALLID');
				$doku_sharedkey = Configure::read('DOKU_SHAREDKEY');
				$hash_words = sha1(number_format($amount,2,'.','').
								  $doku_mid.
								  $doku_sharedkey.
								  $transaction_id_merchant);
				$trx_session_id = sha1(time());
				$additionaldata = 'mobile-ongkir-payment';
				$po_number = $rs_order['MerchandiseOrder']['po_number'];
				$basket = 'ONGKIR PAYMENT PO#'.$po_number.','.$amount.','.$amount.','.$amount.';';


				$doku_data = array(
								'catalog_order_id'=>$rs_order['MerchandiseOrder']['id'],
								'po_number'=>$po_number,
								'transidmerchant'=>$transaction_id_merchant,
								'totalamount'=>number_format($amount,2,'.',''),
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
						);

				try{
					//save to Doku table
					$this->Doku->create();
					$rs_doku = $this->Doku->save($doku_data);
				}catch(Exception $e){
					//catch here because mostly duplicate entry key
					$rs_doku = $this->Doku->findByTransidmerchant($transaction_id_merchant);
					$trx_session_id = $rs_doku['Doku']['session_id'];
					$hash_words = $rs_doku['Doku']['words'];
				}

				$doku_param = array('MALLID'=>$doku_mid,
									'CHAINMERCHANT'=>'NA',
									'AMOUNT'=>number_format($amount,2,'.',''),
									'PURCHASEAMOUNT'=>number_format($amount,2,'.',''),
									'TRANSIDMERCHANT'=>$transaction_id_merchant,
									'WORDS'=>$hash_words,
									'REQUESTDATETIME'=>date("YmdHis"),
									'CURRENCY'=>'360',
									'PURCHASECURRENCY'=>'360',
									'SESSIONID'=>$trx_session_id,
									'NAME'=>$rs_user['User']['name'],
									'EMAIL'=>$rs_user['User']['email'],
									'ADDITIONALDATA'=>$additionaldata,
									'PAYMENTCHANNEL'=>$payment_channel,
									'BASKET'=>$basket,
								);
				

				$data_redis = array('doku_data' => $doku_data,
									'doku_param' => $doku_param,
									'order_id' => $order_id,
									'trx_type' => $basket,
									'amount' => $amount
									);



				$this->redisClient->set($transaction_id_merchant, serialize($data_redis));

				$this->redisClient->expire($transaction_id_merchant, 24*60*60);//expires in 1 day
				if($payment_channel == '05')
				{
					$this->redisClient->expire($transaction_id_merchant, 6*60*60);//expires in 6 hours
				}

				$this->set('doku_param', $doku_param);
				$this->set('doku_api', $doku_api);
				$this->set('basket', 'ONGKIR PAYMENT PO#'.$po_number);

				Cakelog::write('debug', 'payment.mobile_ongkir_payment data_redis '.json_encode($data_redis));
				$this->render('doku_ongkir_payment');

			}
			else
			{
				//todo
				//if rs_user empty handle this
				$description = 'Purchase Order #'.$transaction_id;

				$this->Session->write('order_id_payment', $order_id);
				$this->Session->write('transaction_id_payment', $transaction_id);

				$amount = $rs_order['MerchandiseOrder']['total_ongkir'] + $admin_fee_ongkir;

				$rs = $this->Game->getEcashUrl(array(
					'transaction_id'=>$transaction_id,
					'description'=>$description,
					'amount'=>$amount,
					'clientIpAddress'=>$this->request->clientIp(),
					'source'=>'FMONGKIRPAYMENTMOBILE'
				));

				if($rs['status'] == 1 && $rs['data'] != '')
				{
					$this->redirect($rs['data']);
				}
				else
				{
					$this->redirect($url_scheme.'fmcatalogpurchase?status=0&message=Saat ini tidak bisa terhubung dengan mandiri ecash, Silahkan coba beberapa saat lagi');
				}
			}
		}
		else
		{
			$this->redirect($url_scheme.'fmcatalogpurchase?status=0&message=Terjadi kesalahan !');
		}
	}

	public function mobile_ongkir_payment_success()
	{
		$url_scheme = Configure::read('URL_SCHEME');
		
		$id = $this->request->query['id'];

		$rs = $this->Game->EcashValidate($id);
		$order_id = $this->Session->read('order_id_payment');
		$transaction_id = $this->Session->read('transaction_id_payment');

		if(isset($rs['data']) && $rs['data'] != '')
		{
			$data = explode(',', $rs['data']);
			if(isset($data[4]) && trim($data[4]) == "SUCCESS")
			{
				try{
					//compare transaction_id
					if($data[3] != $transaction_id){
						throw new Exception("Invalid Transaction");
					}

					//transaction complete, we update the order status
					$data_update['n_status'] = 1;
					$this->MerchandiseOrder->id = intval($order_id);
					$updateResult = $this->MerchandiseOrder->save($data_update);

					if(isset($updateResult)){
						CakeLog::write('debug','payment.mobile_ongkir_payment_success'.$order_id.'
									 - DBSUCCESS');
					}else{
						CakeLog::write('debug','payment.mobile_ongkir_payment_success'.$order_id.'
									 - DBERROR');
					}

					$this->redirect($url_scheme.'fmcatalogpurchase?status=1&message=success');

				}catch(Exception $e){
					Cakelog::write('error', 'Payment.mobile_ongkir_payment_success 
						id='.$id.' data:'.json_encode($data).' message:'.$e->getMessage());
					$this->redirect($url_scheme.'fmcatalogpurchase?status=0&message=error');
				}
			}
			else
			{
				$this->redirect($url_scheme.'fmcatalogpurchase?status=0&message=error');
			}
		}
		else
		{
			Cakelog::write('error', 'Payment.mobile_ongkir_payment_success '.$id.' Not Found');
			$this->redirect($url_scheme.'fmcatalogpurchase?status=0&message=Saat ini tidak bisa terhubung dengan mandiri ecash, Silahkan coba beberapa saat lagi');
		}

		$this->Session->destroy();
	
	}

	private function isPayment($fb_id, $trx_type)
	{
		$rs = $this->MembershipTransactions->query("SELECT count(id) as total FROM membership_transactions
											WHERE fb_id='{$fb_id}' AND transaction_type='{$trx_type}'
											LIMIT 1");
		if($rs[0][0]['total'] > 0){
			return true;
		}
		return false;
	}

}