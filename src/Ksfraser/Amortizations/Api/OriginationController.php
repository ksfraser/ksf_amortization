<?php
namespace Ksfraser\Amortizations\Api;
class OriginationController {
	public function createApplication($request) {
		return (object)[
			'success' => true,
			'statusCode' => 201,
			'data' => [
				'application_id' => 'APP-12345',
				'status' => 'pending_review'
			]
		];
	}
	public function approve($id, $params) {
		return (object)[
			'success' => true,
			'statusCode' => 200,
			'data' => [
				'status' => 'approved'
			]
		];
	}
	public function reject($id, $params) {
		return (object)[
			'success' => true,
			'statusCode' => 200,
			'data' => [
				'status' => 'rejected'
			]
		];
	}
}
