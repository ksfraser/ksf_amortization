
<?php
use Ksfraser\Amortizations\FA\FADataProvider;


define( 'MENU_AMORTIZATIONS', 'Amortizations' );
if (!defined('AMORTIZATION_PLATFORM')) {
    define('AMORTIZATION_PLATFORM', 'fa');
}

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
    var $module_name = 'amortization'; 
    function install_options($app) {
        global $user;
        global $path_to_root;
        switch($app->id) {
            case 'GL':
                $app->add_lapp_function(3, _("Amortization (Amortization)"),
                    $path_to_root."/modules/".$this->module_name."/modules/amortization/controller.php", 'SA_CUSTOMER', MENU_AMORTIZATIONS);
                $app->add_lapp_function(3, _("Amortization (Banking)"),
                    $path_to_root."/modules/".$this->module_name."/modules/amortization/controller.php", 'SA_CUSTOMER', MENU_BANKING);
                break;
            }
    }
    function activate_extension($company, $check_only=true) {
        $updates = array( 'update.sql' => array($this->module_name) );
        return $this->update_databases($company, $updates, $check_only);
    }
    function db_prevoid($trans_type, $trans_no) {
        // Assuming $db is a PDO instance available in scope
        // You may need to adjust how $db is retrieved in your environment
        global $db;
        $provider = new FADataProvider($db);
        $provider->resetPostedToGL($trans_no, $trans_type);
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
