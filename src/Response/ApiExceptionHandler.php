<?php

declare(strict_types=1);

namespace HiTaqnia\Haykal\Api\Response;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

/**
 * Translate common framework exceptions into the Haykal API envelope.
 *
 * Wire into the exception handler in `bootstrap/app.php`:
 *
 *     ->withExceptions(function (Exceptions $exceptions) {
 *         $exceptions->render(function (Throwable $e, Request $request) {
 *             return ApiExceptionHandler::handle($e, $request);
 *         });
 *     })
 *
 * Only requests under `api/*` are intercepted; all other requests fall
 * through to Laravel's default handler.
 */
final class ApiExceptionHandler
{
    public static function handle(Throwable $e, Request $request): ?JsonResponse
    {
        if (! $request->is('api/*')) {
            return null;
        }

        return match (true) {
            $e instanceof ValidationException => ApiResponse::validationError(errors: $e->errors()),
            $e instanceof AuthenticationException => ApiResponse::unauthorized(),
            $e instanceof AuthorizationException,
            $e instanceof AccessDeniedHttpException => ApiResponse::forbidden(),
            $e instanceof HttpExceptionInterface && $e->getStatusCode() === 403 => ApiResponse::forbidden(),
            $e instanceof ModelNotFoundException => ApiResponse::notFound(),
            $e instanceof NotFoundHttpException => ApiResponse::notFound('The requested resource was not found.'),
            $e instanceof MethodNotAllowedHttpException => ApiResponse::methodNotAllowed(),
            $e instanceof ThrottleRequestsException => ApiResponse::tooManyRequests(),
            default => ApiResponse::serverError(debug: config('app.debug') ? ['trace' => $e->getTrace()] : []),
        };
    }
}
