<?php
/**
 * Market Controller

 */
App::uses('AppController', 'Controller');
App::uses('Sanitize', 'Utility');

class EventsController extends AppController {

/**
 * Controller name
 *
 * @var string
 */
	public $name = 'Events';

/**
 * This controller does not use a model
 *
 * @var array
 */
	public $uses = array();
	public function beforeFilter(){
		parent::beforeFilter();
		$this->loadModel('Team');
		$this->loadModel('User');
		$userData = $this->getUserData();
		$user = $this->userDetail;
		$this->set('user',$user['User']);
		if(!$this->hasTeam()){
			$this->redirect('/login/expired');
		}
	}
	public function hasTeam(){
		$userData = $this->getUserData();
		if(is_array($userData['team'])){
			return true;
		}
	}
	public function index(){
		$this->redirect('/');
	}
	//redeem perk from offer events
	public function redeem(){
		$userData = $this->getUserData();
		$original_team_id = $userData['team']['team_id'];
		$game_team_id = $userData['team']['id'];
		// extract the hidden variables
		$osign = unserialize(decrypt_param($this->request->query['osign']));

		//now we retrieve the offer by its offer_id we got from osign.
		$this->loadModel('TriggeredEvents');
		$offer = $this->TriggeredEvents->findById($osign['offer_id']);
		
		//make sure that the offer is available just before the next matchday.
		//the possible next match will be : 
		$closest_fixture = $this->Game->query("SELECT matchday FROM ".$_SESSION['ffgamedb'].".game_fixtures 
							WHERE match_date > '{$offer['TriggeredEvents']['schedule_dt']}'
							AND (home_id = '{$original_team_id}' OR away_id = '{$original_team_id}')
							ORDER BY matchday ASC
							LIMIT 1",false);
		$event_next_matchday = $closest_fixture[0]['game_fixtures']['matchday'];
		
		if(isset($offer['TriggeredEvents']['id'])){
			//also make sure that the user hasnt answer the offer yet.
			$perks = $this->Game->query("SELECT * FROM ".$_SESSION['ffgamedb'].".game_perks
									 WHERE event_id={$offer['TriggeredEvents']['id']}
									 AND game_team_id={$game_team_id}
									 LIMIT 1;");
		}
		
		
		if(sizeof($perks)==0){
			$can_apply = true;
			
		}else{
			$can_apply = false;

		}

		//make sure that the tier is right
		if($offer['TriggeredEvents']['recipient_type'] !=0){
			$tier = $offer['TriggeredEvents']['recipient_type'];
			$ranks = $this->Game->query("SELECT MAX(rank) AS max_rank FROM points;");
			$max_rank = $ranks[0][0]['max_rank'];
			
			
			switch($tier){
				case 1:
					if($this->userRank > floor(0.25 * $max_rank)){
						$can_apply = false;
						
						$tier_fault = true;
					}
				break;
				case 2:
					if(($this->userRank <= floor(0.25 * $max_rank))
						|| ($this->userRank > ceil(0.5 * $max_rank))
					 ){
						$can_apply = false;
						
						$tier_fault = true;
					}
				break;
				case 3:
					if(($this->userRank <= floor(0.5 * $max_rank))
						|| ($this->userRank > ceil(0.75 * $max_rank))
					 ){
						$can_apply = false;
						
						$tier_fault = true;
					}
				break;
				case 4:
					if(($this->userRank <= floor(0.75 * $max_rank))){
						$can_apply = false;
						
						$tier_fault = true;
					}
				break;
				case 5:

					//if 5, we compare the team_id, if match, then it can apply
					$is_team_matched = $this->isTeamMatched($offer['TriggeredEvents']['target_team']);
					if(!$is_team_matched){
						$can_apply = false;
						$wrong_offer = true;
						$this->set('wrong_offer',@$wrong_offer);
					}else{
						$wrong_offer = false;

					}
				break;
				default:
					$can_apply = false;
				break;
			}

		}else{
			
			if(strlen($offer['TriggeredEvents']['offered_player_id']) > 0){
				
				//check apakah team punya pemain ini ?
				$players = $this->Game->get_team_players($this->userData['fb_id']);
				
				$can_apply = false;
				$wrong_offer = true;
				if(sizeof($players)>0){
					foreach($players as $player){
						if(trim($player['uid'])==trim($offer['TriggeredEvents']['offered_player_id'])){
							$can_apply = true;
							$wrong_offer = false;
						}
					}
					$this->set('wrong_offer',@$wrong_offer);
				}
			}
			
		}

		//and finally, make sure that people can only get the perk if the match is not started yet
		//or the formation update is not closed yet.
		//UPDATE 21/11/2013, now we set the expired date

		
		if( ($event_next_matchday >= $this->nextMatch['match']['matchday'])
			&& (strtotime($offer['TriggeredEvents']['expired_dt']) > time())
			&& $can_apply){
			
			//assign it into the view
			$this->set('offer',$offer['TriggeredEvents']);

			//lets reuse the osign string
			$this->set('osign',$this->request->query['osign']);	
			$this->set('week',$event_next_matchday);
			$this->set('offer_valid',true);
		}else{
			$this->set('offer',$offer['TriggeredEvents']);
			
			$this->set('week',$event_next_matchday);
			$this->set('offer_valid',false);
			$this->set('can_apply',$can_apply);
			$this->set('tier_fault',@$tier_fault);
			$this->set('tier',@$tier);
		}
		
		
	}
	private function isTeamMatched($target_team){
		$userData = $this->getUserData();
		$original_team_id = $userData['team']['team_id'];

		if($target_team == $original_team_id){
			return true;
		}
	}
	public function confirm($flag=0){
		$this->loadModel('GamePerk');
		//we need the user credential
		$userData = $this->getUserData();
		$original_team_id = $userData['team']['team_id'];
		$game_team_id = $userData['team']['id'];

		// extract the hidden variables
		$osign = unserialize(decrypt_param($this->request->query['osign']));

		//now we retrieve the offer by its offer_id we got from osign.
		$this->loadModel('TriggeredEvents');
		$offer = $this->TriggeredEvents->findById($osign['offer_id']);
		
		//make sure that the offer is available just before the next matchday.
		//the possible next match will be : 
		$closest_fixture = $this->Game->query("SELECT game_id,matchday FROM ".$_SESSION['ffgamedb'].".game_fixtures 
							WHERE match_date > '{$offer['TriggeredEvents']['schedule_dt']}'
							AND (home_id = '{$original_team_id}' OR away_id = '{$original_team_id}')
							ORDER BY matchday ASC
							LIMIT 1",false);
		$event_next_matchday = $closest_fixture[0]['game_fixtures']['matchday'];
		$event_next_game_id = $closest_fixture[0]['game_fixtures']['game_id'];
		
		//also make sure that the user hasnt answer the offer yet.
		$perks = $this->Game->query("SELECT * FROM ".$_SESSION['ffgamedb'].".game_perks
									 WHERE event_id={$offer['TriggeredEvents']['id']}
									 AND game_team_id={$game_team_id}
									 LIMIT 1;");
		
		if(sizeof($perks)==0){
			$can_apply = true;
		}else{
			$can_apply = false;
		}
		if($offer['TriggeredEvents']['recipient_type'] !=0){
			$tier = $offer['TriggeredEvents']['recipient_type'];
			$ranks = $this->Game->query("SELECT MAX(rank) AS max_rank FROM points;");
			$max_rank = $ranks[0][0]['max_rank'];
			
			switch($tier){
				case 1:
					if($this->userRank > floor(0.25 * $max_rank)){
						$can_apply = false;
						
						$tier_fault = true;
					}
				break;
				case 2:
					if(($this->userRank <= floor(0.25 * $max_rank))
						|| ($this->userRank > ceil(0.5 * $max_rank))
					 ){
						$can_apply = false;
						
						$tier_fault = true;
					}
				break;
				case 3:
					if(($this->userRank <= floor(0.5 * $max_rank))
						|| ($this->userRank > ceil(0.75 * $max_rank))
					 ){
						$can_apply = false;
						
						$tier_fault = true;
					}
				break;
				case 4:
					if(($this->userRank <= floor(0.75 * $max_rank))){
						$can_apply = false;
						
						$tier_fault = true;
					}
				break;
				case 5:
					//if 5, we compare the team_id, if match, then it can apply
					$is_team_matched = $this->isTeamMatched($offer['TriggeredEvents']['target_team']);
					if(!$is_team_matched){
						$can_apply = false;
						$wrong_offer = true;
						$this->set('wrong_offer',@$wrong_offer);
					}else{
						$wrong_offer = false;

					}
				break;
				default:
					$can_apply = false;
				break;
			}

		}
		//and offcourse the budget is must be sufficient to pay the cost

		
		if( ($event_next_matchday >= $this->nextMatch['match']['matchday'])
			&& (strtotime($offer['TriggeredEvents']['expired_dt']) > time())
			&& $can_apply){
			//ok we save to apply the perks
			$item = $offer['TriggeredEvents'];
			$perk = array(
				'event_id'=>$item['id'],
				'game_team_id'=>$game_team_id,
				'matchday'=>$event_next_matchday,
				'name'=>$item['name'],
				'n_status'=>0,
				'money_reward'=>intval($item['money_reward']),
				'points_reward'=>intval($item['points_reward']),
				'additional_points_modifier'=>floatval($item['point_mod_reward'])
			);

			
			//apply the perks
			if($flag==1){
				$this->set('success_message',$item['yes_txt']);
				$can_spend = true;
				if($item['event_type']!=4){
					//can spend money ?
					$money = $this->Game->query("SELECT SUM(budget+balance) AS money FROM (
												SELECT budget, 0 AS balance 
												FROM ".$_SESSION['ffgamedb'].".game_team_purse 
												WHERE game_team_id={$game_team_id}
													UNION
												SELECT 0 AS budget,SUM(amount) AS balance 
												FROM ".$_SESSION['ffgamedb'].".game_team_expenditures 
												WHERE game_team_id = {$game_team_id}) a;",
												false);
					//ChangeLog
					//BUG https://code18.atlassian.net/browse/SSFM-158

					if($item['money_cost']>0){
						if($money[0][0]['money'] < $item['money_cost']){
							$can_spend = false;
						}	
					}
					
				}
				if($can_spend){
					
					$this->GamePerk->create();
					$rs = $this->GamePerk->save($perk);
					if(isset($rs['GamePerk'])>0){
						if($item['event_type']==1 || $item['event_type']==2){
							//deduct the money if event_type 1 and 2
							$cost = $item['money_cost'];
							$this->Game->query("INSERT IGNORE INTO ".$_SESSION['ffgamedb'].".game_team_expenditures
												(game_team_id,item_name,item_type,
												 amount,game_id,match_day,item_total,base_price)
												VALUES
												({$game_team_id},'transaction_fee_{$item['id']}',2,
												 -{$item['money_cost']},
												 '{$event_next_game_id}',{$event_next_matchday},
												 1,1)",false);
							$this->set('success',true);
						}else{
							//process player transfer
							if($this->process_player_transfer($item,
															$game_team_id,
															$event_next_game_id,
															$event_next_matchday)){
								
								$this->set('success',true);
							
							}else{
								$this->set('success',false);
								$this->set('event_type',$item['event_type']);
								$this->set('player_error',true);
								$this->GamePerk->id = $rs['GamePerk']['id'];
							}

							$this->GamePerk->save(array(
													'apply_dt'=>date("Y-m-d H:i:s"),
													'n_status'=>1
												));
						}
					}else{
						$this->set('success',false);
					}
				}else{
					$this->set('success',false);
					$this->set('can_spend',$can_spend);
					
				}

			}else{

				$perk['n_status'] = 2;
				$this->set('success_message',$item['no_txt']);
				$this->GamePerk->create();
				$rs = $this->GamePerk->save($perk);
				
				//flag the perk as denied
				$this->redirect('/');
			}

		}else{
			$this->set('offer',$offer['TriggeredEvents']);
			$this->set('week',$event_next_matchday);
			$this->set('offer_valid',false);
			$this->set('can_apply',$can_apply);
			$this->set('tier_fault',@$tier_fault);
			$this->set('tier',@$tier);
			$this->render('redeem');
		}
		
	}
	private function process_player_transfer($item,$game_team_id,$game_id,$matchday){

		if($item['event_type']==3){
			return $this->buy_player($item,$game_team_id,$game_id,$matchday);
		}else{
			return $this->sale_player($item,$game_team_id,$game_id,$matchday);
		}
	}
	private function buy_player($item,$game_team_id,$game_id,$matchday){
		//check if the player is not owned by the club
		$check = $this->Game->query("SELECT COUNT(id) AS total
						FROM 
						".$_SESSION['ffgamedb'].".game_team_players 
						WHERE game_team_id={$game_team_id} 
						AND player_id = '{$item['offered_player_id']}' 
						LIMIT 1;",false);

		if($check[0][0]['total']==0){
			//insert the player into game_team_players
			$rs = $this->Game->query("INSERT IGNORE INTO ".$_SESSION['ffgamedb'].".game_team_players
										 (game_team_id,player_id)
										 VALUES({$game_team_id},'{$item['offered_player_id']}');",false);
			if(isset($rs)){
				//deduct the price
				$this->Game->query("INSERT INTO ".$_SESSION['ffgamedb'].".game_team_expenditures
											(game_team_id,item_name,item_type,amount,game_id,match_day)
											VALUES
											({$game_team_id},'buy_player',2,-{$item['money_cost']},
											  '{$game_id}',{$matchday})
											ON DUPLICATE KEY UPDATE
											amount = amount + VALUES(amount);",false);
				
				return true;
			}
		}else{
			return false;
		}

	}
	private function sale_player($item,$game_team_id,$game_id,$matchday){
		//check if the player is not owned by the club
		$check = $this->Game->query("SELECT COUNT(id) AS total
						FROM 
						".$_SESSION['ffgamedb'].".game_team_players 
						WHERE game_team_id={$game_team_id} 
						AND player_id = '{$item['offered_player_id']}' 
						LIMIT 1;");
		if($check[0][0]['total']==1){
			//remove the player from game_team_players
			$rs = $this->Game->query("DELETE FROM ".$_SESSION['ffgamedb'].".game_team_players
										 WHERE game_team_id = {$game_team_id}
										  AND player_id = '{$item['offered_player_id']}';");
			if(isset($rs)){
				//deduct the price
				$this->Game->query("INSERT INTO ".$_SESSION['ffgamedb'].".game_team_expenditures
											(game_team_id,item_name,item_type,amount,game_id,match_day)
											VALUES
											({$game_team_id},'player_sold',1,{$item['money_cost']},
											  '{$game_id}',{$matchday})
											ON DUPLICATE KEY UPDATE
											amount = amount + VALUES(amount);");
				
				return true;
			}
		}else{
			return false;
		}

	}
}