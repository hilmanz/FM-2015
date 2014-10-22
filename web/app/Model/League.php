<?php
App::uses('AppModel', 'Model');
/**
 * League Model

 */
class League extends AppModel {
	public $useTable = 'league';
	public $useDbConfig = 'default';

	public function getLeague($team_id, $league)
	{
		$rs = $this->query("SELECT * FROM league_member a 
							INNER JOIN league b ON a.league_id = b.id 
							WHERE team_id = '{$team_id}' AND a.league='{$league}' LIMIT 1000");
		Cakelog::write('debug', json_encode($rs));

		foreach ($rs as $key => $value)
		{
			$total_invite = $this->query("SELECT count(id) as total_invited FROM league_invitations 
										WHERE league_id='{$value['b']['id']}' AND league='{$league}' 
										LIMIT 1000");

			$total_joined = $this->query("SELECT count(id) as total_joined FROM league_invitations 
										WHERE league_id='{$value['b']['id']}'
										AND n_status = 1 AND league='{$league}' 
										LIMIT 1000");

			$rs[$key]['a']['total_invited'] = $total_invite[0][0]['total_invited'];
			$rs[$key]['a']['total_joined'] = $total_joined[0][0]['total_joined'];
		}

		return $rs;
	}

	//check user if has create or joined private league
	public function checkUser($email, $team_id, $league)
	{
		$result = array();

		$rs1 = $this->query("SELECT * FROM fantasy.league_invitations 
							WHERE email='{$email}' AND n_status NOT IN (2) 
							AND league='{$league}' LIMIT 1");

		if(count($rs1) > 0)
		{
			$result['has_invited'] = 1;
			if($rs1[0]['league_invitations']['n_status'] == 0)
			{
				$result['has_joined'] = 0;
			}
			else
			{
				$result['has_joined'] = 1;
			}

			return $result;
		}

		$rs2 = $this->query("SELECT * FROM fantasy.league_member 
							WHERE team_id='{$team_id}' AND league='{$league}' LIMIT 1");

		if(count($rs2) > 0)
		{
			$result['has_invited']	= 0;
			$result['has_joined']	= 1;

			return $result;
		}

		$result['has_invited']	= 0;
		$result['has_joined']	= 0;

		return $result;
	}
}