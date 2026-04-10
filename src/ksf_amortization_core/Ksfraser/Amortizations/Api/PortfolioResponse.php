<?php
namespace Ksfraser\Amortizations\Api;

class PortfolioResponse {
    public $success;
    public $portfolio;
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
            'portfolio' => $this->portfolio,
            'errors' => $this->errors,
            'timestamp' => $this->timestamp
        ];
    }
}
