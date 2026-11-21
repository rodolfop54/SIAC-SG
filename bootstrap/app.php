<?php

use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\IdleSession;
use App\Http\Middleware\RoleMiddleware;
use App\Http\Middleware\SecurityHeaders;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Inertia\Inertia;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $proxySetting = env('TRUSTED_PROXIES', '127.0.0.1,::1');
        $proxyHeaders = Request::HEADER_X_FORWARDED_FOR | Request::HEADER_X_FORWARDED_PORT | Request::HEADER_X_FORWARDED_PROTO;
        if ($proxySetting !== '*') {
            $proxyHeaders |= Request::HEADER_X_FORWARDED_HOST;
        }
        $middleware->trustProxies(
            at: $proxySetting === '*' ? '*' : explode(',', $proxySetting),
            headers: $proxyHeaders,
        );
        $middleware->trimStrings(except: ['message']);
        $middleware->web(append: [IdleSession::class, HandleInertiaRequests::class, SecurityHeaders::class]);
        $middleware->alias(['role' => RoleMiddleware::class]);
        $middleware->redirectGuestsTo(fn () => route('login', ['ended' => 1]));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->dontFlash(['message', 'document_number', 'current_password', 'password', 'password_confirmation', 'code', 'access_code', 'token']);
        $exceptions->respond(function ($response) {
            $status = $response->getStatusCode();
            if (in_array($status, [403, 404, 419, 422, 500, 503]) && ! request()->expectsJson()) {
                return Inertia::render('Error', ['status' => $status])->toResponse(request())->setStatusCode($status);
            }

            return $response;
        });
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
