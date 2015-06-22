<?php

App::uses('AppController', 'Controller');


class CoinsController extends AppController {

/**
 * Controller name
 *
 * @var string
 */
	public $name = 'Coins';

	public function index(){

	}
	//all id_list are actually the masked user ids,
	//to get the original id, we must substract the masked one with Configure::read('RANK_RANDOM_NUM')
	public function add(){
		if(isset($this->request->data['id_list'])){
			$this->search_team();
		}
	}

	private function search_team(){
		$arr = explode(PHP_EOL,$this->request->data['id_list']);
		$team_ids = array();
		//assign real IDs
		while(sizeof($arr)){
			$team_ids[] = intval(array_shift($arr)) - intval(Configure::read('RANK_RANDOM_NUM'));
		}	
		$teams = $this->Game->query("SELECT game_team.*,game_users.*,user.*,cash.cash,master_team.*,teams.* 
			                       FROM ".$_SESSION['ffgamedb'].".game_teams game_team
									INNER JOIN ".$_SESSION['ffgamedb'].".game_users game_users
									ON game_users.id = game_team.user_id
									INNER JOIN users user
									ON user.fb_id = game_users.fb_id
									INNER JOIN teams teams
									ON teams.user_id = user.id
									INNER JOIN ".$_SESSION['ffgamedb'].".master_team master_team
									ON master_team.uid = teams.team_id
									INNER JOIN game_team_cash cash
									ON cash.fb_id = user.fb_id
									WHERE game_team.id IN (".implode(',',$team_ids).")",false);
		$this->set('teams',$teams);
	}
	public function history($transaction_id=null){
		$this->loadModel('AddCoinHistory');
		$this->set('view_transaction',false);
		if($transaction_id!=null){
			$transaction = $this->Game->query("SELECT * 
											FROM ".$_SESSION['ffgamedb'].".add_coin_history a
											WHERE id = {$transaction_id}
											LIMIT 1");
			$team_ids = unserialize($transaction[0]['a']['team_ids']);
			

			$transaction[0]['a']['teams'] = $this->Game->query("SELECT * FROM ".$_SESSION['ffgamedb'].".game_teams game_team
										INNER JOIN ".$_SESSION['ffgamedb'].".game_users game_users
										ON game_users.id = game_team.user_id
										INNER JOIN users user
										ON user.fb_id = game_users.fb_id
										INNER JOIN teams teams
										ON teams.user_id = user.id
										INNER JOIN ".$_SESSION['ffgamedb'].".master_team master_team
										ON master_team.uid = teams.team_id
										WHERE game_team.id IN (".implode(',',$team_ids).")");

			$this->set('transaction',$transaction[0]['a']);
			$this->set('view_transaction',true);
			

		}
			
		$this->paginate = array('limit'=>10,
								'order'=>array(
									'AddCoinHistory.id'=>'DESC'
								)
						);
		$rs = $this->paginate('AddCoinHistory');
		
		$this->set('rs',$rs);
		
		
	}
	public function bulk_send(){
		if($this->request->is('post')){
			if(md5($this->request->data['authcode'])==md5("soccerdesk")){
				extract($this->request->data);
				$expenditure_name = 'other_'.str_replace(" ","_",$name);
				
				$success = 0;
				$success_id = array();

				for($i=0;$i<sizeof($team_id);$i++){
					$team_id[$i] = trim($team_id[$i]);
					
					$current_coin = $this->Game->getCurrentCoin(intval($team_id[$i]));
					
					$rs = $this->Game->addCoinTransaction(intval($team_id[$i]),
																$expenditure_name,$amount,'ADMIN');

					$new_coin = $this->Game->getCurrentCoin(intval($team_id[$i]));
					
					$current_coin = intval($current_coin);
					$new_coin = intval($new_coin);

					if($current_coin != $new_coin){
						//send notification
						/*$this->Game->query("INSERT INTO notifications
											(content,url,dt,game_team_id)
											VALUES
											('{$message}','#',NOW(),{$team_id[$i]})");*/
						$success++;
						$success_id[] = $rs;

					}
					
					
				}

				if($success == sizeof($team_id)){
					//update history
					$this->Game->query(
						"INSERT INTO ".$_SESSION['ffgamedb'].".add_coin_history
						(name,team_ids,amount,post_dt,n_status)
						VALUES
						('{$name}','".serialize($team_id)."',{$amount},NOW(),1)"
					);
					$this->set('transfer_ok',true);
				}else{
					//rollback now !
					
					for($i=0; $i<sizeof($success_id);$i++){
						$this->Game->query("DELETE FROM ".$_SESSION['ffgamedb'].".game_transactions 
											WHERE id={$success_id[$i]}");
					}

					//update history
					$this->Game->query(
						"INSERT INTO ".$_SESSION['ffgamedb'].".add_coin_history
						(name,team_ids,amount,post_dt,n_status)
						VALUES
						('{$name}','".serialize($team_id)."',{$amount},NOW(),0)"
					);

					$this->set('transfer_ok',false);
				}
			}else{

				$this->set('transfer_ok',false);
			}
		}
	}
}
