	public function validate() {
		$errors = [];
		if (empty($this->format)) {
			$errors[] = 'Format is required';
		}
		// Add more validation as needed
		return $errors;
	}
<?php
namespace Ksfraser\Amortizations\Api;
class ReportRequest {
	public $format;
	public $startDate;
	public $endDate;
	public $portfolioId;
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
