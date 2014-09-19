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
	public $useDbConfig = "ffgame";
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
	public function transfer_window(){
		$response = $this->api_call('/transfer_window');
		return $response;
	}
	public function getMasterTeam($team_id){
		$response = $this->api_call('/players/'.$team_id);
		return $response;
	}
	public function getMasterTopPlayers($team_id,$total=10){
		$response = $this->api_call('/top_players/'.$total);
		
		return $response;
	}
	public function create_team($data){
		$response = $this->api_post('/create_team',$data);
		return $response;
	}
	public function get_team_players($fb_id){
		$response = $this->api_call('/team/get/'.$fb_id);
		
		if(intval(@$response['id'])>0){
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
	public function getCash($fb_id){
		$response = $this->api_call('/cash/'.$fb_id);
		if($response['status']==1){
			return $response['cash'];
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
	* selling player
	*/
	public function sale_player($window_id,$team_id,$player_id){
		$response = $this->api_post('/sale',array(
			'window_id'=>$window_id,
			'game_team_id'=>$team_id,
			'player_id'=>$player_id
		));
		return $response;
	}
	/*
	* buy_player
	*/
	public function buy_player($window_id,$team_id,$player_id){
		$response = $this->api_post('/buy',array(
			'window_id'=>$window_id,
			'game_team_id'=>$team_id,
			'player_id'=>$player_id
		));
		return $response;
	}
	/*
	* add expenditure
	*/
	public function add_expenditure($game_team_id,$transaction_name,$amount){
		$response = $this->api_post('/add_expenditure',array(
			'game_team_id'=>$game_team_id,
			'transaction_name'=>$transaction_name,
			'amount'=>$amount
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
	/*
	* similar to get_team_player_info, but we dont care if the player is not ours
	*/
	public function get_player_info($player_id){
		$response = $this->api_call('/player/'.$player_id);
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
	public function getMatchDetailsByGameTeamId($game_team_id,$game_id){
		$response = $this->api_call('/match/user_match_results/'.$game_team_id.'/'.$game_id);
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
	* weekly finance
	*/
	function weekly_finance($fb_id,$week){
		$team = $this->api_call('/team/get/'.$fb_id);
		if(isset($team['id'])){
			$response = $this->api_call('/weekly_finance/'.$team['id'].'/'.$week);
		}
		if($response['status']==1){
			return $response['data'];
		}
		return array();
	}
	/**
	*	get team's last earning from previous match.
	*/
	function getLastEarnings($game_team_id){
		$response = $this->api_call('/last_earning/'.$game_team_id);
		return $response;	
	}
	/**
	*	get team's last expenses from previous match.
	*/
	function getLastExpenses($game_team_id){
		$response = $this->api_call('/last_expenses/'.$game_team_id);
		return $response;	
	}
	/**
	* Team Points
	*/
	function getTeamPoints($fb_id){
		$response = $this->api_call('/points/'.$fb_id);
		if($response['fb_id']==$fb_id){
			return array("points"=>$response['points'],
						 "extra_points"=>$response['extra_points'],
						 "game_points"=>$response['game_points']);
		}else{
			return array();
		}
	}

	/** get master match result statistic **/
	function getMatchResultStats(){
		$response = $this->api_call('/leaderboard');
		return $response;
	}

	public function getMatchStatus($matchday){
		$response = $this->api_call('/matchstatus/'.$matchday);
		
		return $response;
	}

	/////SPONSORSHIPS
	/*
	* apply sponsorship
	*/
	public function apply_sponsorship($game_id,$matchday,$team_id,$sponsor_id){
		$response = $this->api_post('/sponsorship/apply',array(
			'game_id'=>$game_id,
			'matchday'=>$matchday,
			'game_team_id'=>$team_id,
			'sponsor_id'=>$sponsor_id
		));
		return $response;
	}


	///TEAM EXPENDITURES HELPER
	//it's a helper to insert data to ffgame.game_team_expenditures
	public function addTeamExpenditures(
		$game_team_id,
		$item_name,
		$item_type,
		$amount,
		$game_id,
		$match_day,
		$item_total=1,
		$base_price=1){
		$sql = "INSERT IGNORE INTO ffgame.game_team_expenditures
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
		return $this->query($sql,false);

	}
	
	public function getPerkByType($perks,$perk_type,$reward_type=null){
		$result = array();
		for($i=0;$i < sizeof($perks); $i++){
			$perk = $perks[$i];
			$perk_data = unserialize($perk['MasterPerk']['data']);

			if($perk['MasterPerk']['perk_name'] == $perk_type 
				&& is_array($perk_data)
				&& $perk_data['type'] == 'jersey'){
					if($reward_type!=null && $perk_data['type'] == $reward_type){
						 array_push($result,$perk_data);
					}else if($reward_type==null){
						 array_push($result,$perk_data);
					}else{

					}
			}
			
		}
		return $result;
	}
	/*
	* apply digital perk to game_team_id
	* @return $response
	*/
	public function apply_digital_perk($game_team_id,$perk_id){
		
		$response = $this->api_call('/apply_perk/'.$game_team_id.'/'.$perk_id);

		return $response;
	}
	/*
	* check if we can apply these perk
	* @return $response
	*/
	public function can_apply_perk($game_team_id,$perk_id){
		$response = $this->api_call('/check_perk/'.$game_team_id.'/'.$perk_id);
		
		if($response['status']==1){
			return true;
		}
	}
	/*
	returns the hardcoded custom jersey CSS style
	*/
	public function getCustomJerseyStyle($jersey_id){
		$rs = $this->query("SELECT css FROM ffgame.master_jersey a 
									WHERE id = {$jersey_id} LIMIT 1");
		return "<style>".PHP_EOL.$rs[0]['a']['css'].PHP_EOL."</style>".PHP_EOL;
	}

	public function getPoinBooster($game_team_id){
		$rs = $this->query("SELECT 
							    a.available, b.name
							FROM
						    ffgame.digital_perks a 
								INNER JOIN
						    ffgame.master_perks b 
								ON 
							a.master_perk_id = b.id
								WHERE
						    a.game_team_id = {$game_team_id} AND n_status = 1
								LIMIT 100000;");

		return $rs;
	}

	public function livestats($game_id){
		$response = $this->api_call_raw('/livestats/'.$game_id);
		return $response;
	}
	public function livegoals($game_id){
		$response = $this->api_call_raw('/livegoals/'.$game_id);
		return $response;	
	}
	
	public function livematches($league = 'epl'){
		$rs = $this->api_call('/fixtures');
		$fixtures = $rs['matches'];
		
		$matchday = 1;

		$tr = 0;
		$ts = 0;
		$dd = array();
		for($i=0;$i<sizeof($fixtures);$i++){
			//strtotime($fixtures[$i]['match_date']) > (time()-24*60*60*7)
			if($fixtures[$i]['period']=='FullTime'){
				$matchday = $fixtures[$i]['matchday'];
				$tr++;
			}else{
				$ts++;
				$dd[] = $matchday.' - '.$fixtures[$i]['matchday'];
				if($matchday > $fixtures[$i]['matchday']){
					$matchday = $fixtures[$i]['matchday'];
				}
			}
		}
		$matchday+=1;
		$nm = $matchday;
		$response 	= $this->api_call('/livematches/'.$matchday);
		$standings	= $this->api_call('/standings/'.$league);
		$is_live = 1;
		$show_stats = 0;
		if(sizeof($response['data'])==0){
			$matchday -= 1; //we use the previous matches
			if($matchday < 1){
				$matchday = 1;
			}
			$is_live = 0;
			//check if the previous matchday has cached stats.
			$response = $this->api_call('/livematches/'.$matchday);
			if(sizeof($response['data'])>0){
				$show_stats = 1;
			}
		}
		$matches = array();
		for($i=0;$i<sizeof($fixtures);$i++){
			if($fixtures[$i]['matchday']==$matchday){
				$matches[] = $fixtures[$i];
			}
		}

		return json_encode(
			array('status'=>1,'data'=>array('matches'=>$matches,
				  'live'=>$is_live,
				  'fixtures'=>$fixtures,
				  'live_data'=>$response['data'],
				  'show_stats'=>$show_stats,
				  'matchday'=>$matchday,
				  'standings'=>$standings,
				  'nm'=>$nm,
				  'ts'=>$ts,
				  'tr'=>$tr,
				  'dd'=>$dd
				))
		);
	}

	public function redeemCode($game_team_id,$coupon_code){
		$game_team_id = intval($game_team_id);
		if(strlen($coupon_code) > 10){
			$rs = $this->query("SELECT * FROM ffgame.coupon_codes a
						WHERE coupon_code = '{$coupon_code}' 
						AND game_team_id = 0 
						AND n_status = 0 
						LIMIT 1;",false);
			
			if(isset($rs[0]['a']['coupon_code']) 
				&& $rs[0]['a']['coupon_code'] == $coupon_code
				&& $rs[0]['a']['game_team_id'] == 0
				&& $rs[0]['a']['n_status'] == 0){
				//the code is available, then we claim the code for these user
				$claim = $this->query("UPDATE ffgame.coupon_codes
										SET game_team_id = {$game_team_id},
										n_status=1,
										redeem_dt = NOW() 
										WHERE coupon_code='{$coupon_code}'",false);
				if(is_array($claim)){
					$rs = $this->api_post('/redeemCode',array(
						'game_team_id'=>$game_team_id,
						'coupon_code'=>$coupon_code
					));
					if($rs['status']==1){
						return true;	
					}else{
						//if we failed to redeem the code,
						//we set the coupon code to 0 again.
						$reset = $this->query("UPDATE ffgame.coupon_codes
										SET game_team_id = {$game_team_id},
										n_status=0,
										redeem_dt = NOW() 
										WHERE coupon_code='{$coupon_code}'",false);
					}
					
				}
				
			}
		}
	}

	public function setInputAttempt($game_team_id,$input_name,$input_value){
		$name = $input_name.'_'.$game_team_id;
		$rs = $this->api_call('/setInputAttempt',array(
					'name'=>$name,
					'value'=>$input_value
				));
		return $rs;
	}
	public function getInputAttempt($game_team_id,$input_name){
		$name = $input_name.'_'.$game_team_id;
		$rs = $this->api_call('/getInputAttempt',array('name'=>$name));
		return $rs;
	}

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
	/*
	* $params - transaction_id,amount,clientIpAddress,description,source
	*/
	public function getEcashUrl($params){
		
		if($params['amount']>0){
			$rs = $this->api_call('/getEcashUrl',$params);	
		}else{
			$rs = array('status'=>0);
		}
		return $rs;
	}
	public function EcashValidate($id){
		$rs = $this->api_call('/ecash_validate',array('id'=>$id));
		return $rs;	
	}

	public function getBetInfo($game_id){
		$rs = $this->api_call('/bet_info/'.$game_id,array());
		return $rs;	
	}
}

