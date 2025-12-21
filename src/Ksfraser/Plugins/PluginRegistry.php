<?php

declare(strict_types=1);

namespace Ksfraser\Plugins;

/**
 * Plugin interface
 *
 * All plugins must implement this interface to be loadable by the system.
 */
interface PluginInterface
{
    /**
     * Get plugin metadata
     */
    public function getMetadata(): array;

    /**
     * Initialize the plugin
     */
    public function initialize(): void;

    /**
     * Activate the plugin
     */
    public function activate(): void;

    /**
     * Deactivate the plugin
     */
    public function deactivate(): void;

    /**
     * Get plugin version
     */
    public function getVersion(): string;

    /**
     * Get required dependencies
     */
    public function getDependencies(): array;
}

/**
 * Abstract base class for plugins
 *
 * Provides common plugin functionality and lifecycle methods.
 */
abstract class AbstractPlugin implements PluginInterface
{
    protected string $name = '';
    protected string $version = '1.0.0';
    protected string $description = '';
    protected array $dependencies = [];
    protected bool $isActive = false;

    public function getMetadata(): array
    {
        return [
            'name' => $this->name,
            'version' => $this->version,
            'description' => $this->description,
            'dependencies' => $this->dependencies,
            'active' => $this->isActive,
        ];
    }

    public function initialize(): void
    {
        // Override in subclasses
    }

    public function activate(): void
    {
        $this->isActive = true;
    }

    public function deactivate(): void
    {
        $this->isActive = false;
    }

    public function getVersion(): string
    {
        return $this->version;
    }

    public function getDependencies(): array
    {
        return $this->dependencies;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }
}

/**
 * Plugin registry
 *
 * Manages plugin registration, loading, and lifecycle.
 */
class PluginRegistry
{
    /**
     * @var array Registered plugins
     */
    private array $plugins = [];

    /**
     * @var array Loaded plugin instances
     */
    private array $loadedPlugins = [];

    /**
     * @var array Hook handlers
     */
    private array $hooks = [];

    /**
     * @var array Plugin directory paths
     */
    private array $pluginDirs = [];

    /**
     * Register a plugin path
     */
    public function registerPluginPath(string $path): void
    {
        if (!in_array($path, $this->pluginDirs)) {
            $this->pluginDirs[] = $path;
        }
    }

    /**
     * Register a plugin class
     */
    public function register(string $pluginClass): bool
    {
        if (!class_exists($pluginClass)) {
            return false;
        }

        // Verify it implements PluginInterface
        $reflection = new \ReflectionClass($pluginClass);
        if (!$reflection->implementsInterface(PluginInterface::class)) {
            return false;
        }

        $this->plugins[$pluginClass] = [
            'class' => $pluginClass,
            'loaded' => false,
            'registered_at' => date('Y-m-d H:i:s'),
        ];

        return true;
    }

    /**
     * Load a plugin
     */
    public function load(string $pluginClass): bool
    {
        if (!isset($this->plugins[$pluginClass])) {
            return false;
        }

        if ($this->plugins[$pluginClass]['loaded']) {
            return true; // Already loaded
        }

        try {
            $plugin = new $pluginClass();

            // Check dependencies
            foreach ($plugin->getDependencies() as $dependency) {
                if (!isset($this->loadedPlugins[$dependency])) {
                    return false; // Missing dependency
                }
            }

            $plugin->initialize();
            $plugin->activate();

            $this->loadedPlugins[$pluginClass] = $plugin;
            $this->plugins[$pluginClass]['loaded'] = true;

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Load all registered plugins
     */
    public function loadAll(): array
    {
        $loaded = [];
        $failed = [];

        foreach (array_keys($this->plugins) as $pluginClass) {
            if ($this->load($pluginClass)) {
                $loaded[] = $pluginClass;
            } else {
                $failed[] = $pluginClass;
            }
        }

        return ['loaded' => $loaded, 'failed' => $failed];
    }

    /**
     * Unload a plugin
     */
    public function unload(string $pluginClass): bool
    {
        if (!isset($this->loadedPlugins[$pluginClass])) {
            return false;
        }

        $this->loadedPlugins[$pluginClass]->deactivate();
        unset($this->loadedPlugins[$pluginClass]);
        $this->plugins[$pluginClass]['loaded'] = false;

        return true;
    }

    /**
     * Get loaded plugin
     */
    public function get(string $pluginClass): ?PluginInterface
    {
        return $this->loadedPlugins[$pluginClass] ?? null;
    }

    /**
     * Register a hook
     */
    public function addHook(string $hookName, callable $callback, int $priority = 10): void
    {
        if (!isset($this->hooks[$hookName])) {
            $this->hooks[$hookName] = [];
        }

        $this->hooks[$hookName][] = [
            'callback' => $callback,
            'priority' => $priority,
        ];

        // Sort by priority
        usort($this->hooks[$hookName], fn($a, $b) => $a['priority'] <=> $b['priority']);
    }

    /**
     * Execute a hook
     */
    public function doHook(string $hookName, mixed $value = null, array $args = []): mixed
    {
        if (!isset($this->hooks[$hookName])) {
            return $value;
        }

        foreach ($this->hooks[$hookName] as $hook) {
            $value = call_user_func_array($hook['callback'], array_merge([$value], $args));
        }

        return $value;
    }

    /**
     * Get all hooks
     */
    public function getHooks(): array
    {
        return $this->hooks;
    }

    /**
     * Get all plugins
     */
    public function getPlugins(): array
    {
        return $this->plugins;
    }

    /**
     * Get loaded plugins
     */
    public function getLoadedPlugins(): array
    {
        return $this->loadedPlugins;
    }

    /**
     * Get statistics
     */
    public function getStatistics(): array
    {
        return [
            'total_plugins' => count($this->plugins),
            'loaded_plugins' => count($this->loadedPlugins),
            'total_hooks' => count($this->hooks),
            'total_hook_handlers' => array_sum(array_map('count', $this->hooks)),
        ];
    }

    /**
     * Verify all dependencies are met
     */
    public function verifyDependencies(string $pluginClass): array
    {
        if (!isset($this->plugins[$pluginClass])) {
            return ['valid' => false, 'missing' => ['plugin' => 'Plugin not registered']];
        }

        $plugin = new $pluginClass();
        $missing = [];

        foreach ($plugin->getDependencies() as $dependency) {
            if (!isset($this->loadedPlugins[$dependency])) {
                $missing[] = $dependency;
            }
        }

        return [
            'valid' => empty($missing),
            'missing' => $missing,
        ];
    }
}
