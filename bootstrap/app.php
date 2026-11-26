<?php

use App\Exceptions\ApiException;
use App\Exceptions\InvalidCredentialsException;
use App\Http\Middleware\AuthToken;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: [
            __DIR__ . '/../routes/Api/auth.php',
            __DIR__ . '/../routes/Api/profile.php',
            __DIR__ . '/../routes/Api/conversations.php',
            __DIR__ . '/../routes/Api/models.php',
            __DIR__ . '/../routes/Api/library_projects.php',
        ],
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'auth.token' => AuthToken::class,

        ]);

        $middleware->api(prepend: []);
    })
    ->withExceptions(function (Exceptions $exceptions): void {

        $exceptions->render(function (InvalidCredentialsException $e, Request $request) {
            return Response::error($e->getMessage(), $e->getStatusCode());
        });

        $exceptions->render(function (ApiException $e, Request $request) {
            return Response::error($e->getMessage(), $e->getStatusCode());
        });

        $exceptions->render(function (AuthorizationException $e, Request $request) {
            $message = $e->response()?->message()
                ?? ($e->getMessage() !== 'This action is unauthorized.'
                    ? $e->getMessage()
                    : 'You are not authorized to perform this action.');

            return Response::error($message, 403);
        });

        $exceptions->render(function (NotFoundHttpException $e, Request $request) {

            $previous = $e->getPrevious();

            // Check if the 404 was caused by a missing Eloquent model
            if ($previous instanceof ModelNotFoundException) {
                $model = $previous->getModel();
                $ids = implode(', ', (array) $previous->getIds());

                return Response::error("Model not found {$model} {$ids}", 404);
            }
            $message = $e->getMessage() ?: 'The requested resource does not exist.';

            return Response::error($message, 404);
        });

        $exceptions->render(function (AccessDeniedHttpException $e, Request $request) {
            return Response::error($e->getMessage(), $e->getStatusCode());
        });







    })->create();
