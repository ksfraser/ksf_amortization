<?php
namespace Ksfraser\Amortizations\Api;

class MarketResponse {
    public $success;
    public $marketData;
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
            'marketData' => $this->marketData,
            'errors' => $this->errors,
            'timestamp' => $this->timestamp
        ];
    }
}
