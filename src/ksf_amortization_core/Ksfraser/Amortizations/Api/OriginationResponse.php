<?php
namespace Ksfraser\Amortizations\Api;

class OriginationResponse {
    public $success;
    public $application;
    public $errors = [];
    public $timestamp;
    public function __construct($data = []) {
        foreach ($data as $key => $value) {
            $this->$key = $value;
        }
        $this->timestamp = date('c');
    }
    public function toArray() {
        return [
            'success' => $this->success,
            'application' => $this->application,
            'errors' => $this->errors,
            'timestamp' => $this->timestamp
        ];
    }
}
