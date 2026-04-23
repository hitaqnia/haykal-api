<?php

declare(strict_types=1);

namespace HiTaqnia\Haykal\Api\Response\Concerns;

use Illuminate\Http\JsonResponse;

/**
 * Centralized JSON response construction for the Haykal API envelope.
 *
 * Every ApiResponse method funnels through `make()` so the envelope
 * shape (success, code, message, data, errors, optional debug) stays
 * identical across 2xx, 4xx, 5xx, and business errors.
 */
trait InteractsWithResponseMaker
{
    /**
     * @param  array<string, mixed>|null  $errors
     * @param  array<string, mixed>|null  $debug
     */
    protected static function make(
        int $code,
        ?string $message = null,
        mixed $data = null,
        ?array $errors = null,
        ?array $debug = null,
    ): JsonResponse {
        $isSuccess = $code >= 200 && $code < 300;

        $response = [
            'success' => $isSuccess ? 1 : 0,
            'code' => $code,
            'message' => $message,
            'data' => $isSuccess ? $data : null,
            'errors' => $isSuccess ? null : $errors,
        ];

        if (config('app.debug') && $debug !== null) {
            $response['debug'] = $debug;
        }

        // Codes above 999 are business error codes — map to HTTP 409 Conflict.
        if ($code > 999) {
            return response()->json($response, 409);
        }

        // Valid HTTP status codes pass through as-is.
        if ($code <= 599) {
            return response()->json($response, $code);
        }

        return response()->json($response);
    }

    protected static function makeSuccess(int $code, string $message, mixed $data): JsonResponse
    {
        return static::make(code: $code, message: $message, data: $data);
    }

    /**
     * @param  array<string, mixed>|null  $errors
     * @param  array<string, mixed>|null  $debug
     */
    protected static function makeError(
        int $code,
        string $message,
        ?array $errors = null,
        ?array $debug = null,
    ): JsonResponse {
        return static::make(code: $code, message: $message, errors: $errors, debug: $debug);
    }
}
