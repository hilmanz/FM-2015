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

		$amount = 0;
		if($this->checkTotalTeam() > 1)
		{
			$amount = $amount + $this->epl_charge($userData['fb_id']);
			$amount = $amount + $this->ita_charge($userData['fb_id']);
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

		$this->set('rs', $rs);
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
			$description = 'Purchase Order #'.$transaction_id;

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
			{
				$amount = $amount + ($this->bill_interval($userData['fb_id'], 'epl')*$this->charge);
				if($amount == 0)
				{
					$amount = $amount + ($this->bill_interval($userData['fb_id'], 'ita')*$this->charge);
				}

			}
			

			$rs = $this->Game->getEcashUrl(array(
				'transaction_id'=>$transaction_id,
				'description'=>$description,
				'amount'=>intval($amount),
				'clientIpAddress'=>$this->request->clientIp(),
				'source'=>'FMRENEWAL'
			));

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
					$transaction_name = 'Purchase Order #'.$data[3];
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
						$amount = $amount+($this->bill_interval($userData['fb_id'], 'epl')*$this->charge);
						$league = 'epl';
						if($amount == 0)
						{
							$amount = $amount+($this->bill_interval($userData['fb_id'], 'ita')*$this->charge);
							$league = 'ita';

							if($amount)
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
												SET log_dt=NOW(),expire=NOW() + INTERVAL 1 MONTH
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
											AND league = '{$league}'
											ORDER BY transaction_dt desc
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
}