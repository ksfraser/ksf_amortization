<?php
/**
 * DIContainer - Lightweight Dependency Injection Container
 *
 * Simple service locator pattern for managing dependencies without external frameworks.
 * Supports singleton and factory patterns for flexible object creation.
 *
 * ### UML Class Diagram
 * ```
 * ┌──────────────────────────────────────┐
 * │        DIContainer                   │
 * ├──────────────────────────────────────┤
 * │ - services: array<string, callable>  │
 * │ - singletons: array<string, object>  │
 * ├──────────────────────────────────────┤
 * │ + set(id, factory): void             │
 * │ + singleton(id, factory): void       │
 * │ + get(id): mixed                     │
 * │ + has(id): bool                      │
 * │ + remove(id): void                   │
 * └──────────────────────────────────────┘
 * ```
 *
 * ### Design Principles
 * - **Single Responsibility:** Only manages service registration/retrieval
 * - **Dependency Inversion:** Classes depend on interfaces, not implementations
 * - **Testability:** Easy to swap implementations for testing
 * - **Simplicity:** No complex features like autowiring or compiler passes
 *
 * @package   Ksfraser\Amortizations\Tests
 * @author    KSF Development Team
 * @version   1.0.0
 * @since     2025-12-08
 */

namespace Ksfraser\Amortizations\Tests;

use RuntimeException;
use Closure;

/**
 * Simple dependency injection container
 */
class DIContainer
{
    /**
     * @var array<string, Closure> Service factories
     */
    private array $services = [];

    /**
     * @var array<string, object> Singleton instances
     */
    private array $singletons = [];

    /**
     * Register a service factory (new instance on each get)
     *
     * ### Pattern
     * Factory pattern - creates new instance each time
     *
     * @param string $id Service identifier
     * @param Closure $factory Factory function returning service instance
     *
     * @return void
     */
    public function set(string $id, $factory): void
    {
        if ($factory instanceof Closure) {
            $this->services[$id] = $factory;
        } else {
            // If not a closure, wrap it as one
            $this->services[$id] = static fn() => $factory;
        }

        // Remove from singletons cache if exists
        unset($this->singletons[$id]);
    }

    /**
     * Register a singleton service (same instance on each get)
     *
     * ### Pattern
     * Singleton pattern - returns same instance every time
     *
     * @param string $id Service identifier
     * @param Closure $factory Factory function returning service instance
     *
     * @return void
     */
    public function singleton(string $id, Closure $factory): void
    {
        $this->services[$id] = $factory;
    }

    /**
     * Get a service instance
     *
     * ### Behavior
     * - If registered as singleton, returns cached instance
     * - Otherwise creates new instance via factory
     * - Throws exception if service not found
     *
     * @param string $id Service identifier
     *
     * @return mixed Service instance
     * @throws RuntimeException If service not registered
     */
    public function get(string $id)
    {
        if (!$this->has($id)) {
            throw new RuntimeException("Service '{$id}' not registered in container");
        }

        // Check if singleton exists
        if (isset($this->singletons[$id])) {
            return $this->singletons[$id];
        }

        // Create instance from factory
        $factory = $this->services[$id];
        $instance = $factory($this);

        // Cache as singleton
        $this->singletons[$id] = $instance;

        return $instance;
    }

    /**
     * Check if service is registered
     *
     * @param string $id Service identifier
     *
     * @return bool True if service is registered
     */
    public function has(string $id): bool
    {
        return isset($this->services[$id]);
    }

    /**
     * Remove a service registration
     *
     * @param string $id Service identifier
     *
     * @return void
     */
    public function remove(string $id): void
    {
        unset($this->services[$id], $this->singletons[$id]);
    }

    /**
     * Get all registered service IDs
     *
     * @return array<string> Service identifiers
     */
    public function getServiceIds(): array
    {
        return array_keys($this->services);
    }

    /**
     * Clear all services
     *
     * @return void
     */
    public function clear(): void
    {
        $this->services = [];
        $this->singletons = [];
    }
}

?>
