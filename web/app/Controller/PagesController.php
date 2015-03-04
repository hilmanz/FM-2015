<?php
/**
 * Static content controller.
 *
 * This file will render views from views/pages/
 *
 * PHP 5
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @package       app.Controller
 * @since         CakePHP(tm) v 0.2.9
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
App::uses('AppController', 'Controller');

/**
 * Static content controller
 *
 * Override this controller by placing a copy in controllers directory of an application
 *
 * @package       app.Controller
 * @link http://book.cakephp.org/2.0/en/controllers/pages-controller.html
 */
class PagesController extends AppController {

/**
 * Controller name
 *
 * @var string
 */
	public $name = 'Pages';

/**
 * This controller does not use a model
 *
 * @var array
 */
	public $uses = array();

/**
 * Displays a view
 *
 * @param mixed What page to display
 * @return void
 */
	public function beforeFilter(){
		parent::beforeFilter();
		$user = $this->userDetail;
		$this->set('user',$user['User']);
	}
	
	public function display() {
		$path = func_get_args();

		$count = count($path);
		if (!$count) {

			$this->redirect('/');
		}
		$page = $subpage = $title_for_layout = null;

		if (!empty($path[0])) {
			$page = $path[0];
		}
		if (!empty($path[1])) {
			$subpage = $path[1];
		}
		if (!empty($path[$count - 1])) {
			$title_for_layout = Inflector::humanize($path[$count - 1]);
		}
		$this->set(compact('page', 'subpage', 'title_for_layout'));

		//what's happening
		$this->loadModel('Info');
		$this->loadModel('User');
		$info = $this->Info->getLatest($this->User,20);
		
		$this->set('info',$info);
		//-->
		
		//Banner nih
		
		$banners = $this->getBanners('FRONTPAGE',10);
		$this->set('banners',$banners);
		
		$small_banners = $this->getBanners('FRONTPAGE_SMALL_MIDDLE',10,true);
		$this->set('small_banner_1',$small_banners);

		$small_banners = $this->getBanners('FRONTPAGE_SMALL_RIGHT',10,true);
		$this->set('small_banner_2',$small_banners);

		$sidebar_banner = $this->getBanners('FRONTPAGE_SIDEBAR',10,true);
		$this->set('sidebar_banner',$sidebar_banner);

		//-->
		
		if($path[0]=='home'&&$this->userDetail['Team']['id']>0){
			if($this->Session->read('pending_redirect')!=null){
				$redirect_url = $this->Session->read('pending_redirect');
				$this->Session->write('pending_redirect',null);
				$this->redirect($redirect_url);
			}else{
				$this->redirect('/manage/team');
			}
			
		}else if($path[0]=='mobile'){
			$this->layout="mobile";
		}else if($path[0]=='home'){
			$this->getHomeContent();
		}else if($path[0]=='coming_soon'){
			$this->set('disable_login',true);
			$this->layout="default";
		}
		$this->render(implode('/', $path));
	}
	private function getHomeContent(){
		$this->getLastWeekTopManagers();
		$this->getTopPlayers();
	}
	private function getTopPlayers(){
		$top_players = $this->Game->getMasterTopPlayers(5);
		$this->set('top_players',$top_players);
	}
	
	private function getLastWeekTopManagers(){
		$this->loadModel("Point");
	    $this->loadModel('User');
	    $this->loadModel('Weekly_point');
	    $this->loadModel('Weekly_rank');

	    $rs = $this->Session->read('last_week_rank');
	    if(!isset($rs)){
	    	
		    //get the current matchday
		    $sql = "SELECT matchday FROM ffgame.game_fixtures 
		    		WHERE is_processed = 1 AND period='FullTime'  AND session_id = 2014
		    		ORDER BY matchday DESC LIMIT 1;";

		    $match = $this->Game->query($sql);


		    
		    $matchday = $match[0]['game_fixtures']['matchday'];
			
			$this->Weekly_point->virtualFields['TotalPoints'] = 'SUM(Weekly_point.points + Weekly_point.extra_points)';
			
			$this->paginate = array(
									'conditions'=>array('matchday'=>$matchday),
									'limit'=>10,
									'order'=> array('rank'=>'asc')
								);


		 	
		    $rs = $this->paginate('Weekly_rank');
		    
		    $game_id = '';
		 
		  	if(sizeof($rs)>0){
		  		foreach($rs as $n=>$r){
			    	$poin = $this->Weekly_point->find('first',array(
			    									'conditions'=>array(
			    										'Weekly_point.team_id'=>$r['Weekly_rank']['team_id'],
			    										'matchday'=>$matchday
			    									)
			    								));
			    	$rs[$n]['Weekly_point'] = $poin['Weekly_point'];
			    	$rs[$n]['Weekly_point']['points'] = $poin['Weekly_point']['TotalPoints'];
			    	$rs[$n]['Point'] = $rs[$n]['Weekly_point'];
			    	$rs[$n]['Team'] = $poin['Team'];
			    	//get manager's name

			    	$manager = $this->User->findById($poin['Team']['user_id']);
			    	$game_team = $this->Game->query("SELECT b.id as id FROM ffgame.game_users a
								INNER JOIN ffgame.game_teams b
								ON a.id = b.user_id WHERE fb_id = '{$manager['User']['fb_id']}' LIMIT 1;");

			    	$rs[$n]['Manager'] = @$manager['User'];
			    	
			    	$rs[$n]['manager_id'] = $game_team[0]['b']['id'] + intval(Configure::read('RANK_RANDOM_NUM'));

			    }
		  	}
	   
	   
		    //store to cache
		   	$this->Session->write('last_week_rank',$rs);
		}
		for($i=0;$i<sizeof($rs);$i++){
			$pic = $rs[$i]['Manager']['avatar_img'];
			$fb_id = $rs[$i]['Manager']['fb_id'];
			$team_id = str_replace("t_","", $rs[$i]['Team']['team_id']);
			if($pic=='0'){
				$pic = "http://graph.facebook.com/".$fb_id."/picture";
			}else{
				$pic = Configure::read('avatar_web_url')."120x120_".$pic;
			}
			$rs[$i]['pic'] = $pic;
		}
		//assign team ranking list to template
	    $this->set('team',$rs);
	}
	public function versus(){
		
	}
}
