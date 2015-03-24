<?php
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
			&&$this->request->params['action']!='payment_pending'){
			$this->redirect('/login/expired');
		}
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
			//$this->render('ecash');
		}
	}
	
	public function index()
	{
		$this->redirect('/profile');
	}

	private function pay_with_ecash(){
		
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
					if($this->checkTotalTeam() > 1)
					{
						$amount_epl = $amount + $this->epl_charge($userData['fb_id']);
						$amount_ita = $amount + $this->ita_charge($userData['fb_id']);

						$save_data[] = array(
										'fb_id' => $userData['fb_id'],
										'transaction_dt' => date("Y-m-d H:i:s"),
										'transaction_name' => $transaction_name,
										'transaction_type' => 'UPGRADE MEMBER',
										'amount' => $amount_epl,
										'details' => $detail,
										'league' => 'epl'
									);

						$save_data[] = array(
										'fb_id' => $userData['fb_id'],
										'transaction_dt' => date("Y-m-d H:i:s"),
										'transaction_name' => $transaction_name,
										'transaction_type' => 'UPGRADE MEMBER',
										'amount' => $amount_ita,
										'details' => $detail,
										'league' => 'ita'
									);
						$this->MembershipTransactions->saveMany($save_data);
					}
					else
					{
						$amount = $amount + $this->epl_charge($userData['fb_id']);
						$league = 'epl';

						if($amount == 0)
						{
							$amount = $amount + $this->ita_charge($userData['fb_id']);
							$league = 'ita';
						}
						$save_data = array(
										'fb_id' => $userData['fb_id'],
										'transaction_dt' => date("Y-m-d H:i:s"),
										'transaction_name' => $transaction_name,
										'transaction_type' => 'UPGRADE MEMBER',
										'amount' => $amount,
										'details' => $detail,
										'league' => $league
									);
						$this->MembershipTransactions->create();
						$this->MembershipTransactions->save($save_data);
					}

					$this->MembershipTransactions->query("INSERT INTO member_billings
												(fb_id,log_dt,expire)
												VALUES('{$userData['fb_id']}',
														NOW(), NOW() + INTERVAL 1 MONTH)");

					$this->User->query("UPDATE users SET paid_member=1,paid_member_status=1 
										WHERE fb_id='{$userData['fb_id']}'");

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
						'NAME'=>$first_name.' '.$last_name,
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
						$this->set('order_doku',$data);
						return $data;
					}
				}
			}

		

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