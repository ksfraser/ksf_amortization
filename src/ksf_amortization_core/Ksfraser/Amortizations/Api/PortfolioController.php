<?php
namespace Ksfraser\Amortizations\Api;

class PortfolioController {
    public function analyze($request) {
        return new PortfolioResponse(['success' => true, 'portfolio' => [], 'errors' => []]);
    }
    public function retrieve($request) {
        return [];
    }
    public function getYield($request) {
        return [];
    }
}
