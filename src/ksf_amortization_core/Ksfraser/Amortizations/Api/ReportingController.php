<?php
namespace Ksfraser\Amortizations\Api;

class ReportingController {
    public function generate($request) {
        return new ReportResponse(['success' => true, 'report' => [], 'errors' => []]);
    }
    public function generateCsv($request) {
        return '';
    }
    public function export($request) {
        return '';
    }
}
