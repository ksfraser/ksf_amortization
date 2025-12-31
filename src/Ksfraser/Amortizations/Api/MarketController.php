<?php
namespace Ksfraser\Amortizations\Api;
class MarketController {
	public function getRates() {
		return (object)[
			'success' => true,
			'statusCode' => 200,
			'data' => [
				'mortgage_30_year' => 0.067
			]
		];
	}
	public function forecast($request) {
		return (object)[
			'success' => true,
			'statusCode' => 200,
			'data' => [
				'forecast' => []
			]
		];
	}
	public function compareRates($request) {
		return (object)[
			'success' => true,
			'statusCode' => 200,
			'data' => [
				'competitiveness_rank' => 1
			]
		];
	}
}
