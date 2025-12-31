<?php
namespace Ksfraser\Amortizations\Api;
class LoanAnalysisController {
	public function analyze($request) {
		if (isset($request['principal']) && $request['principal'] > 0) {
			return (object)[
				'success' => true,
				'statusCode' => 200,
				'data' => ['analysis' => 'result']
			];
		} else {
			return (object)[
				'success' => false,
				'statusCode' => 422,
				'data' => null
			];
		}
	}
	public function getRates() {
		return (object)[
			'success' => true,
			'statusCode' => 200,
			'data' => [
				'prime_rate' => 0.05,
				'average_mortgage_30' => 0.067
			]
		];
	}
	public function compare($request) {
		return (object)[
			'success' => true,
			'statusCode' => 200,
			'data' => [
				'comparisons' => []
			]
		];
	}
}
