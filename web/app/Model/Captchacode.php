<?php

class Captchacode extends AppModel {
	
	var $useTable = false;
	var $name='Captchacode';
	var $captcha = ''; //intializing captcha var

	var $validate = array(
			'captcha'=>array(
				'rule' => array('matchCaptcha'),
				'message'=>'Failed validating human check.'
			),
		);

	function matchCaptcha($inputValue)	{
		return $inputValue['captcha']==$this->getCaptcha(); //return true or false after comparing submitted value with set value of captcha
	}

	function setCaptcha($value)	{
		$this->captcha = $value; //setting captcha value
	}

	function getCaptcha()	{
		return $this->captcha; //getting captcha value
	}

}