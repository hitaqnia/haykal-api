<?php

declare(strict_types=1);

namespace HiTaqnia\Haykal\Api\Response;

use HiTaqnia\Haykal\Api\Response\Concerns\InteractsWithResponseMaker;
use HiTaqnia\Haykal\Core\ResultPattern\Error;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\MessageBag;

/**
 * Fluent factory for Haykal's standard API response envelope.
 *
 * Every endpoint in a Haykal app returns through one of these methods
 * so responses share a consistent shape:
 *
 *     {
 *         "success": 1 | 0,
 *         "code":     <http or business code>,
 *         "message":  <string | null>,
 *         "data":     <payload | null>,
 *         "errors":   <array | null>
 *     }
 *
 * Use `businessError()` to surface a domain-level failure from a
 * `HiTaqnia\Haykal\Core\ResultPattern\Error` instance.
 */
final class ApiResponse
{
    use InteractsWithResponseMaker;

    // -------------------------------------------------------------
    // 2xx — Success
    // -------------------------------------------------------------

    public static function ok(string $message = 'OK', mixed $data = []): JsonResponse
    {
        return self::makeSuccess(code: 200, message: $message, data: $data);
    }

    public static function created(string $message = 'Created', mixed $data = []): JsonResponse
    {
        return self::makeSuccess(code: 201, message: $message, data: $data);
    }

    public static function accepted(string $message = 'Accepted', mixed $data = []): JsonResponse
    {
        return self::makeSuccess(code: 202, message: $message, data: $data);
    }

    public static function paginated(string $message = 'OK', ?LengthAwarePaginator $data = null): JsonResponse
    {
        return self::makeSuccess(code: 200, message: $message, data: new PaginatedResource($data));
    }

    public static function noContent(): JsonResponse
    {
        return self::makeSuccess(code: 204, message: 'No Content', data: null);
    }

    // -------------------------------------------------------------
    // 4xx — Client error
    // -------------------------------------------------------------

    public static function badRequest(string $message = 'Bad Request'): JsonResponse
    {
        return self::makeError(code: 400, message: $message);
    }

    public static function unauthorized(string $message = 'Unauthorized'): JsonResponse
    {
        return self::makeError(code: 401, message: $message);
    }

    public static function forbidden(string $message = 'Forbidden'): JsonResponse
    {
        return self::makeError(code: 403, message: $message);
    }

    /**
     * @param  array<string, mixed>  $errors
     */
    public static function notFound(string $message = 'Not Found', array $errors = []): JsonResponse
    {
        return self::makeError(code: 404, message: $message, errors: $errors);
    }

    public static function methodNotAllowed(string $message = 'Method Not Allowed'): JsonResponse
    {
        return self::makeError(code: 405, message: $message);
    }

    public static function conflict(string $message = 'Conflict'): JsonResponse
    {
        return self::makeError(code: 409, message: $message);
    }

    /**
     * @param  MessageBag|array<string, array<int, string>>  $errors
     */
    public static function validationError(
        string $message = 'Unprocessable Entity',
        MessageBag|array $errors = [],
    ): JsonResponse {
        $normalized = $errors instanceof MessageBag ? $errors->toArray() : $errors;

        return self::makeError(code: 422, message: $message, errors: $normalized);
    }

    public static function tooManyRequests(string $message = 'Too Many Requests'): JsonResponse
    {
        return self::makeError(code: 429, message: $message);
    }

    // -------------------------------------------------------------
    // 5xx — Server error
    // -------------------------------------------------------------

    /**
     * @param  array<string, mixed>  $debug
     */
    public static function serverError(string $message = 'Internal Server Error', array $debug = []): JsonResponse
    {
        return self::makeError(code: 500, message: $message, debug: $debug);
    }

    public static function notImplemented(string $message = 'Not Implemented'): JsonResponse
    {
        return self::makeError(code: 501, message: $message);
    }

    public static function serviceUnavailable(string $message = 'Service Unavailable'): JsonResponse
    {
        return self::makeError(code: 503, message: $message);
    }

    public static function gatewayTimeout(string $message = 'Gateway Timeout'): JsonResponse
    {
        return self::makeError(code: 504, message: $message);
    }

    // -------------------------------------------------------------
    // Business error — surface a domain Result failure.
    // -------------------------------------------------------------

    public static function businessError(Error $error): JsonResponse
    {
        return self::makeError(
            code: $error->getCode(),
            message: $error->getMessage() ?? 'An error occurred.',
        );
    }
}
