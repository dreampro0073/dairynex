<?php

use App\Http\Middleware\EnsureAccountAccess;
use App\Http\Middleware\HandleInertiaRequests;
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
        $middleware->web(append: [HandleInertiaRequests::class]);
        $middleware->alias(['account' => EnsureAccountAccess::class]);
        $middleware->redirectGuestsTo('/login');
        $middleware->redirectUsersTo(fn () => auth()->user()?->role()?->home() ?? '/login');
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->dontFlash(['pin']);
    })->create();
