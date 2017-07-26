<?php
/**
 * Redeem Controller
 * page for redeeming digital coupon code.
 */
App::uses('AppController', 'Controller');
App::uses('Sanitize', 'Utility');

//reCaptcha library
require_once APP . 'Vendor' . DS. 'recaptchalib.php';

class RedeemController extends AppController {

/**
 * Controller name
 *
 * @var string
 */
	public $name = 'Redeem';

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
		$userData = $this->getUserData();
		$user = $this->userDetail;
		$this->set('user',$user['User']);
		if(!$this->hasTeam()){
			$this->redirect('/login/expired');
		}
	}

	public function hasTeam(){
		$userData = $this->getUserData();
		if(is_array($userData['team'])){
			return true;
		}
	}
	
	public function index(){

		//make sure that user will be banned for 24 hour if they input wrong codes 5 times
		$check = $this->Game->getInputAttempt($this->userData['team']['id'],
											'redeem_att');

		if($check['status']==1){
			if(intval($check['data']) > Configure::read('REDEEM_MAXIMUM_TRY')){
				$this->redirect('/redeem/disallowed');
			}
		}
		
		if($this->request->is('post')){
			$this->checkCode(intval($check['data']));
		}
		$captcha_public_key = Configure::read("reCaptcha_PUBLIC_KEY");
		$captcha_html = recaptcha_get_html($captcha_public_key);
		$this->set('captcha_html',$captcha_html);
	}
	//page for banned user
	public function disallowed(){

	}
	private function checkCode($total_try=0){
		
		if($this->isCaptchaValid()){

			
			if($this->redeemCode()){
				$kode = Sanitize::clean($this->request->data['kode']);
				$redeemed = $this->Game->query("SELECT coin_amount,ss_dollar 
									FROM ".$_SESSION['ffgamedb'].".coupons a
									INNER JOIN ".$_SESSION['ffgamedb'].".coupon_codes b
									ON a.id = b.coupon_id
									WHERE 
									b.coupon_code = '{$kode}' 
									AND 
									b.game_team_id = {$this->userData['team']['id']} 
									LIMIT 1;");
				$this->Session->setFlash('Selamat, ss$'.number_format($redeemed[0]['a']['ss_dollar']).
										' telah berhasil ditambahkan ke budget loe !');
			}else{
				$this->Game->setInputAttempt($this->userData['team']['id'],
											'redeem_att',$total_try + 1);
				$this->Session->setFlash('Maaf, kode yang lo masukkan salah !');
			}
		}else{
			$this->Session->setFlash('Maaf, kode captcha yang lo masukkan salah !');
		}
	}
	private function isCaptchaValid(){

		$resp = recaptcha_check_answer (
								Configure::read("reCaptcha_PRIVATE_KEY"),
                                $_SERVER["REMOTE_ADDR"],
                                $this->request->data["recaptcha_challenge_field"],
                                $this->request->data["recaptcha_response_field"]);
		return $resp->is_valid;
	}

	private function redeemCode(){
		return $this->Game->redeemCode(
			$this->userData['team']['id'],
			Sanitize::clean($this->request->data['kode'])
		);
	}
}
