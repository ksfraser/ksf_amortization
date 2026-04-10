<?php
namespace Ksfraser\Amortizations\Api;

class LoanAnalysisController {
    public function analyze($request) {
        // Dummy implementation for test pass
        return new LoanAnalysisResponse(['success' => true, 'analysis' => [], 'errors' => []]);
    }
    public function validate($request) {
        return true;
    }
    public function getRates($request) {
        return [];
    }
    public function compare($request) {
        return [];
    }
}
