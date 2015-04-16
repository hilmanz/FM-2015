<?php

require_once APP.DS.'Vendor'.DS.'common.php';

class ManageInGamePurchaseTask extends Shell
{

	public $uses = array('User');

	public function execute()
	{
		$url_mobile_notif = Configure::read('URL_MOBILE_NOTIF').'fm_pro_league_notification';
		$url_mobile_unlock = Configure::read('URL_MOBILE_NOTIF').'fm_payment_notification';
		$limit = 100;

		do{
			$rs_user = $this->User->query("SELECT a.in_gamepurchase_processed, b.fb_id, b.id, 
									b.paid_member, b.paid_member_status, b.paid_plan
									FROM fantasy.member_billings a
									INNER JOIN fantasy.users b
									USING(fb_id) WHERE DATE(a.expire) < NOW()
									AND b.paid_member_status = 0
									AND a.in_gamepurchase_processed = 0
									LIMIT 0,{$limit}");

			$i = 1;
			foreach ($rs_user as $key => $value)
			{
				if($value['a']['in_gamepurchase_processed'] == 0)
				{

					if($value['b']['paid_plan'] == 'pro2')
					{
						$user_data = 'fb_id='.$value['b']['fb_id'].'&trx_type[]=LOCK_TRIVIA&trx_type[]=LOCK_CATALOG_1&trx_type[]=LOCK_CATALOG_2&trx_type[]=LOCK_CATALOG_3&user_id='.$value['b']['id'];

						echo 'user_data '.$user_data;

						$result_mobile = json_decode(curlPost($url_mobile_notif, $user_data), true);
					}
					else
					{
						$lock_ingame = array('LOCK_TRIVIA');

						$user_data = 'fb_id='.$value['b']['fb_id'].'&trx_type[]=LOCK_TRIVIA&user_id='.$value['b']['id'];

						echo 'user_data '.$user_data;


						$result_mobile = json_decode(curlPost($url_mobile_notif, $user_data), true);
					}

					print_r($result_mobile);

					if($result_mobile['code'] == 1)
					{

						//Unlock in game purchase

						$rs_purchase = $this->User->query("SELECT SUBSTRING_INDEX(transaction_type, ' ', -1) AS transaction_type
											FROM fantasy.membership_transactions
											WHERE fb_id='{$value['b']['fb_id']}' 
											AND transaction_type!= 'SUBSCRIPTION' 
											AND n_status=1");

						if(count($rs_purchase) > 0)
						{
							foreach ($rs_purchase as $key => $value2) 
							{
								$array_unlock = array('fb_id' => $value['b']['fb_id'], 'trx_type' => $value2[0]['transaction_type']);

								echo 'array unlock';
								print_r($array_unlock);
								$result_unlock = curlPost($url_mobile_unlock,$array_unlock);

								echo 'result_unlock';
								print_r($result_unlock);
							}
						}

						echo "UPDATE fantasy.member_billings 
													SET in_gamepurchase_processed = 1
													WHERE fb_id ='{$value['b']['fb_id']}'";

						$this->User->query("UPDATE fantasy.member_billings 
													SET in_gamepurchase_processed = 1
													WHERE fb_id ='{$value['b']['fb_id']}'");

					}
					else
					{
						$i++;
					}
				}
			}

			
		}while( count($rs_user)> 0 && $i<20 );


	}
}