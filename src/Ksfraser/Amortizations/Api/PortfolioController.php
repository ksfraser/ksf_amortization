<?php
namespace Ksfraser\Amortizations\Api;
class PortfolioController {
	public function analyze($request) {
		return (object)[
			'success' => true,
			'statusCode' => 200,
			'data' => [
				'portfolio' => []
			]
		];
	}
	public function retrieve($id) {
		return (object)[
			'success' => true,
			'statusCode' => 200,
			'data' => [
				'portfolio_id' => $id
			]
		];
	}
	public function getYield($id) {
		return (object)[
			'success' => true,
			'statusCode' => 200,
			'data' => [
				'total_yield' => 0.055
			]
		];
	}
}
