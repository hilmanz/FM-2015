<?php
/**
* OPTA Valde HTTP Push EndPoint Implementation
*/
App::uses('AppController', 'Controller');
App::uses('Folder', 'Utility');
App::uses('File', 'Utility');
require_once APP.DS.'Vendor'.DS.'common.php';
class ResultController extends AppController {

/**
 * Controller name
 *
 * @var string
 */
	public $name = 'Result';

	public function index(){
		$id = $this->request->query('id');
		$type = intval($this->request->query('type'));
		$this->setupData();
		if($type==1){
			//overall match
			$this->render('index');
		}else if($type==2){
			$this->render('goalscorer');
		}else if($type==3){
			$this->render('lineup');
		}else if($type==4){
			//$this->render('player_stats');
		}else{
			//default is overall match
			$this->render('index');
		}
	}
	public function json(){
		$id = $this->request->query('id');
		$type = intval($this->request->query('type'));
		$this->setupData();
		$this->layout = 'ajax';
		if($type==1){
			//overall match
			$this->render('json/index');
		}else if($type==2){
			$this->render('json/goalscorer');
		}else if($type==3){
			$this->render('json/lineup');
		}else if($type==4){
			//$this->render('player_stats');
		}else{
			//default is overall match
			$this->render('json/index');
		}
	}
	private function setupData(){
		$id = $this->request->query('id');
		$type = intval($this->request->query('type'));
		$this->loadModel('Game_maps');
		$this->loadModel('Matchinfo');
		$this->loadModel('Booking');
		$this->loadModel('Goal');
		$this->loadModel('Substitution');
		$this->loadModel('Lineup');
		$this->loadModel('Team_stats');
		$this->loadModel('Player');
		$this->loadModel('Playerrefs');
		$this->loadModel('Teamrefs');
		$db = Configure::read('optadb');

		$map = $this->Game_maps->findById($id);
		$game_id = $map['Game_maps']['game_id'];

		$match = $this->Matchinfo->findByGame_id($game_id);
		if(isset($match['Matchinfo'])){
			$home_lineup = $this->Lineup->find('all',
										array('conditions'=>array(
												'game_id'=>$game_id,
												'team_id'=>$match['Matchinfo']['home_team']
											),
												'order'=>'position ASC',
												'limit'=>1000));

			foreach($home_lineup as $n=>$v){
				@$player = $this->Player->findByUid($v['Lineup']['player_id']);
				$home_lineup[$n]['Player'] = @$player['Player'];
			}
			
			$home_lineup_refs = $this->Playerrefs->find('all',
										array('conditions'=>array(
												'game_id'=>$game_id,
												'team_id'=>$match['Matchinfo']['home_team']
											),
												'order'=>'position ASC',
												'limit'=>1000));
			$home_team_refs = $this->Teamrefs->find('all',
										array('conditions'=>array(
												'game_id'=>$game_id,
												'team_id'=>$match['Matchinfo']['home_team']
											),
												
												'limit'=>1));
			$away_lineup = $this->Lineup->find('all',
										array('conditions'=>array(
												'game_id'=>$game_id,
												'team_id'=>$match['Matchinfo']['away_team']
											),
										'order'=>'position ASC',
										'limit'=>1000));

			$away_lineup_refs = $this->Playerrefs->find('all',
										array('conditions'=>array(
												'game_id'=>$game_id,
												'team_id'=>$match['Matchinfo']['away_team']
											),
												'order'=>'position ASC',
												'limit'=>1000));

			$away_team_refs = $this->Teamrefs->find('all',
										array('conditions'=>array(
												'game_id'=>$game_id,
												'team_id'=>$match['Matchinfo']['away_team']
											),
												
												'limit'=>1));

			foreach($away_lineup as $n=>$v){
				@$player = $this->Player->findByUid($v['Lineup']['player_id']);
				$away_lineup[$n]['Player'] = @$player['Player'];
			}
			
			$home_bulk_stats = $this->Team_stats->find('all',
										array('conditions'=>array(
												'game_id'=>$game_id,
												'team_id'=>$match['Matchinfo']['home_team']
											),
										'limit'=>1000));
			$home_stats = array();
			foreach($home_bulk_stats as $stat){
				$home_stats[$stat['Team_stats']['stats_name']] = $stat['Team_stats']['stats_value'];
			}
			
			$away_bulk_stats = $this->Team_stats->find('all',
										array('conditions'=>array(
												'game_id'=>$game_id,
												'team_id'=>$match['Matchinfo']['away_team']
											),
										'limit'=>1000));
			$away_stats = array();
			foreach($away_bulk_stats as $stat){
				$away_stats[$stat['Team_stats']['stats_name']] = $stat['Team_stats']['stats_value'];
			}

			$home_bookings = $this->Booking->find('all',
										array('conditions'=>array(
												'game_id'=>$game_id,
												'team_id'=>$match['Matchinfo']['home_team']
											),
										'limit'=>1000));

			$away_bookings = $this->Booking->find('all',
										array('conditions'=>array(
												'game_id'=>$game_id,
												'team_id'=>$match['Matchinfo']['away_team']
											),
										'limit'=>1000));

			$home_goals = $this->Goal->find('all',
										array('conditions'=>array(
												'game_id'=>$game_id,
												'team_id'=>$match['Matchinfo']['home_team']
											),
										'limit'=>1000));

			foreach($home_goals as $n=>$v){
				$player = $this->Player->findByUid($v['Goal']['player_id']);
				$home_goals[$n]['Goal']['player'] = $player['Player'];
			}
			
			$away_goals = $this->Goal->find('all',
										array('conditions'=>array(
												'game_id'=>$game_id,
												'team_id'=>$match['Matchinfo']['away_team']
											),
										'limit'=>1000));
			foreach($away_goals as $n=>$v){
				$player = $this->Player->findByUid($v['Goal']['player_id']);
				$away_goals[$n]['Goal']['player'] = @$player['Player'];
			}
			$home_subs = $this->Substitution->find('all',
										array('conditions'=>array(
												'game_id'=>$game_id,
												'team_id'=>$match['Matchinfo']['home_team']
											),
										'limit'=>1000));

			$away_subs = $this->Substitution->find('all',
										array('conditions'=>array(
												'game_id'=>$game_id,
												'team_id'=>$match['Matchinfo']['away_team']
											),
										'limit'=>1000));

			$this->set('result',array(
					'info'=>$match['Matchinfo'],
					'lineup'=>array('home'=>$home_lineup,'away'=>$away_lineup),
					'lineup_refs'=>array('home'=>$home_lineup_refs,'away'=>$away_lineup_refs),
					'team_refs'=>array('home'=>$home_team_refs[0]['Teamrefs'],'away'=>$away_team_refs[0]['Teamrefs']),
					'stats'=>array('home'=>$home_stats,'away'=>$away_stats),
					'goals'=>array('home'=>$home_goals,'away'=>$away_goals)
				));
		}
	}
}
