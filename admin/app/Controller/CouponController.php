<?php

App::uses('AppController', 'Controller');
/*
* Manage Digital Coupon
* Pad
*/

class CouponController extends AppController {

/**
 * Controller name
 *
 * @var string
 */
	public $name = 'Coupon';
	private $error_codes = array(); //an array of invalid coupon codes.
	private $success_codes = array();
	//display the available coupons, 20 items each
	public function index(){
		$this->loadModel('Admin');
		$this->Coupon->bindModel(array(
			'belongsTo'=>array('Admin'=>array(
				'foreignKey'=>false,
				'conditions'=>array(
					'Coupon.creator_id=Admin.id'
				)
			))
		));
		$this->paginate = array('limit'=>20,
								'order'=>array('Coupon.id'=>'desc')
								);
		$this->set('data',$this->Paginate('Coupon'));
	}
	public function view($coupon_id){
		$this->loadModel('Admin');
		$this->loadModel('CouponCode');

		if($this->request->is('post')){
			if(is_array($_FILES['img'])){
				$aFile = explode(".",$_FILES['img']['name']);
				$ext = array_pop($aFile);
				$filename = 'voucher_'.Inflector::slug(implode('_',$aFile)).'.'.$ext;
				if(move_uploaded_file($_FILES['img']['tmp_name'],
									Configure::read('avatar_img_dir').$filename)){
					$this->request->data['Coupon']['img'] = $filename;
					
					$this->Coupon->id = $coupon_id;
					$rs = $this->Coupon->save(array('img'=>$filename));
					$this->Session->setFlash("New picture has been uploaded successfully !");
					
				}else{
					$this->Session->setFlash("Please upload the Coupon/Voucher image !");
				}
			}

			$this->Coupon->id = $coupon_id;
			$rs = $this->Coupon->save($this->request->data['Coupon']);

			if(isset($rs['Coupon'])){
				$this->Session->setFlash("The Coupon/Voucher has been updated successfully !");
			}else{
				$this->Session->setFlash("Cannot update the data, please try again later !");
			}
		}

		$this->Coupon->bindModel(array(
			'belongsTo'=>array('Admin'=>array(
				'foreignKey'=>false,
				'conditions'=>array(
					'Coupon.creator_id=Admin.id'
				)
			))
		));
		$coupon = $this->Coupon->findById($coupon_id);

		$coupon_count = $this->CouponCode->find('count',
												array('conditions'=>array('coupon_id'=>$coupon_id)));
		$this->set('coupon',$coupon);
		$this->set('coupon_count',$coupon_count);
	}
	public function create(){
		if($this->request->is('post')){
			if(is_array($_FILES['img'])){
				$aFile = explode(".",$_FILES['img']['name']);
				$ext = array_pop($aFile);
				$filename = 'voucher_'.Inflector::slug(implode('_',$aFile)).'.'.$ext;
				if(move_uploaded_file($_FILES['img']['tmp_name'],
									Configure::read('avatar_img_dir').$filename)){
					$this->request->data['Coupon']['img'] = $filename;
					$this->request->data['Coupon']['created_dt'] = date("Y-m-d H:i:s");
					$this->Coupon->create();
					$rs = $this->Coupon->save($this->request->data['Coupon']);
					if(isset($rs['Coupon']['id']) && $rs['Coupon']['id'] > 0){
						$this->Session->setFlash("New Coupon/Voucher has been created successfully !");
					}else{
						$this->Session->setFlash("Please upload the Coupon/Voucher image !");
					}
				}else{
					$this->Session->setFlash("Please upload the Coupon/Voucher image !");
				}
			}
		}

	}
	public function download($coupon_id){
		$info = $this->Coupon->findById($coupon_id);

		// The user will receive a PDF to download
		header('Content-type: plain/text');
		// File will be called downloaded.pdf
		header('Content-Disposition: attachment; filename="'.
					Inflector::slug($info['Coupon']['vendor_name']).'-'.
					$info['Coupon']['id'].
					'-'.
					date("Ymdhis").
					'.csv"');


		$this->loadModel('CouponCode');
		$start = 0;

		while(1){
			$coupon = $this->CouponCode->find('all',array(
				'conditions'=>array(
					'coupon_id'=>$coupon_id,
					'n_status'=>0,
					'paid'=>0
				),
				'offset'=>$start,
				'limit'=>100
			));
			$start+=100;
			if(sizeof($coupon)==0){
				break;
			}
			for($i=0;$i<sizeof($coupon);$i++){
				print $coupon[$i]['CouponCode']['coupon_code'].PHP_EOL;
			}
		}
		die();
	}
	/*
	* page for generating unique voucher codes
	*/
	public function generate($id){
		$coupon = $this->Coupon->findById($id);
		$this->set('coupon',$coupon);

	}
	public function ajax_generate($id){
		$n_total = intval($this->request->query['total']);
		$n_length = strlen($id);
		$sql = "";
		for($i=0;$i<$n_total;$i++){
			if($sql==""){
				$sql.="INSERT INTO coupon_codes
									(coupon_id,coupon_code,created_dt,redeem_dt,game_team_id,n_status)
									VALUES";
			}
			set_time_limit(30);
			while(1){
				$code = $id.rand(0,9).rand(0,9).rand(0,9).rand(0,9).rand(0,9).rand(0,9).rand(0,9).
					rand(0,9).rand(0,9).rand(0,9).rand(0,9);
				$rs = $this->Coupon->query("SELECT id FROM coupon_codes 
										WHERE coupon_code = '{$code}' LIMIT 1");
				if(sizeof($rs)==0){
					if($i > 0){
						$sql.=",";
					}
					$sql.="({$id},{$code},NOW(),NULL,0,0)";
					break;
				}
			}
		}

		$this->layout = "ajax";
		

		if(strlen($sql)>0){
			$this->Coupon->query($sql,false);
			$this->set('response',array('status'=>1,'sql'=>$sql));
		}else{
			$this->set('response',array('status'=>0));
		}
		
		
		$this->render('response');
	}

	/*
	* user will upload the csv file, and we parse the csv, and update the code usage.
	* CSV Format : 
	* code,purchase_date
	*/
	public function update_data($coupon_id){
		$coupon = $this->Coupon->findById($coupon_id);
		$this->set('coupon',$coupon);
	}
	public function import_csv($coupon_id){
		
		if(move_uploaded_file($_FILES['csv']['tmp_name'], 
							Configure::read('CSV_DIR').Inflector::slug($_FILES['csv']['name']))){
			$this->Session->setFlash("The file is uploaded successfully !");
			$this->import_data($coupon_id,
								Configure::read('CSV_DIR').Inflector::slug($_FILES['csv']['name'])
								);
		}else{
			$this->Session->setFlash("Cannot import the file, please try again later !");
		}
		$coupon = $this->Coupon->findById($coupon_id);
		$this->set('coupon',$coupon);
	}

	private function import_data($coupon_id,$filename){
		$fp = fopen($filename,"r");
		while(!feof($fp)){
			$str = fgets($fp,4096);
			$this->update_from_csv($coupon_id,$str);
		}
		fclose($fp);
		$this->set('success_codes',$this->success_codes);
		$this->set('error_codes',$this->error_codes);

	}
	private function update_from_csv($coupon_id,$str){
		$str = str_replace("\"","",$str);
		$str = str_replace(";",",",$str);
		$arr = explode(",",$str);

		$this->loadModel('CouponCode');

		//1. make sure that the coupon code is valid.
		$code = $this->CouponCode->find('first',array('conditions'=>array(
			'coupon_id'=>$coupon_id,
			'coupon_code'=>$arr[0],
			'paid'=>0
		)));

		if($code['CouponCode']['coupon_code']==$arr[0] && strlen($code['CouponCode']['coupon_code']) > 10){
			$this->CouponCode->id = $code['CouponCode']['id'];
			$rs = $this->CouponCode->save(array(
				'paid'=>1,
				'paid_dt'=>date("Y-m-d H:i:s",strtotime($arr[1]))
			));
			if(!$rs){
				$this->error_codes[] = array('code'=>$arr[0],
											'purchase_date'=>$arr[1],
											'reason'=>'unable to update the code status');	
			}else{
				$this->success_codes[] = array('code'=>$arr[0],
											'purchase_date'=>$arr[1]);	
				return true;
			}
		}else{
			$this->error_codes[] = array('code'=>$arr[0],
										'purchase_date'=>$arr[1],
										'reason'=>'the code is not found or already been paid');
		}

	}

	/*
	* ajax method for retrieving the redeemed code 
	*/
	public function ajax_code_redeemed($coupon_id){
		$start = intval($this->request->query['start']);
		$this->loadModel('CouponCode');
		$db = Configure::read('DB');

		$coupon = $this->CouponCode->find('all',array(
				'conditions'=>array(
					'coupon_id'=>$coupon_id,
					'n_status'=>1
				),
				'start'=>$start,
				'limit'=>100
			));
		for($i=0;$i<sizeof($coupon);$i++){
			$team = $this->CouponCode->query("SELECT a.fb_id,a.name,a.email,a.phone_number,b.team_name,d.id
												FROM fantasy.users a
												INNER JOIN fantasy.teams b
												ON b.user_id = a.id
												INNER JOIN ".$_SESSION['ffgamedb'].".game_users c
												ON c.fb_id = a.fb_id
												INNER JOIN ".$_SESSION['ffgamedb'].".game_teams d
												ON d.user_id = c.id
												WHERE d.id = {$coupon[$i]['CouponCode']['game_team_id']} 
												LIMIT 1;");

			$coupon[$i]['User'] = array(
					'fb_id'=>$team[0]['a']['fb_id'],
					'name'=>$team[0]['a']['name'],
					'email'=>$team[0]['a']['email'],
					'phone_number'=>$team[0]['a']['phone_number'],
					'team_name'=>$team[0]['b']['team_name'],
					'user_id'=>$team[0]['d']['id']+Configure::read('RANK_RANDOM_NUM')
				);
			unset($team);
		}
		$this->set('response',array('status'=>1,'data'=>$coupon,'total_rows'=>sizeof($coupon)));
		$this->layout="ajax";
		$this->render('response');
	}

}
