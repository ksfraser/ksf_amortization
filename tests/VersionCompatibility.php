<?php
/**
 * VersionCompatibility - Runtime PHP version detection and compatibility layer
 *
 * Provides version-aware utilities to handle differences between PHP versions.
 * Allows tests to conditionally use PHP 7.4+ features (arrow functions) while
 * maintaining PHP 7.3 compatibility through traditional closures.
 *
 * ### Design Pattern
 * - **Adapter Pattern:** Adapts arrow functions to traditional closures
 * - **Decorator Pattern:** Wraps version-specific logic
 * - **Strategy Pattern:** Selects approach based on PHP version
 *
 * ### Usage Example
 * ```php
 * // Instead of:
 * $result = array_filter($array, fn($x) => $x > 5);
 *
 * // Use:
 * $callback = VersionCompatibility::createFilterCallback(
 *     fn($x) => $x > 5  // Works in PHP 7.4+
 * );
 * // Or provide fallback:
 * $callback = VersionCompatibility::createFilterCallback(
 *     fn($x) => $x > 5,
 *     function($x) { return $x > 5; }  // PHP 7.3 fallback
 * );
 * $result = array_filter($array, $callback);
 * ```
 *
 * @package   Ksfraser\Amortizations\Tests
 * @author    KSF Development Team
 * @version   1.0.0
 * @since     2025-12-20
 */

namespace Ksfraser\Amortizations\Tests;

/**
 * Runtime version detection and compatibility utilities
 */
class VersionCompatibility
{
    /**
     * Current PHP version as major.minor
     * @var float
     */
    private static float $phpVersion;

    /**
     * Whether arrow functions are supported (PHP 7.4+)
     * @var bool
     */
    private static bool $supportsArrowFunctions;

    /**
     * Initialize version detection
     * @return void
     */
    public static function initialize(): void
    {
        $version = explode('.', PHP_VERSION);
        self::$phpVersion = (float)($version[0] . '.' . $version[1]);
        self::$supportsArrowFunctions = self::$phpVersion >= 7.4;
    }

    /**
     * Get current PHP version (major.minor)
     *
     * @return float Version like 7.3, 7.4, 8.0, etc.
     */
    public static function getPhpVersion(): float
    {
        if (!isset(self::$phpVersion)) {
            self::initialize();
        }
        return self::$phpVersion;
    }

    /**
     * Check if PHP version is 7.3 or lower
     *
     * @return bool True if PHP 7.3 or lower
     */
    public static function isLegacy(): bool
    {
        return self::getPhpVersion() < 7.4;
    }

    /**
     * Check if arrow functions are supported (PHP 7.4+)
     *
     * @return bool True if PHP 7.4 or higher
     */
    public static function supportsArrowFunctions(): bool
    {
        if (!isset(self::$supportsArrowFunctions)) {
            self::initialize();
        }
        return self::$supportsArrowFunctions;
    }

    /**
     * Create a filter callback that works across PHP versions
     *
     * ### Design
     * - If arrow functions supported, returns the arrow function directly
     * - If not, returns the fallback traditional closure
     * - Allows seamless usage in array_filter() and similar functions
     *
     * @param mixed $arrowFunction The arrow function (PHP 7.4+)
     * @param callable $legacyCallback Traditional closure fallback (PHP 7.3)
     * @return callable The appropriate callback for current PHP version
     *
     * @example
     * ```php
     * $callback = VersionCompatibility::createCallback(
     *     fn($x) => $x > 5,
     *     function($x) { return $x > 5; }
     * );
     * $result = array_filter([1,6,3,8], $callback);
     * ```
     */
    public static function createCallback($arrowFunction, ?callable $legacyCallback = null): callable
    {
        if (self::supportsArrowFunctions()) {
            return $arrowFunction;
        }

        if ($legacyCallback === null) {
            throw new \RuntimeException(
                'Legacy callback required for PHP 7.3 compatibility. '
                . 'Provide fallback as second argument.'
            );
        }

        return $legacyCallback;
    }

    /**
     * Create a map callback for array_map()
     *
     * @param mixed $arrowFunction Arrow function for PHP 7.4+
     * @param callable|null $legacyCallback Traditional closure for PHP 7.3
     * @return callable The appropriate callback
     */
    public static function createMapCallback($arrowFunction, ?callable $legacyCallback = null): callable
    {
        return self::createCallback($arrowFunction, $legacyCallback);
    }

    /**
     * Create a reduce callback for array_reduce()
     *
     * ### Pattern
     * array_reduce($items, callback($carry, $item), $initial)
     *
     * @param mixed $arrowFunction Arrow function (PHP 7.4+)
     * @param callable|null $legacyCallback Traditional closure (PHP 7.3)
     * @return callable The appropriate callback
     */
    public static function createReduceCallback($arrowFunction, ?callable $legacyCallback = null): callable
    {
        return self::createCallback($arrowFunction, $legacyCallback);
    }

    /**
     * Create a sort callback for usort()
     *
     * @param mixed $arrowFunction Arrow function (PHP 7.4+)
     * @param callable|null $legacyCallback Traditional closure (PHP 7.3)
     * @return callable The appropriate callback
     */
    public static function createSortCallback($arrowFunction, ?callable $legacyCallback = null): callable
    {
        return self::createCallback($arrowFunction, $legacyCallback);
    }

    /**
     * Conditionally skip a test for specific PHP versions
     *
     * ### Usage
     * ```php
     * protected function setUp(): void
     * {
     *     parent::setUp();
     *     // Skip this test on PHP 7.3
     *     VersionCompatibility::skipIfLegacy('This test uses arrow functions');
     * }
     * ```
     *
     * @param string $reason Why test is being skipped
     * @param float|null $maxVersion Skip if PHP version is at or below this
     * @return void
     * @throws \PHPUnit\Framework\SkippedTestError
     */
    public static function skipIfLegacy(string $reason = '', float $maxVersion = 7.3): void
    {
        if (self::getPhpVersion() <= $maxVersion) {
            throw new \PHPUnit\Framework\SkippedTestError(
                sprintf(
                    'Skipped for PHP %s: %s',
                    self::getPhpVersion(),
                    $reason ?: 'Test requires PHP 7.4+'
                )
            );
        }
    }

    /**
     * Conditionally skip a test for modern PHP versions
     *
     * ### Usage
     * ```php
     * // Skip test on PHP 7.4+
     * VersionCompatibility::skipIfModern('Backward compatibility test');
     * ```
     *
     * @param string $reason Why test is being skipped
     * @param float $minVersion Skip if PHP version is at or above this
     * @return void
     * @throws \PHPUnit\Framework\SkippedTestError
     */
    public static function skipIfModern(string $reason = '', float $minVersion = 7.4): void
    {
        if (self::getPhpVersion() >= $minVersion) {
            throw new \PHPUnit\Framework\SkippedTestError(
                sprintf(
                    'Skipped for PHP %s: %s',
                    self::getPhpVersion(),
                    $reason ?: "Backward compatibility test"
                )
            );
        }
    }

    /**
     * Conditionally execute code based on PHP version
     *
     * ### Usage
     * ```php
     * VersionCompatibility::ifSupportsArrowFunctions(
     *     function() {
     *         // This runs only on PHP 7.4+
     *         $result = array_filter($data, fn($x) => $x > 5);
     *     }
     * );
     * ```
     *
     * @param callable $modernCallback Code to run on PHP 7.4+
     * @param callable|null $legacyCallback Code to run on PHP 7.3
     * @return mixed Return value from executed callback
     */
    public static function ifSupportsArrowFunctions(
        callable $modernCallback,
        ?callable $legacyCallback = null
    ) {
        if (self::supportsArrowFunctions()) {
            return $modernCallback();
        }

        if ($legacyCallback !== null) {
            return $legacyCallback();
        }

        return null;
    }

    /**
     * Get version info for debugging
     *
     * @return array Version information
     */
    public static function getVersionInfo(): array
    {
        return [
            'php_version' => PHP_VERSION,
            'php_version_short' => self::getPhpVersion(),
            'is_legacy' => self::isLegacy(),
            'supports_arrow_functions' => self::supportsArrowFunctions(),
            'php_major' => PHP_MAJOR_VERSION,
            'php_minor' => PHP_MINOR_VERSION,
            'php_release' => PHP_RELEASE_VERSION,
        ];
    }
}

// Initialize on first load
VersionCompatibility::initialize();
