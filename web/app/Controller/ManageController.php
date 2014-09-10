<?php
/**
 * Manage Controller
 */
App::uses('AppController', 'Controller');
App::uses('Sanitize', 'Utility');

class ManageController extends AppController {

/**
 * Controller name
 *
 * @var string
 */
	public $name = 'Manage';

/**
 * This controller does not use a model
 *
 * @var array
 */
	public $uses = array();
	private $weekly_balances = null;
	private $expenditures = null;
	private $starting_budget = 0;
	private $finance_total_items_raw = null;
	private $tickets_sold;

	public $components = array('ActivityLog');

	public function beforeFilter(){
		parent::beforeFilter();
		$this->loadModel('Team');
		$this->loadModel('User');
		

		
		if(!$this->hasTeam()){
			$this->redirect('/login/expired');
		}

		if(time()<strtotime($this->userDetail['User']['register_date'])+(24*60*60)){
			$is_new_user = true;
		}else{
			$is_new_user = false;
		}
		$this->set('is_new_user',$is_new_user);	
		

		$userData = $this->userData;

		//list of players
		$players = $this->Game->get_team_players($userData['fb_id']);
		$this->set('players',$players);

		//user data
		$user = $this->userDetail;
		$this->set('user',$user['User']);
		
		

		//budget
		$this->budget = $this->Game->getBudget($userData['team']['id']);
		$this->set('team_bugdet',$this->budget);

		$this->set('club',$user['Team']);

		$this->set('game_team_id',$userData['team']['id']);
		//$this->logTime($this->ActivityLog);
	}
	public function hasTeam(){
		$userData = $this->userData;
		if(is_array($userData['team'])){
			return true;
		}
	}
	public function index(){
		$this->redirect('/manage/club');
	}
	public function club(){
		$this->loadModel('Weekly_point');
		$this->loadModel('Weekly_rank');

		$userData = $this->userData;
		
		$user = $this->userDetail;

		$club = $this->club;
		//get original club
		$original_club = $this->Game->getClub($club['Team']['team_id']);
		$this->set('original',$original_club);
		
		//list of players
		$players = $this->Game->get_team_players($userData['fb_id']);
		
		$this->set('players',$players);

		$best_players = subval_rsort($players,'points');

		if($best_players[0]['points'] == 0){
			$best_players = array();
		}
		$this->set('best_players',$best_players);
		
		//weekly salaries
		$weekly_salaries = 0;
		foreach($players as $p){
			$weekly_salaries += intval(@$p['salary']);
		}
		
		//-->
		//list of staffs
		//get officials
		$officials = $this->Game->getAvailableOfficials($userData['team']['id']);
		$staffs = array();
		foreach($officials as $official){
			if(isset($official['hired'])){
				$staffs[] = $official;
			}
		}
		$this->set('staffs',$staffs);

		foreach($staffs as $p){
			$weekly_salaries += intval(@$p['salary']);
		}
		
		$this->set('weekly_salaries',$weekly_salaries);
		

		//financial statements & cache it when necessary.  these are a hell of heavy queries.
		$this->Session->write('FinancialStatement',null); //debug only.

		if(!is_array($this->Session->read('FinancialStatement'))){

		
			$financial_statement['finance'] = $this->getFinancialStatements($userData['fb_id']);
			$financial_statement['weekly_balances'] = $this->weekly_balances;
			$financial_statement['total_items'] = $this->finance_total_items_raw;
			//last earnings
			$rs = $this->Game->getLastEarnings($userData['team']['id']);
			if($rs['status']==1){
				$financial_statement['last_earning'] = $rs['data']['total_earnings'];
			}else{
				$financial_statement['last_earning'] = 0;
			}

			//last expenses
			$rs = $this->Game->getLastExpenses($userData['team']['id']);
			if($rs['status']==1){
				$financial_statement['last_expenses'] = $rs['data']['total_expenses'];
			}else{
				$financial_statement['last_expenses'] = 0;
			}
			
			$financial_statement['expenditures'] = $this->expenditures;
			$financial_statement['tickets_sold'] = $this->tickets_sold;
			$financial_statement['starting_budget'] = $this->starting_budget;

			$this->Session->write('FinancialStatement',$financial_statement);
		}
		
		$financial_statement = $this->Session->read('FinancialStatement');
		
		$weeks = array();
		if(sizeof($financial_statement['weekly_balances'])>0){
			foreach($financial_statement['weekly_balances'] as $fs){
				$weeks[] = $fs['week'];
			}
		}
		$this->set('weeks',$weeks);
		//filter finance by week
		$week = intval(@$this->request->query['week']);
		if($week > 0){
			$this->set('active_tab',1);
			$weekly_finance = $this->Game->weekly_finance($userData['fb_id'],$week);
			$weekly_statement = $this->getWeeklyFinancialStatement($weekly_finance);
			$this->set('finance',$weekly_statement['transaction']);
			$this->set('total_items',$weekly_statement['total_items']);
		}else{
			if(isset($this->request->query['week'])){
				$this->set('active_tab',1);
			}

			$this->set('finance',$financial_statement['finance']);
			$this->set('total_items',$financial_statement['total_items']);
		}

		$rooster = intval(@$this->request->query['rooster']);
		if($rooster==1){
			$this->set('active_tab',2);
		}
		$this->set('week',$week);
		$this->set('total_matches',$financial_statement['finance']['total_matches']);
		$this->set('starting_budget',$financial_statement['starting_budget']);
		$this->set('weekly_balances',$financial_statement['weekly_balances']);
		$this->set('last_earning',$financial_statement['last_earning']);
		$this->set('last_expenses',$financial_statement['last_expenses']);
		
		//--> 

		//weekly points and weekly ranks
		//for weekly points, make sure the points from other player are included

		$this->Weekly_point->virtualFields['TotalPoints'] = 'SUM(Weekly_point.points + Weekly_point.extra_points)';
		$options = array('fields'=>array('Weekly_point.id', 'Weekly_point.team_id', 
							'Weekly_point.game_id', 'Weekly_point.matchday', 'Weekly_point.matchdate', 
							'SUM(Weekly_point.points+Weekly_point.extra_points) AS TotalPoints',
							'Team.id', 'Team.user_id', 
							'Team.team_id','Team.team_name'),
			'conditions'=>array('Weekly_point.team_id'=>$user['Team']['id'],
								'Weekly_point.league'=>$_SESSION['league']),
	        'limit' => 100,
	        'group' => 'Weekly_point.matchday',
	        'order' => array(
	            'matchday' => 'asc'
	        ));
		$weekly_points = $this->Weekly_point->find('all',$options);
		
		$weekly_team_points = array();
		while(sizeof($weekly_points) > 0){
			$p = array_shift($weekly_points);
			$weekly_team_points[] = array(
					'game_id'=>$p['Weekly_point']['game_id'],
					'matchday'=>$p['Weekly_point']['matchday'],
					'matchdate'=>$p['Weekly_point']['matchdate'],
					'points'=>$p[0]['TotalPoints']
				);
		}
		unset($weekly_points);
		$this->set('weekly_points',$weekly_team_points);
		
		//matches
		$matches = $this->getMatches($weekly_team_points,
										$financial_statement['expenditures'],
										$financial_statement['tickets_sold']);

		
		$this->set('matches',$matches);

		//enable OPTA Widget
		$this->set('ENABLE_OPTA',true);
		$this->set('OPTA_CUSTOMER_ID',Configure::read('OPTA_CUSTOMER_ID'));
		//-->

		if(isset($this->request->query['tab'])){
			$this->set('tab',$this->request->query['tab']);
		}

		//banners
		

		//banner in tab info.
		$info_banner = $this->getBanners('MY_CLUB_INFO',1,true);
		$this->set('info_banner',$info_banner);

		//sidebar banner
		$sidebar_banner = $this->getBanners('INSIDE_SIDEBAR',2,true);
		$this->set('sidebar_banner',$sidebar_banner);

		//long banner
		$long_banner = $this->getBanners('MY_CLUB_LONG2',2,true);
		$this->set('long_banner2',$long_banner);
		
		//render time !
		$this->render('klab');
	}
	protected function getFinancialStatements($fb_id){

		$finance = $this->Game->financial_statements($fb_id);
		
		$this->weekly_balances = @$finance['data']['weekly_balances'];
		$this->expenditures = @$finance['data']['expenditures'];
		$this->tickets_sold = @$finance['data']['tickets_sold'];
		$this->starting_budget = @intval($finance['data']['starting_budget']);

		if($finance['status']==1){

			$report = array('total_matches' => $finance['data']['total_matches'],
							'budget' => $finance['data']['budget']);
			$total_items = array();
			foreach($finance['data']['report'] as $n=>$v){
				$report[$v['item_name']] = $v['total'];
				$total_items[$v['item_name']] = $v['item_total'];
			}
			
			$report['total_earnings'] = intval(@$report['tickets_sold'])+
										intval(@$report['commercial_director_bonus'])+
										intval(@$report['marketing_manager_bonus'])+
										intval(@$report['public_relation_officer_bonus'])+
										intval(@$report['win_bonus'])+
										
										intval(@$report['player_sold']);

			foreach($report as $item_name=>$price){
				if($price > 0 && @eregi('other_',$item_name)){
					//$report['total_earnings'] += intval($price);
					
				}
				if($price > 0 && @eregi('perk-',$item_name)){
					//$report['total_earnings'] += intval($price);

				}
			}
			$this->finance_total_items_raw = $total_items;
			return $report;
		}
	}
	private function getWeeklyFinancialStatement($weekly_finance){
		$weekly_statement = array();
		$total_items = array();
		while(sizeof($weekly_finance['transactions'])>0){
			$p = array_shift($weekly_finance['transactions']);
			$weekly_statement[$p['item_name']] = $p['amount'];
			$total_items[$p['item_name']] = $p['item_total'];
		}

		$weekly_statement['total_earnings'] = intval(@$weekly_statement['tickets_sold'])+
									intval(@$weekly_statement['commercial_director_bonus'])+
									intval(@$weekly_statement['marketing_manager_bonus'])+
									intval(@$weekly_statement['public_relation_officer_bonus'])+
									intval(@$weekly_statement['win_bonus'])+
									intval(@$weekly_statement['player_sold']);
		return array('transaction'=>$weekly_statement,'total_items'=>$total_items);
	}
	private function getMatches($arr,$expenditures,$tickets_sold){
		
		$matches = array();
		if(sizeof($arr)>0){
			$game_ids = array();

			foreach($arr as $a){
				$game_ids[] = "'".$a['game_id']."'";
			}

			$a_game_ids = implode(',',$game_ids);
			
			$sql = "SELECT game_id,home_id,away_id,b.name AS home_name,c.name AS away_name,
					a.matchday,a.match_date,a.home_score,a.away_score
					FROM ".$_SESSION['ffgamedb'].".game_fixtures a
					INNER JOIN ".$_SESSION['ffgamedb'].".master_team b
					ON a.home_id = b.uid
					INNER JOIN ".$_SESSION['ffgamedb'].".master_team c
					ON a.away_id = c.uid
					WHERE (a.home_id = '{$this->userData['team']['team_id']}' 
							OR a.away_id = '{$this->userData['team']['team_id']}')
					AND EXISTS (SELECT 1 FROM ".$_SESSION['ffgamestatsdb'].".game_match_player_points d
								WHERE d.game_id = a.game_id 
								AND d.game_team_id = {$this->userData['team']['id']} LIMIT 1)
					ORDER BY a.matchday DESC";
			
		
			//get the game_id stored in lineup history

			/*$sql = "SELECT game_id FROM ".$_SESSION['ffgamedb'].".game_team_lineups_history a
					WHERE game_team_id={$this->userData['team']['id']} GROUP BY game_id;";*/
				
			$current_game_ids = $this->Game->query($sql,false);

			$gids = array();
			for($i=0;$i<sizeof($current_game_ids);$i++){
				$gids[] = "'".$current_game_ids[$i]['a']['game_id']."'";
			}
			
			$gids = implode(",",$gids);
			
			$sql = "SELECT game_id,home_id,away_id,b.name AS home_name,c.name AS away_name,
					a.matchday,a.match_date,a.home_score,a.away_score
					FROM ".$_SESSION['ffgamedb'].".game_fixtures a
					INNER JOIN ".$_SESSION['ffgamedb'].".master_team b
					ON a.home_id = b.uid
					INNER JOIN ".$_SESSION['ffgamedb'].".master_team c
					ON a.away_id = c.uid
					WHERE a.game_id IN ({$gids})
					AND a.is_processed = 1
					ORDER BY a.matchday DESC";
		
			try{


				$rs = $this->Game->query($sql);
			}catch(Exception $err){
				$rs = array();
			}
			

			foreach($rs as $n=>$r){
				$points = 0;
				$balance = 0;
				$income = 0;
				foreach($arr as $a){
					if($r['a']['matchday']==$a['matchday']){
						$points = $a['points'];
						break;
					}
				}
				foreach($tickets_sold as $b){
					if($r['a']['game_id']==$b['game_id']){
						$income = $b['total_income'];
						break;
					}
				}
				$match = $r['a'];
				$match['home_name'] = $r['b']['home_name'];
				$match['away_name'] = $r['c']['away_name'];
				$match['points'] = $points;
				$match['income'] = $income;
				$matches[] = $match;
			}

			//clean memory
			$rs = null;
			unset($rs);
		}
		return $matches;
	}
	
	public function hiring_staff(){
		
		$userData = $this->userData;

		if(isset($this->request->query['hire'])){
			$official_id = intval($this->request->query['id']);
			if($official_id>0){
				$rs = $this->Game->hire_staff($userData['team']['id'],$official_id);
				if($rs['status']==1){
					$msg = "@p1_".$this->userDetail['User']['id']." telah merekrut {$rs['officials']['name']} baru.";
					$this->Info->write('set formation',$msg);
				}
			}
		}
		if(isset($this->request->query['dismiss'])){
			$official_id = intval($this->request->query['id']);
			if($official_id>0){
				$this->Game->dismiss_staff($userData['team']['id'],$official_id);
			}
		}
		//budget
		$budget = $this->Game->getBudget($userData['team']['id']);
		$this->set('team_bugdet',$budget);

		//get officials
		$officials = $this->Game->getAvailableOfficials($userData['team']['id']);

		//estimated costs
		$total_weekly_salary = 0;
		foreach($officials as $official){
			if(isset($official['hired'])){
				$total_weekly_salary+=$official['salary'];
			}
		}
		$this->set('officials',$officials);
		$this->set('weekly_salaries',$total_weekly_salary);
	}
	public function team(){

		$club = $this->club;
		$userData = $this->userData;
		
		$next_match = $this->nextMatch;
		$next_match['match']['home_original_name'] = @$next_match['match']['home_name'];
		$next_match['match']['away_original_name'] = @$next_match['match']['away_name'];
		if(@$next_match['match']['home_id']==@$userData['team']['team_id']){
			$next_match['match']['home_name'] = $club['Team']['team_name'];
		}else{
			$next_match['match']['away_name'] = $club['Team']['team_name'];
		}
		
		$this->set('next_match',$next_match['match']);

		//match venue
		$match_venue = @$this->Game->getVenue($next_match['match']['home_id']);
		$this->set('venue',$match_venue);

		//best match
		$best_match = $this->Game->getBestMatch($userData['team']['id']);
		$team_id = $userData['team']['team_id'];
		
		if($best_match['status']==0){
			$this->set('best_match','N/A');
		}else{
			
			
			$best_match['data']['points'] = number_format($best_match['data']['points']);
			if(isset($best_match['data']['match'])){
				if($best_match['data']['match']['home_id']==$team_id){
					$against = $best_match['data']['match']['away_name'];
				}else if($best_match['data']['match']['away_id']==$team_id){
					$against = $best_match['data']['match']['home_name'];
				}
				$this->set('best_match_url','/manage/matchinfo/?game_id='.@$best_match['data']['match']['game_id'].'&r='.encrypt_param(serialize($best_match['data']['match'])));
				$this->set('best_match_id',@$best_match['data']['match']['game_id']);
				$this->set('best_match',"VS. {$against} (+{$best_match['data']['points']})");
			}
			
		}

		//last earnings
		$rs = $this->Game->getLastEarnings($userData['team']['id']);
		if($rs['status']==1){
			$this->set('last_earning',$rs['data']['total_earnings']);
		}else{
			$this->set('last_earning',0);
		}

		//best player
		$rs = $this->Game->getBestPlayer($userData['team']['id']);
		
		if($rs['status']==1){
			$this->set('best_player',$rs['data']);
		}

		$this->set('first_time',$this->Session->read('first_time'));
		$this->Session->write('first_time',false);


		//banners
		$sidebar_banner = $this->getBanners('INSIDE_SIDEBAR',2,true);
		$this->set('popup_setformation', $this->popup_setformation());
		$this->set('sidebar_banner',$sidebar_banner);
	}
	public function player($player_id){
		
		$userData = $this->userData;
		//user data
		$user = $this->userDetail;
		$this->set('user',$user['User']);

		//budget
		$budget = $this->Game->getBudget($userData['team']['id']);
		$this->set('team_bugdet',$budget);

		//club
		$club = $this->Team->findByUser_id($user['User']['id']);
		$this->set('club',$club['Team']);

		//player detail : 
		$rs = $this->Game->get_team_player_info($userData['fb_id'],$player_id);
		
		if($rs['status']==1){

			if(isset($rs['data']['daily_stats'])&&sizeof($rs['data']['daily_stats'])>0){
				
				foreach($rs['data']['daily_stats'] as $n=>$v){
					$fixture = $this->Team->query("SELECT matchday,match_date,
										UNIX_TIMESTAMP(match_date) as ts
										FROM ".$_SESSION['ffgamedb'].".game_fixtures 
										WHERE game_id='{$n}' 
										LIMIT 1");

					$rs['data']['daily_stats'][$n]['fixture'] = $fixture[0]['game_fixtures'];
					$rs['data']['daily_stats'][$n]['fixture']['ts'] = $fixture[0][0]['ts'];
				}
			}
			
			$this->set('data',$rs['data']);
		}
		
		//stats modifier
		$modifier = $this->Game->query("SELECT * FROM ".$_SESSION['ffgamedb'].".game_matchstats_modifier as Modifier");
		$this->set('modifiers',$modifier);

		//enable OPTA Widget
		$this->set('ENABLE_OPTA',true);
		$this->set('OPTA_CUSTOMER_ID',Configure::read('OPTA_CUSTOMER_ID'));
		//-->

		//$this->set('tab',@$this->request->query['tab']);
	}
	public function playerstats($player_id){
		$player_id = Sanitize::paranoid($player_id);
		$game_id = $this->request->query('game_id');
		$game_id = Sanitize::paranoid($game_id);
		$match = unserialize(decrypt_param($this->request->query['r']));

		$userData = $this->userData;
		//user data
		$user = $this->userDetail;
		$this->set('user',$user['User']);

		//club
		$club = $this->Team->findByUser_id($user['User']['id']);
		$this->set('club',$user['Team']);

		$this->set('match',$match);
		$this->set('r',$this->request->query['r']);


		$players = $this->Session->read('PlayerStats_Matchinfo_'.$game_id);
		
		if(!isset($players)){
			$players = $this->Game->getMatchDetailsByGameTeamId($userData['team']['id'],$game_id);

		}
		$data = $players['data'][$player_id];

		$this->set('data',$data);
		$this->set('player_id',$player_id);

		//poin modifiers
		$rs = $this->Team->query("SELECT name,
										g as Goalkeeper,
										d as Defender,
										m as Midfielder,
										f as Forward
										FROM ".$_SESSION['ffgamedb'].".game_matchstats_modifier as stats;");

		$modifier = array();
		foreach($rs as $r){
			$modifier[$r['stats']['name']] = $r['stats'];
		}
		$rs = null;
		unset($rs);

		$this->set('modifier',$modifier);

		//enable OPTA Widget
		$this->set('ENABLE_OPTA',true);
		$this->set('OPTA_CUSTOMER_ID',Configure::read('OPTA_CUSTOMER_ID'));
		//-->
	}
	public function matchinfo(){
		$game_id = $this->request->query('game_id');
		$game_id = Sanitize::paranoid($game_id);

		$match = unserialize(decrypt_param($this->request->query['r']));
		$this->set('r',$this->request->query['r']);
		
		$userData = $this->userData;
		//user data
		$user = $this->userDetail;
		$this->set('user',$user['User']);

		//club
		$club = $this->Team->findByUser_id($user['User']['id']);
		$this->set('club',$user['Team']);

		//match details

		
		$players = $this->Game->getMatchDetailsByGameTeamId($userData['team']['id'],$game_id);
		
		$this->Session->write('PlayerStats_Matchinfo_'.$game_id,$players);
		
		$this->set('players',$players['data']);


		//poin modifiers
		$rs = $this->Team->query("SELECT name,
										g as Goalkeeper,
										d as Defender,
										m as Midfielder,
										f as Forward
										FROM ".$_SESSION['ffgamedb'].".game_matchstats_modifier as stats;");

		$modifier = array();
		foreach($rs as $r){
			$modifier[$r['stats']['name']] = $r['stats'];
		}
		$rs = null;
		unset($rs);

		$this->set('modifier',$modifier);
		$this->set('match',$match);
	}
	public function error(){
		$this->render('error');
	}
	public function team_error(){
		$this->set('error_type','team');
		$this->render('error');
	}
	public function success(){
		$this->render('success');
	}
	/**
	*	fungsi2 dibawah ini harus dimatiin di production
	*/
	public function reset(){
		if(Configure::read('debug')>0){
			if(@$this->request->query['confirm']==1){
				//perform deletion here
				// remove di database game
				$user_id = $this->userData['team']['user_id'];
				$team_id = $this->userData['team']['id'];
				$this->User->query("DELETE FROM ".$_SESSION['ffgamedb'].".game_users WHERE id ={$user_id};");
				$this->User->query("DELETE FROM ".$_SESSION['ffgamedb'].".game_teams WHERE id = {$team_id};");
				$this->User->query("DELETE FROM ".$_SESSION['ffgamedb'].".game_team_players WHERE game_team_id = {$team_id};");
				//remove di database frontend.

				$user = $this->User->findByFb_id($this->userData['fb_id']);
				
				$id = $user['User']['id'];
				$club = $this->Team->findByUser_id($user['User']['id']);
				$this->Team->delete($club['Team']['id']);
				$this->User->delete($id);
				//hapus session
				$this->Session->destroy();
				$this->set('confirm',1);
			}else if(@$this->request->query['confirm']==2){
				$this->redirect('/manage/team');
			}else{
				$this->set('confirm',0);	
			}
		}else{
			$this->redirect('/manage/team');
		}
	}
	public function play_match(){
		if(Configure::read('debug')>0){
			$userData = $this->userData;
			$user = $this->User->findByFb_id($userData['fb_id']);
			$club = $this->Team->findByUser_id($user['User']['id']);
			$next_match = $this->Game->getNextMatch($userData['team']['team_id']);
			$this->loadModel('Team');
			$rs = $this->Team->query("UPDATE ".$_SESSION['ffgamedb'].".game_fixtures SET is_processed = 0 
								WHERE id={$next_match['match']['id']}");
		}else{
			$this->redirect('/manage/team');
		}
	}
	public function reset_matches(){
		if(Configure::read('debug')>0){
			$this->loadModel('Team');
			$rs = $this->Team->query("UPDATE ".$_SESSION['ffgamedb'].".game_fixtures 
										SET period='PreMatch',is_processed = 1");
		}else{
			$this->redirect('/manage/team');
		}
	}
	public function reset_finance(){
		if(Configure::read('debug')>0){
			$userData = $this->userData;
			$user = $this->User->findByFb_id($userData['fb_id']);
			$club = $this->Game->getTeam($userData['fb_id']);
			
			$this->loadModel('Team');
			$rs = $this->Team->query("DELETE FROM ".$_SESSION['ffgamedb'].".game_team_expenditures
								WHERE game_team_id={$club['id']}");
		}else{
			$this->redirect('/manage/team');
		}
	}
	public function new_user_event(){
		if(Configure::read('debug')>0){
			$this->loadModel('Info');
			$msg = "@p1_".$this->userDetail['User']['id']." telah bergabung ke dalam liga.";
			$this->Info->write('new player',$msg);
			$this->redirect('/');
		}else{
			$this->redirect('/manage/team');
		}
	}

	/*Tactics Page
	in tactics page, the player lists and tactic options can only be displayed if
	user already set a formation for the upcoming matchday.
	Note that the new tactics only applied for the upcoming matchday.*/
	public function tactics(){
		
		
		$userData = $this->userData;

		

		//list of players
		$players = $this->Game->get_team_players($userData['fb_id']);
		$this->set('players',$players);


		//lineup
		$lineup = $this->Game->getLineup($userData['team']['id']);

		//user data
		$user = $this->userDetail;
		$this->set('user',$user['User']);
		

		//budget
		$budget = $this->Game->getBudget($userData['team']['id']);
		$this->set('team_bugdet',$budget);

		//club
		$club = $this->Team->findByUser_id($user['User']['id']);
		$this->set('club',$club['Team']);

		$this->set('game_team_id',$userData['team']['id']);
	
		$next_match = $this->nextMatch;
		$next_match['match']['home_original_name'] = @$next_match['match']['home_name'];
		$next_match['match']['away_original_name'] = @$next_match['match']['away_name'];
		if(@$next_match['match']['home_id']==@$userData['team']['team_id']){
			$next_match['match']['home_name'] = $club['Team']['team_name'];
		}else{
			$next_match['match']['away_name'] = $club['Team']['team_name'];
		}
		
		$this->set('next_match',$next_match['match']);

		$instruction_points = $this->getInstructionPoints();
		$upcoming_matchday = $this->getUpcomingMatchday();
		
		if($this->request->is('post')){
			$save_success = $this->saveTactics($next_match['match'],$instruction_points,$upcoming_matchday);

			if(!$save_success){
				$this->set("msg","Tactic tidak berhasil disimpan karena melebih instruction points !");
			}else{
				$this->set("msg","Tactic loe sudah berhasil disimpan !");
			}
		}
		
		$this->set('lineup',$lineup['lineup']);

		//get current tactics
		$current_tactics = $this->getCurrentTactics($upcoming_matchday);

		$this->set('tactics',$current_tactics);


		//banners
		$sidebar_banner = $this->getBanners('INSIDE_SIDEBAR',2,true);
		$this->set('sidebar_banner',$sidebar_banner);


		//instruction points
		$this->set('INSTRUCTION_POINTS',$instruction_points);
	}

	private function popup_setformation()
	{
		$userData = $this->userData;
		$lineup = $this->Game->getLineup($userData['team']['id']);
		$next_match =  $this->Game->getNextMatch(@$this->userData['team']['team_id']);
		if(intval(@$lineup['lineup'][0]['matchday']) == $next_match['match']['matchday'])
		{
			return false;
		}

		return true;
	}

	private function saveTactics($next_match,$instruction_points,$upcoming_matchday){

		$is_ok = false;

		
		
		$this->Game->query("DELETE FROM ".$_SESSION['ffgamedb'].".game_team_instructions 
							WHERE game_team_id = {$this->userData['team']['id']} AND matchday = {$upcoming_matchday}");

		$sql = "INSERT INTO ".$_SESSION['ffgamedb'].".game_team_instructions
				(game_team_id,
				matchday,
				player_id,
				instruction_id,
				amount) VALUES ";
		
		$total_spend = 0;
		for($i=0;$i<sizeof($this->request->data['instruction']);$i++){

			//maksimal instruction poin yang bisa dibagikan hanya 5pts
			$this->request->data['points'][$i] = intval($this->request->data['points'][$i]);
			if($this->request->data['points'][$i] > 5){
				$this->request->data['points'][$i] = 5;
			}else if($this->request->data['points'][$i] < 0){
				$this->request->data['points'][$i] = 0;
			}else{
				//do nothing
			}

			$total_spend += $this->request->data['points'][$i];
			
			if($i>0){
				$sql.=",";
			}
			$sql.="({$this->userData['team']['id']},
					{$upcoming_matchday},
					'{$this->request->data['player'][$i]}',
					{$this->request->data['instruction'][$i]},
					{$this->request->data['points'][$i]})";

		}
		$sql.="
				ON DUPLICATE KEY UPDATE
				amount = VALUES(amount);";


		if($total_spend <= $instruction_points){
			$is_ok = true;
		}
		if($is_ok){
			$this->Game->query($sql);
		}
		return $is_ok;
	}
	//get upcoming matchday
	//these is useful for saving lineup / formations and tactics.
	private function getUpcomingMatchday(){
		$check = $this->Game->query("SELECT * FROM (SELECT matchday,MAX(is_processed) AS match_status
											FROM ".$_SESSION['ffgamedb'].".game_fixtures GROUP BY matchday) a 
											WHERE match_status = 0 ORDER BY matchday ASC LIMIT 1;");
		
		try{
			$upcoming_matchday = @$check[0]['a']['matchday'];
		}catch(Exception $e){
			$upcoming_matchday = 0;
		}
		
		return $upcoming_matchday;
	}
	// instruction points is 1% of the average weekly points + minimum instruction points (10)
	// for example, if average weekly points is 1000, then the instruction points is 10
	private function getInstructionPoints(){

		$team_id = $this->userDetail['Team']['id'];
		$minimum_ip = Configure::read('MINIMUM_INSTRUCTION_POINTS');

		$rs = $this->Game->query("SELECT (points+extra_points) as total_points 
											FROM ".Configure::read('FRONTEND_SCHEMA').".points a
											WHERE team_id={$team_id} 
											AND league = '{$_SESSION['league']}' 
											LIMIT 1");

		
		
		$total_points = intval(@$rs[0][0]['total_points']);

		
		$total_matches =  $this->Game->query("SELECT matchday FROM ".$_SESSION['ffgamedb'].".game_fixtures a
											 WHERE period='FullTime' AND is_processed = 1 AND session_id=2014
											 ORDER BY matchday DESC LIMIT 1;");

		$total_matchday = intval(@$total_matches[0]['a']['matchday']);
		

		if($total_matchday > 0){
			$average_points = round($total_points / $total_matchday);	
		}else{
			$average_points = 0;
		}
		
		
		$instruction_points = round($average_points * 0.01) + $minimum_ip;
		
		/*if($instruction_points < $minimum_ip){
			$instruction_points = $minimum_ip;
		}*/

		return $instruction_points;
	}

	private function getCurrentTactics($upcoming_matchday){
		if($upcoming_matchday==null){
			$upcoming_matchday = 0;
		}
		$game_team_id = $this->userData['team']['id'];
		$sql = "SELECT * FROM ".$_SESSION['ffgamedb'].".game_team_instructions a
				WHERE game_team_id = {$game_team_id} 
				AND matchday={$upcoming_matchday} 
				LIMIT 16";
		$rs = $this->Game->query($sql);

		$tactics = array();
		for($i=0;$i<sizeof($rs);$i++){
			$tactics[] = $rs[$i]['a'];
		}
		return $tactics;
	}
}
