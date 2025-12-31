<?php
namespace Ksfraser\Amortizations\Api;
class MarketResponse {
	public $success;
	public $timestamp;
	public $data;
	public $errors;
	public $message;

	public function __construct($success = true, $data = null, $message = '', $errors = null) {
		$this->success = $success;
		$this->timestamp = date('c');
		$this->data = $data;
		$this->message = $message;
		$this->errors = $errors;
	}

	public static function create($data = null, $success = true, $message = '', $errors = null) {
		return new self($success, $data, $message, $errors);
	}

	public function toArray() {
		return [
			'success' => $this->success,
			'timestamp' => $this->timestamp,
			'data' => $this->data,
			'message' => $this->message,
			'errors' => $this->errors,
		];
	}
}
