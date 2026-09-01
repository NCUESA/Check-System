<?php

use App\Http\Middleware\AdminMiddleware;
use App\Http\Middleware\AuthMiddleware;
use App\Http\Middleware\HandleInertiaRequests;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        //
        $middleware->alias([
            'ipAuth' => AuthMiddleware::class,
            'admin' => AdminMiddleware::class
        ]);
        $middleware->redirectGuestsTo(fn () => route('login.keycloak.redirect'));

        // 前面有反向代理時，要信任它送來的 X-Forwarded-For，
        // 否則 $request->ip() 拿到的是代理的 IP，打卡的地點判斷會全部失準。
        // 預設信任所有代理（容器只對內網開放）；要收緊就在 .env 設
        // TRUSTED_PROXIES=10.0.0.0/8,172.16.0.0/12
        $middleware->trustProxies(
            at: env('TRUSTED_PROXIES') ? explode(',', env('TRUSTED_PROXIES')) : '*'
        );
        $middleware->web(append: [
            HandleInertiaRequests::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
