<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

$app = Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })
    ->create();

// Register dev-only providers like Pail only in local
if (env('APP_ENV') === 'local' && class_exists(\Laravel\Pail\PailServiceProvider::class)) {
    $app->register(\Laravel\Pail\PailServiceProvider::class);
}

return $app;
