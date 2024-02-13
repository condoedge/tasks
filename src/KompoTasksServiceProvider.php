<?php

namespace Kompo\Auth;

use Illuminate\Support\ServiceProvider;

class KompoTaskserviceProvider extends ServiceProvider
{
    use \Kompo\Routing\Mixins\ExtendsRoutingTrait;

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        $this->loadHelpers();

        $this->extendRouting();

        $this->loadTranslationsFrom(__DIR__.'/../resources/lang', 'kompo-tasks');

        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        $this->loadViewsFrom(__DIR__.'/../resources/views', 'kompo-tasks');

         //Usage: php artisan vendor:publish --tag="kompo-tasks"
        $this->publishes([
            __DIR__.'/../config/tasks.php' => config_path('tasks.php'),
        ], 'kompo-tasks');
    }

    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->loadRoutes();
    }

    protected function loadHelpers()
    {
        $helpersDir = __DIR__.'/Helpers';

        $autoloadedHelpers = collect(\File::allFiles($helpersDir))->map(fn($file) => $file->getRealPath());

        $packageHelpers = [
        ];

        $autoloadedHelpers->concat($packageHelpers)->each(function ($path) {
            if (file_exists($path)) {
                require_once $path;
            }
        });
    }

    protected function loadRoutes()
    {
        $this->booted(function () {
            \Route::middleware('web')->group(__DIR__.'/../routes/web.php');
        });
    }
}
