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
App::uses('Controller', 'Controller');
//require_once APP.DS.'Vendor'.DS.'common.php';

/**
 * Payment Controller
 *
 *
 * @package		payment.Controller
 */
class PaymentController extends AppController {
	public $name = 'Payment';

	public function beforeFilter()
	{
		parent::beforeFilter();
		$this->loadModel('Game');
		$this->loadModel('User');
		$this->loadModel('MembershipTransactions');
		$this->loadModel('MerchandiseOrder');
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
			$this->redirect($url_scheme.'fmpayment?status=1&message=Already, owned !');
		}

		$fb_id = trim($this->request->query('fb_id'));
		$trx_type = strtoupper($this->request->query('trx_type'));

		$is_payment = $this->isPayment($fb_id, 'UNLOCK '.$trx_type);

		if($is_payment){
			$this->redirect($url_scheme.'fmpayment?status=0&message=Terjadi Kesalahan !');
		}

		if(array_key_exists($trx_type, $charge))
		{
			$amount = $charge[$trx_type];

			$rs_user = $this->User->findByFb_id($fb_id);
			
			//todo
			//if rs_user empty handle this
			$transaction_id = intval($rs_user['User']['id']).'-'.date("YmdHis").'-'.rand(0,999);
			$description = 'Purchase Order #'.$transaction_id;

			$this->Session->write('fb_id_payment', $fb_id);
			$this->Session->write('trx_type_payment', $trx_type);
			$this->Session->write('transaction_id_payment', $transaction_id);

			$rs = $this->Game->getEcashUrl(array(
				'transaction_id'=>$transaction_id,
				'description'=>$description,
				'amount'=>$amount,
				'clientIpAddress'=>$this->request->clientIp(),
				'source'=>'FMPAYMENTMOBILE'
			));

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
		$fb_id = $this->Session->read('fb_id_payment');
		$trx_type = $this->Session->read('trx_type_payment');
		$transaction_id = $this->Session->read('transaction_id_payment');

		$array_session = array('fb_id' => $fb_id, 'trx_type' => $trx_type);

		$is_payment = $this->isPayment($fb_id, 'UNLOCK '.$trx_type);

		if($is_payment){
			$this->redirect($url_scheme.'fmpayment?status=1&message=Success, Already owned !');
		}

		if(isset($rs['data']) && $rs['data'] != '')
		{
			$data = explode(',', $rs['data']);
			if(isset($data[4]) && trim($data[4]) == "SUCCESS")
			{
				try{
					//compare transaction_id
					/*if($data[3] != $transaction_id){
						throw new Exception("Invalid Transaction");
					}*/

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

					$this->redirect($url_scheme.'fmpayment?status=1&message=success');

				}catch(Exception $e){
					Cakelog::write('error', 'Payment.success 
						id='.$id.' data:'.json_encode($data).' message:'.$e->getMessage());
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

		$this->Session->destroy();
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