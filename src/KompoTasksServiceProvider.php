<?php

namespace Kompo\Tasks;

use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\ServiceProvider;
use Kompo\Tasks\Facades\TaskModel;
use Kompo\Tasks\Models\TaskDetail;
use Kompo\Tasks\Policies\TaskDetailPolicy;
use Kompo\Tasks\Policies\TaskPolicy;

class KompoTasksServiceProvider extends ServiceProvider
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

        $this->registerPolicies();

        $this->extendRouting();

        $this->loadJSONTranslationsFrom(__DIR__.'/../resources/lang');

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

        Relation::morphMap([
            'user' => \App\Models\User::class,
            'taskDetail' => TaskDetail::class,
            'task' => TaskModel::getClass(),
        ]);

        $this->app->bind('task-model', function () {
            return new (config('tasks.task-model-namespace'))();
        });
    }

    protected function registerPolicies()
    {
        $policies = [
            TaskDetail::class => TaskDetailPolicy::class,
            TaskModel::getClass() => TaskPolicy::class,
        ];

        foreach ($policies as $key => $value) {
            \Gate::policy($key, $value);
        }
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
