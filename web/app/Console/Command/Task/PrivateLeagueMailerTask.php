<?php

require_once APP.DS.'Vendor'.DS.'common.php';

class PrivateLeagueMailerTask extends shell
{
	public $uses = array('League', 'User');

	public function execute()
	{	
		$start = 0;
		$limit = 100;
		$domain = Configure::read('DOMAIN');

		do{
			$rs_email = $this->League->query('SELECT a.id,b.id,c.name AS inviter, b.name AS league_name, 
											a.email AS email, a.league AS league 
											FROM league_invitations a
											INNER JOIN league b ON a.league_id = b.id 
											INNER JOIN users c ON b.user_id = c.id 
											WHERE a.is_processed=0 AND a.n_status=0 
											LIMIT '.$start.','.$limit);

			foreach ($rs_email as $key => $value)
			{

				$rs_user = $this->User->findByEmail($value['a']['email']);
				if(count($rs_user) > 0)
				{
					$data_enc = array(
						'league_id' => $value['b']['id'],
						'email' => $value['a']['email'],
						'league' => $value['a']['league']
					);

					$data['subject'] = 'Private League Invitation';
					$data['to'] = $value['a']['email'];
					$data['inviter'] = $value['c']['inviter'];
					$data['league_name'] = $value['b']['league_name'];
					$data['league'] = $value['a']['league'];
					$data['url'] = 'http://'.$domain.'/privateleague/linkjoin/?trx='.
									encrypt_param(serialize($data_enc));
					$data['name'] = $rs_user['User']['name'];
					$data['template'] = 'private_league_mailer';

					$send_mail = send_mail($data);
				}
				else
				{
					$data_enc = array(
						'league_id' => $value['b']['id'],
						'email' => $value['a']['email'],
						'league' => $value['a']['league']
					);

					$data['subject'] = 'Private League Invitation';
					$data['to'] = $value['a']['email'];
					$data['inviter'] = $value['c']['inviter'];
					$data['league_name'] = $value['b']['league_name'];
					$data['league'] = $value['a']['league'];
					$data['url'] = 'http://'.$domain.'/privateleague/linkjoin/?trx='.
									encrypt_param(serialize($data_enc));
					$data['url_daftar'] = 'http://'.$domain.'/login/register';
					$data['template'] = 'private_league_mailer_not_user';

					$send_mail = send_mail($data);
				}

				if($send_mail){
					$this->update_status($value['a']['id']);
				}

			}

			$start += $limit;

		}while( count($rs_email)> 0 );
	}

	public function update_status($id)
	{
		$this->League->query("UPDATE league_invitations SET is_processed = 1 WHERE id={$id}");
	}
}