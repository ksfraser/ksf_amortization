
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
     * Install the Amortization module and run generic installer
     * 
     * This method:
     * 1. Ensures composer dependencies are installed
     * 2. Loads the autoloader
     * 3. Runs the database installer
     */
    function install() {
        // Step 1: Ensure Composer has been run
        $this->ensureComposerDependencies();
        
        // Step 2: Load autoloader
        $autoload = __DIR__ . '/vendor/autoload.php';
        if (!file_exists($autoload)) {
            error_log('ERROR: Composer autoloader not found. Run: composer install');
            throw new \Exception('Composer dependencies not installed. Run: composer install');
        }
        require_once $autoload;
        
        // Step 3: Get DB adapter and prefix from FA environment
        global $db, $dbPrefix;
        // Fallback for FA: $db is PDO, $dbPrefix is usually '0_'
        if (!isset($dbPrefix)) {
            $dbPrefix = '0_';
        }
        
        // Step 4: Run generic installer
        $installer = new \Ksfraser\Amortizations\AmortizationModuleInstaller($db, $dbPrefix);
        $installer->install();
    }
    
    /**
     * Ensure composer dependencies are installed
     * 
     * Automatically runs `composer install` if vendor/ directory doesn't exist
     * or if composer.lock is missing
     */
    private function ensureComposerDependencies() {
        $moduleDir = __DIR__;
        $vendorDir = $moduleDir . '/vendor';
        $composerLock = $moduleDir . '/composer.lock';
        $composerJson = $moduleDir . '/composer.json';
        
        // Check if composer.json exists
        if (!file_exists($composerJson)) {
            error_log('WARNING: No composer.json found in ' . $moduleDir);
            return; // Assume dependencies are in parent
        }
        
        // If vendor directory exists and composer.lock exists, we're good
        if (file_exists($vendorDir) && file_exists($composerLock)) {
            return; // Already installed
        }
        
        // Run composer install
        $this->runComposerInstall($moduleDir);
    }
    
    /**
     * Run composer install command
     * 
     * @param string $directory The directory to run composer in
     * @throws Exception if composer is not available or command fails
     */
    private function runComposerInstall($directory) {
        // Check if we can execute shell commands
        if (!function_exists('shell_exec') || !function_exists('exec')) {
            error_log('WARNING: Cannot run shell commands. Please run: cd ' . $directory . ' && composer install');
            return;
        }
        
        // Build the composer command
        $command = 'which composer >/dev/null 2>&1 && composer install --no-dev --working-dir=' . escapeshellarg($directory) . ' 2>&1';
        
        // If 'which' doesn't work (Windows), try composer directly
        if (!file_exists('/usr/bin/which')) {
            $command = 'composer install --no-dev --working-dir=' . escapeshellarg($directory) . ' 2>&1';
        }
        
        // Execute composer install
        $output = shell_exec($command);
        
        if ($output === null) {
            error_log('ERROR: Composer command failed or returned null. Make sure composer is installed and in PATH.');
            error_log('Manual installation: cd ' . $directory . ' && composer install');
            throw new \Exception('Failed to run composer install. See logs for details.');
        }
        
        // Log output for debugging
        error_log('Composer install output: ' . $output);
        
        // Verify installation succeeded
        if (!file_exists($directory . '/vendor/autoload.php')) {
            error_log('ERROR: Composer installation failed. vendor/autoload.php not found after running composer install.');
            throw new \Exception('Composer installation failed. See error logs.');
        }
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
                $app->add_lapp_function(3, _("Amortization (Amortization)"),
                    $path_to_root."/modules/".$this->module_name."/modules/amortization/controller.php", 'SA_CUSTOMER', MENU_AMORTIZATIONS);
                $app->add_lapp_function(3, _("Amortization (Banking)"),
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
