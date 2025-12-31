<?php
namespace Ksfraser\Amortizations\Api;
class LoanAnalysisRequest {
	public $principal;
	public $interestRate;
	public $annualRate;
	public $months;
	public $monthlyIncome;
	public $creditScore;
	public $term;
	public $paymentFrequency;
	public $startDate;
	public $extraPayments;
	// Add more properties as needed for test coverage
	public static function fromArray(array $data) {
		$obj = new self();
		$map = [
			'annual_rate' => 'annualRate',
			'monthly_income' => 'monthlyIncome',
			'credit_score' => 'creditScore',
			'months' => 'months',
			// Add more mappings as needed
		];
		foreach ($data as $key => $value) {
			$prop = isset($map[$key]) ? $map[$key] : $key;
			if (property_exists($obj, $prop)) {
				$obj->$prop = $value;
			}
		}
		// If annualRate is set but interestRate is not, set interestRate from annualRate
		if (isset($obj->annualRate) && !isset($data['interestRate'])) {
			$obj->interestRate = $obj->annualRate;
		}
		return $obj;
	}
	public function validate() {
		$errors = [];
		if (empty($this->principal)) {
			$errors[] = 'Principal is required';
		} elseif ($this->principal <= 0) {
			$errors[] = 'principal must be positive';
		}
		if (!isset($this->interestRate) && !isset($this->annualRate)) {
			$errors[] = 'Interest rate or annual rate is required';
		}
		// Add more validation as needed
		return $errors;
	}
}
