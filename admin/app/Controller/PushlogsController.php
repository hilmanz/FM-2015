<?php
/**
* Game Fixtures Monitoring.
*
*/
App::uses('AppController', 'Controller');


class PushlogsController extends AppController {

/**
 * Controller name
 *
 * @var string
 */
	public $name = 'Pushlogs';

	public function index(){
		
	}
	/**
	*	retrieve logs
	*/
	public function logs(){
		$this->layout='ajax';

		$this->loadModel('Pushlogs');
		$rs = $this->Pushlogs->find('all',array(
				'fields'=>array('id',
								'push_date',
								'feedType',
								'productionServer',
								'encoding',
								'saved_file'),
				'order'=>array('id DESC'),
				'limit'=>10,
			));
		$this->set('response',array('status'=>1,
									'data'=>$rs,
									'last_id'=>0));
	}

	public function show_list(){
		$this->loadModel('Pushlogs');

		$feedType = $this->Pushlogs->query("SELECT feedType FROM pushlogs GROUP BY feedType");
		$feed_types = array();
		
		foreach($feedType as $ft){
			$feed_types[] =$ft['pushlogs']['feedType'];
		}
		unset($feedType);

		$this->set('types',$feed_types);

		if(isset($this->request->query['feedtype'])){

			$this->paginate = array(
							'conditions'=>array(
								'feedType'=>$this->request->query['feedtype']
							),
							'fields'=>array('id',
											'push_date',
											'feedType',
											'productionServer',
											'encoding',
											'saved_file'),
							'order'=>array('id DESC'),
							'limit'=>20,
						);

			$this->set('current',$this->request->query['feedtype']);
		}else{
			$this->paginate = array(
							'fields'=>array('id',
											'push_date',
											'feedType',
											'productionServer',
											'encoding',
											'saved_file'),
							'order'=>array('id DESC'),
							'limit'=>20,
						);
		}
		$rs = $this->paginate('Pushlogs');
		$this->set('data',$rs);
	}
	/*
	* displaying last hour performance.
	*/
	public function performance(){
		$this->loadModel('Pushlogs');
		$last_hit = $this->Pushlogs->find('first',array(
				'conditions'=>array('feedType'=>'F9'),
				'fields'=>array('id','push_date'),
				'limit'=>1,
				'order'=>array('id DESC')
			));
		$last_time = strtotime($last_hit['Pushlogs']['push_date']);
		
		//we need the starting hour
		$start_hour_ts = $last_time - (60*60*4);
		$start_hour = (date("Y-m-d H:i:s",$start_hour_ts));
		$sql = "SELECT UNIX_TIMESTAMP(push_date) AS ts,push_date
				FROM pushlogs WHERE push_date > '{$start_hour}' 
				LIMIT 100000;";
		$logs = $this->Pushlogs->query($sql);
		$rs = array();
		
		for($i=1;$i<sizeof($logs);$i++){
			$dt = date("d/m H:i:s",$logs[$i][0]['ts']);
			$d = $logs[$i][0]['ts'] - $logs[$i-1][0]['ts'];
			$m = round($d/60);
			$rs[] = array('dt'=>$dt,'m'=>$m,'d'=>$d);
		}
		$this->set('rs',$rs);
	}
}
