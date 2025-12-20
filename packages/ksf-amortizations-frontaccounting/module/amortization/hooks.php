
<?php
use Ksfraser\Amortizations\FA\FADataProvider;
use Ksfraser\Amortizations\Utilities\ComposerDependencyInstaller;


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
     * Install the Amortization module and run generic installer
     * 
     * Automatically runs composer install if dependencies are not loaded
     */
    function install() {
        $module_dir = __DIR__;
        $autoload = $module_dir . '/vendor/autoload.php';
        
        // Try to install Composer dependencies if missing
        if (!file_exists($autoload)) {
            try {
                $installer = new ComposerDependencyInstaller($module_dir);
                $installer->install();
            } catch (\Exception $e) {
                // Log error but continue - dependencies may be pre-packaged
                error_log("Warning: Failed to auto-install Composer dependencies: " . $e->getMessage());
            }
        }
        
        // Ensure Composer dependencies are loaded
        if (file_exists($autoload)) {
            require_once $autoload;
        } else {
            // Log warning but continue - installer might not need all dependencies
            error_log("Warning: KSF Amortization Composer dependencies not found at $autoload");
        }
        
        // Get DB adapter and prefix from FA environment
        global $db;
        // FrontAccounting defines TB_PREF as table prefix (e.g., '0_')
        $dbPrefix = defined('TB_PREF') ? TB_PREF : '0_';
        
        // Run generic installer
        $installer = new \Ksfraser\Amortizations\AmortizationModuleInstaller($db, $dbPrefix);
        $installer->install();
    }
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
                // Generic amortization interface (default view)
                $app->add_lapp_function(3, _("Amortization"),
                    $path_to_root."/modules/".$this->module_name."/modules/amortization/controller.php", 'SA_CUSTOMER', MENU_AMORTIZATIONS);
                
                // Admin functions
                $app->add_lapp_function(3, _("Amortization Admin"),
                    $path_to_root."/modules/".$this->module_name."/modules/amortization/controller.php?action=admin", 'SA_CUSTOMER', MENU_AMORTIZATIONS);
                
                // Create new loan
                $app->add_lapp_function(3, _("Create Loan"),
                    $path_to_root."/modules/".$this->module_name."/modules/amortization/controller.php?action=create", 'SA_CUSTOMER', MENU_AMORTIZATIONS);
                
                // Reports
                $app->add_lapp_function(3, _("Amortization Reports"),
                    $path_to_root."/modules/".$this->module_name."/modules/amortization/controller.php?action=report", 'SA_CUSTOMER', MENU_AMORTIZATIONS);
                
                // Banking menu
                $app->add_lapp_function(3, _("Amortization Payments (Banking)"),
                    $path_to_root."/modules/".$this->module_name."/modules/amortization/controller.php", 'SA_CUSTOMER', MENU_BANKING);
                break;
            }
    }
    function activate_extension($company, $check_only=true) {
        $this->install();
        $updates = array( 'update.sql' => array($this->module_name) );
        return $this->update_databases($company, $updates, $check_only);
    }
    function db_prevoid($trans_type, $trans_no) {
        // Called when GL entry is voided - reset posting info for any related amortization staging records
        global $db;
        
        // Get FrontAccounting table prefix (TB_PREF is defined by FA, typically '0_')
        $dbPrefix = defined('TB_PREF') ? TB_PREF : '0_';
        
        $provider = new FADataProvider($db, $dbPrefix);
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
