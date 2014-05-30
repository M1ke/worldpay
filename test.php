<?php
require 'Worldpay.php';
require 'vendor/autload.php';

class testWorldpay extends PHPUnit_Framework_TestCase {
	private $worldpay;

	function __construct(){
		$this->worldpay=$this->worldpay_new();
	}

	private function worldpay_new(){
		$api_key=shell_exec('cat api_id');
		if (empty($api_key)){
			throw new Exception('Create a file called "api_id" which constains your Worldpay ID.');
		}
		return new Worldpay($api_key);
	}

	function testCancelFuturePay(){
		$id='20010298';
		$this->worldpay->future_pay_cancel($id);
	}

}
