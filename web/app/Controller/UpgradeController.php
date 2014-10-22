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
		if(!$this->hasTeam()){
			$this->redirect('/login/expired');
		}
	}

	public function hasTeam(){
		$userData = $this->userData;
		if(is_array($userData['team'])){
			return true;
		}
	}
	
	public function index()
	{
		$this->redirect('/profile');
	}

	public function member()
	{
		$userData = $this->userData;
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
			$can_upgrade = false;
		}

		if($can_upgrade)
		{
			$data_view = array();
			if($this->checkTotalTeam() > 1)
			{
				$amount = $amount + $this->epl_charge($userData['fb_id']);
				$amount = $amount + $this->ita_charge($userData['fb_id']);
				$data_view['league'] = array('English Premier League', 'Serie A Italy');

				$period_epl = $this->checkRegisterGameUser($userData['fb_id'], 'epl');
				$period_ita = $this->checkRegisterGameUser($userData['fb_id'], 'ita');

				$data_view['register_date'] = array(
										$period_epl[0][0]['tanggal'],
										$period_ita[0][0]['tanggal']
									);

				$data_view['period'] = array($period_epl[0][0]['period'],
											$period_ita[0][0]['period']);

				$data_view['charge'] = array($this->charge,$this->charge);
			}
			else
			{
				$amount = $amount + $this->epl_charge($userData['fb_id']);
				if($amount == 0)
				{
					$amount = $amount + $this->ita_charge($userData['fb_id']);
				}

			}

			$rs = $this->Game->getEcashUrl(array(
				'transaction_id'=>$transaction_id,
				'description'=>$description,
				'amount'=>$amount,
				'clientIpAddress'=>$this->request->clientIp(),
				'source'=>'FMUPGRADE'
			));
			$this->set('data_view', $data_view);
			$this->set('rs', $rs);
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
		$userData = $this->userData;
		$rs_user = $this->User->findByFb_id($userData['fb_id']);

		if($rs_user['User']['paid_member'] == 1 && $rs_user['User']['paid_member_status'] == 0)
		{
			$userData = $this->userData;
			$rs_user = $this->User->findByFb_id($userData['fb_id']);
			$transaction_id = intval($rs_user['User']['id']).'-'.date("YmdHis").'-'.rand(0,999);
			$description = 'Monthly Subscription '.date('m/Y').' #'.$transaction_id;

			$amount = 0;
			if($this->checkTotalTeam() > 1)
			{
				$epl_interval_month = ($this->bill_interval($userData['fb_id'], 'epl') == 0) ? '1': 
										$this->bill_interval($userData['fb_id'], 'epl');
				$ita_interval_month = ($this->bill_interval($userData['fb_id'], 'ita') == 0) ? '1': 
										$this->bill_interval($userData['fb_id'], 'ita');

				$amount = $amount + ($epl_interval_month*$this->charge);
				$amount = $amount + ($ita_interval_month*$this->charge);
			}
			else
			{	$epl_interval_month = ($this->bill_interval($userData['fb_id'], 'epl') == 0) ? '1': 
										$this->bill_interval($userData['fb_id'], 'epl');
				$amount = $amount + ($epl_interval_month*$this->charge);
				if($amount == 0)
				{
					$ita_interval_month = ($this->bill_interval($userData['fb_id'], 'ita') == 0) ? '1': 
										$this->bill_interval($userData['fb_id'], 'ita');
					$amount = $amount + ($ita_interval_month*$this->charge);
				}

			}
			
			Cakelog::write('debug','Upgrade.paymonthly '.json_encode(array(
				'transaction_id'=>$transaction_id,
				'description'=>$description,
				'amount'=>intval($amount),
				'clientIpAddress'=>$this->request->clientIp(),
				'source'=>'FMRENEWAL'
			)));

			$rs = $this->Game->getEcashUrl(array(
				'transaction_id'=>$transaction_id,
				'description'=>$description,
				'amount'=>intval($amount),
				'clientIpAddress'=>$this->request->clientIp(),
				'source'=>'FMRENEWAL'
			));
			Cakelog::write('debug', 'Upgrade.paymonthly '.json_encode($rs));

			$invoice = array(
								'amount' => $amount,
								'total_team' => $this->checkTotalTeam(),
								'desc' => 'Monthly Subscription '.date('m/Y')
							);

			$this->set('invoice', $invoice);

			$this->set('rs', $rs);
		}
		else
		{
			$this->redirect('/profile');
		}

	}

	public function renewal()
	{
		$id = $this->request->query['id'];

		$rs = $this->Game->EcashValidate($id);

		if(isset($rs['data']) && $rs['data'] != '')
		{
			$data = explode(',', $rs['data']);
			if(isset($data[4]) && trim($data[4]) == "SUCCESS")
			{
				try{
					$userData = $this->userData;
					$transaction_name = 'Monthly Subscription '.date('m/Y').' #'.$data[3];
					$detail = json_encode($rs['data']);

					$amount = 0;
					$dataSource = $this->User->getDataSource();
					$dataSource->begin();
					if($this->checkTotalTeam() > 1)
					{
						$epl_interval_month = ($this->bill_interval($userData['fb_id'], 'epl') == 0) ? '1': 
										$this->bill_interval($userData['fb_id'], 'epl');
						$ita_interval_month = ($this->bill_interval($userData['fb_id'], 'ita') == 0) ? '1': 
												$this->bill_interval($userData['fb_id'], 'ita');

						$epl_amount = $amount + ($epl_interval_month*$this->charge);
						$ita_amount = $amount + ($ita_interval_month*$this->charge);

						$save_data[] = array(
										'fb_id' => $userData['fb_id'],
										'transaction_dt' => date("Y-m-d H:i:s"),
										'transaction_name' => $transaction_name,
										'transaction_type' => 'RENEWAL MEMBER',
										'amount' => $epl_amount,
										'details' => $detail,
										'league' => 'epl'
									);
						$save_data[] = array(
										'fb_id' => $userData['fb_id'],
										'transaction_dt' => date("Y-m-d H:i:s"),
										'transaction_name' => $transaction_name,
										'transaction_type' => 'RENEWAL MEMBER',
										'amount' => $ita_amount,
										'details' => $detail,
										'league' => 'ita'
									);
						$this->MembershipTransactions->saveMany($save_data);
					}
					else
					{
						$epl_interval_month = ($this->bill_interval($userData['fb_id'], 'epl') == 0) ? '1': 
										$this->bill_interval($userData['fb_id'], 'epl');
						$amount = $amount+($epl_interval_month*$this->charge);
						$league = 'epl';
						if($amount == 0)
						{
							$ita_interval_month = ($this->bill_interval($userData['fb_id'], 'ita') == 0) ? '1': 
												$this->bill_interval($userData['fb_id'], 'ita');
							$amount = $amount+($ita_interval_month*$this->charge);
							$league = 'ita';

							if($amount == 0)
							{
								throw new Exception("Error amount");
							}
						}

						$save_data = array(
										'fb_id' => $userData['fb_id'],
										'transaction_dt' => date("Y-m-d H:i:s"),
										'transaction_name' => $transaction_name,
										'transaction_type' => 'RENEWAL MEMBER',
										'amount' => $amount,
										'details' => $detail,
										'league' => $league
									);
						$this->MembershipTransactions->save($save_data);
					}

					$this->MembershipTransactions->query("UPDATE member_billings
												SET log_dt=NOW(),expire=NOW() + INTERVAL 1 MONTH,
												is_sevendays_notif=0,is_threedays_notif=0
												WHERE fb_id='{$userData['fb_id']}'");
					
					$this->User->query("UPDATE users SET paid_member_status=1 
										WHERE fb_id='{$userData['fb_id']}'");

					$dataSource->commit();
				}catch(Exception $e){
					$dataSource->rollback();
					Cakelog::write('error', 'Upgrade.renewal 
						id='.$id.' data:'.json_encode($data).' message:'.$e->getMessage());
					$this->render('error');
				}
			}
			else
			{
				Cakelog::write('error', 'Upgrade.renewal id='.$id.' data:'.json_encode($data));
				$this->render('error');
			}
		}
		else
		{
			Cakelog::write('error', 'Upgrade.renewal '.$id.'Not Found');
			$this->render('error');
		}
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
}