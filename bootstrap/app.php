<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

// $app->configure('modules');
// $app->register(\Nwidart\Modules\LumenModulesServiceProvider::class);
return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware
            ->group('tenant', [
                \Spatie\Multitenancy\Http\Middleware\NeedsTenant::class,
                \Spatie\Multitenancy\Http\Middleware\EnsureValidTenantSession::class,
            ]);
        // $middleware->alias([
        //     'check' => \App\Http\Middleware\CheckAuthenticated::class,
        // ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
