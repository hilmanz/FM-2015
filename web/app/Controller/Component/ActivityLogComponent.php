<?php
App::uses('Component', 'Controller');
class ActivityLogComponent extends Component {
	private $model;
	public function initialize(Controller $controller){
		$controller->loadModel('ActivityLogs');
      
        $this->model = $controller->ActivityLogs;
	}
    public function writeLog($user_id, $activity, $ref_code = NULL) {

        $this->model->create();
        $this->model->save(array(
        		'user_id'=>$user_id,
        		'log_dt'=>date("Y-m-d H:i:s"),
        		'log_type'=>$activity,
                'league'=>$_SESSION['league'],
                'ref_code' => $ref_code
        	));
    }

    // logTime is used for logging the time spent in the website
    // there would be 2 variable to track : 
    // a. initial_ts (unix timestamp)
    // b. last_ts (unix timestamp)
    // time_spent = last_ts - initial_ts
    // initial_ts is resetted when 'LOGIN' activity captured.


    public function logTime($user_id,$session,
                            $reset_time=false){
        $initial_ts = intval($session->read('track_time_initial_ts'));
        $last_ts = intval($session->read('track_time_initial_ts'));
        //pr('yippy');
        if($reset_time){
            //we save the time first to database ?
            //only when the session is still active, and we have the diff_ts exists
            $diff_ts = $last_ts - $initial_ts;
            if($diff_ts > 0){
                //save the time difference to database
                $this->model->query("
                    INSERT IGNORE INTO user_time_spent
                    (user_id,initial_ts,last_ts,time_spent)
                    VALUES
                    ({$user_id},{$initial_ts},{$last_ts},{$diff_ts})
                    ");
            }
            $session->write('track_time_initial_ts',time());
        }else{

            //if initial time is none, and last time logged is also none, we save the time now.
            if($initial_ts==0 && $last_ts==0){
                $session->write('track_time_initial_ts',time());
            }
        }
        $session->write('track_time_current_ts',time());
    }
}