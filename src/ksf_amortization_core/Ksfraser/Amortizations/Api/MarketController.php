<?php
namespace Ksfraser\Amortizations\Api;

class MarketController {
    public function getRates($request) {
        return new MarketResponse(['success' => true, 'marketData' => [], 'errors' => []]);
    }
    public function forecast($request) {
        return [];
    }
    public function compareRates($request) {
        return [];
    }
}
