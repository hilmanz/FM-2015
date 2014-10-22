<?php
/**
 * Leaderboard Controller

 */
App::uses('AppController', 'Controller');
App::uses('Sanitize', 'Utility');

class LeaderboardController extends AppController {

/**
 * Controller name
 *
 * @var string
 */
	public $name = 'Leaderboard';

/**
 * This controller does not use a model
 *
 * @var array
 */
	public $uses = array();
	protected $userData;

	public function beforeFilter(){
		parent::beforeFilter();
		
		if(!$this->hasTeam()){
			$this->redirect('/login/expired');
		}
		$user = $this->userDetail;
		$this->set('user',$user['User']);
		$this->set('club',$user['Team']);
		$this->getFinanceSummary($this->userData['fb_id']);

		//banners
	
		$long_banner = $this->getBanners('LEADERBOARD_TOP',2,true);
		$this->set('long_banner',$long_banner);
		$long_banner2 = $this->getBanners('LEADERBOARD_BOTTOM',2,true);
		$this->set('long_banner2',$long_banner2);
	}
	public function hasTeam(){
		$userData = $this->getUserData();
		if(is_array($userData['team'])){

			return true;
		}
	}
	private function getTier($myRank){
		$ranks = $this->Point->query("SELECT MAX(rank) as total FROM points a
									INNER JOIN teams b
									ON a.team_id = b.id 
									AND a.league = b.league
									WHERE a.league = '{$_SESSION['league']}';");
		
		$max_rank = intval($ranks[0][0]['total']);
		$q1 = ceil(0.25 * $max_rank);
		$q2 = ceil(0.5 * $max_rank);
		$q3 = ceil(0.75 * $max_rank);
		
		if($myRank <= $q1){
			return 1;
		}else if($myRank > $q1 && $myRank <= $q2){
			return 2;
		}else if($myRank > $q2 && $myRank <= $q3){
			return 3;
		}else{
			return 4;
		}
		
	}
	public function index(){
		$this->loadModel("Point");
	    $this->loadModel('User');
	    $this->loadModel('Weekly_point');
	    $this->loadModel('Weekly_rank');
	  
	    $next_match = $this->Game->getNextMatch($this->userData['team']['team_id']);
	    $this->set('next_match',$next_match);
		//define week.
		if(!isset($this->request->query['week'])){
		    if($next_match['match']['matchday']<=1){
		    	$matchday = 1;
		    }else{
		    	$matchday = $next_match['match']['matchday']-1;
		    }
		}else{
			$matchday = intval($this->request->query['week']);
		}
		
		$this->Weekly_point->virtualFields['TotalPoints'] = 'SUM(Weekly_point.points + Weekly_point.extra_points)';
		
		$this->paginate = array(
								'conditions'=>array('matchday'=>$matchday,'league'=>$_SESSION['league']),
								'limit'=>100,
								'order'=> array('rank'=>'asc')
							);


	 
	    $rs = $this->paginate('Weekly_rank');
	    
	    $game_id = '';
	 
	  	if(sizeof($rs)>0){
	  		foreach($rs as $n=>$r){
		    	$poin = $this->Weekly_point->find('first',array(
		    									'conditions'=>array(
		    										'Weekly_point.team_id'=>$r['Weekly_rank']['team_id'],
		    										'matchday'=>$matchday,
		    										'Weekly_point.league'=>$_SESSION['league']
		    									)
		    								));
		    	$rs[$n]['Weekly_point'] = $poin['Weekly_point'];
		    	$rs[$n]['Weekly_point']['points'] = $poin['Weekly_point']['TotalPoints'];
		    	$rs[$n]['Point'] = $rs[$n]['Weekly_point'];
		    	$rs[$n]['Team'] = $poin['Team'];
		    	//get manager's name
		    	$manager = $this->User->findById($poin['Team']['user_id']);
		    	$game_team = $this->Game->query("SELECT b.id as id FROM ".$_SESSION['ffgamedb'].".game_users a
							INNER JOIN ".$_SESSION['ffgamedb'].".game_teams b
							ON a.id = b.user_id WHERE fb_id = '{$manager['User']['fb_id']}' LIMIT 1;");

		    	$rs[$n]['Manager'] = @$manager['User'];
		    	
		    	$rs[$n]['manager_id'] = $game_team[0]['b']['id'] + intval(Configure::read('RANK_RANDOM_NUM'));

		    }
	  	}
	   
	    
	    //assign team ranking list to template
	    $this->set('team',$rs);
	    

	    $myRank = $this->Weekly_rank->find('first',
	    									array('conditions'=>
											array('team_id'=>$this->userDetail['Team']['id'],
												'matchday'=>$matchday)));
	    
	    $this->set('matchday',$matchday);
	    $this->set('rank',$myRank['Weekly_rank']['rank']);

	    $this->set('tier',$this->getTier($myRank['Weekly_rank']['rank']));


	   	
	}
	
	public function monthly(){
		$this->loadModel("Point");
	    $this->loadModel('User');
	    $this->loadModel('Weekly_point');
	    $this->loadModel('Monthly_point');
	   
	   	if(isset($this->request->query['m']) && isset($this->request->query['y'])){
	   		$current_month = intval($this->request->query['m']);
	  		$current_year = intval($this->request->query['y']);
	   	}else{
	   		$current_month = date('m');
	  		$current_year = date('Y');
	  		$session_var_name = 'weekly_point'.'-'.$current_month.'-'.$current_year;
	  		if(!isset($_SESSION[$session_var_name])){
	  			$check_count = $this->Weekly_point->find('count',array(
		  			'conditions'=>array('MONTH(matchdate)'=>$current_month,
		  								'YEAR(matchdate)'=>$current_year)
		  		));
		  		$_SESSION[$session_var_name] = $check_count;
		  		
	  		}else{
	  			$check_count = intval($_SESSION[$session_var_name]);
	  			
	  		}
	  	

		  	//kalau bulan ini tidak ada data, kita pakai data bulan lalu.
		  	if($check_count==0){
		  		$current_month -= 1;
		  		if($current_month==0){
		  			$current_month = 12;
		  			$current_year -= 1;
		  		}
		  	}
	   	}
	  	
	   
	  	

	  	$available_months = $this->Monthly_point->query("SELECT 
														bln,
														thn 
														FROM monthly_points
														WHERE league='{$_SESSION['league']}'
														GROUP BY thn,bln;");
	  	

	  	$this->set('available_months',$available_months);

	  	
		$this->paginate = array(
			'conditions'=>array('bln'=>$current_month,
	  							'thn'=>$current_year,
	  							'Monthly_point.league'=>$_SESSION['league']),
	        'limit' => 100,
	        'order' => array(
	            'Monthly_point.points' => 'desc'
	        )
	    );
	  	$rs =  $this->paginate('Monthly_point');
	  	

		
	  
	  	foreach($rs as $n=>$r){
	    	$rs[$n]['Point'] = $rs[$n]['Monthly_point'];
	    	
	    	unset($rs[$n]['Monthly_point']);
	    	
	    	//get manager's name
	    	$manager = $this->User->findById($r['Team']['user_id']);
	    	$rs[$n]['Manager'] = @$manager['User'];
	    }

	    $myRank = $this->Monthly_point->find('first',
	    										array('conditions'=>array(	
	    													'Monthly_point.team_id'=>$this->userDetail['Team']['id'],
	    													'Monthly_point.league'=>$_SESSION['league'],
	    													'bln'=>$current_month,
	  														'thn'=>$current_year),

	    										)
	    									);

	    
	    $this->set('team',$rs);
	    $this->set('monthly',true);
	    $this->set('current_month',intval($current_month));
	    $this->set('current_year',intval($current_year));
	    $this->set('rank',$myRank['Monthly_point']['rank']);
	  	$this->set('tier',$this->getTier($myRank['Monthly_point']['rank']));

	}
	public function overall(){
		//$this->render('offline'); //enable these to close the leaderboard page.
		$this->loadModel("Point");
	    $this->loadModel('User');
	    $this->Point->virtualFields['TotalPoints'] = '(Point.points + Point.extra_points)';
	    $this->paginate = array(
	    	'conditions'=>array('NOT'=>array('rank'=>0),'Point.league'=>$_SESSION['league']),
	        'limit' => 100,
	        'order' => array(
	            'Point.rank' => 'asc'
	        )
	    );

	    $rs = $this->paginate('Point');
	   
	    foreach($rs as $n=>$r){
	    	//get manager's name
	    	$manager = $this->User->find('first',array('conditions'=>array('User.id'=>$r['Team']['user_id'],
	    																  'Team.league'=>$_SESSION['league'])));
	    	$rs[$n]['Manager'] = @$manager['User'];
	    }
	    
	    if($rs[0]['Point']['TotalPoints']==0){
	    	$rs = null;
	    	$this->userRank = 0;
	    }
	    $this->set('team',$rs);
	    $this->set('rank',$this->userRank);
	    $this->set('tier',$this->getTier($this->userRank));
	    $this->set('overall',true);
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



	
	public function manager(){
		$manager = $this->getManagerData();

		$rs = $this->getRealManagerPoints();
		$results = array();
		$matchday = 0;
		while(sizeof($rs)>0){
			$t = array_shift($rs);
			$results[] = array(
				'matchday'=>$t['b']['matchday'],
				'team_id'=>$t['a']['team_id'],
				'points'=>$t['a']['overall_points'],
				'team'=>$t['c']['team'],
				'manager'=>$manager[$_SESSION['league']][$t['a']['team_id']]
			);
			$matchday = $t['b']['matchday'];
		}
		
		$this->set('rs',$results);

		$this->set('rank',$this->userRank);
	    $this->set('tier',$this->getTier($this->userRank));
	    $this->set('manager',true);
	    $this->set('matchday',$matchday);
	}
	private function getManagerData(){
		$manager = array('epl'=>array(),'ita'=>array());
		$manager['epl']['t8'] = "Jose Mourinho";
		$manager['epl']['t43'] = "Manuel Pellegrini";
		$manager['epl']['t20'] = "Ronald Koeman";
		$manager['epl']['t21'] = "Sam Allardyce";
		$manager['epl']['t14'] = "Brendan Rodgers";
		$manager['epl']['t1'] = "Louis Van Gaal";
		$manager['epl']['t3'] = "Arsene Wenger";
		$manager['epl']['t80'] = "Gary Monk";
		$manager['epl']['t6'] = "Mauricio Pochettino";
		$manager['epl']['t110'] = "Mark Hughes";
		$manager['epl']['t88'] = "Steve Bruce";
		$manager['epl']['t7'] = "Paul Lambert";
		$manager['epl']['t11'] = "Roberto Martinez";
		$manager['epl']['t35'] = "Alan Irvine";
		$manager['epl']['t13'] = "Nigel Pearson";
		$manager['epl']['t31'] = "Neil Warnock";
		$manager['epl']['t56'] = "Gus Poyet";
		$manager['epl']['t4'] = "Alan Pardew";
		$manager['epl']['t90'] = "Sean Dyche";
		$manager['epl']['t52'] = "Harry Redknapp";

		$manager['ita']['t128'] = "Massimiliano Allegri";
		$manager['ita']['t121'] = "Rudi Garcia";
		$manager['ita']['t603'] = "Sinisa Mihajlovic";
		$manager['ita']['t120'] = "Filippo Inzaghi";
		$manager['ita']['t136'] = "Andrea Stramaccioni";
		$manager['ita']['t129'] = "Stefano Pioli";
		$manager['ita']['t459'] = "Rafael Benitez";
		$manager['ita']['t126'] = "Andrea Mandorlini";
		$manager['ita']['t127'] = "Walter Mazzarri";
		$manager['ita']['t990'] = "Gian Piero Gasperini";
		$manager['ita']['t125'] = "Vincenzo Montella";
		$manager['ita']['t135'] = "Giampiero Ventura";
		$manager['ita']['t695'] = "Maurizio Sarri";
		$manager['ita']['t456'] = "Stefano Colantuono";
		$manager['ita']['t1731'] = "Pierpaolo Bisoli";
		$manager['ita']['t1405'] = "Giuseppe Iachini";
		$manager['ita']['t124'] = "Zdenek Zeman";
		$manager['ita']['t988'] = "Eugenio Corini";
		$manager['ita']['t2182'] = "Eusebio Di Francesco";
		$manager['ita']['t131'] = "Roberto Donadoni";

		return $manager;
	}
	private function getRealManagerPoints(){
		$sql = "SELECT b.matchday,c.name AS team,a.team_id,a.overall_points 
		FROM {$_SESSION['ffgamestatsdb']}.master_match_points a
		INNER JOIN {$_SESSION['ffgamedb']}.game_fixtures b
		ON a.game_id = b.game_id
		INNER JOIN {$_SESSION['ffgamedb']}.master_team c
		ON c.uid = a.team_id ORDER BY matchday ASC,overall_points DESC";
		$rs = $this->Game->query($sql);
		return $rs;
	}
}
