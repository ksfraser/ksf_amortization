<?php
namespace Ksfraser\Amortizations\Api;

class MarketRequest {
	public $marketId;
	public $date;
	public $rateType;

	public function validate() {
		$errors = [];
		if (empty($this->marketId)) {
			$errors[] = 'Market ID is required';
		}
		// Add more validation as needed
		return $errors;
	}

	// Add more properties as needed for test coverage
	public static function fromArray(array $data) {
		$obj = new self();
		foreach ($data as $key => $value) {
			if (property_exists($obj, $key)) {
				$obj->$key = $value;
			}
		}
		return $obj;
	}
}
