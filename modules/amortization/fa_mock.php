<?php
// FA mock definitions for linting and local development
if (!class_exists('hooks')) {
    /**
     * Mock base hooks class for FrontAccounting
     */
    class hooks {
        // ...mock methods if needed...
    }
}

if (!defined('MENU_BANKING')) {
    define('MENU_BANKING', 1);
}

if (!function_exists('_')) {
    function _($str) { return $str; }
}

if (!method_exists('app', 'add_module_menu_option')) {
    class app {
        public function add_module_menu_option($section, $label, $url, $menu) {
            // mock implementation
        }
    }
}
