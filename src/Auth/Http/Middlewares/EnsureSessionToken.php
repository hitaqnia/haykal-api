<?php

declare(strict_types=1);

namespace HiTaqnia\Haykal\Api\Auth\Http\Middlewares;

use Closure;
use HiTaqnia\Haykal\Api\Auth\Enums\TokenType;
use HiTaqnia\Haykal\Api\Auth\Models\Token;
use HiTaqnia\Haykal\Api\Response\ApiResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Reject single-purpose tokens on routes that expect a real session.
 *
 * `auth:sanctum` authenticates any unexpired token and never looks at
 * abilities, so without this the short-lived OTP token — issued on proof of
 * phone possession alone, no password — would authenticate the whole API for
 * its lifetime.
 *
 * Alias: `haykal.session.token`.
 */
final class EnsureSessionToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->user()?->currentAccessToken();

        if ($token === null) {
            return $next($request);
        }

        if (! $this->isSessionToken($token)) {
            return ApiResponse::unauthorized(__('haykal-api::auth.invalid_token'));
        }

        return $next($request);
    }

    private function isSessionToken(mixed $token): bool
    {
        return $token instanceof Token && in_array(
            $token->name,
            [TokenType::Access->getName(), TokenType::Refresh->getName()],
            strict: true,
        );
    }
}
