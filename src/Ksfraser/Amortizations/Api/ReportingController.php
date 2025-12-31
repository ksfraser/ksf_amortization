<?php
namespace Ksfraser\Amortizations\Api;
class ReportingController {
	public function generate($request) {
		$format = isset($request['format']) ? $request['format'] : 'json';
		$content = $format === 'csv' ? 'Principal,Rate,Months\n' : ['data' => 'report'];
		return (object)[
			'success' => true,
			'statusCode' => 200,
			'data' => [
				'format' => $format,
				'content' => $content
			]
		];
	}
	public function export($params) {
		return (object)[
			'success' => true,
			'statusCode' => 200,
			'data' => []
		];
	}
}
