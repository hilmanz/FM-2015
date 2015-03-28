<?php
/**
 * Market Controller

 */
App::uses('AppController', 'Controller');
App::uses('Sanitize', 'Utility');
require_once APP . 'Vendor' . DS. 'lib/Predis/Autoloader.php';
class MerchandisesController extends AppController {

/**
 * Controller name
 *
 * @var string
 */
	public $name = 'Merchandises';

/**
 * This controller does not use a model
 *
 * @var array
 */
	public $uses = array();
	
	public function beforeFilter(){

		parent::beforeFilter();
		$this->loadModel('Team');
		$this->loadModel('User');
		$this->loadModel('MerchandiseItem');
		$this->loadModel('MerchandiseCategory');
		$this->loadModel('MerchandiseOrder');
		$this->loadModel('Ongkir');
		$this->loadModel('DigitalPerk');
		$this->loadModel('MasterPerk');
		$this->MasterPerk->useDbConfig = $_SESSION['ffgamedb'];
		$this->DigitalPerk->useDbConfig = $_SESSION['ffgamedb'];
		$userData = $this->getUserData();
		$user = $this->userDetail;
		$this->set('user',$user['User']);
		if(!$this->hasTeam()){
			$this->redirect('/login/expired');
		}
		
		//banners
		$sidebar_banner = $this->getBanners('CATALOG_SIDEBAR',3,true);
		$this->set('sidebar_banner',$sidebar_banner);

		$this->loadModel('Ongkir');
		Predis\Autoloader::register();
		$this->redisClient = new Predis\Client(array(
											    'host'     => Configure::read('REDIS.Host'),
											    'port'     => Configure::read('REDIS.Port'),
											    'database' => Configure::read('REDIS.Database')
											));

		
		if(!Configure::read('MERCHANDISE_ENABLE') 
				&& $this->request->params['action'] != 'offline' 
				&& $this->userDetail['Team']['id'] != 264){
			$this->redirect('/merchandises/offline');
		}
	}
	public function hasTeam(){
		$userData = $this->getUserData();
		if(is_array($userData['team'])){
			return true;
		}
	}
	/**
	* the index page will display all available (in-stock) merchandises.
	*/
	public function index(){
		if(isset($this->request->query['cid'])){
			$category_id = intval($this->request->query['cid']);
		}else{
			$category_id = 0;
		}
		
		$merchandise = $this->MerchandiseItem->find('count');
		if($merchandise > 0){
			$this->set('has_merchandise',true);	
		}else{
			$this->set('has_merchandise',false);
		}
		

		//bind the model's association first.
		//i'm too lazy to create a new Model Class :P
		$this->MerchandiseItem->bindModel(array(
			'belongsTo'=>array('MerchandiseCategory'=>array(
					'type' => 'inner',
					'conditions' => array('is_mobile' => 0)
				))
		));

		//we need to populate the category
		$this->populate_main_categories();

		//if category is set, we filter the query by category_id
		if($category_id != 0){
			$category_ids = array($category_id);
			//check for child ids, and add it into category_ids
			$category_ids = $this->getChildCategories($category_id,$category_ids);
			$this->paginate = array('conditions'=>array('merchandise_category_id'=>$category_ids,'n_status'=>1),
									'limit'=>9
									);
			//maybe the category has children in it.
			//so we try to populate it
			$this->populate_sub_categories($category_id);

			//we need to know the category details
			$category = $this->MerchandiseCategory->findById($category_id);
			//cek if category is mobile redirect to merchandise page
			if($category['MerchandiseCategory']['is_mobile'] == 1)
			{
				$this->redirect('/merchandises');
			}
			$this->set('category_name',h($category['MerchandiseCategory']['name']));

		}else{
			//if doesnt, we query everything.
			$this->paginate = array(
									'conditions'=>array('n_status'=>1),
									'limit'=>9
									);
		}


		//get previous orders
		$orders = $this->getPreviousOrders();
		$this->set('orders',$orders);
		
		//retrieve the paginated results.
		$rs = $this->paginate('MerchandiseItem');
		for($i=0;$i<sizeof($rs);$i++){
			//get the available stock
			
			$rs[$i]['MerchandiseItem']['available'] = $rs[$i]['MerchandiseItem']['stock'];
		}
		//assign it.
		$this->set('rs',$rs);
	}
	
	public function history(){
		
		$fb_id = $this->userDetail['User']['fb_id'];
		$this->paginate = array('conditions'=>array('fb_id'=>$fb_id, 'payment_method' => 'coins'),
								  'order'=>array('MerchandiseOrder.id'=>'DESC'),
								 'limit'=>20);
		
		$fb_id = $this->userDetail['User']['fb_id'];

		$rs = $this->Paginate('MerchandiseOrder');
		
		$this->set('rs',$rs);
	}

	public function view_order($order_id){

		//attach order detail
		$rs = $this->MerchandiseOrder->findById($order_id);
		$this->set('rs',$rs);

		//attach chosen delivery city
		$this->set('city_id',$rs['MerchandiseOrder']['ongkir_id']);

		//attach ongkos kirim list.
		$this->getOngkirList();


	}
	public function pay($type,$order_id=0,$payment_method = NULL){

		if($this->request->is("post"))
		{
			try{
				$this->getOngkirList();
				$this->loadModel("MerchandiseOrder");

				$city_id = $this->request->data['city_id'];
				$order_id = $this->request->data['order_id'];
				$fb_id = $this->userData['fb_id'];

				foreach($this->ongkirList as $ongkir){
					if($ongkir['Ongkir']['id'] == intval($city_id)){
						$ongkir_value = intval($ongkir['Ongkir']['cost']);
						break;
					}
				}
				$this->MerchandiseOrder->query("UPDATE merchandise_orders 
												SET ongkir_id='{$city_id}',ongkir_value='{$ongkir_value}'
												WHERE id='{$order_id}' AND fb_id='{$fb_id}'");
				$this->redirect('/merchandises/pay/ongkir/'.$order_id);

			}catch(Exception $e){
				CakeLog::write("error", "merchandises.pay msg:".$e->getMessage());
			}
			

		}

		if($type=='ongkir'){
			if($payment_method == NULL)
			{
				$this->payOngkirPage($order_id);
			}
			else
			{
				$doku_data = $this->dokuOngkirPayment($this->userData['fb_id'], $order_id, $payment_method);
				
				$this->set('payment_method', 'Kartu Kredit');
				if($payment_method == 'va'){
					$this->set('payment_method', 'Transfer Bank');
				}

				$action_form = Configure::read('DOKU_API');
				$this->set('action_form', $action_form);
				$this->set('doku_data', $doku_data);
				$this->render('doku_ongkir_payment');
			}
			
		}else{
			//ecash return page
			$this->handlePayment();
		}
		
	}

	public function ongkir_payment_success()
	{
		$this->render('doku_ongkir_payment_success');
	}

	public function ongkir_payment_pending()
	{
		$paymentcode = decrypt_param($this->request->query('trx'));
		$this->set('paymentcode', $paymentcode);
		$this->render('doku_ongkir_payment_pending');
	}

	public function ongkir_payment_error()
	{
		$this->render('doku_ongkir_payment_error');
	}

	//param POST fb_id, order_id, payment_method
	private function dokuOngkirPayment($fb_id, $order_id, $payment_method)
	{
		try{
			$this->loadModel('MerchandiseOrder');
			$this->loadModel('Doku');

			$payment_channel = '01';
			if($payment_method == "va")
			{
				$payment_channel = '05';
			}

			if($fb_id == NULL || $order_id == NULL)
			{
				throw new Exception("Error Param ");
			}

			$rs_order = $this->MerchandiseOrder->find('first', array(
				    	'conditions' => array(
				    			'id' => $order_id,
				    			'payment_method' => 'coins'
				    	)));

			if(count($rs_order) == 0)
			{
				throw new Exception("Order not found param ");
			}

			if($rs_order['MerchandiseOrder']['fb_id'] != $fb_id)
			{
				throw new Exception("facebook id didn't match");
			}

			$trx_session_id = sha1(time());
			$doku_mid = Configure::read('DOKU_MALLID');
			$doku_sharedkey = Configure::read('DOKU_SHAREDKEY');

			$po_number = $rs_order['MerchandiseOrder']['po_number'];

			//add suffix -1 to define that its the payment for shipping for these po number.
			$transaction_id =  $po_number.'-1';
			$transaction_id_merchant = date("YmdHis").rand(0,99);
			$total_ongkir = $rs_order['MerchandiseOrder']['total_ongkir'];
			$admin_fee = $rs_order['MerchandiseOrder']['total_admin_fee'];
			$total_amount =  $total_ongkir + $admin_fee;

			$hash_words = sha1(number_format($total_amount,2,'.','').
						  $doku_mid.
						  $doku_sharedkey.
						  $transaction_id_merchant);

			$basket = 'ONGKIR PAYMENT PO#'.$po_number.','.$total_amount.','.$total_amount.','.$total_amount.';';
			$additionaldata = 'fm-ongkir-payment';
			$doku_data = array(
								'catalog_order_id'=>$order_id,
								'po_number'=>$transaction_id,
								'transidmerchant'=>$transaction_id_merchant,
								'totalamount'=>number_format($total_amount,2,'.',''),
								'words'=>$hash_words,
								'statustype'=>'',
								'response_code'=>'',
								'approvalcode'=>'',
								'trxstatus'=>'Requested',
								'payment_channel'=>$payment_channel,
								'paymentcode'=>'',
								'session_id'=>$trx_session_id,
								'bank_issuer'=>'',
								'creditcard'=>'',
								'payment_date_time'=>date("Y-m-d H:i:s"),
								'verifyid'=>'',
								'verifyscore'=>'',
								'verifystatus'=>'',
								'additionaldata'=> $additionaldata
						);

			try{
				$this->Doku->create();
				$this->Doku->save($doku_data);
			}catch(Exception $f){
				//catch here because mostly duplicate entry key
				Cakelog::write('error', 'merchandise.dokuOngkirPayment nested try-catch'.$f->getMessage());
				$rs_doku = $this->Doku->findByTransidmerchant($transaction_id_merchant);
				$trx_session_id = $rs_doku['Doku']['session_id'];
				$hash_words = $rs_doku['Doku']['words'];
			}
			
			
			$data_param = array('MALLID'=>$doku_mid,
							'CHAINMERCHANT'=>'NA',
							'AMOUNT'=>number_format($total_amount,2,'.',''),
							'PURCHASEAMOUNT'=>number_format($total_amount,2,'.',''),
							'TRANSIDMERCHANT'=>$transaction_id_merchant,
							'WORDS'=>$hash_words,
							'REQUESTDATETIME'=>date("YmdHis"),
							'CURRENCY'=>'360',
							'PURCHASECURRENCY'=>'360',
							'SESSIONID'=>$trx_session_id,
							'NAME'=>$rs_order['MerchandiseOrder']['first_name'].' '.$rs_order['MerchandiseOrder']['last_name'],
							'EMAIL'=>$rs_order['MerchandiseOrder']['email'],
							'ADDITIONALDATA'=>$additionaldata,
							'PAYMENTCHANNEL'=>$payment_channel,
							'BASKET'=>$basket
						);

			$data_redis = array('doku_data' => $doku_data,
								'doku_param' => $doku_param,
								'order_id' => $order_id,
								'trx_type' => $basket,
								'amount' => $total_amount
								);



			$this->redisClient->set($transaction_id_merchant, serialize($data_redis));

			$this->redisClient->expire($transaction_id_merchant, 24*60*60);//expires in 1 day
			if($payment_channel == '05')
			{
				$this->redisClient->expire($transaction_id_merchant, 6*60*60);//expires in 6 hours
			}

			$status = 1;

		}catch(Exception $e){
			$status = 0;
			$data_param = NULL;
			Cakelog::write('error', 'merchandise.dokuOngkirPayment '.$e->getMessage());
		}

		return $data_param;
		
	}
	private function handlePayment(){
		$body = file_get_contents('php://input');

		if(!empty($body)) {
		  $data = explode(",", $body);
		  $ticket = $data[0];
		  $phone_no = $data[1];
		  $trace_no = $data[2];
		  $order_id = trim($data[3]);
		  $status = trim($data[4]);
		  
		  $returnid = $ticket;
		  $this->Session->write('ecash_return',array(
		  							'id'=>$ticket,
		  							'nohp'=>$phone_no,
		  							'transaction_id'=>$order_id,
		  							'trace_number'=>$trace_no,
		  							'status'=>$status
		  						));

		  die();
		}else{
			$rs  = $this->Game->EcashValidate($this->request->query['id']);
			list($id,$trace_number,$nohp,$transaction_id,$status) = explode(',',$rs['data']);

			$result = array(
				'id'=>trim($id),
				'trace_number'=>trim($trace_number),
				'nohp'=>trim($nohp),
				'transaction_id'=>trim($transaction_id),
				'status'=>trim($status)
			);
			
			$is_valid = true;
			

			if(strtoupper($result['status'])=='SUCCESS' && $is_valid){
				//update order status
				$data['n_status'] = 1;
				$this->MerchandiseOrder->id = intval($this->Session->read($result['transaction_id']));
				$rs = $this->MerchandiseOrder->save($data);
				if(isset($rs)){
					$this->Session->setFlash('Pembayaran Telah Berhasil ! Terima Kasih !');
				}else{
					$this->Session->setFlash('Transaksi Ecash Berhasil, namun kami gagal menyimpan transaksi anda, silahkan hubungi soccerdesk@supersoccer.co.id untuk bantuan. Mohon maaf atas ketidaknyamanannya.');
				}
				$this->redirect('/merchandises/history');

			}else{
				$this->Session->setFlash("Pembayaran anda gagal diproses, silahkan coba kembali !");
				$this->redirect('/merchandises/history');
				

			}
		}

	}
	private function payOngkirPage($order_id){
		$rs = $this->MerchandiseOrder->find('first', array(
				    	'conditions' => array(
				    			'id' => $order_id,
				    			'payment_method' => 'coins'
				    	)));

		if($rs['MerchandiseOrder']['ongkir_id'] == 0)
		{
			$this->set('ongkir', $ongkir);
		}

		$admin_fee = $rs['MerchandiseOrder']['total_admin_fee'];

		$items = unserialize($rs['MerchandiseOrder']['data']);

		$kg = 0;
		for($i=0;$i<sizeof($items);$i++){
			
			if(floatval(@$items[$i]['data']['MerchandiseItem']['weight'])==0){
				$merchandise_item =  $this->MerchandiseItem->findById($items[$i]['data']['MerchandiseItem']['id']);
				$items[$i]['data']['MerchandiseItem']['weight'] = $merchandise_item['MerchandiseItem']['weight'];
			}
		}

		$this->set('rs',$rs);
		
		$total_ongkir = $rs['MerchandiseOrder']['total_ongkir'];
		$amount = $total_ongkir + $admin_fee;

		$this->set('city',$rs['MerchandiseOrder']['city']);
		$this->set('total_ongkir',$total_ongkir);

		//add suffix -1 to define that its the payment for shipping for these po number.
		$transaction_id =  $rs['MerchandiseOrder']['po_number'].'-1';
		//ecash url
		$rs = $this->Game->getEcashUrl(array(
			'transaction_id'=>$transaction_id,
			'amount'=>$amount,
			'clientIpAddress'=>$this->request->clientIp(false),
			'description'=>'Shipping Fee #'.$transaction_id,
			'source'=>'FMPAY'
		));

		if($rs['status']==0){
			$rs['data'] = "#";
		}

		$this->set('transaction_id',$transaction_id);
		$this->set('ecash_url',$rs['data']);
		$this->set('admin_fee', $admin_fee);

		$this->Session->write($transaction_id,$order_id);
	}
	public function view($item_id){
		


		//we need to populate the category
		$this->populate_main_categories();
		
		
		//parno mode.
		$item_id = Sanitize::clean($item_id);

		//get the item detail
		$item = $this->MerchandiseItem->findById($item_id);
		
		
			
		$item['MerchandiseItem']['available'] = $item['MerchandiseItem']['stock'];



		$this->set('item',$item);
		$this->set('user_detail', $this->userDetail);

		$category = $this->MerchandiseCategory->findById($item['MerchandiseItem']['merchandise_category_id']);
		if($category['MerchandiseCategory']['is_mobile'] == 1)
		{
			$this->redirect('/merchandises');
		}
		$this->set('category_name',h($category['MerchandiseCategory']['name']));

		
		if($item['MerchandiseItem']['merchandise_type']==1){
			
			$this->set('can_update_formation',$this->can_update_formation());
		}else{
			$this->set('can_update_formation',true);
		}

	}
	private function get_ongkir(){
		
		$rs = $this->Ongkir->find('all');
		$ongkir = array();
		while(sizeof($rs)>0){
			$p = array_shift($rs);
			$ongkir[] = $p['Ongkir'];
		}	
		return $ongkir;
	}
	private function getPreviousOrders(){
	
		$game_team_id = $this->userData['team']['id'];
		
		//we need to link the order with the item
		$this->MerchandiseOrder->bindModel(
			array('belongsTo'=>array('MerchandiseItem'))
		);
		$orders = $this->MerchandiseOrder->find('all',
					array('conditions'=>array(
								'game_team_id'=>$game_team_id
							),
							'order'=>array('MerchandiseOrder.id'=>'DESC'),
						  	'limit'=>1000));
		
		return $orders;
	}
	/**
	*	get the list of child category_ids, 1 level under only.
	*/
	private function getChildCategories($category_id,$category_ids){
		$categories = $this->MerchandiseCategory->find('all',
														array('conditions'=>array('parent_id'=>$category_id),
															  'limit'=>100)
													);
		for($i=0;$i<sizeof($categories);$i++){
			$category_ids[] = $categories[$i]['MerchandiseCategory']['id'];
		}

		return $category_ids;
	}
	/**
	*	get the list of child categories, 1 level under only.
	*/
	private function getSubCategories($category_id){
		$categories = $this->MerchandiseCategory->find('all',
														array('conditions'=>array('parent_id'=>$category_id),
															  'limit'=>100)
													);
		return $categories;
	}
	/**
	* populate main categories (all categories that has parent_id = 0)
	*/
	private function populate_main_categories(){
		//retrieve main categories
		$categories = $this->MerchandiseCategory->find('all',
														array('conditions'=>array('parent_id'=>0,
																				'is_mobile'=>0),
															  'limit'=>100)
													);
		for($i=0;$i<sizeof($categories);$i++){
			$categories[$i]['Child'] = $this->getSubCategories($categories[$i]['MerchandiseCategory']['id']);
		}
		
		$this->set('categories',$categories);
	}

	private function populate_sub_categories($category_id){
		//retrieve main categories
		$categories = $this->MerchandiseCategory->find('all',
														array('conditions'=>
															array('parent_id'=>$category_id),
															      'limit'=>100)
													);
		$this->set('sub_categories',$categories);
	}
	

	//Buy Merchandise Page.
	//these page will display an order form.
	//user should fill all the fields in order to process the order.
	//here, the user can choose the payment method,
	//at the moment only 2 options available
	//1. coins
	//2. Rupiah via Mandiri Ecash
	//when the user choose coins, we automatically deduct the coins, and
	//then save the purchase order.
	//the tricky part is, the digital item (perks) must be applied directly
	//while the non-digitals doesnt.
	//these conditions should apply : 
	//if all items are digital items, we close the purchase automatically.
	//if all items are non-digital items, or at least has 1 non-digital items,
	//we keep the purchase order open (or pending) so that administrator can check the order
	//and proceed the item delivery manually.
	//=====================================================================================
	//workflows 
	//1. check if all stocks are available 
	//2. if there's stock that isnt available, we redirect back the user to cart and notify the user
	//(already covered in checkout()  )
	//3. if all items area available, display the form.
	//4. upon submitting the form, check the payment method
	//5. create the transaction_id
	//6. if paid with coins, deduct the coins and then save the order
	//7. if paid with ecash, proceed the ecash payment workflow by invoking the ecash webservice
	//8. when the payment completed, we display the success page. 
	//9. send notification email to user.
	//10. send notification email to administrator.
	//11. distribute the digital items
	//12. we start locking items from here.

	public function buy(){
	
		$can_use_ecash = true;
		$can_use_coin = true;
		$enable_ongkir = true;
		//display the cart content
		$shopping_cart = $this->Session->read('shopping_cart');
		for($i=0;$i<sizeof($shopping_cart);$i++){
			$shopping_cart[$i]['data'] = $this->MerchandiseItem->findById($shopping_cart[$i]['item_id']);
			$price_money = intval($shopping_cart[$i]['data']['MerchandiseItem']['price_money']);
			$price_credit = intval($shopping_cart[$i]['data']['MerchandiseItem']['price_credit']);
			if($price_money==0){
				$can_use_ecash = false;
			}
			if($price_credit ==0){
				$can_use_coin = false;
			}
		}
		//recheck the stock of all items.
		$stock_status = $this->recheckStockBeforePayment();

		if($this->Session->read('PO_NUMBER') == NULL){
			$po_number = $this->userData['team']['id'].'-'.date("ymdhis");
			$this->Session->write('PO_NUMBER',$po_number);
		}

		Cakelog::write('debug', 'Merchandise.buy - write po_number:'.$this->Session->read('PO_NUMBER').' team id:'.$this->userData['team']['id']);

		if($stock_status){

			$this->set('shopping_cart',$shopping_cart);


			if(count($shopping_cart) < 2 && count($shopping_cart) != 0)
			{
				if($shopping_cart[0]['data']['MerchandiseItem']['enable_ongkir'] == 0)
				{
					$enable_ongkir = false;
				}
			} 
			
			$this->set('enable_ongkir', $enable_ongkir);

			//generate CSRF Token
			$csrf_token = md5('purchase_order_merchandise-'.date("YmdHis").rand(0,100));
			$this->Session->write('po_csrf',$csrf_token);
			$this->set('csrf_token',$csrf_token);

			//pre-populate user details on the form
			$name = $this->getDetailedName();
			$this->set('first_name',$name['first_name']);
			$this->set('last_name',$name['last_name']);
			$this->set('phone_number',$this->userDetail['User']['phone_number']);
			$this->set('email',$this->userDetail['User']['email']);
			
			//attach chosen delivery city
			$this->set('city_id',$this->Session->read('city_id'));

			//attaching ongkir
			$ongkir = $this->Ongkir->findById($this->Session->read('city_id'));

			$this->set('city',@$ongkir['Ongkir']);

			//attach ongkos kirim list.
			$this->getOngkirList();

			$this->set('can_use_ecash',$can_use_ecash);
			$this->set('can_use_coin',$can_use_coin);
			$this->set('po_number',$this->Session->read('PO_NUMBER'));


			//book item stocks
			//display the cart content
			$this->book_items($shopping_cart,$this->Session->read('PO_NUMBER'));
		}else{

		}
	}	

	/*
	* add item to shopping cart
	* if the selected item is a digital item, we check if the user is permitted to buy that perk.
	* for example, you cannot buy point booster while the point booster in the same category is in active.
	* if the cart is already exists in shopping cart, no need to re-add it.
	*/
	public function select($item_id){
		
		$shopping_cart = $this->Session->read('shopping_cart');
		$can_add = false;
		$canAddPerk = true;

		//parno mode.
		$item_id = Sanitize::clean($item_id);

		//get the item detail
		$item = $this->MerchandiseItem->findById($item_id);
		$user_detail = $this->userDetail;

		if($item['MerchandiseItem']['is_pro_item'] == 1 && 
			$user_detail['User']['paid_member'] == 1 &&
			$user_detail['User']['paid_member_status'] == 0)
		{

			$this->redirect('/merchandises/bayar_bulanan');
		}
		else if($item['MerchandiseItem']['is_pro_item'] == 1 && 
										$user_detail['User']['paid_member'] == 0 &&
										$user_detail['User']['paid_member'] == 0)
		{
			$this->redirect('/merchandises/upgrade_member');
		}

		//if its digital item, we need to make sure that these 
		if($item['MerchandiseItem']['merchandise_type'] == 1){
			$canAddPerk = $this->Game->can_apply_perk($this->userData['team']['id'],
										$item['MerchandiseItem']['perk_id']);
			
		}

		
		if($shopping_cart == null){
			$shopping_cart = array();
			$can_add = true;

		}else if(!is_array($shopping_cart)){
			$shopping_cart = array();
			$can_add = true;

		}else{
			$can_add = true;
			//make sure that the item is not in the cart yet
			for($i=0;$i<sizeof($shopping_cart);$i++){
				if($shopping_cart[$i]['item_id'] == $item_id){
					$can_add = false;
					break;
				}
			}
		}

		if(intval($item['MerchandiseItem']['stock']) == 0){
			$can_add = false;
		}

		if($can_add && $canAddPerk){
			
			$shopping_cart[] = array(
				'item_id'=>$item_id,
				'qty'=>1
			);	
			$this->Session->write('shopping_cart',$shopping_cart);
			$this->set('canAddPerk',$canAddPerk);
			$this->set('item',$item['MerchandiseItem']);
			$this->Session->write('out_of_stock',null);
		}else if(intval($item['MerchandiseItem']['stock']) == 0){
			$this->set('item',$item['MerchandiseItem']);
			$this->render('out_of_stock');
		}else if(!$canAddPerk){
			$this->set('item',$item['MerchandiseItem']);
			$this->render('cannot_add_perk');
		}else{
			$this->set('item',$item['MerchandiseItem']);
			$this->render('catalog_error');
		}

		
	}
	//create purchase order and make payment.
	//1. if payment method is ecash, we only generate the ecash payment url for user.
	public function order(){
		
		
		//these is our flags
		$is_transaction_ok = true;
		$no_fund = false;

		$po_number = $this->Session->read('PO_NUMBER');

		Cakelog::write('debug', 'Merchandise.order - read po_number:'.$po_number.' team id:'.$this->userData['team']['id']);

		//recheck the stock of all items.
		$stock_status = $this->recheckStockBeforePayment();
		//if all items are available, we can continute the purchase.
		if($stock_status){
			//make sure the csrf token still valid

			//-> csrf check di disable dulu selama development
			//if(
			//	(strlen($this->request->data['ct']) > 0)
			//		&& ($this->Session->read('po_csrf') == $this->request->data['ct'])
			//  ){

			if($this->request->data['payment_method']=='coins'){
				//cek user if CANT_USE_COIN 
				$users = $this->User->findByFb_id($this->userData['fb_id']);
				$rs_banned = $this->Game->query("SELECT * FROM ".Configure::read('FRONTEND_SCHEMA').".banned_users
												WHERE user_id = '{$users['User']['id']}'
												AND banned_type = 'CANT_USE_COIN' LIMIT 1");
				if(count($rs_banned) != 0)
				{
					$this->render('cant_use_coin');
				}
				else
				{
					$this->process_with_coins($po_number);
				}
			}
			else if($this->request->data['payment_method']=='bank_transfer' || 
				$this->request->data['payment_method']=='kartu_kredit')
			{
				$this->pay_with_doku($po_number);
				$doku_api = Configure::read('DOKU_API');
				$this->set('doku_api', $doku_api);
				$this->render('doku');
			}
			else
			{
				$this->pay_with_ecash($po_number);
				$this->render('ecash');
			}
		}else{
			//we will already be redirected back to shopping cart.
		}
		
	}
	private function process_with_coins($po_number){
		$result = $this->pay_with_coins($po_number);
		$is_transaction_ok = $result['is_transaction_ok'];
		$no_fund = $result['no_fund'];
		$order_id = @$result['order_id'];
		

		if($is_transaction_ok == true){
			//check accross the items, we apply the perk for all digital items
			$this->process_items($result['items'],$order_id);
		}

		
	
		$this->set('apply_digital_perk_error',$this->Session->read('apply_digital_perk_error'));
		$this->set('is_transaction_ok',$is_transaction_ok);
		$this->set('no_fund',$no_fund);
		$this->Session->write('apply_digital_perk_error',null);
		//reset the csrf token
		$this->Session->write('po_csrf',null);
		//-->
		//attach chosen delivery city
		$this->set('city_id',$this->Session->read('city_id'));

		//attach ongkos kirim list.
		$this->getOngkirList();
		//reset the shopping_cart in session (disable these for debug only)
		$this->Session->write('shopping_cart',null);

	}
	private function pay_with_ecash($po_number){

		
		
		//attach chosen delivery city
		$this->set('city_id',$this->Session->read('city_id'));

		//attach ongkos kirim list.
		$this->getOngkirList();



		$result = array('is_transaction_ok'=>false,
						'no_fund'=>false);

		//display the cart content
		$shopping_cart = $this->Session->read('shopping_cart');

		

		//get total coins to be spent.
		$total_price = 0;
		$all_digital = true;
		$kg = 0;
		$total_admin_fee = 0;
		for($i=0;$i<sizeof($shopping_cart);$i++){

			$shopping_cart[$i]['data'] = $this->MerchandiseItem->findById($shopping_cart[$i]['item_id']);
			$item = $shopping_cart[$i]['data']['MerchandiseItem'];
			$kg += floatval($item['weight']) * intval($shopping_cart[$i]['qty']);
			$total_price += (intval($shopping_cart[$i]['qty']) * intval($item['price_money']));
			$total_admin_fee += $item['admin_fee'];
			//is there any non-digital item ?
			if($item['merchandise_type']==0){
				$all_digital = false;
			}
		}
		$kg = ceil($kg);

		$admin_fee = $total_admin_fee;
		$enable_ongkir = true;

		if(count($shopping_cart) > 1)
		{
			//$admin_fee = Configure::read('PO_ADMIN_FEE'); ->skip broo
		}
		else
		{

			if($item['enable_ongkir'] == 0)
			{
				$enable_ongkir = false;
			}
		}
		
		if($all_digital){
			$admin_fee = 0;
		}


		$total_price += $admin_fee;
		
		if($enable_ongkir)
		{
			foreach($this->ongkirList as $ongkir){
				if($ongkir['Ongkir']['id'] == intval($this->Session->read('city_id'))){
					$base_ongkir = intval($ongkir['Ongkir']['cost']);
					break;
				}
			}
		}

		//tambahkan harga ongkir kedalam total price
		$total_ongkir = $kg * $base_ongkir;
		$total_price += $total_ongkir;


		$this->set('shopping_cart',$shopping_cart);
		//add shipping and handling cost
		$this->set('admin_fee',$admin_fee);
		$this->set('enable_ongkir', $enable_ongkir);
		

		//1. create transaction ID
		$data = $this->request->data;

		//$transaction_id = $this->userData['team']['id'].'-'.date("ymdhis");
		$transaction_id = $po_number;

		$this->Session->write($transaction_id,
								serialize(array('data'=>$data,'shopping_cart'=>$shopping_cart))
							 );

		$rs = $this->Game->getEcashUrl(array(
			'transaction_id'=>$transaction_id,
			'amount'=>$total_price,
			'clientIpAddress'=>$this->request->clientIp(false),
			'description'=>'Purchase Order #'.$transaction_id,
			'source'=>'FM'
		));

		$this->set('transaction_id',$transaction_id);
		$hashed_url = encrypt_param($rs['data']);

		if($hashed_url == '')
		{
			$this->render('ecash_error');
		}
		
		$view = new View();
		$this->set('ecash_url',$view->Html->url('/merchandises/ecash?r='.$hashed_url));
	}

	private function pay_with_doku($po_number){

		$this->loadModel('MerchandiseItem');
		$this->loadModel('MerchandiseCategory');
		$this->loadModel('MerchandiseOrder');
		$this->loadModel('Doku');

		//attach chosen delivery city
		$this->set('city_id',$this->Session->read('city_id'));

		$profile_order	 		= $this->request->data;

		//attach ongkos kirim list.
		$this->getOngkirList();

		$result = array('is_transaction_ok'=>false,
						'no_fund'=>false);

		$payment_channel = '01';
		if($this->request->data['payment_method'] == "bank_transfer")
		{
			$payment_channel = '05';
		}

		//display the cart content
		$shopping_cart = $this->Session->read('shopping_cart');

		CakeLog::write('doku','doku_create_order '.json_encode($this->request->data));

		$transaction_id = $po_number;
		
		$description = 'Purchase Order #'.$transaction_id;
		$user_login = $this->Session->read('Userlogin');
		$fb_id = $user_login['info']['fb_id'];

		//get total money to be spent.
		$total_price = 0;
		$all_digital = true;
		$kg = 0;
		$category = array();
		$total_admin_fee = 0;
		$basket = "Pembelian ";
		for($i=0;$i<sizeof($shopping_cart);$i++){

			$shopping_cart[$i]['data'] = $this->MerchandiseItem->findById($shopping_cart[$i]['item_id']);
			$item = $shopping_cart[$i]['data']['MerchandiseItem'];
			$basket .= htmlspecialchars($item['name']).','.$item['price_money'].','.$item['id'].','.$item['price_money'].';';
			$kg += floatval($item['weight']) * intval($shopping_cart[$i]['qty']);
			$total_admin_fee += $item['admin_fee'];
			$total_price += (intval($shopping_cart[$i]['qty']) * intval($item['price_money']));
			$category[$i] = $item['merchandise_category_id'];
			//is there any non-digital item ?
			if($item['merchandise_type']==0){
				$all_digital = false;
			}
		}
		$basket = rtrim($basket,',');
		$kg = ceil($kg);
		
		$admin_fee = $total_admin_fee;
		$enable_ongkir = true;
		if(count($shopping_cart) > 1)
		{
			//$admin_fee = Configure::read('PO_ADMIN_FEE'); -> skip this process
		}
		else
		{
			//check enable or disable admin fee

			//check ongkir
			if($item['enable_ongkir'] == 0)
			{
				$enable_ongkir = false;
			}
		}


		if($all_digital){
			$admin_fee = 0;
		}
		$total_price += $admin_fee;

		//include ongkir
		if($enable_ongkir)
		{
			foreach($this->ongkirList as $ongkir){
				if($ongkir['Ongkir']['id'] == intval($this->Session->read('city_id'))){
					$base_ongkir = intval($ongkir['Ongkir']['cost']);
					break;
				}
			}
		}

		$total_ongkir = ($kg*$base_ongkir);
		$total_price += $total_ongkir;
		
		$this->set('transaction_id',$transaction_id);
		$this->set('shopping_cart',$shopping_cart);
		//add shipping and handling cost
		$this->set('admin_fee',$admin_fee);
		$this->set('enable_ongkir', $enable_ongkir);

		$transaction_data = array('profile'=>$this->request->data,
								 'shopping_cart'=>$shopping_cart,
								 'base_ongkir_value'=>$total_ongkir);

		$doku_mid = Configure::read('DOKU_MALLID');
		$doku_sharedkey = Configure::read('DOKU_SHAREDKEY');
		$hash_words = sha1(number_format($total_price,2,'.','').
						  $doku_mid.
						  $doku_sharedkey.
						  str_replace('-', '', $transaction_id));
		$trx_session_id = sha1(time());
		$additionaldata = 'fm-onlinecatalog';
		$data = array('MALLID'=>$doku_mid,
						'CHAINMERCHANT'=>'NA',
						'AMOUNT'=>number_format($total_price,2,'.',''),
						'PURCHASEAMOUNT'=>number_format($total_price,2,'.',''),
						'TRANSIDMERCHANT'=>str_replace('-', '', $transaction_id),
						'WORDS'=>$hash_words,
						'REQUESTDATETIME'=>date("YmdHis"),
						'CURRENCY'=>'360',
						'PURCHASECURRENCY'=>'360',
						'SESSIONID'=>$trx_session_id,
						'NAME'=>$this->request->data['first_name'].' '.$this->request->data['last_name'],
						'EMAIL'=>$this->request->data['email'],
						'ADDITIONALDATA'=>$additionaldata,
						'PAYMENTCHANNEL'=>$payment_channel,
						'BASKET'=>$basket
						);

		try{

			$rs_order = $this->MerchandiseOrder->findByPo_number($transaction_id);
			$rs_user = $this->User->findByFb_id($fb_id);
			$dataSource = $this->MerchandiseOrder->getDataSource();
			$dataSource->begin();

			$payment_method = 'cc';
			if($payment_channel != '01'){
				$payment_method = 'va';
			}
			if(count($rs_order) > 0){
				$rs_doku = $this->Doku->findByPo_number($transaction_id);
				throw new Exception("entry already exists");
			}

			$this->MerchandiseOrder->create();
			$rs_order = $this->MerchandiseOrder->save(array(
					'fb_id'=>$fb_id,
					'po_number'=>$transaction_id,
					'game_team_id'=>intval($this->userData['team']['id']),
					'user_id'=>$rs_user['User']['id'],
					'first_name'=>$this->request->data['first_name'],
					'last_name'=>$this->request->data['last_name'],
					'ktp'=>$this->request->data['ktp'],
					'email'=>$this->request->data['email'],
					'phone'=>$this->request->data['phone'],
					'city'=>$this->request->data['city'],
					'address'=>$this->request->data['address'],
					'province'=>$this->request->data['province'],
					'country'=>$this->request->data['country'],
					'zip'=>$this->request->data['zip'],
					'order_date'=>date('Y-m-d H:i:s'),
					'data'=>serialize($shopping_cart),
					'payment_method'=>$payment_method,
					'total_sale'=>$total_price,
					'ongkir_id'=>$this->Session->read('city_id'),
					'ongkir_value' => $base_ongkir,
					'total_weight' => $kg,
					'total_ongkir' => $total_ongkir,
					'total_admin_fee' => $admin_fee,
					'n_status' => 0
			));


			$this->Doku->create();
			$rs_doku = $this->Doku->save(array(
					'catalog_order_id'=>$this->MerchandiseOrder->getInsertID(),
					'po_number'=>$transaction_id,
					'transidmerchant'=>str_replace('-', '', $transaction_id),
					'totalamount'=>number_format($total_price,2,'.',''),
					'words'=>$hash_words,
					'statustype'=>'',
					'response_code'=>'',
					'approvalcode'=>'',
					'trxstatus'=>'Requested',
					'payment_channel'=>$payment_channel,
					'paymentcode'=>'',
					'session_id'=>$trx_session_id,
					'bank_issuer'=>'',
					'creditcard'=>'',
					'payment_date_time'=>date("Y-m-d H:i:s"),
					'verifyid'=>'',
					'verifyscore'=>'',
					'verifystatus'=>'',
					'additionaldata'=>$additionaldata
			));
			
			CakeLog::write('doku',date("Y-m-d H:i:s").' - [request] create doku entry '.json_encode($rs_doku));
			$dataSource->commit();

		}catch(Exception $e){
			$dataSource->rollback();
			CakeLog::write('doku',date("Y-m-d H:i:s").' - [request] error '.$e->getMessage());

			try{
				$dataSource2 = $this->MerchandiseOrder->getDataSource();
				$dataSource2->begin();
				//update
				$this->MerchandiseOrder->id = $rs_order['MerchandiseOrder']['id'];

				$this->MerchandiseOrder->save(array(
						'fb_id'=>$fb_id,
						'po_number'=>$transaction_id,
						'game_team_id'=>intval($this->userData['team']['id']),
						'user_id'=>$rs_user['User']['id'],
						'first_name'=>$this->request->data['first_name'],
						'last_name'=>$this->request->data['last_name'],
						'ktp'=>$this->request->data['ktp'],
						'email'=>$this->request->data['email'],
						'phone'=>$this->request->data['phone'],
						'city'=>$this->request->data['city'],
						'address'=>$this->request->data['address'],
						'province'=>$this->request->data['province'],
						'country'=>$this->request->data['country'],
						'zip'=>$this->request->data['zip'],
						'order_date'=>date('Y-m-d H:i:s'),
						'data'=>serialize($shopping_cart),
						'payment_method'=>$payment_method,
						'total_sale'=>$total_price,
						'ongkir_id'=>$this->Session->read('city_id'),
						'ongkir_value' => $base_ongkir,
						'total_weight' => $kg,
						'total_ongkir' => $total_ongkir,
						'total_admin_fee' => $admin_fee,
						'n_status' => 0
				));

				$this->Doku->id = $rs_doku['Doku']['id'];
		    	$this->Doku->save(array(
						'catalog_order_id'=>$rs_order['MerchandiseOrder']['id'],
						'po_number'=>$transaction_id,
						'transidmerchant'=>str_replace('-', '', $transaction_id),
						'totalamount'=>number_format($total_price,2,'.',''),
						'words'=>$hash_words,
						'statustype'=>'',
						'response_code'=>'',
						'approvalcode'=>'',
						'trxstatus'=>'Requested',
						'payment_channel'=>$payment_channel,
						'paymentcode'=>'',
						'session_id'=>$trx_session_id,
						'bank_issuer'=>'',
						'creditcard'=>'',
						'payment_date_time'=>date("Y-m-d H:i:s"),
						'verifyid'=>'',
						'verifyscore'=>'',
						'verifystatus'=>'',
						'additionaldata'=>$additionaldata
				));
				CakeLog::write('doku',date("Y-m-d H:i:s").' - [request] 
									update doku entry order_id '.$rs_order['MerchandiseOrder']['id']);
				$dataSource2->commit();
			}catch(Exception $e2){
				$dataSource2->rollback();
				//overide data variable
				$data = NULL;
				CakeLog::write('doku',date("Y-m-d H:i:s").' - [request] error doku entry '.json_encode($rs_doku));
			}
		}
		$this->set('order_doku', $data);

	}

	public function ecash(){
		//on the last minute, we recheck the stock again, just for sure.
		$stock_status = $this->recheckStockBeforePayment();
		if($stock_status){
			$target = decrypt_param($this->request->query['r']);
			$this->redirect($target);	
		}
	}

	public function success()
	{
		$this->Session->delete('shopping_cart');
		$this->Session->delete('city_id');
		$pending = false;
		$session_id = decrypt_param($this->request->query['sid']);
		$rs_doku = $this->get_doku_transaction($session_id);
		$this->set('rs_doku', $rs_doku);
		if(isset($this->request->query['pending']))
		{
			$pending = true;
		}
		$this->set('is_pending', $pending);
	}

	public function failure()
	{
		$this->Session->delete('shopping_cart');
		$this->Session->delete('city_id');
	}

	private function get_doku_transaction($session_id)
	{
		$this->loadModel("Doku");
		$this->loadModel("MerchandiseOrder");

		$rs_doku = $this->Doku->findBySession_id($session_id);
		$rs_order = $this->MerchandiseOrder->findById($rs_doku['Doku']['catalog_order_id']);
		$rs_merge = array_merge($rs_doku, $rs_order);

		return $rs_merge;
	}

	public function payment(){
		

		$body = file_get_contents('php://input');

		if(!empty($body)) {
		  $data = explode(",", $body);
		  $ticket = $data[0];
		  $phone_no = $data[1];
		  $trace_no = $data[2];
		  $order_id = trim($data[3]);
		  $status = trim($data[4]);
		  
		  $returnid = $ticket;

		  //todo ini harusnya di set di redis saja.
		  $this->Session->write('ecash_return',array(
		  							'id'=>$ticket,
		  							'nohp'=>$phone_no,
		  							'transaction_id'=>$order_id,
		  							'trace_number'=>$trace_no,
		  							'status'=>$status
		  						));

		  die();
		}else{
			$rs  = $this->Game->EcashValidate($this->request->query['id']);
			list($id,$trace_number,$nohp,$transaction_id,$status) = explode(',',$rs['data']);

			$result = array(
				'id'=>trim($id),
				'trace_number'=>trim($trace_number),
				'nohp'=>trim($nohp),
				'transaction_id'=>trim($transaction_id),
				'status'=>trim($status)
			);

			$is_valid = true;
			if(Configure::read('debug')==0){
				//ini masih blm bisa diimplement,
				//karena sessionnya akan berbeda dengan session ketika kita mendapat return.
				/*
				$ecash_validate = $this->Session->read('ecash_return');
				print "foo";
				if($result['id']==$ecash_validate['id'] &&
					$result['trace_number']==$ecash_validate['trace_number'] &&
					$result['nohp']==$ecash_validate['nohp'] &&
					$result['transaction_id']==$ecash_validate['transaction_id'] &&
					$result['status']==$ecash_validate['status']
					){
					$is_valid = true;
				}else{
					$is_valid = false;
				}
				*/
				$is_valid = true;
			}

			if(strtoupper($result['status'])=='SUCCESS' && $is_valid){

				$this->pay_with_ecash_completed($result);

			}else{
				$this->Session->setFlash("Pembayaran anda gagal diproses, silahkan coba kembali !");
				$this->redirect('/merchandises/buy');
				

			}
		}

		
	}
	private function pay_with_ecash_completed($ecash_data){
		
		
		//attach ongkos kirim list.
		$this->getOngkirList();


		//get transaction data from session
		$sess = unserialize($this->Session->read($ecash_data['transaction_id']));
		if($ecash_data['status']=="SUCCESS"){
			$is_transaction_ok = true;
		}else{
			$is_transaction_ok = false;
		}

		$data = $sess['data'];
		$shopping_cart = $sess['shopping_cart'];
		
		if(sizeof($shopping_cart) > 0){
			$total_price = 0;
		
			$all_digital = true;
			$kg = 0;
			$total_admin_fee = 0;
			for($i=0;$i<sizeof($shopping_cart);$i++){

				$item = $shopping_cart[$i]['data']['MerchandiseItem'];
				$kg += floatval($item['weight']) * intval($shopping_cart[$i]['qty']);
				$total_admin_fee += $item['admin_fee'];
				$total_price += (intval($shopping_cart[$i]['qty']) * intval($item['price_money']));
				//is there any non-digital item ?
				if($item['merchandise_type']==0){
					$all_digital = false;
				}
			}
			$kg = ceil($kg);
			
			$admin_fee = $total_admin_fee;
			$enable_ongkir = true;
			if(count($shopping_cart) > 1)
			{
				//$admin_fee = Configure::read('PO_ADMIN_FEE'); ->skip bro
			}
			else
			{
				//check enable or disable admin fee
				if($item['enable_ongkir'] == 0)
				{
					$enable_ongkir = false;
				}
			}

			if($all_digital){
				$admin_fee = 0;
			}
			$total_price += $admin_fee;

			if($enable_ongkir)
			{
				foreach($this->ongkirList as $ongkir){
					if($ongkir['Ongkir']['id'] == intval($this->Session->read('city_id'))){
						$base_ongkir = intval($ongkir['Ongkir']['cost']);
						break;
					}
				}
			}
			
			$total_ongkir  = $base_ongkir * $kg;

			//tambahkan harga ongkir kedalam total price
			$total_price+=$total_ongkir;

			$data['fb_id'] = $this->userDetail['User']['fb_id'];
			$data['merchandise_item_id'] = 0;
			$data['game_team_id'] = $this->userData['team']['id'];
			$data['user_id'] = $this->userDetail['User']['id'];
			$data['order_type'] = 1;
			$data['ongkir_id'] = intval($this->Session->read('city_id')); //the related ongkir_id

			//we need ongkir value
			$ok = $this->Ongkir->findById($this->Session->read('city_id'));
			$data['ongkir_value'] = $base_ongkir;
			$data['total_weight'] = $kg;
			$data['total_ongkir'] = $total_ongkir;
			$data['total_admin_fee'] = $total_admin_fee;
			
			if($all_digital){
				$data['n_status'] = 3;	
			}else{
				$data['n_status'] = 1;
			}

			$data['order_date'] = date("Y-m-d H:i:s");
			$data['data'] = serialize($shopping_cart);
			$data['po_number'] = $ecash_data['transaction_id'];
			$data['total_sale'] = intval($total_price);
			$data['payment_method'] = 'ecash';
			$data['trace_code'] = $ecash_data['trace_number'];

			$this->MerchandiseOrder->create();
			$rs = $this->MerchandiseOrder->save($data);	
			
			if($this->MerchandiseOrder->getInsertID() > 0){
				$this->process_items($shopping_cart,$ecash_data['transaction_id']);
				$this->set('apply_digital_perk_error',$this->Session->read('apply_digital_perk_error'));
				$this->Session->write('apply_digital_perk_error',null);
				//reset the csrf token
				$this->Session->write('po_csrf',null);
				//-->
			
			 //reset the shopping_cart in session (disable these for debug only)
			 $this->Session->write('shopping_cart',null);
			 $this->Session->write($ecash_data['transaction_id'],null);
			 $this->Session->write('final_'.$ecash_data['transaction_id'],$ecash_data['status']);
			}
		}else{
			$final_transaction = $this->Session->read('final_'.$ecash_data['transaction_id']);

			if(!isset($final_transaction)){
				$final_transaction = 'FAILED';
			}
			if($final_transaction == 'SUCCESS'){
				$is_transaction_ok = true;
			}else{
				$is_transaction_ok = false;
			}
			
		}
		$this->set('is_transaction_ok',$is_transaction_ok);
		
		
	}
	private function pay_with_coins($po_number){
		
		

		$result = array('is_transaction_ok'=>false);

		//display the cart content
		$shopping_cart = $this->Session->read('shopping_cart');
		if(sizeof($shopping_cart) > 0){
			//get total coins to be spent.
			$total_coins = 0;
			$all_digital = true;
			$kg = 0;
			$all_stock_ok = true;
			$total_admin_fee = 0;
			for($i=0;$i<sizeof($shopping_cart);$i++){
				if($shopping_cart[$i]['qty']<=0){
					$shopping_cart[$i]['qty'] = 1;
				}
				$shopping_cart[$i]['data'] = $this->MerchandiseItem->findById($shopping_cart[$i]['item_id']);
				$item = $shopping_cart[$i]['data']['MerchandiseItem'];
				$kg += floatval($item['weight']) * intval($shopping_cart[$i]['qty']);
				$total_admin_fee += $item['admin_fee'];

				$total_coins += (intval($shopping_cart[$i]['qty']) * intval($item['price_credit']));
				//is there any non-digital item ?
				if($item['merchandise_type']==0){
					$all_digital = false;
				}
				if($item['stock'] <= 0){
					$all_stock_ok = false;
				}
			}
			$kg = ceil($kg);
			
			//1. check if the coins are sufficient
			if(intval($this->cash) >= $total_coins){
				$no_fund = false;
			}else{
				$no_fund = true;
			}
			
			//2. if fund is available, we create transaction id and order detail.
			if(!$no_fund && $all_stock_ok){

				$data = $this->request->data;
				$data['merchandise_item_id'] = 0;
				$data['game_team_id'] = $this->userData['team']['id'];
				$data['user_id'] = $this->userDetail['User']['id'];
				$data['order_type'] = 1;
				$data['fb_id'] = $this->userDetail['User']['fb_id'];
				$data['ongkir_id'] = intval($this->Session->read('city_id'));
				//we need ongkir value
				$ok = $this->Ongkir->findById($this->Session->read('city_id'));


				$data['ongkir_value'] = $ok['Ongkir']['cost'];
				$data['total_weight'] = $kg;
				$data['total_ongkir'] = $kg * $ok['Ongkir']['cost'];
				$data['total_admin_fee'] = $total_admin_fee;
			
				if($all_digital){
					$data['n_status'] = 3;
				}else{
					$data['n_status'] = 0;
				}
				$data['order_date'] = date("Y-m-d H:i:s");
				$data['data'] = serialize($shopping_cart);
				$data['po_number'] = $po_number;
				$data['total_sale'] = intval($total_coins);
				$data['payment_method'] = 'coins';

				$this->MerchandiseOrder->create();
				$rs = $this->MerchandiseOrder->save($data);	
				if($rs){
					$result['order_id'] = $this->MerchandiseOrder->id;
					//time to deduct the money
					$this->Game->query("
					INSERT IGNORE INTO ".Configure::read('FRONTEND_SCHEMA').".game_transactions
					(fb_id,transaction_name,transaction_dt,amount,
					 details)
					VALUES
					({$data['fb_id']},'purchase_{$data['po_number']}',
						NOW(),
						-{$total_coins},
						'{$data['po_number']} - {$result['order_id']}');");
					
					//update cash summary
					$this->Game->query("INSERT INTO ".Configure::read('FRONTEND_SCHEMA').".game_team_cash
					(fb_id,cash)
					SELECT fb_id,SUM(amount) AS cash 
					FROM ".Configure::read('FRONTEND_SCHEMA').".game_transactions
					WHERE fb_id = {$data['fb_id']}
					GROUP BY fb_id
					ON DUPLICATE KEY UPDATE
					cash = VALUES(cash);");

					//flag transaction as ok
					$is_transaction_ok = true;
					$result['is_transaction_ok'] = $is_transaction_ok;
					$result['items'] = $shopping_cart;
					$result['order_id'] = $data['po_number'];
				}
			}

			$result['no_fund'] = $no_fund;
		}else{
			$result['no_fund'] = false;
			$result['order_id'] = 0;
		}

		$this->Session->write('PO_NUMBER', NULL);
		
		return $result;
	}

	/*
	* process digital items
	* when the digital items redeemed, we reduce its stock.
	*/
	private function process_items($items,$order_id){	
		$this->loadModel('MerchandiseItemPerk');
		
		for($i=0; $i < sizeof($items); $i++){
			
			$item = $items[$i]['data']['MerchandiseItem'];
			$qty = $items[$i]['qty'];
			if($item['merchandise_type']==1){
				$this->apply_digital_perk($this->userData['team']['id'],
											$item['perk_id'],$order_id);

			}else if($item['perk_id'] == 0){
				$perks = $this->MerchandiseItemPerk->find('all',
													array('conditions'=>array('merchandise_item_id'=>$item['id']),
														 'limit'=>20)
													);
				CakeLog::write('apply_digital_perk',date("Y-m-d H:i:s").'-'.$item['id'].'-'.json_encode($perks));
				for($j=0;$j<sizeof($perks);$j++){

					$this->apply_digital_perk($this->userData['team']['id'],
											$perks[$j]['MerchandiseItemPerk']['perk_id'],$order_id);
				}
			
			}
			$this->reduceStock($item['id'],$qty);
			CakeLog::write('stock','process_items - '.$order_id.' - '.$item['id'].' - '.$qty.' REDUCED');
		}
	}
	/*
	* book items stock.
	* upon checkout, the stock will be locked for 5 minutes to prevent other people to order
	*/
	private function book_items($items,$order_id){	
		$this->loadModel('MerchandiseItemPerk');
		
		for($i=0; $i < sizeof($items); $i++){
			
			$qty = $items[$i]['qty'];
			$keyname = 'claim_stock_'.$items[$i]['item_id'].'_'.$this->userData['team']['id'];
			$ttl = 15*60; //user have 5 minutes to complete the payment.
			$this->Game->storeToTmp($this->userData['team']['id'],
									$keyname,
									$qty,
									$ttl);

			$this->Game->storeToTmp($this->userData['team']['id'],
									'purchase_order_'.$order_id,
									'1',
									$ttl);

			CakeLog::write('stock','lock item- '.$order_id.' - '.$items[$i]['item_id'].' - qty : '.$qty.' key:'.$keyname);
		}
	}
	/*
	public function order(){
		$this->loadModel('MerchandiseItem');
		$this->loadModel('MerchandiseCategory');

		$item_id = $this->Session->read('po_item_id');
		
		//parno mode.
		$item_id = Sanitize::clean($item_id);

		//get the item detail
		$item = $this->MerchandiseItem->findById($item_id);
		if(isset($item['MerchandiseItem'])){
			$this->set('item',$item['MerchandiseItem']);	
		}
		
		//these is our flags
		$is_transaction_ok = true;
		$no_fund = false;
		
		//make sure the csrf token still valid

		//-> csrf check di disable dulu selama development
		if(
			(strlen($this->request->data['ct']) > 0)
				&& ($this->Session->read('po_csrf') == $this->request->data['ct'])
		  ){

			$result = $this->pay_with_game_cash($item_id,$item);
			$is_transaction_ok = $result['is_transaction_ok'];
			$no_fund = $result['no_fund'];
			if($is_transaction_ok == true){
				//we reduce the stock in front
				//$this->ReduceStock($item_id,$item['MerchandiseItem']);
			}
		}else{
			$is_transaction_ok = false;
		}
		
		$this->set('apply_digital_perk_error',$this->Session->read('apply_digital_perk_error'));
		$this->set('is_transaction_ok',$is_transaction_ok);
		$this->set('no_fund',$no_fund);
		$this->Session->write('apply_digital_perk_error',null);
		//reset the csrf token
		$this->Session->write('po_csrf',null);
		//-->

		//reset the item_id in session (disable these for debug only)
		$this->Session->write('po_item_id',0);
	}
	*/
	public function cart(){
		$shopping_cart = array();
		if($this->request->is('post')){

			$item_id = $this->request->data['item_id'];
			$qty = $this->request->data['qty'];

			for($i=0;$i<sizeof($item_id);$i++){
				$shopping_cart[] = array('item_id'=>intval($item_id[$i]),
										  'qty'=>intval($qty[$i]));
			}
			
			$this->Session->write('shopping_cart',$shopping_cart);	
			$this->Session->write('city_id',intval($this->request->data['city_id']));	
			
			
		}
		
		$shopping_cart = $this->Session->read('shopping_cart');

		//default value for ongkir
		$enable_ongkir = true;

		//if shopping cart only 1 item
		if(count($shopping_cart) < 2 && count($shopping_cart) != 0)
		{
			//query for check enable or disable ongkir
			$rs_enable_ongkir = $this->MerchandiseItem->findById($shopping_cart[0]['item_id']);

			if($rs_enable_ongkir['MerchandiseItem']['enable_ongkir'] != 1)
			{
				$enable_ongkir = false;
			}
		}

		$this->set('enable_ongkir', $enable_ongkir);
			
		
		$this->request->data['update_type'] = intval(@$this->request->data['update_type']);


		if($this->request->data['update_type']==1){

			$this->checkout($shopping_cart);

		}else{

			$this->displayShoppingCartContent($shopping_cart);
		}
		
	}
	private function displayShoppingCartContent($shopping_cart){
		for($i=0;$i<sizeof($shopping_cart);$i++){
			$shopping_cart[$i]['data'] = $this->MerchandiseItem->findById($shopping_cart[$i]['item_id']);
			$out_of_stock = $this->Session->read('out_of_stock');


			for($j=0;$j<sizeof($out_of_stock);$j++){
				$shopping_cart[$i]['out_of_stock'] = false;
				if($out_of_stock[$j]['MerchandiseItem']['id'] == $shopping_cart[$i]['item_id']){
					$shopping_cart[$i]['qty'] = $out_of_stock[$j]['MerchandiseItem']['stock'];
					$shopping_cart[$i]['out_of_stock'] = true;
					break;
				}
			}
		}
		$this->set('shopping_cart',$shopping_cart);
		$this->set('city_id',intval($this->Session->read('city_id')));
		$this->getOngkirList();
	}
	private function getOngkirList(){
		
		$ongkir = $this->Ongkir->find('all',
									array('limit'=>10000,
									'order'=>array('Ongkir.kecamatan'=>'ASC')));
		$this->ongkirList = $ongkir;
		$this->set('ongkir',$ongkir);
	}
	/*
	* checkout procedure
	* 1. make sure that all items stock are available
	* 2. if stock available, redirect to order
	*/
	private function checkout($shopping_cart){
		$game_team_id = $this->userData['team']['id'];
		$stocks = $this->getItemsStock();
		$stock_available = true;
		$out_of_stock = array();

		//capture the city_id (of ongkir)
		$this->Session->write('city_id',intval($this->request->data['city_id']));
		
		if(sizeof($stocks)>0){
			foreach($stocks as $item_id=>$stock){
				if($stock==0){
					$stock_available = false;
					$claimed_qty = $this->getClaimedStock($game_team_id,$item_id);
					$item =  $this->MerchandiseItem->findById($item_id);
					
					$item['MerchandiseItem']['stock'] = intval($item['MerchandiseItem']['stock']) - intval($claimed_qty);
					$out_of_stock[] =  $item;
					
				}
			}
		}
		if($stock_available){
			$this->redirect('/merchandises/buy');
		}else{
			$str = "Mohon maaf, stok barang - barang berikut tidak mencukupi :<br/>";
			for($i=0;$i<sizeof($out_of_stock);$i++){
				$str .= $out_of_stock[$i]['MerchandiseItem']['name']." - Sisa : ".
						$out_of_stock[$i]['MerchandiseItem']['stock']."<br/>";
			}
			$this->Session->write('out_of_stock',$out_of_stock);
			$this->Session->setFlash($str);
			

			$this->displayShoppingCartContent($shopping_cart);
		}
	}
	/*
	* recheck the items stock just before we close the order.
	*/
	private function recheckStockBeforePayment(){
		$game_team_id = $this->userData['team']['id'];

		$stocks = $this->getItemsStock();
		$stock_available = true;
		$out_of_stock = array();

		
		
		if(sizeof($stocks)>0){
			foreach($stocks as $item_id=>$stock){
				if($stock==0){
					$stock_available = false;

					$claimed_qty = $this->getClaimedStock($game_team_id,$item_id);
					$item =  $this->MerchandiseItem->findById($item_id);
					$item['MerchandiseItem']['stock'] -= intval($claimed_qty);
					$out_of_stock[] = $item;
				}
			}
		}
		
		if($stock_available){
			return true;
		}else{
			$str = "Mohon maaf, stok barang - barang berikut tidak mencukupi :<br/>";
			for($i=0;$i<sizeof($out_of_stock);$i++){
				$str .= $out_of_stock[$i]['MerchandiseItem']['name']." - Sisa : ".
						$out_of_stock[$i]['MerchandiseItem']['stock']."<br/>";
			}
			$this->Session->write('out_of_stock',$out_of_stock);
			$this->Session->setFlash($str);
			$this->redirect('/merchandises/cart');
		}
	}
	/*
	* check items stocks
	*/
	private function getItemsStock(){
		
		$items = $this->Session->read('shopping_cart');

		$rs = array();
		for($i=0;$i<sizeof($items);$i++){
			$item = $this->MerchandiseItem->findById(intval($items[$i]['item_id']));
			$game_team_id = $this->userData['team']['id'];
			
			$total_claimed_qty = $this->getClaimedStock($game_team_id,$items[$i]['item_id']);

			if((($item['MerchandiseItem']['stock'] - $total_claimed_qty) - $items[$i]['qty']) >= 0){
				$rs[intval($item['MerchandiseItem']['id'])] = intval($item['MerchandiseItem']['stock']);
			}else{
				$rs[intval($item['MerchandiseItem']['id'])] = 0;
			}
		}
		return $rs;
	}
	private function getClaimedStock($game_team_id,$item_id){
		$pattern = 'claim_stock_'.$item_id.'_*';
		$claimed = $this->Game->getTmpKeys($game_team_id,
								$pattern);
		$total_claimed_qty = 0;
		if($claimed['status']==1){
			for($k=0;$k<sizeof($claimed['data']);$k++){
				//pastikan yg kita cek itu bukan punya si user
				$arr = explode("_",$claimed['data'][$k]);
				$owner_id = $arr[3];
				if($owner_id != $game_team_id){
					$claimed_qty = $this->Game->getFromTmp($game_team_id,$claimed['data'][$k]);
					$total_claimed_qty += intval($claimed_qty['data']);	
				}
			}
		}
		return $total_claimed_qty;
	}
	private function ReduceStock($item_id,$qty=1){
		try{
			$item_id = intval($item_id);
			$sql1 = "UPDATE ".Configure::read('FRONTEND_SCHEMA').".merchandise_items SET stock = stock - {$qty} WHERE id = {$item_id}";
			$this->MerchandiseItem->query($sql1);

			Cakelog::write('api_stock', 'Merchandise.ReduceStock type:sql1 sql:'.$sql1);

			$sql2 = "UPDATE ".Configure::read('FRONTEND_SCHEMA').".merchandise_items SET stock = 0 WHERE id = {$item_id} AND stock < 0";
			$this->MerchandiseItem->query($sql2);

			Cakelog::write('api_stock', 'Merchandise.ReduceStock type:sql2 sql:'.$sql2);

			CakeLog::write('stock','stock '.$item_id." {$qty} reduced");
		}catch(Exception $e){
			Cakelog::write('api_stock', 'Merchandise.ReduceStock type:sql1 sql:'.$sql1);
			Cakelog::write('api_stock', 'Merchandise.ReduceStock type:sql1 sql:'.$sql2);
			
			Cakelog::write('api_stock', 'Merchandise.ReduceStock type:error msg:'.$e->getMessage());
		}	
	}
	private function pay_with_ingame_funds($item_id,$item){
		//if valid, 
		//save the order to database
		//at these time, we assume that user will pay with in-game funds
		$data = $this->request->data;
		$data['merchandise_item_id'] = $this->Session->read('po_item_id');
		$data['game_team_id'] = $this->userData['team']['id'];
		$data['user_id'] = $this->userDetail['User']['id'];
		$data['order_type'] = 0;
		$data['n_status'] = 0;
		$data['order_date'] = date("Y-m-d H:i:s");
		$data['po_number'] = $item_id.'-'.$data['game_team_id'].'-'.date("ymdhis");

		//oops, before that, we need to know if user has sufficient funds
		

		$finance = $this->Game->financial_statements($this->userData['fb_id']);
		
		if(intval($finance['data']['budget']) > 
				intval($item['MerchandiseItem']['price_currency'])){
			$no_fund = false;
		}else{
			$no_fund = true;
		}
		

		
		
		if(!$no_fund){
			//ok the user has enough fund... purchase it now.
			$this->MerchandiseOrder->create();
			$rs = $this->MerchandiseOrder->save($data);	

			if($rs){
				//get next match's id
				
				$match = $this->nextMatch['match'];
				$game_id = $match['game_id'];
				$matchday = $match['matchday'];
				//time to deduct the money
				$this->Game->query("
				INSERT IGNORE INTO ".$_SESSION['ffgamedb'].".game_team_expenditures
				(game_team_id,item_name,item_type,
				 amount,game_id,match_day,item_total,base_price)
				VALUES
				({$data['game_team_id']},'purchase merchandise - {$data['po_number']}',
				  2,-{$item['MerchandiseItem']['price_currency']},
				  '{$game_id}',{$matchday},1,1);");
				
				$is_transaction_ok = true;

			}else{
				$is_transaction_ok = false;
			}
		}else{
			$is_transaction_ok = false;
			$no_fund = true;
		}
		return array('is_transaction_ok'=>$is_transaction_ok,
						'no_fund'=>$no_fund);
	}

	private function pay_with_game_cash($item_id,$item){

		//if valid, 
		//save the order to database
		//at these time, we assume that user will pay with in-game funds
		$data = $this->request->data;
		$data['merchandise_item_id'] = $this->Session->read('po_item_id');
		$data['game_team_id'] = $this->userData['team']['id'];
		$data['user_id'] = $this->userDetail['User']['id'];
		$data['fb_id'] = $this->userDetail['User']['fb_id'];
		$data['order_type'] = 1;
		$data['n_status'] = 0;
		$data['order_date'] = date("Y-m-d H:i:s");
		$data['po_number'] = $item_id.'-'.$data['game_team_id'].'-'.date("ymdhis");
	
		//oops, before that, we need to know if user has sufficient funds
		if(intval($this->cash) > 
				intval($item['MerchandiseItem']['price_credit'])){
			$no_fund = false;
		}else{
			$no_fund = true;
		}
		

		$this->loadModel('MerchandiseOrder');
		
		if(!$no_fund){
			//ok the user has enough fund... purchase it now.


			//1. check if the item is digital or non-digital
			// if it's digital, we automatically set the order status into closed.
			// and redeem the perk.

			//this is for safety precaution
			//make sure that the digital is successfully applied before processing the order

			$continue = true; 
			//make sure that the match isnt in progress.
			//people cant buy the perk while the match is in progress.
			if($this->can_update_formation()){
				if($item['MerchandiseItem']['merchandise_type']==1){
					$data['n_status']=3; //order status : closed
					$continue = $this->apply_digital_perk($data['game_team_id'],
											$item['MerchandiseItem']['perk_id']);
				}
			}else{
				$continue = false;
			}
			
			if($continue){
				$this->MerchandiseOrder->create();
				$rs = $this->MerchandiseOrder->save($data);		
			}else{
				$rs = false;
			}
			

			if($rs){
				//get next match's id
				$match = $this->nextMatch['match'];
				$game_id = $match['game_id'];
				$matchday = $match['matchday'];
				//time to deduct the money
				$this->Game->query("
				INSERT IGNORE INTO ".Configure::read('FRONTEND_SCHEMA').".game_transactions
				(fb_id,transaction_name,transaction_dt,amount,
				 details)
				VALUES
				({$data['fb_id']},'purchase_{$data['po_number']}',
					NOW(),
					-{$item['MerchandiseItem']['price_credit']},
					'{$data['po_number']} - {$item['MerchandiseItem']['name']}');");
				
				//update cash summary
				$this->Game->query("INSERT INTO ".Configure::read('FRONTEND_SCHEMA').".game_team_cash
				(fb_id,cash)
				SELECT fb_id,SUM(amount) AS cash 
				FROM ".Configure::read('FRONTEND_SCHEMA').".game_transactions
				WHERE game_team_id = {$data['fb_id']}
				GROUP BY fb_id
				ON DUPLICATE KEY UPDATE
				cash = VALUES(cash);");

				//flag transaction as ok
				$is_transaction_ok = true;

			}else{
				$is_transaction_ok = false;
			}
		}else{
			$is_transaction_ok = false;
			$no_fund = true;
		}
		return array('is_transaction_ok'=>$is_transaction_ok,
						'no_fund'=>$no_fund);
	}
	//retrieve customer's first name and last name
	private function getDetailedName(){
		$name_arr = explode(" ",$this->userDetail['User']['name']);
		$first_name = $name_arr[0];
		$last_name = '';
		for($i=1;$i<sizeof($name_arr);$i++){
			$last_name = $name_arr[$i].' ';
		}
		$last_name = trim($last_name);
		return array('first_name'=>$first_name,
					 'last_name'=>$last_name);
	}
	private function apply_digital_perk($game_team_id,$perk_id,$unique=false){
		$this->loadModel('MasterPerk');
		$this->MasterPerk->useDbConfig = $_SESSION['ffgamedb'];
		
		CakeLog::write('apply_digital_perk',date("Y-m-d H:i:s").' - '.$game_team_id.' - '.$perk_id);
		$perk = $this->MasterPerk->findById($perk_id);
		$perk['MasterPerk']['data'] = unserialize($perk['MasterPerk']['data']);
		switch($perk['MasterPerk']['data']['type']){
			case "jersey":
				return $this->apply_jersey_perk($game_team_id,$perk['MasterPerk']);
			break;
			case "free_player":
				$player_id = $perk['MasterPerk']['data']['player_id'];
				$amount = intval($perk['MasterPerk']['data']['money_reward']);
				return $this->apply_free_player_perk($game_team_id,$player_id,$unique,$amount);
			break;
			default:
				//check if it's a money perk
				if($perk['MasterPerk']['perk_name']=='IMMEDIATE_MONEY'){
					$this->apply_money_perk($game_team_id,$unique,$perk['MasterPerk']['amount']);
				}else{
					//for everything else, let the game API handle the task
					$rs = $this->Game->apply_digital_perk($game_team_id,$perk_id);

					CakeLog::write('apply_digital_perk',date("Y-m-d H:i:s").' - '.json_encode($rs));
					if($rs['data']['can_add'] && $rs['data']['success']){
						return true;
					}else if(!$rs['data']['can_add']){
						//tells us that the perk cannot be redeemed because these perk is already redeemed before
						$this->Session->write('apply_digital_perk_error','1');
					}else{
						//tells us that the perk cannot be redeemed because we cannot save the perk.
						$this->Session->write('apply_digital_perk_error','2');
					}
				}
				
			break;
		}
		
	}
	/*
	* $unique_id -> can be item_id, or any unique identifier we can use as transaction name.
	*/
	private function apply_money_perk($game_team_id,$unique_id,$amount){
		$transaction_name = 'apply_money_perk_'.$unique_id;
		CakeLog::write('apply_digital_perk',date("Y-m-d H:i:s").' - '.$game_team_id.' - apply_money_perk_'.$unique_id.' -> '.$amount);
		$rs = $this->Game->add_expenditure($game_team_id,$transaction_name,intval($amount));
		if($rs['status']==1){
			return true;
		}
	}
	private function apply_free_player_perk($game_team_id,$player_id,$unique_id,$amount){
		//check if the user has the player
		$my_player = $this->Game->query("SELECT * FROM ".$_SESSION['ffgamedb'].".game_team_players a
							WHERE game_team_id={$game_team_id} 
							AND player_id='{$player_id}'",false);
		if($my_player[0]['a']['player_id'] == $player_id){
			CakeLog::write('apply_digital_perk',date("Y-m-d H:i:s").' - '.$game_team_id.' - '.$unique_id.' - player exists, reward money instead : '.$amount);
			//reward money instead
			return $this->apply_money_perk($game_team_id,$unique_id,$amount);
		}else{
			CakeLog::write('apply_digital_perk',date("Y-m-d H:i:s").' - '.$game_team_id.' - '.$unique_id.' - free player : '.$player_id);
			return $this->Game->query("INSERT IGNORE INTO ".$_SESSION['ffgamedb'].".game_team_players
								(game_team_id,player_id)
								VALUES({$game_team_id},'{$player_id}')",false);
		}

	}
	private function apply_jersey_perk($game_team_id,$perk_data){
		
		$this->DigitalPerk->cache = false;


		//only 1 jersey can be used


		//so we disabled all existing jersey
		
		$this->DigitalPerk->bindModel(
			array('belongsTo'=>array(
				'MasterPerk'=>array(
					'type'=>'inner',
					'foreignKey'=>false,
					'conditions'=>array(
						"MasterPerk.id = DigitalPerk.master_perk_id",
						"MasterPerk.perk_name = 'ACCESSORIES'"
					)
				)
			))
		);
		$current_perks = $this->DigitalPerk->find('all',array(
			'conditions'=>array('game_team_id'=>$game_team_id),
			'limit'=>40
		));
		$has_bought = false;
		$bought_id = 0;
		//we only take the jersey perks
		$jerseys = array();
		while(sizeof($current_perks)>0){
			$p = array_pop($current_perks);
			$p['MasterPerk']['data'] = unserialize($p['MasterPerk']['data']);
			if($p['MasterPerk']['data']['type']=='jersey'){
				$jerseys[] = $p['DigitalPerk']['id'];
			}
			if($p['DigitalPerk']['master_perk_id'] == $perk_data['id']){
				$has_bought = true;
				$bought_id = $p['DigitalPerk']['id'];
			}
		}
		//check if these jersy has been bought before.
		
		//disable the current jerseys
		for($i=0;$i<sizeof($jerseys);$i++){

			$this->DigitalPerk->id = intval($jerseys[$i]);
			$this->DigitalPerk->save(array(
				'n_status'=>0
			));
		}


		//add new jersey
		if(!$has_bought){
			$this->DigitalPerk->create();
			$rs = $this->DigitalPerk->save(
				array('game_team_id'=>$game_team_id,
					  'master_perk_id'=>$perk_data['id'],
					  'n_status'=>1,
					  'redeem_dt'=>date("Y-m-d H:i:s"),
					  'available'=>99999)
			);
			if(isset($rs['DigitalPerk'])){
				return true;
			}
		}else{
			//update the status only
			$this->DigitalPerk->id = intval($bought_id);
			$rs = $this->DigitalPerk->save(array(
				'n_status'=>1
			));
			if($rs){
				return true;
			}
		}
		
	}

	public function status($order_id){

	}
	public function offline(){

	}

	public function upgrade_member()
	{

	}

	public function bayar_bulanan()
	{

	}

}
