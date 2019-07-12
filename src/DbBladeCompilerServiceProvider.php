<?php

namespace Kiroushi\DbBlade;

use Illuminate\Support\Collection;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\ServiceProvider;
use Kiroushi\DbBlade\Compilers\DbBladeCompiler;

class DbBladeCompilerServiceProvider extends ServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = false;

    /**
     * Bootstrap the application events.
     *
     * @return void
     */
    public function boot(Filesystem $filesystem)
    {
        $this->publishes([
            __DIR__.'/../config/db-blade.php' => config_path('db-blade.php'),
        ], 'config');

        $this->publishes([
            __DIR__.'/../database/migrations/create_db_views_table.php' => $this->getMigrationFileName($filesystem),
        ], 'migrations');

        $this->publishes([
            __DIR__.'/../config/.gitkeep' => storage_path('app/db-blade/cache/views/.gitkeep'),
        ]);
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/db-blade.php',
            'db-blade'
        );

        $this->registerFactory();

        $this->registerDbViewFinder();

        $this->app->singleton(DbBladeCompiler::class, function ($app) {
            $cachePath = storage_path('app/db-blade/cache/views');

            return new DbBladeCompiler($app['files'], $cachePath);
        });
    }

    /**
     * Register the view environment.
     *
     * @return void
     */
    public function registerFactory()
    {
        $this->app->singleton('dbview', function ($app) {
            $finder = $app['dbview.finder'];

            $factory = $this->createFactory($finder, $app['events']);

            // We will also set the container instance on this view environment since the
            // view composers may be classes registered in the container, which allows
            // for great testable, flexible composers for the application developer.
            $factory->setContainer($app);

            $factory->share('app', $app);

            return $factory;
        });
    }

    /**
     * Create a new Factory Instance.
     *
     * @param  \Kiroushi\DbBlade\DbViewFinder  $finder
     * @param  \Illuminate\Contracts\Events\Dispatcher  $events
     * @return \Kiroushi\DbBlade\Factory
     */
    protected function createFactory($finder, $events)
    {
        return new Factory($finder, $events);
    }

    /**
     * Register the view finder implementation.
     *
     * @return void
     */
    public function registerDbViewFinder()
    {
        $this->app->bind('dbview.finder', function ($app) {
            return new DbViewFinder(
                $app['config']['db-blade.model_name'],
                $app['config']['db-blade.name_field']
            );
        });
    }

    /**
     * Returns existing migration file if found, else uses the current timestamp.
     *
     * Credits to Freek Van der Herten:
     * https://github.com/spatie/laravel-permission/blob/master/src/PermissionServiceProvider.php#L157
     *
     * @param Filesystem $filesystem
     * @return string
     */
    protected function getMigrationFileName(Filesystem $filesystem): string
    {
        $timestamp = date('Y_m_d_His');

        return Collection::make($this->app->databasePath().DIRECTORY_SEPARATOR.'migrations'.DIRECTORY_SEPARATOR)
            ->flatMap(function ($path) use ($filesystem) {
                return $filesystem->glob($path.'*_create_db_views_table.php');
            })->push($this->app->databasePath()."/migrations/{$timestamp}_create_db_views_table.php")
            ->first();
    }
}
