<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleMiddleware;
use Spatie\Permission\Middleware\RoleOrPermissionMiddleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->validateCsrfTokens(except: [
            'webhooks/stripe/ecommerce',
        ]);

        $middleware->alias([
            'role' => RoleMiddleware::class,
            'permission' => PermissionMiddleware::class,
            'role_or_permission' => RoleOrPermissionMiddleware::class,
            'demo.scope' => \App\Http\Middleware\EnsureDemoModeScope::class,
            'two_factor.enforced' => \App\Http\Middleware\EnsureTwoFactorEnabled::class,
            'shop.enabled' => \App\Http\Middleware\EnsureShopEnabled::class,
            'sito.scope' => \App\Http\Middleware\ScopeToActiveSito::class,
        ]);

        $middleware->web(append: [
            \App\Http\Middleware\AssignRequestCorrelationId::class,
            \App\Http\Middleware\SecurityHeaders::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
