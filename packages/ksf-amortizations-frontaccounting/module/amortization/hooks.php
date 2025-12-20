
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
     * Automatically runs composer install if dependencies are not loaded
     */
    function install() {
        $module_dir = __DIR__;
        $autoload = $module_dir . '/vendor/autoload.php';
        
        // If Composer dependencies not loaded, try to install them
        if (!file_exists($autoload)) {
            $this->install_composer_dependencies($module_dir);
        }
        
        // Ensure Composer dependencies are loaded
        if (file_exists($autoload)) {
            require_once $autoload;
        } else {
            // Log warning but continue - installer might not need all dependencies
            error_log("Warning: KSF Amortization Composer dependencies not found at $autoload");
        }
        
        // Get DB adapter and prefix from FA environment
        global $db, $dbPrefix;
        // Fallback for FA: $db is PDO, $dbPrefix is usually '0_'
        if (!isset($dbPrefix)) {
            $dbPrefix = '0_';
        }
        
        // Run generic installer
        $installer = new \Ksfraser\Amortizations\AmortizationModuleInstaller($db, $dbPrefix);
        $installer->install();
    }
    
    /**
     * Install Composer dependencies for the module
     * 
     * @param string $module_dir Module directory
     * @return bool True if successful, false otherwise
     */
    private function install_composer_dependencies($module_dir) {
        $composer_file = $module_dir . '/composer.json';
        
        // Check if composer.json exists
        if (!file_exists($composer_file)) {
            error_log("No composer.json found in KSF Amortization module at $composer_file");
            return false;
        }
        
        // Try to find composer executable
        $composer = $this->find_composer_executable();
        if (!$composer) {
            error_log("Composer executable not found. Please run: composer install in {$module_dir}");
            return false;
        }
        
        // Change to module directory and run composer install
        $cwd = getcwd();
        chdir($module_dir);
        
        // Build command with proper escaping
        $command = escapeshellcmd($composer) . ' install --no-dev --optimize-autoloader 2>&1';
        
        error_log("Running: $command in $module_dir");
        
        // Execute composer install
        $output = shell_exec($command);
        $return_code = $GLOBALS['shell_return_code'] ?? 0;
        
        // Restore previous directory
        chdir($cwd);
        
        if ($output) {
            error_log("Composer output: $output");
        }
        
        // Check if vendor/autoload.php now exists
        $autoload = $module_dir . '/vendor/autoload.php';
        if (file_exists($autoload)) {
            error_log("KSF Amortization: Composer dependencies installed successfully");
            return true;
        } else {
            error_log("KSF Amortization: Failed to install composer dependencies");
            return false;
        }
    }
    
    /**
     * Find Composer executable in system PATH
     * 
     * Looks for:
     * 1. composer (Linux/Mac)
     * 2. composer.phar (in same directory as FA)
     * 3. php composer.phar (fallback)
     * 
     * @return string|false Path to composer or false if not found
     */
    private function find_composer_executable() {
        // Try 'composer' command
        if ($this->command_exists('composer')) {
            return 'composer';
        }
        
        // Try composer.phar in module directory
        $module_composer_phar = __DIR__ . '/composer.phar';
        if (file_exists($module_composer_phar)) {
            return 'php ' . escapeshellarg($module_composer_phar);
        }
        
        // Try composer.phar in FrontAccounting root
        global $path_to_root;
        $fa_composer_phar = $path_to_root . '/composer.phar';
        if (file_exists($fa_composer_phar)) {
            return 'php ' . escapeshellarg($fa_composer_phar);
        }
        
        // Try in common system locations
        $common_paths = array(
            '/usr/bin/composer',
            '/usr/local/bin/composer',
            'C:\\ProgramData\\ComposerSetup\\bin\\composer',
            'C:\\tools\\composer',
        );
        
        foreach ($common_paths as $path) {
            if (file_exists($path)) {
                return $path;
            }
        }
        
        return false;
    }
    
    /**
     * Check if a command exists in system PATH
     * 
     * @param string $command Command to check
     * @return bool True if command exists
     */
    private function command_exists($command) {
        $test = (PHP_OS_FAMILY === 'Windows') ? 'where' : 'which';
        $result = shell_exec("$test $command 2>/dev/null");
        return !empty($result);
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
