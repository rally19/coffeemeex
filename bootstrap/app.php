<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use App\Http\Middleware\{CheckEmailDomain, CheckRoleAdmin, CheckRoleStaff, CheckRoleUser};

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'check.email' => CheckEmailDomain::class,
            'check.admin' => CheckRoleAdmin::class,
            'check.staff' => CheckRoleStaff::class,
            'check.user'  => CheckRoleUser::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
