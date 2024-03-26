<?php

namespace Tests;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;


abstract class TestCase extends BaseTestCase
{
    public function createApplication()
    {
        $app = new \Illuminate\Foundation\Application(
            $_ENV['APP_BASE_PATH'] ?? dirname(__DIR__).'/src/'
        );
//        $app->singleton(
//            \Illuminate\Contracts\Http\Kernel::class,
//            \Illuminate\Foundation\Http\Kernel::class
//        );
        $app->singleton(
            \Illuminate\Contracts\Console\Kernel::class,
            \Illuminate\Foundation\Console\Kernel::class
        );
//        $app->singleton(
//            \Illuminate\Contracts\Debug\ExceptionHandler::class,
//            \Illuminate\Foundation\Exceptions\Handler::class
//        );

        $app->make(Kernel::class)->bootstrap();

        return $app;
    }
}
