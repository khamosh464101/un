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
        $middleware->validateCsrfTokens(except: [
            'http://127.0.0.1:8000/*',
            'https://un.momtazhost.com/*',
            'http://35.211.144.31/*',
        ]);
        $middleware->statefulApi();
        $middleware->alias([
            'verified' => \App\Http\Middleware\EnsureEmailIsVerified::class,
            'twofactor' => \App\Http\Middleware\TwoFactorMiddleware::class,
        ]);
    })
    ->withCommands([
    \App\Console\Commands\CheckMissingSurveyPdfs::class,
    ])
    ->withExceptions(function (Exceptions $exceptions) {
        //
      
    })->create();
