<?php

namespace Wave\Plugins;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Str;
use Wave\Plugins\Plugin;

class PluginManager
{
    protected $app;
    protected $plugins = [];

    public function __construct(Application $app)
    {
        $this->app = $app;
        PluginAutoloader::register();
    }

    public function loadPlugins()
    {
        $installedPlugins = $this->getInstalledPlugins();

        // Only log in debug mode and only once per minute to reduce log noise
        if (config('app.debug') && !app('cache')->has('plugins_loaded_recently')) {
            Log::debug("Loading installed plugins: " . json_encode($installedPlugins));
            app('cache')->put('plugins_loaded_recently', true, 60); // Cache for 1 minute
        }

        foreach ($installedPlugins as $pluginName) {
            $studlyPluginName = Str::studly($pluginName);
            $pluginClass = "Wave\\Plugins\\{$studlyPluginName}\\{$studlyPluginName}Plugin";
            
            $expectedPath = $this->findPluginFile($pluginName);
            if ($expectedPath) {
                include_once $expectedPath;
                
                if (class_exists($pluginClass)) {
                    $plugin = new $pluginClass($this->app);
                    $this->plugins[$pluginName] = $plugin;
                    $this->app->register($plugin);
                    
                    if (config('app.debug') && !app('cache')->has('plugin_loaded_recently_' . $pluginName)) {
                        Log::debug("Loaded plugin: {$pluginClass}");
                        app('cache')->put('plugin_loaded_recently_' . $pluginName, true, 60); // Cache for 1 minute
                    }
                } else {
                    if (!app('cache')->has('plugin_class_not_found_' . $pluginName)) {
                        Log::warning("Plugin class not found after including file: {$pluginClass}");
                        app('cache')->put('plugin_class_not_found_' . $pluginName, true, 60); // Cache for 1 minute
                    }
                }
            } else {
                if (!app('cache')->has('plugin_file_not_found_' . $pluginName)) {
                    Log::warning("Plugin file not found for: {$pluginName}");
                    app('cache')->put('plugin_file_not_found_' . $pluginName, true, 60); // Cache for 1 minute
                }
            }
        }
    }

    protected function findPluginFile($pluginName)
    {
        $basePath = resource_path('plugins');
        $studlyName = Str::studly($pluginName);
        
        // Check for exact case match
        $exactPath = "{$basePath}/{$studlyName}/{$studlyName}Plugin.php";
        if (File::exists($exactPath)) {
            return $exactPath;
        }
        
        // Check for case-insensitive match
        $directories = File::directories($basePath);
        foreach ($directories as $directory) {
            if (strtolower(basename($directory)) === strtolower($pluginName)) {
                $filePath = "{$directory}/{$studlyName}Plugin.php";
                if (File::exists($filePath)) {
                    return $filePath;
                }
            }
        }
        
        return null;
    }

    protected function runPostActivationCommands(Plugin $plugin)
    {
        // Call the plugin's postActivation method
        $plugin->postActivation();
    }

    protected function getInstalledPlugins()
    {
        return app('cache')->remember('wave_installed_plugins', 60 * 24, function () {
            $path = resource_path('plugins/installed.json');
            if (!File::exists($path)) {
                if (!app('cache')->has('installed_json_not_found')) {
                    Log::warning("installed.json does not exist at: {$path}");
                    app('cache')->put('installed_json_not_found', true, 60); // Cache for 1 minute
                }
                return [];
            }
            return File::json($path);
        });
    }
    
    /**
     * Clear the installed plugins cache.
     * Call this method when plugins are installed or uninstalled.
     */
    public function clearPluginsCache()
    {
        app('cache')->forget('wave_installed_plugins');
        app('cache')->forget('plugins_loaded_recently');
        app('cache')->forget('installed_json_not_found');
        
        // Clear plugin-specific caches
        foreach ($this->plugins as $pluginName => $plugin) {
            app('cache')->forget('plugin_loaded_recently_' . $pluginName);
            app('cache')->forget('plugin_class_not_found_' . $pluginName);
            app('cache')->forget('plugin_file_not_found_' . $pluginName);
        }
    }
}
