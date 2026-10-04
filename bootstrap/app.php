<?php

use App\Http\Middleware\HandleInertiaRequests;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Src\Ordering\Infrastructure\Http\OrderingExceptions;
use Symfony\Component\HttpFoundation\Response;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [HandleInertiaRequests::class]);

        $middleware->redirectGuestsTo('/login');
        $middleware->redirectUsersTo('/products');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        // Expired CSRF token (e.g. a form left open past the session lifetime): instead of
        // Inertia's error modal, send the user back to the page with a toast to retry.
        $exceptions->respond(function (Response $response, Throwable $e, Request $request) {
            if ($response->getStatusCode() === 419 && ! $request->is('api/*')) {
                Inertia::flash('toast', ['type' => 'error', 'message' => 'The page expired, please try again.']);

                return back();
            }

            return $response;
        });

        OrderingExceptions::register($exceptions);
    })->create();
