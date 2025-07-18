<?php
// Mock hooks class for FrontAccounting module in SuiteCRM  

if (!class_exists('hooks')) {
    //namespace Ksfraser\Amortizations\FA_Mock;

    /**
     * Mock hooks class for FA module testing
     */
    class hooks {
        public $db_updates = [];
        public function update_databases($company, $updates, $checkOnly) {
            // Simulate database update logic
            $this->db_updates[] = [
                'company' => $company,
                'updates' => $updates,
                'check_only' => $checkOnly
            ];
            // Return true to simulate success
            return true;
        }
        public function add_lapp_function($pos, $label, $url, $access, $menu) {
            // Simulate adding a menu function
            // No-op for mock
        }
    }
}
