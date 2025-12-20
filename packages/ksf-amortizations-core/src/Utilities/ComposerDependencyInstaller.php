<?php

namespace Ksfraser\Amortizations\Utilities;

use RuntimeException;
use InvalidArgumentException;

/**
 * Composer Dependency Installer
 * 
 * Handles automatic installation of Composer dependencies for packages.
 * Searches for Composer in common system locations and executes installation.
 * 
 * Designed for use in module/plugin installation hooks across platforms:
 * - FrontAccounting
 * - SuiteCRM
 * - WordPress
 * - Standalone applications
 * 
 * @package Ksfraser\Amortizations\Utilities
 * @author KS Fraser
 */
class ComposerDependencyInstaller
{
    /**
     * Directory containing composer.json to install dependencies for
     * @var string
     */
    private $targetDir;
    
    /**
     * Constructor
     * 
     * @param string $targetDir Directory containing composer.json
     * @throws InvalidArgumentException If directory doesn't exist
     */
    public function __construct($targetDir)
    {
        if (!is_dir($targetDir)) {
            throw new InvalidArgumentException(
                "Target directory does not exist: $targetDir"
            );
        }
        $this->targetDir = rtrim($targetDir, '/\\');
    }
    
    /**
     * Install Composer dependencies
     * 
     * Checks if vendor/autoload.php exists; if not, attempts to run
     * 'composer install' with proper error handling and fallbacks.
     * 
     * @return bool True if dependencies are loaded (pre-existing or newly installed)
     * @throws RuntimeException If composer.json missing or install fails
     */
    public function install()
    {
        $autoload = $this->targetDir . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';
        
        // Dependencies already installed
        if (file_exists($autoload)) {
            return true;
        }
        
        // Check for composer.json
        $composerFile = $this->targetDir . DIRECTORY_SEPARATOR . 'composer.json';
        if (!file_exists($composerFile)) {
            throw new RuntimeException(
                "composer.json not found in {$this->targetDir}"
            );
        }
        
        // Find Composer executable
        $composer = $this->findComposerExecutable();
        if (!$composer) {
            throw new RuntimeException(
                "Composer executable not found. Please install Composer or run: composer install in {$this->targetDir}"
            );
        }
        
        // Execute installation
        $this->runComposerInstall($composer);
        
        // Verify installation succeeded
        if (!file_exists($autoload)) {
            throw new RuntimeException(
                "Failed to install composer dependencies. vendor/autoload.php not created."
            );
        }
        
        return true;
    }
    
    /**
     * Require a specific package via Composer
     * 
     * Executes 'composer require' for a specific package.
     * Useful for adding packages during installation.
     * 
     * @param string $package Package name (e.g., "vendor/package:^1.0")
     * @throws RuntimeException If composer not found or require fails
     */
    public function require($package)
    {
        if (empty($package)) {
            throw new InvalidArgumentException("Package name cannot be empty");
        }
        
        $composer = $this->findComposerExecutable();
        if (!$composer) {
            throw new RuntimeException(
                "Composer executable not found. Cannot require package: $package"
            );
        }
        
        $this->runComposerRequire($composer, $package);
    }
    
    /**
     * Get path to Composer executable
     * 
     * Searches in order:
     * 1. System PATH (composer command)
     * 2. Module directory (composer.phar)
     * 3. Parent directory levels (composer.phar)
     * 4. Common installation paths
     * 
     * @return string|null Path to Composer or null if not found
     */
    private function findComposerExecutable()
    {
        // Try 'composer' command in PATH
        if ($this->commandExists('composer')) {
            return 'composer';
        }
        
        // Try composer.phar in target directory
        $targetPhar = $this->targetDir . DIRECTORY_SEPARATOR . 'composer.phar';
        if (file_exists($targetPhar)) {
            return 'php ' . escapeshellarg($targetPhar);
        }
        
        // Try composer.phar in parent directory (for modules)
        $parentPhar = dirname($this->targetDir) . DIRECTORY_SEPARATOR . 'composer.phar';
        if (file_exists($parentPhar)) {
            return 'php ' . escapeshellarg($parentPhar);
        }
        
        // Try common system locations
        $commonPaths = [
            '/usr/bin/composer',
            '/usr/local/bin/composer',
            '/opt/composer/bin/composer',
            'C:\\ProgramData\\ComposerSetup\\bin\\composer.bat',
            'C:\\tools\\composer.bat',
            'C:\\composer\\composer.bat',
        ];
        
        foreach ($commonPaths as $path) {
            if (file_exists($path)) {
                return $path;
            }
        }
        
        return null;
    }
    
    /**
     * Check if command exists in system PATH
     * 
     * @param string $command Command name
     * @return bool True if command found in PATH
     */
    private function commandExists($command)
    {
        $test = (PHP_OS_FAMILY === 'Windows') ? 'where' : 'which';
        $result = @shell_exec("$test $command 2>/dev/null");
        return !empty($result);
    }
    
    /**
     * Run 'composer install' in target directory
     * 
     * @param string $composer Path to Composer executable
     * @throws RuntimeException If installation fails
     */
    private function runComposerInstall($composer)
    {
        $cwd = getcwd();
        
        try {
            chdir($this->targetDir);
            
            $command = escapeshellcmd($composer) . ' install --no-dev --optimize-autoloader 2>&1';
            $output = shell_exec($command);
            
            if ($output === null) {
                throw new RuntimeException(
                    "Failed to execute composer install command"
                );
            }
            
            // Check for common error indicators in output
            if (stripos($output, 'error') !== false || stripos($output, 'failed') !== false) {
                throw new RuntimeException(
                    "Composer install failed with output: " . substr($output, 0, 500)
                );
            }
        } finally {
            chdir($cwd);
        }
    }
    
    /**
     * Run 'composer require' for a specific package
     * 
     * @param string $composer Path to Composer executable
     * @param string $package Package name to require
     * @throws RuntimeException If require fails
     */
    private function runComposerRequire($composer, $package)
    {
        $cwd = getcwd();
        
        try {
            chdir($this->targetDir);
            
            $command = escapeshellcmd($composer) . ' require ' . escapeshellarg($package) . ' 2>&1';
            $output = shell_exec($command);
            
            if ($output === null) {
                throw new RuntimeException(
                    "Failed to execute composer require command"
                );
            }
            
            // Check for error indicators
            if (stripos($output, 'error') !== false || stripos($output, 'failed') !== false) {
                throw new RuntimeException(
                    "Composer require failed for $package: " . substr($output, 0, 500)
                );
            }
        } finally {
            chdir($cwd);
        }
    }
    
    /**
     * Get target directory
     * 
     * @return string Directory containing composer.json
     */
    public function getTargetDir()
    {
        return $this->targetDir;
    }
    
    /**
     * Check if vendor/autoload.php exists
     * 
     * @return bool True if autoloader is available
     */
    public function isLoaded()
    {
        $autoload = $this->targetDir . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';
        return file_exists($autoload);
    }
    
    /**
     * Get path to vendor/autoload.php
     * 
     * @return string Path to autoloader
     */
    public function getAutoloadPath()
    {
        return $this->targetDir . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';
    }
}
