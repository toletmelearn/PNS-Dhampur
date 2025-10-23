<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Config;

class ModuleServiceProvider extends ServiceProvider
{
    /**
     * All available modules in the application
     */
    protected $modules = [
        'Student',
        'Teacher', 
        'Attendance',
        'Academic',
        'Exam',
        'Fee',
        'Library',
        'Transport',
        'Communication',
        'Reports',
        'Settings',
        'ParentPortal',
        'Hostel'
    ];

    /**
     * Register services.
     */
    public function register(): void
    {
        // Register module-specific services
        $this->registerModuleServices();
        
        // Register module configurations
        $this->registerModuleConfigs();
        
        // Register module repositories
        $this->registerModuleRepositories();
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Load module routes
        $this->loadModuleRoutes();
        
        // Load module views
        $this->loadModuleViews();
        
        // Load module translations
        $this->loadModuleTranslations();
        
        // Load module migrations
        $this->loadModuleMigrations();
        
        // Register module commands
        $this->registerModuleCommands();
        
        // Register module event listeners
        $this->registerModuleEventListeners();
    }

    /**
     * Register module-specific services
     */
    protected function registerModuleServices(): void
    {
        foreach ($this->modules as $module) {
            $serviceClass = "App\\Modules\\{$module}\\Services\\{$module}Service";
            
            if (class_exists($serviceClass)) {
                $this->app->singleton($serviceClass, function ($app) use ($serviceClass) {
                    return $app->make($serviceClass);
                });
            }
            
            // Register module repositories
            $repositoryClass = "App\\Modules\\{$module}\\Repositories\\{$module}Repository";
            $repositoryInterface = "App\\Modules\\{$module}\\Contracts\\{$module}RepositoryInterface";
            
            if (class_exists($repositoryClass) && interface_exists($repositoryInterface)) {
                $this->app->bind($repositoryInterface, $repositoryClass);
            }
        }
    }

    /**
     * Register module configurations
     */
    protected function registerModuleConfigs(): void
    {
        foreach ($this->modules as $module) {
            $configPath = app_path("Modules/{$module}/config.php");
            
            if (file_exists($configPath)) {
                $this->mergeConfigFrom($configPath, strtolower($module));
            }
        }
    }

    /**
     * Register module repositories
     */
    protected function registerModuleRepositories(): void
    {
        foreach ($this->modules as $module) {
            // Register Eloquent repositories
            $repositoryClass = "App\\Modules\\{$module}\\Repositories\\Eloquent{$module}Repository";
            $repositoryInterface = "App\\Modules\\{$module}\\Contracts\\{$module}RepositoryInterface";
            
            if (class_exists($repositoryClass) && interface_exists($repositoryInterface)) {
                $this->app->bind($repositoryInterface, $repositoryClass);
            }
        }
    }

    /**
     * Load module routes
     */
    protected function loadModuleRoutes(): void
    {
        foreach ($this->modules as $module) {
            $routePath = app_path("Modules/{$module}/routes.php");
            
            if (file_exists($routePath)) {
                Route::middleware(['web', 'auth', 'verified', 'module:' . strtolower($module), 'security', 'audit'])
                    ->prefix(strtolower($module))
                    ->name(strtolower($module) . '.')
                    ->group($routePath);
            }
            
            // Load API routes if they exist
            $apiRoutePath = app_path("Modules/{$module}/api.php");
            
            if (file_exists($apiRoutePath)) {
                Route::middleware(['api', 'auth:sanctum', 'module', 'security', 'audit'])
                    ->prefix('api/' . strtolower($module))
                    ->name('api.' . strtolower($module) . '.')
                    ->group($apiRoutePath);
            }
        }
    }

    /**
     * Load module views
     */
    protected function loadModuleViews(): void
    {
        foreach ($this->modules as $module) {
            $viewPath = app_path("Modules/{$module}/Views");
            
            if (is_dir($viewPath)) {
                View::addNamespace(strtolower($module), $viewPath);
            }
        }
    }

    /**
     * Load module translations
     */
    protected function loadModuleTranslations(): void
    {
        foreach ($this->modules as $module) {
            $langPath = app_path("Modules/{$module}/Lang");
            
            if (is_dir($langPath)) {
                $this->loadTranslationsFrom($langPath, strtolower($module));
            }
        }
    }

    /**
     * Load module migrations
     */
    protected function loadModuleMigrations(): void
    {
        foreach ($this->modules as $module) {
            $migrationPath = app_path("Modules/{$module}/Migrations");
            
            if (is_dir($migrationPath)) {
                $this->loadMigrationsFrom($migrationPath);
            }
        }
    }

    /**
     * Register module commands
     */
    protected function registerModuleCommands(): void
    {
        if ($this->app->runningInConsole()) {
            foreach ($this->modules as $module) {
                $commandsPath = app_path("Modules/{$module}/Commands");
                
                if (is_dir($commandsPath)) {
                    $commands = glob($commandsPath . '/*.php');
                    
                    foreach ($commands as $command) {
                        $className = 'App\\Modules\\' . $module . '\\Commands\\' . basename($command, '.php');
                        
                        if (class_exists($className)) {
                            $this->commands($className);
                        }
                    }
                }
            }
        }
    }

    /**
     * Register module event listeners
     */
    protected function registerModuleEventListeners(): void
    {
        foreach ($this->modules as $module) {
            $listenersPath = app_path("Modules/{$module}/Listeners");
            
            if (is_dir($listenersPath)) {
                // Auto-register event listeners based on naming convention
                $listeners = glob($listenersPath . '/*.php');
                
                foreach ($listeners as $listener) {
                    $className = 'App\\Modules\\' . $module . '\\Listeners\\' . basename($listener, '.php');
                    
                    if (class_exists($className)) {
                        // Register listener with event dispatcher
                        // This would typically be done in EventServiceProvider
                        // but we're doing basic registration here
                    }
                }
            }
        }
    }

    /**
     * Get all registered modules
     */
    public function getModules(): array
    {
        return $this->modules;
    }

    /**
     * Check if a module is registered
     */
    public function hasModule(string $module): bool
    {
        return in_array($module, $this->modules);
    }

    /**
     * Register a new module dynamically
     */
    public function registerModule(string $module): void
    {
        if (!$this->hasModule($module)) {
            $this->modules[] = $module;
            
            // Re-register services for the new module
            $this->registerModuleServices();
            $this->loadModuleRoutes();
            $this->loadModuleViews();
        }
    }

    /**
     * Get module configuration
     */
    public function getModuleConfig(string $module, string $key = null, $default = null)
    {
        $configKey = strtolower($module);
        
        if ($key) {
            return Config::get("{$configKey}.{$key}", $default);
        }
        
        return Config::get($configKey, $default);
    }

    /**
     * Check if module is enabled
     */
    public function isModuleEnabled(string $module): bool
    {
        return $this->getModuleConfig($module, 'enabled', true);
    }

    /**
     * Get module permissions
     */
    public function getModulePermissions(string $module): array
    {
        return $this->getModuleConfig($module, 'permissions', []);
    }

    /**
     * Get module middleware
     */
    public function getModuleMiddleware(string $module): array
    {
        return $this->getModuleConfig($module, 'middleware', []);
    }
}