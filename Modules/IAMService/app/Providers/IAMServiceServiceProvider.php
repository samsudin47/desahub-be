<?php

namespace Modules\IAMService\Providers;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;
use Modules\IAMService\Http\Middleware\AccessPermissionCheckMiddleware;
use Modules\IAMService\Http\Middleware\ApiPerformanceLoggerMiddleware;
use Modules\IAMService\Http\Middleware\AuthenticationMiddleware;
use Modules\IAMService\Http\Middleware\FeatureCheckMiddleware;
use Modules\IAMService\Http\Middleware\RoleCheckMiddleware;
use Modules\IAMService\Http\Middleware\ScopedAccessPermissionCheckMiddleware;
use Modules\IAMService\Http\Middleware\ServiceModuleCheckMiddleware;
use Modules\IAMService\Models\User;
use Nwidart\Modules\Traits\PathNamespace;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class IAMServiceServiceProvider extends ServiceProvider
{
    use PathNamespace;

    protected string $name = 'IAMService';

    protected string $nameLower = 'iamservice';

    /**
     * @var array<string, class-string>
     */
    protected array $routeMiddleware = [
        'desahub-auth' => AuthenticationMiddleware::class,
        'desahub-user-role' => RoleCheckMiddleware::class,
        'desahub-module-permission' => ServiceModuleCheckMiddleware::class,
        'desahub-feature-permission' => FeatureCheckMiddleware::class,
        'desahub-access-permission' => AccessPermissionCheckMiddleware::class,
        'desahub-scoped-access-permission' => ScopedAccessPermissionCheckMiddleware::class,
        'desahub-api-performance' => ApiPerformanceLoggerMiddleware::class,
    ];

    public function boot(Router $router): void
    {
        $this->registerCommands();
        $this->registerCommandSchedules();
        $this->registerTranslations();
        $this->registerViews();
        $this->loadMigrationsFrom(module_path($this->name, 'database/migrations'));
        $this->registerMiddleware($router);
        $this->registerPasswordResetUrl();
    }

    public function register(): void
    {
        $this->registerConfig();
        $this->app->register(EventServiceProvider::class);
        $this->app->register(RouteServiceProvider::class);
    }

    protected function registerCommands(): void
    {
        // $this->commands([]);
    }

    protected function registerCommandSchedules(): void
    {
        // $this->app->booted(function () {
        //     $schedule = $this->app->make(Schedule::class);
        //     $schedule->command('inspire')->hourly();
        // });
    }

    public function registerTranslations(): void
    {
        $langPath = resource_path('lang/modules/'.$this->nameLower);

        if (is_dir($langPath)) {
            $this->loadTranslationsFrom($langPath, $this->nameLower);
            $this->loadJsonTranslationsFrom($langPath);
        } else {
            $this->loadTranslationsFrom(module_path($this->name, 'lang'), $this->nameLower);
            $this->loadJsonTranslationsFrom(module_path($this->name, 'lang'));
        }
    }

    protected function registerConfig(): void
    {
        $configPath = module_path($this->name, config('modules.paths.generator.config.path'));

        if (! is_dir($configPath)) {
            return;
        }

        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($configPath));

        foreach ($iterator as $file) {
            if (! $file->isFile() || $file->getExtension() !== 'php') {
                continue;
            }

            $config = str_replace($configPath.DIRECTORY_SEPARATOR, '', $file->getPathname());
            $config_key = str_replace([DIRECTORY_SEPARATOR, '.php'], ['.', ''], $config);
            $segments = explode('.', $this->nameLower.'.'.$config_key);

            $normalized = [];
            foreach ($segments as $segment) {
                if (end($normalized) !== $segment) {
                    $normalized[] = $segment;
                }
            }

            $key = ($config === 'config.php') ? $this->nameLower : implode('.', $normalized);

            $this->publishes([$file->getPathname() => config_path($config)], 'config');
            $this->merge_config_from($file->getPathname(), $key);
        }
    }

    protected function merge_config_from(string $path, string $key): void
    {
        $existing = config($key, []);
        $module_config = require $path;

        config([$key => array_replace_recursive($existing, $module_config)]);
    }

    public function registerViews(): void
    {
        $viewPath = resource_path('views/modules/'.$this->nameLower);
        $sourcePath = module_path($this->name, 'resources/views');

        $this->publishes([$sourcePath => $viewPath], ['views', $this->nameLower.'-module-views']);

        $this->loadViewsFrom(array_merge($this->getPublishableViewPaths(), [$sourcePath]), $this->nameLower);

        Blade::componentNamespace(config('modules.namespace').'\\'.$this->name.'\\View\\Components', $this->nameLower);
    }

    /**
     * @return array<int, string>
     */
    public function provides(): array
    {
        return [];
    }

    /**
     * @return array<int, string>
     */
    private function getPublishableViewPaths(): array
    {
        $paths = [];
        foreach (config('view.paths') as $path) {
            if (is_dir($path.'/modules/'.$this->nameLower)) {
                $paths[] = $path.'/modules/'.$this->nameLower;
            }
        }

        return $paths;
    }

    protected function registerMiddleware(Router $router): void
    {
        foreach ($this->routeMiddleware as $alias => $class) {
            $router->aliasMiddleware($alias, $class);
        }
    }

    protected function registerPasswordResetUrl(): void
    {
        ResetPassword::createUrlUsing(function (User $user, string $token): string {
            $baseUrl = rtrim((string) config('iamservice.auth.frontend_reset_password_url'), '?&');

            return $baseUrl.'?'.http_build_query([
                'token' => $token,
                'email' => $user->getEmailForPasswordReset(),
            ]);
        });
    }
}
