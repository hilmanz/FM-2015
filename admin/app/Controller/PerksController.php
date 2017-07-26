<?php

App::uses('AppController', 'Controller');
/*
* Manage Digital Coupon
* Pad
*/

class PerksController extends AppController {

/**
 * Controller name
 *
 * @var string
 */
	public $name = 'Perks';
	private $error_codes = array(); //an array of invalid coupon codes.
	private $success_codes = array();
	//display the available coupons, 20 items each
	public function index(){
		$this->loadModel('Admin');
		$this->loadModel('MasterPerk');
		
		$this->paginate = array('limit'=>100);
		$this->set('data',$this->Paginate('MasterPerk'));
	}

	public function create(){
		$this->loadModel('MasterPerk');
		if($this->request->is('post')){
			
			$data = array();
			for($i=0;$i<sizeof($this->request->data['attributes']);$i++){
				$attr = $this->request->data['attributes'][$i];
				$val = $this->request->data['attribute_values'][$i];
				$data[$attr] = $val;
			}
			$this->MasterPerk->create();
			$rs = $this->MasterPerk->save(
						array(
							'perk_name'=>$this->request->data['perk_name'],
							'name'=>$this->request->data['name'],
							'description'=>$this->request->data['description'],
							'amount'=>$this->request->data['amount'],
							'data'=>serialize($data),
						)
					);
			if(isset($rs['MasterPerk'])){
				$this->Session->setFlash("New perk has been created successfully !");
			}else{
				$this->Session->setFlash("Unable to create the perk, please try again later !");
			}
		}
	}
	public function view($perk_id){
		$this->loadModel('MasterPerk');
		
		if($this->request->is('post')){
			$perk = $this->MasterPerk->findById($perk_id);
			$data = array();
			for($i=0;$i<sizeof($this->request->data['attributes']);$i++){
				$attr = $this->request->data['attributes'][$i];
				$val = $this->request->data['attribute_values'][$i];
				$data[$attr] = $val;
			}
			$this->MasterPerk->id = $perk_id;
			$rs = $this->MasterPerk->save(
						array(
							'perk_name'=>$this->request->data['perk_name'],
							'name'=>$this->request->data['name'],
							'description'=>$this->request->data['description'],
							'amount'=>$this->request->data['amount'],
							'data'=>serialize($data),
						)
					);
			if(isset($rs['MasterPerk'])){
				$this->Session->setFlash("`{$perk['MasterPerk']['name']}` has been updated successfully !");
			}else{
				$this->Session->setFlash("Unable to update the perk, please try again later !");
			}
		}else{
			$perk = $this->MasterPerk->findById($perk_id);	
		}
		$this->set('perk',$perk);
	}

}
