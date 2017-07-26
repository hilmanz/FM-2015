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
}