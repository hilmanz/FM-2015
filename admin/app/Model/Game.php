<?php
App::uses('AppModel', 'Model');
/**
 * Game Model
 *
 * @property status_types $status_types
 * @property document_types $document_types
 * @property topics $topics
 */
class Game extends AppModel {
	public $useTable = false; //kita gak pake table database, karena nembak API langsung.

	public function getTeam($fb_id){
		$response = $this->api_call('/team/get/'.$fb_id,array());
		if(!isset($response['error'])){
			return $response;	
		}
		return null;
	}
	public function getClub($team_id){
		$response = $this->api_call('/teams/'.$team_id,array());
		if(!isset($response['error'])){
			return $response;	
		}
		return null;
	}
	public function getVenue($team_id){
		$response = $this->api_call('/venue/'.$team_id);
		return $response['venue'];
	}
	public function getBudget($team_id){
		$response = $this->api_call('/team/budget/'.$team_id,array());
		return $response['budget'];
	}

	public function setProfile($data){ /*save profile*/
		$response = $this->api_post('/user/register',$data);
		return $response;
	}

	public function getTeams(){ /*load team list*/
		$response = $this->api_call('/teams');
		return $response;
	}
	/*
	* similar to get_team_player_info, but we dont care if the player is not ours
	*/
	public function get_player_info($player_id){
		$response = $this->api_call('/player/'.$player_id);
		return $response;
	}
	public function getMasterTeam($team_id){
		$response = $this->api_call('/players/'.$team_id);
		return $response;
	}
	public function create_team($data){
		$response = $this->api_post('/create_team',$data);
		return $response;
	}
	public function get_team_players($fb_id){
		$response = $this->api_call('/team/get/'.$fb_id);
		
		if(intval($response['id'])>0){
			$team_id = intval($response['id']);
			$team = $this->api_call('/team/list/'.$team_id);
			if(sizeof($team)>0){
				return $team;
			}
		}
	}
	public function getAvailableOfficials($team_id){
		$response = $this->api_call('/official/list/'.$team_id);
		if($response['status']==1){
			return $response['officials'];
		}
	}
	public function hire_staff($team_id,$official_id){
		$response = $this->api_post('/official/hire',array(
			'team_id'=>$team_id,
			'official_id'=>$official_id
		));
		return $response;
	}
	public function dismiss_staff($team_id,$official_id){
		$response = $this->api_post('/official/fire',array(
			'team_id'=>$team_id,
			'official_id'=>$official_id
		));
		return $response;
	}
	/*
	* get current lineup settings
	* @team_id game_team_id 
	*/
	public function getLineup($team_id){
		$response = $this->api_call('/team/lineup/'.$team_id);
		return $response;
	}
	public function setLineup($team_id,$formation,$players){
		$s_players = json_encode($players);
		$response = $this->api_post('/team/lineup/save',array(
						'team_id'=>$team_id,
						'players'=>$s_players,
						'formation'=>$formation
					));
		$response['lineup'] = $players;
		return $response;
	}

	public function get_team_player_info($fb_id,$player_id){
		$team = $this->api_call('/team/get/'.$fb_id);
		if(isset($team['id'])){
			$response = $this->api_call('/team/player/'.$team['id'].'/'.$player_id);
		}
		return $response;
	}
	/**
	* get match list
	*
	*/
	public function getMatches(){
		$response = $this->api_call('/match/list');
		return $response;
	}

	public function getMatchDetails($game_id){
		$response = $this->api_call('/match/results/'.$game_id);
		return $response;
	}
	public function getNextMatch($team_id){
		$response = $this->api_call('/next_match/'.$team_id);
		return $response;
	}
	public function getBestMatch($game_team_id){
		$response = $this->api_call('/best_match/'.$game_team_id);
		return $response;	
	}
	/**
	*	get team's current best player
	*/
	public function getBestPlayer($game_team_id){
		$response = $this->api_call('/best_player/'.$game_team_id);
		return $response;	
	}
	/**
	* financial statements
	*/
	function financial_statements($fb_id){
		$team = $this->api_call('/team/get/'.$fb_id);
		if(isset($team['id'])){
			$response = $this->api_call('/finance/'.$team['id']);
		}			
		return $response;
	}

	/**
	*	get team's last earning from previous match.
	*/
	function getLastEarnings($game_team_id){
		$response = $this->api_call('/last_earning/'.$game_team_id);
		return $response;	
	}
	/**
	* Team Points
	*/
	function getTeamPoints($fb_id){
		$response = $this->api_call('/points/'.$fb_id);
		if($response['fb_id']==$fb_id){
			return array("points"=>$response['points']);
		}else{
			return array();
		}
	}


	///TEAM EXPENDITURES HELPER
	//it's a helper to insert data to ".$_SESSION['ffgamedb'].".game_team_expenditures
	public function addTeamExpenditures(
		$game_team_id,
		$item_name,
		$item_type,
		$amount,
		$game_id,
		$match_day,
		$item_total=1,
		$base_price=1){
		$sql = "INSERT IGNORE INTO ".$_SESSION['ffgamedb'].".game_team_expenditures
				(game_team_id,
				item_name,
				item_type,
				amount,
				game_id,
				match_day,
				item_total,
				base_price)
				VALUES
				(
				{$game_team_id},
				'{$item_name}',
				{$item_type},
				{$amount},
				'{$game_id}',
				{$match_day},
				{$item_total},
				{$base_price}
				);";
		$rs = $this->query($sql,false);
		
		return $this->getLastInsertID();

	}
	/*
	* helper to add coin transactions
	*/
	public function addCoinTransaction($fb_id,$transaction_name,$amount,$details){
		//insert the transaction
		$sql = "INSERT INTO .game_transactions
				(fb_id,transaction_dt,transaction_name,amount,details)
				VALUES
				({$fb_id},NOW(),'{$transaction_name}',{$amount},'{$details}')
				ON DUPLICATE KEY UPDATE
				amount = VALUES(amount);";
		$rs = $this->query($sql,false);

		//and then update the cash 
		$sql = "INSERT INTO game_team_cash
				(fb_id,cash)
				SELECT fb_id,SUM(amount) AS cash 
				FROM game_transactions
				WHERE fb_id = {$fb_id}
				GROUP BY fb_id
				ON DUPLICATE KEY UPDATE
				cash = VALUES(cash);";

		$rs = $this->query($sql,false);

		$rs = $this->query("SELECT id FROM game_transactions a
							WHERE fb_id = {$fb_id} ORDER BY id DESC LIMIT 1",false);

		return $rs[0]['a']['id'];
	}
	public function getCurrentCoin($fb_id){
		$sql = "SELECT * FROM game_team_cash a WHERE fb_id = {$fb_id} LIMIT 1";
		$rs = $this->query($sql,false);
		return $rs[0]['a']['cash'];
	}
	public function transaction_exists($game_team_id,
		$item_name,
		$item_type,
		$amount){
		$sql = "SELECT * FROM ".$_SESSION['ffgamedb'].".game_team_expenditures a
				WHERE game_team_id={$game_team_id} AND item_name='{$item_name}'
				AND item_type='{$item_type}' LIMIT 1";
		$rs = $this->query($sql);
		if($rs[0]['a']['amount']==$amount){
			return true;
		}
	}
	public function getTransaction($game_team_id,
		$item_name,
		$item_type,
		$amount){
		$sql = "SELECT * FROM ".$_SESSION['ffgamedb'].".game_team_expenditures Transaction
				WHERE game_team_id={$game_team_id} AND item_name='{$item_name}'
				AND item_type='{$item_type}' LIMIT 1";
		$rs = $this->query($sql);
		return $rs[0]['Transaction'];
	}

	public function setPostponedMatch($game_id,$toggle){
		$response = $this->api_call('/postponed',
									array('game_id'=>$game_id,'toggle'=>$toggle));
		return $response;
	}
	public function getPostponedMatch($game_id){
		$response = $this->api_call('/postponed_status',
									array('game_id'=>$game_id,'league'=>$_SESSION['league']));
		return $response;
	}
	//wrapper to redis
	public function storeToTmp($game_team_id,$input_name,$input_value,$ttl = 86400){
		$rs = $this->api_call('/storeToTmp',array(
					'name'=>$input_name,
					'value'=>$input_value,
					'ttl'=>$ttl
				));
		return $rs;
	}
	public function getTmpKeys($game_team_id,$pattern){
		$rs = $this->api_call('/getTmpKeys',array(
					'pattern'=>$pattern
					));
		return $rs;
	}
	public function getFromTmp($game_team_id,$input_name){
		
		$rs = $this->api_call('/getFromTmp',array('name'=>$input_name));
		return $rs;
	}

	public function getMatchDetailsByGameTeamId($game_team_id,$game_id){
		$response = $this->api_call('/match/user_match_results/'.$game_team_id.'/'.$game_id);
		return $response;
	}
	
	public function getCash($team_id){
		$response = $this->api_call('/cash/'.$team_id);
		if($response['status']==1){
			return $response['cash'];
		}
	}
	//end of redis wrapper
}