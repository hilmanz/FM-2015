<?php
/**
* OPTA Valde HTTP Push EndPoint Implementation
*/
App::uses('AppController', 'Controller');
App::uses('Folder', 'Utility');
App::uses('File', 'Utility');
require_once APP.DS.'Vendor'.DS.'common.php';
class ServiceController extends AppController {

/**
 * Controller name
 *
 * @var string
 */
	public $name = 'Service';

	public function index(){

		$headers = apache_request_headers();
		$post_data = file_get_contents('php://input') ;
		$posts = array (
		'feedType' => isset($headers['x-meta-feed-type']) ? $headers['x-meta-feed-type'] : '',
		'feedParameters' => isset($headers['x-meta-feed-parameters']) ? $headers['x-meta-feed-parameters'] : '',
		'defaultFilename' => isset($headers['x-meta-default-filename']) ? $headers['x-meta-default-filename'] : '',
		'deliveryType' => isset($headers['x-meta-game-id']) ? $headers['x-meta-game-id'] : '',
		'messageDigest' => md5($post_data),
		'competitionId' => isset($headers['x-meta-competition-id']) ? $headers['x-meta-competition-id'] : '',
		'seasonId' => isset($headers['x-meta-season-id']) ? $headers['x-meta-season-id'] : '',
		'gameId' => isset($headers['x-meta-game-id']) ? $headers['x-meta-game-id'] : '',
		'gameSystemId' => isset($headers['x-meta-gamesystem-id']) ? $headers['x-meta-gamesystem-id'] : '',
		'matchday' => isset($headers['x-meta-matchday']) ? $headers['x-meta-matchday'] : '',
		'awayTeamId' => isset($headers['x-meta-away-team-id'])?$headers['x-meta-away-team-id']:'',
		'homeTeamId' => isset($headers['x-meta-home-team-id']) ? $headers['x-meta-home-team-id'] : '',
		'gameStatus' => isset($headers['x-meta-game-status']) ? $headers['x-meta-game-status'] : '',
		'language' => isset($headers['x-meta-language']) ? $headers['x-meta-language'] : '',
		'productionServer' => isset($headers['x-meta-production-server']) ? $headers['x-meta-production-server'] : '',
		'productionServerTimeStamp' => isset($headers['x-meta-production-server-timestamp']) ? $headers['x-meta-production-server-timestamp'] : '',
		'productionServerModule' => isset($headers['x-meta-production-server-module']) ? $headers['x-meta-production-server-module'] : '',
		'mimeType' => 'text/xml',
		'encoding' => isset($headers['x-meta-encoding']) ? $headers['x-meta-encoding'] : '',
		'content' => $post_data
		);
		header('Content-type: text/xml');
		if(@$this->saveXMLFile($posts)){
			$this->set('response',array('status'=>1,'message'=>'ok'));	
		}else{
			$this->set('response',array('status'=>0,'message'=>'unable to save the xml'));	
		}
		
	}
	private function saveXMLFile($posts){
		$this->loadModel('Pushlogs');
		$filename = ($posts['defaultFilename']!='') ? $posts['defaultFilename'] : $posts['feedType'].'-'.
													$posts['competitionId'].'-'.
													$posts['sessionId'].'-'.
													$posts['gameId'].'-'.
													str_replace(' ','_',$posts['feedParameters']).'.xml';
		$fileFolder  = Configure::read('opta_file_folder');
		$file = new File($fileFolder.$filename, true, 0644);
		
		if($file->write($posts['content'],'w')){
			$posts['push_date'] = date("Y-m-d H:i:s");
			$posts['saved_file'] = $filename;
			$this->Pushlogs->create();
			if($this->Pushlogs->save($posts)){
				if($posts['feedType']=='F9'){
					@curlGet(Configure::read('worker_api'),array('file'=>$filename));
				}
				return true;
			}
		}
		
	}
	
}
