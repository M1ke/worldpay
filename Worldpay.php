<?php
class Worldpay {
	public $test;
	public $errors=array();
	public $country='GB';
	public $currency='GBP';

	protected $id='';
	protected $ref='';
	protected $amount=array();
	protected $customer=array();
	protected $future_pay=array();
	protected $future_pay_type='';

	public function __construct($id,$test=false){
		if (empty($id)){
			throw new Exception('You must specify a valid ID to make a Worldpay connection.');
		}
		$this->id=$id;
		$this->test=$test;
	}

	public function set_customer($customer){
		$this->customer=$customer;
		return $this;
	}

	private function future_pay_intervals($type){
		$intervals=[
			'yearly'=>['intervalUnit'=>4,'intervalMult'=>1],
			'quarterly'=>['intervalUnit'=>3,'intervalMult'=>3],
			'monthly'=>['intervalUnit'=>3,'intervalMult'=>1],
			'weekly'=>['intervalUnit'=>2,'intervalMult'=>1],
			'daily'=>['intervalUnit'=>1,'intervalMult'=>1],
		];
		if (!isset($intervals[$type])){
			throw new Exception('The type of duration chosen ('.$type.') could not be recognised. Accepted durations are '.implode(', ',array_keys($intervals)));
		}
		return $intervals[$type];
	}

	public function set_future_pay($future_pay){
		if (!isset($future_pay['option']) or !is_numeric($future_pay['option'])){
			$this->errors[]='You must specify a valid "option" value.';
		}
		if (!empty($future_pay['type'])){
			list($future_pay['intervalUnit'],$future_pay['intervalMult'])=$this->future_pay_intervals($future_pay['type']);
		}
		$req=array('normalAmount','intervalUnit');
		foreach ($req as $field){
			if (empty($future_pay[$field])){
				$this->errors[]='You must specify "'.$field.'".';
			}
		}
	/*
	Option 0
		Amount limit - This limits the amount of each individual payment. It does not limit the total value of the payments that can be made under the agreement.
		Number of payments - This is a limit on the total number of payments that can be made under this agreement.
		Interval - This is the minimum interval allowed between payments.
		Note: Any of these above limits can be left unset for this option.
	Option 1
		Amount limit - This limits the amount of each individual payment. It does not limit the total value of the payments that can be made under the agreement.
		Number of payments - This is a limit on the number of payments that can be made during the specified interval.
		Interval - This must be one of: 1 - day, 2 - week, 3 - month or 4 - year.
	Option 2
		Amount limit - This is a limit on the total value of payments that can be made under this agreement.
		Number of payments - This cannot be set.
		Interval - This cannot be set.
	Option 3
		Amount limit - This is a limit on the total value of payments that can be made during the specified interval.
		Number of payments - This cannot be set.
		Interval - This must be one of: 1 - day, 2 - week, 3 - month or 4 - year.
	*/
		$this->future_pay=$future_pay;
		return $this;
	}

	public function set_ref($ref){
		$this->ref=$ref;
		return $this;
	}

	public function set_amount($amount){
		$this->amount=$amount;
		return $this;
	}

	public function set_currency($currency){
		$this->currency=$currency;
		return $this;
	}

	public function set_country($country){
		$this->country=$country;
		return $this;
	}

	public function pay($amount,$reset=true){
		if (empty($amount)){
			$this->errors[]='You must specify an amount.';
		}
		$this->amount=$amount;
		$link=$this->construct_link();
		if ($reset){
			$this->reset();
		}
		return $link;
	}

	public function future_pay($type,$now=false,$reset=true){
		$future_pay_types=array('regular','limited');
		if (!in_array($type,$future_pay_types)){
			$this->errors[]='You must specify "type" as '.implode(' or ', $future_pay_types);
		}
		if (empty($this->future_pay)){
			$this->errors[]='You have not set the future pay parameters.';
		}
		$this->future_pay['futurePayType']=$type;
		if ($now){
			$this->future_pay['startDelayUnit']=$this->future_pay['intervalUnit'];
			if (isset($this->future_pay['intervalMult'])){
				$this->future_pay['startDelayMult']=$this->future_pay['intervalMult'];
			}
			if (empty($this->amount)){
				$this->set_amount(!empty($this->future_pay['initialAmount']) ? $this->future_pay['initialAmount'] : $this->future_pay['normalAmount']);
			}
		}
		$link=$this->construct_link();
		if ($reset){
			$this->reset();
		}
		return $link;
	}

	protected function reset(){
		$this->id='';
		$this->ref='';
		$this->amount=array();
		$this->customer=array();
		$this->future_pay=array();
		$this->future_pay_type='';
		return $this;
	}

	protected function build_query(){
		if (empty($this->ref)){
			$this->errors[]='You must set a reference to make a payment.';
		}
		$data=array();
		$data+=$this->customer;
		$data['amount']=$this->amount;
		$data['currency']=$this->currency;
		$data['country']=$this->country;
		$data['instId']=$this->id;
		$data['cartId']=$this->ref;
		if (!empty($this->future_pay)){
			$data+=$this->future_pay;
		}
		if ($this->test){
			$data['testMode']=100;
		}
		return $data;
	}

	private function construct_link(){
		$query=$this->build_query();
		if (!empty($this->errors)){
			throw new Exception('The following errors were encountered in the Worldpay API: '.implode("\n<br/>",$this->errors));
		}
		$url='https://secure'.($this->test ? '-test' : '').'.worldpay.com/wcc/purchase';
		$url.='?'.http_build_query($query);
		return $url;
	}
}
