<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'api.token' => \App\Http\Middleware\EnsureApiToken::class,
            'admin.elevated' => \App\Http\Middleware\EnsureElevatedAdmin::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->respond(function ($response, \Throwable $e, Request $request) {
            if ($response->getStatusCode() === 419 && ! $request->expectsJson()) {
                return redirect()
                    ->route('login.form')
                    ->withErrors(['email' => 'Tu sesion expiro. Inicia sesion nuevamente.']);
            }

            return $response;
        });
    })->create();
