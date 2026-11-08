<?php

use App\Http\Controllers\V1\JwksController;
use App\Http\Middleware\EnsureJwtRole;
use App\Http\Middleware\ForceJsonRequestHeader;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            Route::get('/.well-known/jwks.json', [JwksController::class, 'jwks']);
            Route::get('/.well-known/openid-configuration', [JwksController::class, 'configuration']);
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->append(ForceJsonRequestHeader::class);
        $middleware->alias([
            'role.jwt' => EnsureJwtRole::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        $exceptions->render(function (AuthenticationException $e, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            $message = $e->getMessage() ?: 'Unauthenticated.';

            return response()->json([
                'data' => [],
                'errors' => ['auth' => [$message]],
                'message' => $message,
                'status' => 401,
            ], 401);
        });

        $exceptions->render(function (AccessDeniedHttpException $e, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            $message = $e->getMessage() ?: 'Forbidden.';

            return response()->json([
                'data' => [],
                'errors' => ['auth' => [$message]],
                'message' => $message,
                'status' => 403,
            ], 403);
        });
    })->create();
