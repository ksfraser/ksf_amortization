<?php
namespace Ksfraser\Amortizations\Api;
class PortfolioRequest {
	public $portfolioId;
	public $startDate;
	public $endDate;
	// Add more properties as needed for test coverage
	public function validate() {
		$errors = [];
		if (empty($this->portfolioId)) {
			$errors[] = 'Portfolio ID is required';
		}
		// Add more validation as needed
		return $errors;
	}
}
