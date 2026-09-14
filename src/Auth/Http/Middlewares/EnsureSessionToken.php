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
 * Only the access token passes. A refresh token lives for 90 days and exists
 * to mint access tokens, nothing else; letting it act as one everywhere turns
 * every long-lived credential into a full session. `token/refresh` sits
 * outside this middleware and checks for the refresh token itself.
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
            [TokenType::Access->getName()],
            strict: true,
        );
    }
}
