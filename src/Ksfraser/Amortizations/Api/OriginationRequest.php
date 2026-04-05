<?php
namespace Ksfraser\Amortizations\Api;

class OriginationRequest {
	public $applicantName;
	public $loanAmount;
	public $term;
	public $interestRate;

	public function validate() {
		$errors = [];
		if (empty($this->applicantName)) {
			$errors[] = 'Applicant name is required';
		}
		if (empty($this->loanAmount)) {
			$errors[] = 'Loan amount is required';
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
