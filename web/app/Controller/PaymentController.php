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

	public function mobile_ongkir_payment($order_id = "")
	{
		$url_scheme = Configure::read('URL_SCHEME');
		$admin_fee_ongkir = Configure::read('PO_ADMIN_ONGKIR_FEE');

		if($order_id == "")
		{
			$this->redirect($url_scheme.'fmcatalogpurchase?status=0&message=Terjadi kesalahan !');
		}
		$rs_order = $this->MerchandiseOrder->findByid($order_id);
		
		if(count($rs_order) > 0)
		{
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

				$transaction_id = intval($rs_user['User']['id']).'-0000'.date("YmdHis").'-'.rand(0,999);
				$transaction_id_merchant = str_replace('-', '', $transaction_id);
				$amount = $rs_order['MerchandiseOrder']['ongkir_value'] + $admin_fee_ongkir;

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

				//save to Doku table
				$this->Doku->create();
				$rs_doku = $this->Doku->save($doku_data);

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
				$transaction_id = intval($rs_user['User']['id']).'-'.date("YmdHis").'-'.rand(0,999);
				$description = 'Purchase Order #'.$transaction_id;

				$this->Session->write('order_id_payment', $order_id);
				$this->Session->write('transaction_id_payment', $transaction_id);

				$amount = $rs_order['MerchandiseOrder']['ongkir_value'] + $admin_fee_ongkir;

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