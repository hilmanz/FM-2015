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
require_once APP.DS.'Vendor'.DS.'common.php';

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
	}

	//index()
	// 1. check two param fb_id & trx_type
	// 2. save to session fb_id, transaction_id, transaction_type
	// 3. get ecash url then redirect to payment mandiri ecash page
	// 4. check trx_type in charge
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
		$array_session = array('fb_id' => $fb_id, 'trx_type' => $trx_type);

		if(isset($rs['data']) && $rs['data'] != '')
		{
			$data = explode(',', $rs['data']);
			if(isset($data[4]) && trim($data[4]) == "SUCCESS")
			{
				try{
					$transaction_name = 'Purchase Order #'.$data[3];
					$transaction_type = 'UNLOCK '.$trx_type;
					$amount = $charge[$trx_type];
					$detail = json_encode($rs['data']);

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

					//hit url mobile notification
					$result_mobile = curlPost($url_mobile_notif,$array_session);
					Cakelog::write('debug_payment', json_encode($result_mobile));

					$this->redirect($url_scheme.'fmpayment?status=1&message=success');

				}catch(Exception $e){
					$dataSource->rollback();
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
	}

}