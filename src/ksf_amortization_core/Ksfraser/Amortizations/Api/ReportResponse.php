<?php
namespace Ksfraser\Amortizations\Api;

class ReportResponse {
    public $success;
    public $report;
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
            'report' => $this->report,
            'errors' => $this->errors,
            'timestamp' => $this->timestamp
        ];
    }
}
