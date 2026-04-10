<?php
namespace Ksfraser\Amortizations\Api;

class OriginationController {
    public function createApplication($request) {
        return new OriginationResponse(['success' => true, 'application' => [], 'errors' => []]);
    }
    public function approve($request) {
        return true;
    }
    public function reject($request) {
        return false;
    }
}
