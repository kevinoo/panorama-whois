<?php

namespace kevinoo\PanoramaWhois\Support;

use Illuminate\Support\ServiceProvider;


class PanoramaWhoisServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $configPath = dirname(__DIR__,2) . '/config';

        $this->mergeConfigFrom($configPath .'/config.php','panorama-whois');

        $this->publishes([
            $configPath .'/config.php' => $this->app->configPath() .'/panorama-whois.php',
        ], 'config');
    }
}
