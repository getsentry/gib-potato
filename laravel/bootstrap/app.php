<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Register middleware aliases
        $middleware->alias([
            'verify.potal' => \App\Http\Middleware\VerifyPotalToken::class,
            'verify.slack' => \App\Http\Middleware\VerifySlackSignature::class,
        ]);
        
        // Configure API middleware group to include Sanctum
        $middleware->group('api', [
            \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
            'throttle:api',
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
        ]);
        
        // Configure rate limiters
        $middleware->throttleApi('60,1'); // 60 requests per minute for API
        
        // Exclude CSRF for API and service routes
        $middleware->validateCsrfTokens(except: [
            'api/*',
            'events',
            'health',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // Custom exception handling can be added here
    })
    ->create();