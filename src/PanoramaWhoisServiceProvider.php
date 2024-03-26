<?php

namespace kevinoo\PanoramaWhois;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Container\Container;
use Illuminate\Support\ServiceProvider;

class PanoramaWhoisServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $configPath = __DIR__ . '/config';

        $this->mergeConfigFrom($configPath .'/config.php','panorama-whois');

        $this->publishes([
            $configPath .'/config.php' => $this->app->configPath() .'/panorama-whois.php',
        ], 'config');
    }

    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->registerPanoramaWhoIs();
    }

    public function registerPanoramaWhoIs(): void
    {
        $this->app->singleton(PanoramaWhoIs::class, function(Container $app): PanoramaWhoIs {
            $config = $app->make(Repository::class);
            return new PanoramaWhoIs($app, $config);
        });
        $this->app->tag(PanoramaWhoIs::class, 'panorama-whois');
        $this->app->alias(PanoramaWhoIs::class, 'PanoramaWhoIs');
    }
}
