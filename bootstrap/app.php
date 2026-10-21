<?php

use App\Http\Middleware\EnsureUserHasRole;
use App\Http\Middleware\PreventAuthenticatedCaching;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'role' => EnsureUserHasRole::class,
        ]);

        $middleware->web(append: [
            PreventAuthenticatedCaching::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // Sesi/CSRF sudah tidak valid: arahkan ke login, bukan halaman 419.
        $exceptions->respond(function (Response $response, Throwable $e, Request $request) {
            if ($response->getStatusCode() !== 419) {
                return $response;
            }

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Sesi Anda telah berakhir. Silakan muat ulang halaman.',
                ], 419);
            }

            return redirect()->route('login')
                ->with('error', 'Sesi Anda telah berakhir. Silakan masuk kembali.');
        });
    })->create();
