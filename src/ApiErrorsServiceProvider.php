<?php

namespace LaravelApiErrors;

use Illuminate\Support\ServiceProvider;
use LaravelApiErrors\Console\ExportSwaggerCommand;
use LaravelApiErrors\Console\ExportTypescriptCommand;
use LaravelApiErrors\Console\ListCodesCommand;
use LaravelApiErrors\Console\SyncTranslationsCommand;
use LaravelApiErrors\Enums\DefaultErrorCode;
use LaravelApiErrors\Support\ErrorCodeRegistry;

class ApiErrorsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/api-errors.php', 'api-errors');

        $this->app->singleton(ErrorCodeRegistry::class, function () {
            $registry = new ErrorCodeRegistry();

            // Register built-in codes
            $registry->register(DefaultErrorCode::class);

            // Register user-supplied enums
            foreach (config('api-errors.extra_enums', []) as $enumClass) {
                $registry->register($enumClass);
            }

            return $registry;
        });

        $this->app->alias(ErrorCodeRegistry::class, 'api-errors.registry');
    }

    public function boot(): void
    {
        // Translations
        $this->loadTranslationsFrom(__DIR__ . '/../resources/lang', 'api-errors');

        if ($this->app->runningInConsole()) {
            // Publish config
            $this->publishes([
                __DIR__ . '/../config/api-errors.php' => config_path('api-errors.php'),
            ], 'api-errors-config');

            // Publish translations
            $this->publishes([
                __DIR__ . '/../resources/lang' => $this->app->langPath('vendor/api-errors'),
            ], 'api-errors-translations');

            // Publish stub
            $this->publishes([
                __DIR__ . '/../stubs/AppErrorCode.stub' => app_path('Enums/AppErrorCode.php'),
            ], 'api-errors-stubs');

            // Commands
            $this->commands([
                ExportTypescriptCommand::class,
                ExportSwaggerCommand::class,
                SyncTranslationsCommand::class,
                ListCodesCommand::class,
            ]);
        }
    }
}
