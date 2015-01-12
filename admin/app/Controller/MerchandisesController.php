<?php
/**
* Game Fixtures Monitoring.
*
*/
App::uses('AppController', 'Controller');
App::uses('Sanitize', 'Utility');
App::uses('File', 'Utility');
App::uses('Folder', 'Utility');

class MerchandisesController extends AppController {

/**
 * Controller name
 *
 * @var string
 */
	public $name = 'Merchandises';
	public $components = array('Thumbnail');
	public function index(){
		
	}
	public function categories(){
		$this->loadModel('MerchandiseCategory');
		$this->loadModel('MerchandiseItem');
		$category = $this->MerchandiseCategory->find('all',array('limit'=>100));
		$this->set('rs',$category);

	}

	public function get_items(){
		$this->layout = 'ajax';
		$this->loadModel('MerchandiseItem');
		$this->loadModel('MerchandiseOrder');
		$start = intval(@$this->request->query['start']);
		$limit = 20;
		$this->MerchandiseItem->bindModel(
			array('belongsTo'=>array('MerchandiseCategory'))
		);
		$rs = $this->MerchandiseItem->find('all',array(
											'conditions'=>array(
												'n_status != 2'
											),
											'offset'=>$start,
											'limit'=>$limit
										));
		
		//for each items, we need to calculate its stock availability.
		//so we need the total purchased item so far each.
		for($i=0; $i<sizeof($rs);$i++){
			
			$available_item = $rs[$i]['MerchandiseItem']['stock'];
			$rs[$i]['stock'] = $available_item;
		}


		$this->set('response',array('status'=>1,'data'=>$rs,'next_offset'=>$start+$limit,'rows_per_page'=>$limit));
		$this->render('response');
		
	}

	public function remove_items()
	{
		$this->layout = 'ajax';
		$id = $this->request->query['id'];
		$this->loadModel('MerchandiseItem');
		$rs_update = $this->MerchandiseItem->updateAll(
					    array('MerchandiseItem.n_status' => 2),
					    array('MerchandiseItem.id' => $id)
					);

		if($rs_update)
		{
			$this->set('response',array('status'=>1));
		}
		else
		{
			$this->set('response',array('status'=>0));
		}
		$this->render('response');
	}

	public function edit($id){
		$this->loadModel('MerchandiseItem');
		$this->loadModel('MerchandiseOrder');
		$this->loadModel('MerchandiseItemPerks');
		
		$rs = $this->MerchandiseItem->findById($id);
		$perks = $this->MerchandiseItemPerks->find('all', 
								array(
							        'conditions' => array('MerchandiseItemPerks.merchandise_item_id' => $id),
							        'limit'=>300
							    ));
		$rs_perks = array();
		foreach ($perks as $key => $value) {
			$rs_perks[] = $value['MerchandiseItemPerks']['perk_id'];
		}

		if($this->request->is('post')){
			$this->MerchandiseItem->id = $id;
			
			$this->request->data['data'] = json_encode($this->request->data['json_data']);
			
			//add stock with additional new stock.
			$this->request->data['stock'] = $rs['MerchandiseItem']['stock'] + 
											intval($this->request->data['new_stock']);
	
			if(isset($_FILES['pic']['name'])){
				$this->update_pic($id);
			}

			$this->MerchandiseItemPerks->delete_by_item_id($id);
			if(isset($this->request->data['perk_nondigital']))
			{
				$i=0;
				foreach ($this->request->data['perk_nondigital'] as $key => $value)
		        {
		        	$perk_nondigital[$i]['merchandise_item_id']	= $id;
		        	$perk_nondigital[$i]['perk_id']	= $value;
		        $i++;
		        }
		        $this->MerchandiseItemPerks->saveMany($perk_nondigital);
			}
			
			$this->Session->setFlash('Update Completed !');
			$this->MerchandiseItem->save($this->request->data);
			$this->redirect('/merchandises/');
		}
		

		$this->loadModel('MasterPerk');
		$perks = $this->MasterPerk->find('all',array('limit'=>300));
		$merchandise_items = $this->MerchandiseItem->find('all', array(
											        'order' => array('MerchandiseItem.id' => 'desc'),
											        'limit' => 100000
											    ));
		
		$this->set('merchandise_items', $merchandise_items);
		$this->set('perks',$perks);
		$this->set('rs_perks', $rs_perks);

		$this->set('rs',$rs);

		
		$this->loadModel('MerchandiseCategory');
		$categories = $this->MerchandiseCategory->find('all',array('limit'=>100));
		$this->set('categories',$categories);


	}
	/*
	* database ongkos kirim 
	* saat ini ongkos kirim kita pakai JNE Reguler
	*/
	public function ongkir(){

	}
	/*
	* ajax call for getting the list of delivery cost.
	*/
	public function get_ongkir(){
		$this->loadModel('Ongkir');
		$this->layout = 'ajax';
		$start = intval($this->request->query['start']);

		$rs = $this->Ongkir->find('all',array(
				'limit'=>100,
				'offset'=>$start,
			));

		$this->set('response',array('status'=>1,'data'=>$rs,'next_offset'=>$start+100));
		$this->render('response');
	}
	private function update_pic($id){
		$dir_path = Configure::read('avatar_img_dir')."merchandise/";
		$filename = $_FILES['pic']['name'];
		$dir = new Folder($dir_path, true, 0777);
		$dir->chmod($dir_path,0777,false);
		if(move_uploaded_file($_FILES['pic']['tmp_name'],
				$dir_path.$filename)){
				//is it an image by guessing its extensions ?
				preg_match('/([^\s]+(\.(?i)(jpg|png|gif))$)/',$filename,$matches);
				if(sizeof($matches)>0){
					$this->createThumbnail($dir_path,
											$filename);
				}
			$this->MerchandiseItem->id = $id;
			$this->MerchandiseItem->save(array('pic'=>$filename));	
		}
		$dir->chmod($dir_path,0755,false);
	}
	public function create(){
		$this->loadModel('MerchandiseItem');
		$this->loadModel('MerchandiseItemPerk');
		$this->loadModel('MasterPerk');
		if($this->request->is('post')){
			$this->request->data['data'] = json_encode($this->request->data['json_data']);
			//print_r($this->request->data);
			$dir_path = Configure::read('avatar_img_dir')."merchandise/";
			$filename = $_FILES['pic']['name'];
			$dir = new Folder($dir_path, true, 0777);
			$dir->chmod($dir_path,0777,false);

			if(move_uploaded_file($_FILES['pic']['tmp_name'],
					$dir_path.$filename)){
					//is it an image by guessing its extensions ?
					preg_match('/([^\s]+(\.(?i)(jpg|png|gif))$)/',$filename,$matches);
					if(sizeof($matches)>0){
						$this->createThumbnail($dir_path,
												$filename);
					}
					
			}
			$dir->chmod($dir_path,0755,false);

			$this->request->data['pic'] = $filename;
			$this->MerchandiseItem->create();
			$rs = $this->MerchandiseItem->save($this->request->data);
			if($rs){
				//add perks
				$perks = json_decode($this->request->data['perks'],true);
				$item_id = $this->MerchandiseItem->getInsertID();
				
				$added_perk = array();
				for($i=0;$i<sizeof($perks);$i++){
					$perk_name = 'perk_'.$item_id.$perks[$i]['perk_name'];
					$this->MasterPerk->create();
					$rs = $this->MasterPerk->save(array(
						'perk_name'=>$perks[$i]['perk_name'],
						'name'=>$perks[$i]['name'],
						'description'=>$perks[$i]['description'],
						'amount'=>$perks[$i]['amount'],
						'data'=>serialize($perks[$i]['attributes'])
					));
					$added_perk[] = $this->MasterPerk->getInsertID();
				}
				for($i=0;$i<sizeof($added_perk);$i++){
					$this->MerchandiseItemPerk->create();
					$this->MerchandiseItemPerk->save(array(
						'merchandise_item_id'=>$item_id,
						'perk_id'=>$added_perk[$i]
					));
				}

				$last_id = $this->MerchandiseItem->id;
				$perk_nondigital = array();
				$i=0;
				if(isset($this->request->data['perk_nondigital'])):
					foreach ($this->request->data['perk_nondigital'] as $key => $value)
			        {
			        	$perk_nondigital[$i]['merchandise_item_id']	= $last_id;
			        	$perk_nondigital[$i]['perk_id']	= $value;
			        $i++;
			        }
		        endif;

		        $this->loadModel('MerchandiseItemPerks');

		        //insert into merchandise_item_perks
		        $this->MerchandiseItemPerks->saveMany($perk_nondigital);

				$this->Session->setFlash('New Merchandise has been added successfully !');
			}else{
				$this->Session->setFlash('Cannot add the merchandise, please try again later!');
			}
		}
		$this->loadModel('MerchandiseCategory');
		$categories = $this->MerchandiseCategory->find('all',array('limit'=>100));
		$merchandise_items = $this->MerchandiseItem->find('all', array(
													        'order' => array('MerchandiseItem.id' => 'desc'),
													        'limit' => 100000
													    ));

		$this->set('merchandise_items', $merchandise_items);
		$this->set('categories',$categories);

		$this->loadModel('MasterPerk');
		$perks = $this->MasterPerk->find('all',array('limit'=>300));
		$this->set('perks',$perks);

	}
	public function view_ongkir(){

	}
	/*
	* the page for updating ongkir database
	* provide csv file with the following fields
	* "city";"cost";
	* example : 
	* "jakarta";"10000";
	* "bandung";"10000";
	*/
	public function update_ongkir(){
		$this->loadModel('Ongkir');
		if($this->request->is('post')){
			if(isset($_FILES['file']['tmp_name'])){
				$str = file_get_contents($_FILES['file']['tmp_name']);
				$lines = explode(PHP_EOL,$str);
				$this->Ongkir->query("TRUNCATE TABLE ongkir");
				$sqlStr = "";
				$i = 0;
				while(sizeof($lines) > 0){
					$a = explode(";",array_shift($lines));
					if($a[0]!=null){
						if($i<100){
							if($i>0){
								$sqlStr.=",";
							}
							$sqlStr.= "('".Sanitize::clean($a[0])."',
										'".Sanitize::clean($a[1])."',
										'".Sanitize::clean($a[2])."')";
							$i++;
						}else{
							$this->Ongkir->query("
								INSERT INTO ongkir(city,kecamatan,cost)
								VALUES
								{$sqlStr}
							");	
							$i = 0;
							$sqlStr = "";
						}
						
					}
				}
				$this->Session->setFlash('update completed !');
			}
		}
	}
	public function add_category(){
		if($this->request->is('post')){
			$this->loadModel('MerchandiseCategory');	
			$this->MerchandiseCategory->create();
			$rs = $this->MerchandiseCategory->save($this->request->data);
			if($rs){
				$this->Session->setFlash('New Category has been saved successfully !');
			}else{
				$this->Session->setFlash('Cannot create the category, please try again later!');
			}
		}
		$this->redirect('/merchandises/categories');
	}
	public function delete_category($id){
		$id = intval(Sanitize::clean($id));
		$this->loadModel('MerchandiseCategory');	
		
		$rs = $this->MerchandiseCategory->delete($id);
		if($rs){
			$this->Session->setFlash('The Category has been deleted successfully !');
		}else{
			$this->Session->setFlash('Cannot delete the category, please try again later!');
		}
		$this->redirect('/merchandises/categories');
	}
	private function createThumbnail($upload_dir,$filename){
		$tsize = Configure::read('THUMBNAIL_SIZES');
		$thumb_dir = $upload_dir.'/thumbs';
		$dir = new Folder($thumb_dir, true, 0777);
		$dir->chmod($thumb_dir,0777,false);

		$c = $this->Thumbnail->resizeImage('resize', $filename, 
							$upload_dir, 
							"/thumbs/0_".$filename, 
							400, 
							400, 
							100);

		$c = $this->Thumbnail->resizeImage('resize', $filename, 
							$upload_dir, 
							"/thumbs/1_".$filename, 
							200, 
							200, 
							100);

		$c = $this->Thumbnail->resizeImage('resize', $filename, 
							$upload_dir, 
							"/thumbs/2_".$filename, 
							75, 
							75, 
							100);

		$dir->chmod($thumb_dir,0755,false);
	}
	public function orders(){
		//i dunno what to do yet.
	}

	public function ticketorders(){
	}

	public function view_order($order_id){
		$this->loadModel('MerchandiseOrder');
		$this->loadModel('Ongkir');
		if($this->request->is('post')){
			//refund dan restock dimatiin dulu, karena takut kalo ada salah ganti status,
			//jumlah stock jadi nambah, takutnya pas nambah..ada customer baru beli barang ybs.
			//padahal order ini salah di cancel. - 28/04/2014
			
			//if($this->request->data['n_status']==4){
			//	if($this->refund($order_id)){
			//		/
					//$this->restock($order_id);

			//		$this->update_order($order_id);
			//	}else{
			//		$this->Session->setFlash('cannot update the order, please try again later !');
			//	}
			//}else{
			$this->update_order($order_id);
			//}
		}

		$this->MerchandiseOrder->bindModel(
			array('belongsTo'=>array('MerchandiseItem'))
		);
		$rs = $this->MerchandiseOrder->findById($order_id);
		//get ongkir
		$ongkir = $this->Ongkir->findById($rs['MerchandiseOrder']['ongkir_id']);
		$this->set('ongkir',$ongkir['Ongkir']);
		$this->set('admin_fee',Configure::read('PO_ADMIN_FEE'));
		$this->set('rs',$rs);
	}

	public function view_order_ticket($order_id){
		$this->loadModel('MerchandiseOrder');
		$this->loadModel('Ongkir');
		if($this->request->is('post')){
			$this->update_order($order_id);
		}

		$this->MerchandiseOrder->bindModel(
			array('belongsTo'=>array('MerchandiseItem'))
		);
		$rs = $this->MerchandiseOrder->findById($order_id);

		//get ongkir
		$this->set('ongkir', 'Free');
		$this->set('admin_fee', 5000);
		$this->set('rs',$rs);
	}

	private function restock($order_id){
		$this->loadModel('MerchandiseItem');
		$this->MerchandiseOrder->id = $order_id;
		$this->MerchandiseOrder->bindModel(
			array('belongsTo'=>array('MerchandiseItem'))
		);
		/*
		temporarily disabled these flow.
		there's a bug when people has been canceled, and the order were more than the initial stock number.
		the stock is increased while there's already n items delivered.
		
		$order = $this->MerchandiseOrder->findById($order_id);
		$this->MerchandiseItem->id = $order['MerchandiseItem']['id'];
		$this->MerchandiseItem->save(array('stock'=>$order['MerchandiseItem']['stock'] + 1));
		*/
	}
	private function update_order($order_id){
		$this->MerchandiseOrder->id = $order_id;
		$rs = $this->MerchandiseOrder->save($this->request->data);
		if($rs){
			
			$this->Session->setFlash('the order has been updated successfully !');
		}else{
			$this->Session->setFlash('cannot update the order, please try again later !');
		}
	}
	public function get_orders(){
		$this->layout = 'ajax';
		$this->loadModel('MerchandiseOrder');
		$start = intval(@$this->request->query['start']);
		$limit = 20;
		$this->MerchandiseOrder->bindModel(
			array('belongsTo'=>array('MerchandiseItem'))
		);
		if(!isset($this->request->query['status'])){
			$n_status = array(0,1,2,3,4);
		}else{
			$n_status= $this->request->query['status'];
		}
		$rs = $this->MerchandiseOrder->find('all',
											array('conditions'=>array(
														'MerchandiseOrder.n_status'=>$n_status
												  ),
												  'offset'=>$start,
												  'limit'=>$limit));
		for($i=0; $i<sizeof($rs); $i++){
			if($rs[$i]['MerchandiseOrder']['data']!=null){
				$rs[$i]['MerchandiseOrder']['data'] = unserialize($rs[$i]['MerchandiseOrder']['data']);
			}
		}

		$this->set('response',array('status'=>1,'data'=>$rs,'next_offset'=>$start+$limit,'rows_per_page'=>$limit));
		$this->render('response');
	}

	public function get_ticket_orders(){
		$this->layout = 'ajax';
		$this->loadModel('MerchandiseOrder');
		$start = intval(@$this->request->query['start']);
		$limit = 20;
		if(!isset($this->request->query['status'])){
			$n_status = array(0,1,2,3,4);
		}else{
			$n_status= $this->request->query['status'];
		}

		$rs = $this->MerchandiseOrder->query("SELECT 
												a.id, a.merchandise_order_id, a.merchandise_item_id,
											    a.voucher_code, a.created_dt, a.n_status, b.po_number, 
											    b.game_team_id, b.id, b.data, b.first_name, b.last_name
											FROM
											    merchandise_vouchers a 
											        INNER JOIN
											    merchandise_orders b
													ON 
												a.merchandise_order_id = b.id
													LIMIT 
												".$start.",".$limit);


		for($i=0; $i<sizeof($rs); $i++){
			if($rs[$i]['b']['data']!=null){
				$rs[$i]['b']['data'] = unserialize($rs[$i]['b']['data']);
			}
		}

		$this->set('response',array('status'=>1,'data'=>$rs,'next_offset'=>$start+$limit,'rows_per_page'=>$limit));
		$this->render('response');
	}

	private function refund($order_id){
		$refund_ok = false;
		$this->MerchandiseOrder->bindModel(
			array('belongsTo'=>array('MerchandiseItem'))
		);
		$order = $this->MerchandiseOrder->findById($order_id);

		//make sure that the transaction is not yet canceled
		if($order['MerchandiseOrder']['n_status']!=4){
			if($order['MerchandiseOrder']['order_type']==0){
				$refund_ok = $this->refund_game_funds($order);	
			}else if($order['MerchandiseOrder']['order_type']==1){

				$refund_ok = $this->refund_game_cash($order);	
			}else{
				$refund_ok = true;
			}
			
		}

		return $refund_ok;
	}

	private function refund_game_cash($order){
		$refund_ok = false;
		//make sure that the deducted fund is exists
		$statement = $this->getCashPurchaseStatement($order['MerchandiseOrder']['game_team_id'],
										$order['MerchandiseOrder']['po_number']);
			
		if(intval($statement['id']) > 0){
			$refund_amount = intval($statement['amount']) * -1;
			//if everything is fine, then we process the refund
			$rs = $this->Game->query("
				INSERT IGNORE INTO ".$_SESSION['ffgamedb'].".game_transactions
				(game_team_id,transaction_name,transaction_dt,
				 amount,details)
				VALUES
				({$order['MerchandiseOrder']['game_team_id']},
				  'purchase_{$order['MerchandiseOrder']['po_number']} - refunded',
				  NOW(),
				  {$refund_amount},
				  'purchase merchandise - {$order['MerchandiseOrder']['po_number']} - refunded'
				  );",false);
			//then set n_status of order to 4 (canceled)
			$refund_stmt = $this->getCashRefundedStatement($order['MerchandiseOrder']['game_team_id'],
										$order['MerchandiseOrder']['po_number']);

			//recount the cash
			$this->refresh_cash($order['MerchandiseOrder']['game_team_id']);

			if(intval($refund_stmt['id']) > 0){
				$refund_ok = true;	
			}
		}
		return $refund_ok;
	}
	private function refresh_cash($game_team_id){
		$sql = "INSERT INTO ".$_SESSION['ffgamedb'].".game_team_cash
				(game_team_id,cash)
				SELECT game_team_id,SUM(amount) AS cash 
				FROM ".$_SESSION['ffgamedb'].".game_transactions
				WHERE game_team_id = {$game_team_id}
				GROUP BY game_team_id
				ON DUPLICATE KEY UPDATE
				cash = VALUES(cash);";
		$this->Game->query($sql,false);
	}
	private function refund_game_funds($order){
		$refund_ok = false;
		//make sure that the deducted fund is exists
		$statement = $this->getPurchaseStatement($order['MerchandiseOrder']['game_team_id'],
										$order['MerchandiseOrder']['po_number']);
			
		if(intval($statement['id']) > 0){
			//if everything is fine, then we process the refund
			$rs = $this->Game->query("
				INSERT IGNORE INTO ".$_SESSION['ffgamedb'].".game_team_expenditures
				(game_team_id,item_name,item_type,
				 amount,game_id,match_day,item_total,base_price)
				VALUES
				({$order['MerchandiseOrder']['game_team_id']},
				  'purchase merchandise - {$order['MerchandiseOrder']['po_number']} - refunded',
				  1,
				  {$order['MerchandiseItem']['price_currency']},
				  '{$statement['game_id']}',
				  {$statement['match_day']},1,1);",false);
			//then set n_status of order to 4 (canceled)
			$refund_stmt = $this->getRefundedStatement($order['MerchandiseOrder']['game_team_id'],
										$order['MerchandiseOrder']['po_number']);
			if(intval($refund_stmt['id']) > 0){
				$refund_ok = true;	
			}
		}
		return $refund_ok;
	}
	private function getPurchaseStatement($game_team_id,$po_number){
		$sql = "SELECT * FROM ".$_SESSION['ffgamedb'].".game_team_expenditures a
				WHERE game_team_id={$game_team_id} AND 
				item_name ='purchase merchandise - {$po_number}' LIMIT 1;";
		$rs = $this->Game->query($sql,false);
		if(sizeof($rs)>0){
			return $rs[0]['a'];
		}else{
			return array('id'=>0);
		}
	}
	private function getCashPurchaseStatement($game_team_id,$po_number){
		$sql = "SELECT * FROM ".$_SESSION['ffgamedb'].".game_transactions a
				WHERE game_team_id={$game_team_id} AND 
				transaction_name ='purchase_{$po_number}' LIMIT 1;";
		
		$rs = $this->Game->query($sql,false);

		if(sizeof($rs)>0){
			return $rs[0]['a'];
		}else{
			return array('id'=>0);
		}
	}
	private function getCashRefundedStatement($game_team_id,$po_number){
		$sql = "SELECT * FROM ".$_SESSION['ffgamedb'].".game_transactions a
				WHERE game_team_id={$game_team_id} AND 
				transaction_name ='purchase_{$po_number} - refunded' LIMIT 1;";
		$rs = $this->Game->query($sql,false);
		if(sizeof($rs)>0){
			return $rs[0]['a'];
		}else{
			return array('id'=>0);
		}
	}
	private function getRefundedStatement($game_team_id,$po_number){
		$sql = "SELECT * FROM ".$_SESSION['ffgamedb'].".game_team_expenditures a
				WHERE game_team_id={$game_team_id} AND 
				item_name ='purchase merchandise - {$po_number} - refunded' LIMIT 1;";
		$rs = $this->Game->query($sql,false);
		if(sizeof($rs)>0){
			return $rs[0]['a'];
		}else{
			return array('id'=>0);
		}
	}

	//methods for ticket agents
	public function agent($sub=null,$id=null){
		switch($sub){
			case 'request':
				$this->showAgentRequests();
			break;
			case 'approve_request':
				$this->approveAgentRequest($id);
			break;
			case 'reject_request':
				$this->rejectAgentRequest($id);
			break;
			default:
				$this->showAgentList();
			break;
		}
	}

	public function agent_sales($agent_id = null)
	{
		if($agent_id != NULL)
		{
			$this->loadModel('AgentVoucher');
			$this->paginate = array(
									'fields' => 'AgentVoucher.voucher_code, AgentVoucher.created_dt, 
												AgentOrder.po_number,AgentOrder.first_name,AgentOrder.last_name, 
												AgentOrder.email, MerchandiseItem.name',
									'joins' => array(
										        array(
										        	'table' => 'agent_orders',
										            'alias' => 'AgentOrder',
										            'type' => 'INNER',
										            'conditions' => array(
										                'AgentVoucher.agent_order_id = AgentOrder.id'
										            )
										        ),
										        array(
										        	'table' => 'merchandise_items',
										            'alias' => 'MerchandiseItem',
										            'type' => 'INNER',
										            'conditions' => array(
										                'AgentVoucher.merchandise_item_id = MerchandiseItem.id'
										            )
										        )
										    ),
									'limit'=>10,
									'order'=>array('AgentVoucher.n_status'),
									'conditions' => array('AgentVoucher.agent_id' => $agent_id));

			$agent_sales = $this->paginate('AgentVoucher');
			$this->set('agent_sales',$agent_sales);
		}
	}

	/*
	* upon approving request, the following steps will occur : 
	* 1.  check if the stock is sufficient.
		   n_stock = total_stock - claimed_stock
	* 2. update agent_request n_status to 1
	* 3. add stock to agent_item
	* 4. reduce master stock (merchandise_items)
	* 
	*/
	private function approveAgentRequest($request_id){
		$this->loadModel('Agent');
		$this->loadModel('AgentRequest');
		$this->loadModel('MerchandiseItem');

		$req = $this->AgentRequest->findById($request_id);
		$item = $this->MerchandiseItem->findById($req['AgentRequest']['merchandise_item_id']);
		$stock_left = intval($item['MerchandiseItem']['stock']) - 
					  intval($this->getClaimedStock($req['AgentRequest']['merchandise_item_id']));
		
		if($stock_left < intval($req['AgentRequest']['request_quota'])){
			$request_quota = $stock_left;
		}else{
			$request_quota = intval($req['AgentRequest']['request_quota']);
		}
		if($stock_left > 0 && $req['AgentRequest']['n_status'] == 0){
			$this->AgentRequest->id = $request_id;
			$rs = $this->AgentRequest->save(array('n_status'=>1));
			if(isset($rs['AgentRequest']) && $rs['AgentRequest']['n_status'] == 1){
				$rs = $this->transferStockToAgent($req['AgentRequest']['agent_id'],$item,$request_quota);
				if($rs){
					$this->Session->setFlash('Request successfully approved !');	
				}else{
					$this->Session->setFlash('Unable to transfer the item stocks !');	
				}
			}
		}else{
			$this->Session->setFlash('Maaf, stock tidak cukup !');
		}
		$this->redirect('/merchandises/agent/request');
	}
	private function rejectAgentRequest($request_id){
		$this->loadModel('Agent');
		$this->loadModel('AgentRequest');
		$this->loadModel('MerchandiseItem');

		
		$this->AgentRequest->id = $request_id;
		$rs = $this->AgentRequest->save(array('n_status'=>2));
		
		$this->Session->setFlash('Request telah ditolak !');
		
		$this->redirect('/merchandises/agent/request');
	}
	private function transferStockToAgent($agent_id,$item,$request_quota){
		$rs1 = $this->MerchandiseItem->query("UPDATE merchandise_items SET stock = stock - {$request_quota}
										WHERE id = {$item['MerchandiseItem']['id']};",false);


		$rs2 = $this->AgentRequest->query("INSERT INTO agent_items(agent_id,merchandise_item_id,qty,last_update,n_status) 
									VALUES({$agent_id},{$item['MerchandiseItem']['id']},{$request_quota},NOW(),1)
									ON DUPLICATE KEY UPDATE
									qty = qty + VALUES(qty);");
		
		return true;
	}
	private function showAgentRequests(){
		$this->loadModel('Agent');
		$this->loadModel('AgentRequest');
		$this->loadModel('MerchandiseItem');
		$this->paginate = array('limit'=>10,
								'conditions'=>array('AgentRequest.n_status'=>0),
								'order'=>array('AgentRequest.id'=>'desc'));
		$rs = $this->paginate('AgentRequest');
		for($i=0; $i<sizeof($rs);$i++){
			//agent
			$agent = $this->Agent->findById($rs[$i]['AgentRequest']['agent_id']);
			$rs[$i]['Agent'] = $agent['Agent'];
			//item
			$item = $this->MerchandiseItem->findById($rs[$i]['AgentRequest']['merchandise_item_id']);
			//parent item
			$parent = $this->MerchandiseItem->findById($item['MerchandiseItem']['parent_id']);
			if(isset($parent['MerchandiseItem'])){
				$item['MerchandiseItem']['name'] = $parent['MerchandiseItem']['name'].
													' - '.$item['MerchandiseItem']['name'];
			}
			$rs[$i]['Item'] = $item['MerchandiseItem'];
			$rs[$i]['Item']['stock'] = intval($rs[$i]['Item']['stock']) - 
											intval($this->getClaimedStock($rs[$i]['Item']['id']));
		}

		$this->set('rs',$rs);

		$this->render('agent_requests');

	}
	private function showAgentList(){
		$this->loadModel('Agent');
		$this->paginate = array('limit'=>10,
								'order'=>array('Agent.name'));
		$agents = $this->paginate('Agent');
		$this->set('agents',$agents);
	}
	private function getClaimedStock($item_id){
		$pattern = 'claim_stock_'.$item_id.'_*';
		$claimed = $this->Game->getTmpKeys(0,
								$pattern);
		$total_claimed_qty = 0;
		if($claimed['status']==1){
			for($k=0;$k<sizeof($claimed['data']);$k++){
				//pastikan yg kita cek itu bukan punya si user
				$arr = explode("_",$claimed['data'][$k]);
				$owner_id = $arr[3];
				$claimed_qty = $this->Game->getFromTmp(0,$claimed['data'][$k]);
				$total_claimed_qty += intval($claimed_qty['data']);	
			}
		}
		return $total_claimed_qty;
	}
	public function add_agent(){
		$this->loadModel('Agent');


		$data = array(
			'email'=>trim($this->request->data['email']),
			'password'=>trim($this->request->data['password']),
			'secret'=>md5(date("YmdHis")),
			'name'=>$this->request->data['name'],
			'phone'=>$this->request->data['phone'],
			'address'=>$this->request->data['address'],
			'n_status'=>1
		);

		$data['password'] = sha1($data['email'].$data['password'].$data['secret']);
		
		$this->Agent->create();
		$this->Agent->save($data);
		
		$this->redirect('/merchandises/agent');
	}

	public function report()
	{
		$this->loadModel('MerchandiseItemPerks');
		$date_range = $this->MerchandiseItemPerks->query("SELECT 
											    DATE_FORMAT(MIN(order_date), '%d-%m-%Y') AS order_date
											FROM
											    merchandise_orders 
											UNION SELECT 
											    DATE_FORMAT(MAX(order_date), '%d-%m-%Y') AS order_date
											FROM
											    merchandise_orders
											limit 100000");

		$date_start = $date_range[0][0]['order_date'];
		$date_end 	= $date_range[1][0]['order_date'];

		$this->set('date_start', $date_start);
		$this->set('date_end', $date_end);
	}

	public function export(){
		if($this->request->is('post'))
		{
	        try{
	        	//prepare $_POST data
	        	$payment_method = @$this->request->data['payment_method'];
				$n_status = @$this->request->data['n_status'];

				if(!isset($payment_method) || !isset($n_status))
				{
					throw new Exception("Select Payment Method or Order Status");
				}
				else
				{
					$in_payment	= rtrim(implode('","', $payment_method), ',');
					$in_status	= rtrim(implode(',', $n_status), ',');

					$start_date = $this->request->data['year_from']
								.'-'.$this->request->data['month_from']
								.'-'.$this->request->data['date_from'];

					$end_date = $this->request->data['year_end']
									.'-'.$this->request->data['month_end']
									.'-'.$this->request->data['date_end'];
				}
				//end of prepare $_POST data

				$this->layout = 'csv/default';
				// Stop Cake from displaying action's execution time
		        Configure::write('debug', 0);
		        $this->loadModel('Game');

	        	$rs_report = $this->Game->query('SELECT *
												FROM merchandise_orders
												WHERE payment_method IN ("'.$in_payment.'") 
												AND n_status IN ('.$in_status.') AND
												DATE(order_date) >= "'.$start_date.'" AND 
												DATE(order_date) <= "'.$end_date.'"
												LIMIT 100000');

	        	Cakelog::write('debug', 'Merchandise.export SELECT *
												FROM merchandise_orders
												WHERE payment_method IN ("'.$in_payment.'") 
												AND n_status IN ('.$in_status.') AND
												DATE(order_date) >= "'.$start_date.'" AND 
												DATE(order_date) <= "'.$end_date.'"
												LIMIT 100000');

		        $data = array();
		        foreach ($rs_report as $key => $value)
		        {
		        	$data_unserial = unserialize($value['merchandise_orders']['data']);

		        	$data[$key]['merchandise_orders']['po_number'] = $value['merchandise_orders']['po_number'];
		        	$data[$key]['merchandise_orders']['order_date'] = $value['merchandise_orders']['order_date'];
		        	$data[$key]['merchandise_orders']['first_name'] = $value['merchandise_orders']['first_name'];
		        	$data[$key]['merchandise_orders']['last_name'] = $value['merchandise_orders']['last_name'];
		        	$data[$key]['merchandise_orders']['item_name'] = str_replace('"', '', $data_unserial[0]['data']['MerchandiseItem']['name']);
		        	$data[$key]['merchandise_orders']['qty'] = $data_unserial[0]['qty'];

		        	$data[$key]['merchandise_orders']['price_coins'] = $data_unserial[0]['data']['MerchandiseItem']
		        														['price_credit'];
		        	$data[$key]['merchandise_orders']['price_money'] = $data_unserial[0]['data']['MerchandiseItem']
		        														['price_money'];
		        	$data[$key]['merchandise_orders']['payment_method'] = $value['merchandise_orders']['payment_method'];
		        	$data[$key]['merchandise_orders']['ongkir'] = $value['merchandise_orders']['ongkir_value'];
		        	$data[$key]['merchandise_orders']['total_sale'] = $value['merchandise_orders']['total_sale'];
		        }

		        // Define column headers for CSV file, in same array format as the data itself
		        $headers = array(
		            'merchandise_orders'=>array(
		                'po_number' => 'PO NUMBER',
		                'order_date' => 'ORDER DATE',
		                'first_name' => 'First Name',
		                'last_name' => 'Last Name',
		                'item_name' => 'Item Name',
		                'qty' => 'Quantity',
		                'price_coins' => 'Price Coins',
		                'price_money' => 'Price Money',
		                'payment_method' => 'Payment Method',
		                'ongkir' => 'Ongkir',
		                'total_sale' => 'Total Sale'
		            )
		        );
		        // Add headers to start of data array
		        array_unshift($data, $headers);
		        // Make the data available to the view (and the resulting CSV file)
		        $this->set(compact('data'));

		        $this->set('filename', 'purchase-order-'.date("Y-m-d").'.csv');
		        Cakelog::write('debug', 'merchandises.export SELECT *
												FROM merchandise_orders
												WHERE payment_method IN ("'.$in_payment.'") 
												AND n_status IN ('.$in_status.') AND
												DATE(order_date) >= "'.$start_date.'" AND 
												DATE(order_date) <= "'.$end_date.'"
												LIMIT 100000');

	        }catch(Exception $e){
	        	Cakelog::write('debug', 'merchandises.export message : '.$e->getMessage());

	        	Cakelog::write('debug', 'merchandises.export SELECT *
												FROM merchandise_orders
												WHERE payment_method IN ("'.$in_payment.'") 
												AND n_status IN ('.$in_status.') AND
												DATE(order_date) >= "'.$start_date.'" AND 
												DATE(order_date) <= "'.$end_date.'"
												LIMIT 100000');
	        	$this->Session->setFlash($e->getMessage());
	        	$this->redirect('/merchandises/report');
	        }
    	}
	}
}