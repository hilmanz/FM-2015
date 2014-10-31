<?php
/**
* Game Fixtures Monitoring.
*
*/
App::uses('AppController', 'Controller');


class AnalyticsController extends AppController {

/**
 * Controller name
 *
 * @var string
 */
	public $name = 'Analytics';

	public function beforeFilter(){
		parent::beforeFilter();
		$this->loadModel('Analytics');
	}

	public function index(){
		//get the top teams
		$team_used = $this->Analytics->team_used();
		$this->set('team_used',$team_used);

		//get players ordered by the most usage
		$player_used = $this->Analytics->player_used();
		$this->set('player_used',$player_used);		

		//get formation used
		$formation_used = $this->Analytics->formation_used();
		$this->set('formation_used',$formation_used);


		//transfer window list
		$transfer_window = $this->Analytics->transfer_window();
		$this->set('transfer_window',$transfer_window);

		//total player epl
		$rs_epl = $this->Analytics->total_active_player_epl();
		$this->set('rs_epl',$rs_epl);

		//total player ita
		$rs_ita = $this->Analytics->total_active_player_ita();
		$this->set('rs_ita',$rs_ita);
		/*
		//most bought player
		$most_buy = $this->Analytics->transfer_most_buy();
		$this->set('most_buy',$most_buy);

		//most sold player
		$most_sold = $this->Analytics->transfer_most_sold();
		$this->set('most_sold',$most_sold);
		*/
	}

	public function daily_registrations(){
		$rs = $this->Analytics->daily_registrations();
		
		for($i=0;$i<sizeof($rs);$i++){
			if($rs[$i]['dt']!=null){
				$categories[] = $rs[$i]['dt'];
				$xValue[] = intval($rs[$i]['total']);	
			}
		}
		$this->layout = "ajax";
		$this->set('response',
					array('status'=>1,
						  'data'=>array('categories'=>$categories,
						  				'values'=>$xValue)));
		$this->render('response');
	}

	public function active_players($league = 'epl')
	{
		$rs = $this->Analytics->active_user($league);
		
		for($i=0;$i<sizeof($rs);$i++){
			if($rs[$i]['dt']!=null){
				$categories[] = $rs[$i]['dt'];
				$xValue[] = intval($rs[$i]['total']);	
			}
		}
		$this->layout = "ajax";
		$this->set('response',
					array('status'=>1,
						  'data'=>array('categories'=>$categories,
						  				'values'=>$xValue)));
		$this->render('response');
	}

	public function daily_registration_players_epl(){
		$rs = $this->Analytics->daily_registration_players_epl();
		
		for($i=0;$i<sizeof($rs);$i++){
			if($rs[$i]['dt']!=null){
				$categories[] = $rs[$i]['dt'];
				$xValue[] = intval($rs[$i]['total']);	
			}
		}
		$this->layout = "ajax";
		$this->set('response',
					array('status'=>1,
						  'data'=>array('categories'=>$categories,
						  				'values'=>$xValue)));
		$this->render('response');
	}

	public function daily_registration_players_ita(){
		$rs = $this->Analytics->daily_registration_players_ita();
		
		for($i=0;$i<sizeof($rs);$i++){
			if($rs[$i]['dt']!=null){
				$categories[] = $rs[$i]['dt'];
				$xValue[] = intval($rs[$i]['total']);	
			}
		}
		$this->layout = "ajax";
		$this->set('response',
					array('status'=>1,
						  'data'=>array('categories'=>$categories,
						  				'values'=>$xValue)));
		$this->render('response');
	}

	//getting the most buy players
	public function most_buy($tw_id){
		$rs = $this->Analytics->transfer_most_buy($tw_id);
		$transfer_window = $this->Analytics->getTransferWindowDetail($tw_id);
		$this->layout = "ajax";
		$this->set('response',
					array('status'=>1,
						  'data'=>$rs,
						  'window'=>$transfer_window));

		$this->render('response');
	}
	//getting the most sold players
	public function most_sold($tw_id){
		$rs = $this->Analytics->transfer_most_sold($tw_id);
		$transfer_window = $this->Analytics->getTransferWindowDetail($tw_id);
		$this->layout = "ajax";
		$this->set('response',
					array('status'=>1,
						  'data'=>$rs,
						  'window'=>$transfer_window));

		$this->render('response');
	}

	//retrieving unique user daily stats.
	//we can get the data from fantasy.activity_logs
	public function unique_user_daily(){
		$rs = $this->Analytics->unique_user_daily();
		
		for($i=0;$i<sizeof($rs);$i++){
			$categories[] = $rs[$i]['the_date'];
			$xValue[] = intval($rs[$i]['total']);
		}
		$this->layout = "ajax";
		$this->set('response',
					array('status'=>1,
						  'data'=>array('categories'=>$categories,
						  				'values'=>$xValue)));
		$this->render('response');
	}
	
	//retrieving unique user monthly stats.
	//we can get the data from fantasy.activity_logs
	public function unique_user_weekly(){
		$rs = $this->Analytics->unique_user_weekly();
		
		for($i=0;$i<sizeof($rs);$i++){
			$categories[] = $rs[$i]['the_date'].'('.$rs[$i]['the_week'].')';
			$xValue[] = intval($rs[$i]['total']);
		}

		$this->layout = "ajax";
		$this->set('response',
					array('status'=>1,
						  'data'=>array('categories'=>$categories,
						  				'values'=>$xValue)));
		$this->render('response');
	}
	//retrieving unique user monthly stats.
	//we can get the data from fantasy.activity_logs
	public function unique_user_monthly(){
		$rs = $this->Analytics->unique_user_monthly();
		$month = array('','Jan','Feb','Mar','Apr','Mei','Jun','Jul','Aug','Sep','Oct','Nov','Dec');
		for($i=0;$i<sizeof($rs);$i++){
			$categories[] = $month[$rs[$i]['the_month']].'/'.$rs[$i]['the_year'];
			$xValue[] = intval($rs[$i]['total']);
		}
		$this->layout = "ajax";
		$this->set('response',
					array('status'=>1,
						  'data'=>array('categories'=>$categories,
						  				'values'=>$xValue)));
		$this->render('response');
	}

	/*
	* team usage stats
	*/
	public function team_used(){
		$rs = $this->Analytics->team_used();
		
		$this->layout = "ajax";
		$this->set('response',
					array('status'=>1,
						  'data'=>$rs));
		$this->render('response');
	}
}
