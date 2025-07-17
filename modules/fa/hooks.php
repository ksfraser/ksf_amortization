<?php
namespace Ksfraser\Amortizations\FA;

/**
 * Amortization Module Hooks
 * Registers the module and adds menu entries to FrontAccounting
 * @package AmortizationModule
 */

class hooks_amortization extends hooks {
    /**
     * Install the module menu entry under Banking and General Ledger
     * Only show to users with Loans Administrator or Loans Reader access
     * @return void
     */
    function install_options($app) {
        global $user;
        // Example: Replace with actual FA permission checks
        $isAdmin = isset($user->access) && in_array('LOANS_ADMIN', $user->access);
        $isReader = isset($user->access) && in_array('LOANS_READER', $user->access);
        if ($isAdmin || $isReader) {
            $app->add_module_menu_option(
                'banking',
                _('Amortization'),
                '/modules/amortization/controller.php',
                MENU_BANKING
            );
        }
    }

    /**
     * Check if current user is Loans Administrator
     * @return bool
     */
    function is_loans_admin() {
        global $user;
        return isset($user->access) && in_array('LOANS_ADMIN', $user->access);
    }

    /**
     * Check if current user is Loans Reader
     * @return bool
     */
    function is_loans_reader() {
        global $user;
        return isset($user->access) && in_array('LOANS_READER', $user->access);
    }

    /**
     * Module version
     * @return string
     */
    function get_version() {
        return '1.0.0';
    }
}
