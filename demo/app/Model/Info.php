<?php
App::uses('AppModel', 'Model');
/**
 * Info Model
 *
 */
class Info extends AppModel {
	public function write($title,$content){
		$this->create();
		return $this->save(array(
			'title'=>$title,
			'content'=>$content,
			'dtpublished'=>date("Y-m-d H:i:s"),
			'n_status'=>1
		));
	}
	public function getLatest($userModel,$limit = 20){
		$info = $this->find('all',array(
					'limit'=>$limit,
					'order'=>'Info.id DESC'
				));

		foreach($info as $n=>$v){
			$players = $this->searchPlayerId($v['Info']['content']);
			//cek player 1
			if($players[0]>0){
				$player1 = $userModel->findById($players[0]);
			}
			//cek player 2
			if($players[1]>0){
				$player2 = $userModel->findById($players[1]);
			}
			if($player1){
				$info[$n]['Info']['content'] = str_replace("@p1_".$players[0],
													$player1['User']['name'],
													$info[$n]['Info']['content']);
				$info[$n]['Player1'] = $player1['User'];
			}
			if(isset($player2)){
				$info[$n]['Info']['content'] = str_replace("@p2_".$players[1],
													$player2['User']['name'],
													$info[$n]['Info']['content']);
				$info[$n]['Player2'] = $player2['User'];
			}
			$player1 = null;
			$player2 = null;
		}

		return $info;
	}
	private function searchPlayerId($post){
		$player = array();
		
		//player 1
		preg_match('/(\@p1_[0-9]+)/i',$post,$matches);
		$user_id = intval(str_replace("@p1_","",$matches[0]));
		$player[] = $user_id;

		//player 2
		preg_match('/(\@p2_[0-9]+)/i',$post,$matches);
		if(sizeof($matches)>0){
			$user_id = intval(str_replace("@p2_","",$matches[0]));
			$player[] = $user_id;
		}else{
			$player[] = 0;
		}
		return $player;
	}
}