<?php

App::uses('AppController', 'Controller');


class PaymentController extends AppController {
	/**
	 * Controller name
	 *
	 * @var string
	 */
	public $name = 'Payment';

	public function beforeFilter()
	{
		parent::beforeFilter();
		$this->loadModel('User');
		$this->loadModel('MembershipTransaction');
	}

	public function index()
	{
		$pro_league_user = $this->User->find('count', array('conditions' => array(
											'paid_member' => 1),
											'fields' => 'DISTINCT User.id'));


		$total_proleague_transaction = $this->MembershipTransaction->find('count',
																			array(

																				'conditions'=>array('transaction_type' => 'SUBSCRIPTION'),
																				'group' => 'fb_id',
																				'limit' => 100000)
																			);

		$pro25 = $this->User->query("SELECT count(a.id) as  total FROM fantasy.users a
							INNER JOIN fantasy.member_billings b ON a.fb_id = b.fb_id WHERE 
							a.paid_member_status = 1 AND a.paid_plan = 'pro1'");

		$pro50 = $this->User->query("SELECT count(a.id) as  total FROM fantasy.users a
							INNER JOIN fantasy.member_billings b ON a.fb_id = b.fb_id WHERE 
							a.paid_member_status = 1 AND a.paid_plan = 'pro2'");

		$rs_transaction = $this->MembershipTransaction->find('all', array('limit' => 100000));

		$total_trivia = 0;
		$total_cat1 = 0;
		$total_cat2 = 0;
		$total_cat3 = 0;
		foreach ($rs_transaction as $key => $value)
		{
			if($value['MembershipTransaction']['transaction_type'] == 'UNLOCK TRIVIA')
			{
				$total_trivia++;
			}
			else if($value['MembershipTransaction']['transaction_type'] == 'UNLOCK CATALOG_01')
			{
				$total_cat1++;
			}
			else if($value['MembershipTransaction']['transaction_type'] == 'UNLOCK CATALOG_02')
			{
				$total_cat2++;
			}
			else if($value['MembershipTransaction']['transaction_type'] == 'UNLOCK CATALOG_03')
			{
				$total_cat3++;
			}
		}

		$this->set('pro_league_user', $pro_league_user);
		$this->set('active_proleague_user', $pro25[0][0]['total'] + $pro50[0][0]['total']);
		$this->set('total_proleague_transaction', $total_proleague_transaction);
		$this->set('pro25', $pro25[0][0]['total']);
		$this->set('pro50', $pro50[0][0]['total']);
		$this->set('total_trivia', $total_trivia);
		$this->set('total_cat1', $total_cat1);
		$this->set('total_cat2', $total_cat2);
		$this->set('total_cat3', $total_cat3);

		$this->set('rs_transaction', $rs_transaction);
	}

	public function get_payment()
	{
		$this->layout = 'ajax';
		$start = intval(@$this->request->query['start']);
		$limit = 20;
		$fields = array(
						'MembershipTransaction.id',
						'MembershipTransaction.fb_id',
						'MembershipTransaction.transaction_name',
						'MembershipTransaction.transaction_type',
						'MembershipTransaction.transaction_dt',
						'MembershipTransaction.amount',
						'MembershipTransaction.details',
						'MembershipTransaction.league',
						'User.name'
						);

		$this->MembershipTransaction->bindModel(
			array('belongsTo'=>array('User' => 
								array('type'=>'inner',
									'foreignKey'=>false,
									'conditions'=>'MembershipTransaction.fb_id = User.fb_id')
								)));

		$rs = $this->MembershipTransaction->find('all', 
												array(
													'fields'=>$fields,
													'offset'=>$start,
													'order'=>'id DESC',
													'conditions'=>array('MembershipTransaction.n_status' => '1'),
													'limit'=>$limit
												));

		foreach ($rs as $key => $value) 
		{
			$details_array = explode(',', $value['MembershipTransaction']['details']);
			$rs[$key]['MembershipTransaction']['hp'] = $details_array[2];
		}

		$this->set('response',array('status'=>1,'data'=>$rs,'next_offset'=>$start+$limit,'rows_per_page'=>$limit));
		$this->render('response');
	}

}