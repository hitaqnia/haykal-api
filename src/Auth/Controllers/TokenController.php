<?php

declare(strict_types=1);

namespace HiTaqnia\Haykal\Api\Auth\Controllers;

use HiTaqnia\Haykal\Api\Auth\Actions\Token\IssueTokensAction;
use HiTaqnia\Haykal\Api\Auth\Concerns\InteractsWithDeviceTokens;
use HiTaqnia\Haykal\Api\Auth\Concerns\ResolvesAuthUser;
use HiTaqnia\Haykal\Api\Auth\Enums\TokenType;
use HiTaqnia\Haykal\Api\Auth\Requests\Token\LoginRequest;
use HiTaqnia\Haykal\Api\Auth\Resources\TokenResource;
use HiTaqnia\Haykal\Api\Response\ApiResponse;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;

/**
 * Issuing, rotating and revoking the access/refresh pair.
 */
final class TokenController
{
    use InteractsWithDeviceTokens;
    use ResolvesAuthUser;

    /**
     * Sign in
     *
     * Exchanges a phone number and password for an access and refresh token
     * pair, bound to the calling device.
     */
    public function create(LoginRequest $request, IssueTokensAction $tokens): JsonResponse
    {
        $phone = $request->string('phone')->toString();

        // Key on the canonical number. `07701234567`, `+9647701234567` and
        // `7701234567` all resolve to the same account, so keying on the raw
        // input would hand an attacker a fresh allowance per spelling.
        $key = 'haykal-auth:login:'.$this->normalizePhone($phone).'|'.$request->ip();
        $max = (int) config('haykal-auth.login.max_attempts', 5);

        if (RateLimiter::tooManyAttempts($key, $max)) {
            event(new Lockout($request));

            return ApiResponse::tooManyRequests(
                __('haykal-api::auth.too_many_attempts', ['seconds' => RateLimiter::availableIn($key)])
            );
        }

        // A session pair has to belong to a device, or sign-out and rotation
        // have nothing to scope to. Checked before the credentials so a
        // malformed client gets told what is wrong instead of "unauthorized".
        $deviceId = $this->deviceId($request);

        if ($deviceId === null) {
            return ApiResponse::badRequest(__('haykal-api::auth.device_required'));
        }

        $user = $this->findUserByPhone($phone);

        if ($user === null || $user->password === null || ! Hash::check($request->string('password')->toString(), $user->password)) {
            RateLimiter::hit($key, (int) config('haykal-auth.login.decay_seconds', 60));
            event(new Failed($this->guard(), $user, ['phone' => $phone]));

            return ApiResponse::unauthorized(__('haykal-api::auth.invalid_credentials'));
        }

        RateLimiter::clear($key);
        event(new Login($this->guard(), $user, false));

        $pair = $tokens->pair($user, $deviceId);

        return ApiResponse::ok(
            message: __('haykal-api::auth.login_successful'),
            data: new TokenResource((object) [...$pair, 'user' => $user]),
        );
    }

    /**
     * Refresh a session
     *
     * Trades a refresh token for a fresh pair on the same device. The old pair
     * stays valid briefly so requests already in flight do not fail.
     */
    public function refresh(Request $request, IssueTokensAction $tokens): JsonResponse
    {
        $user = $request->user();
        $current = $user->currentAccessToken();

        // Check the token's name, not its abilities: access tokens carry `*`,
        // which satisfies `can('refresh')` all on its own.
        if ($current->name !== TokenType::Refresh->getName()) {
            return ApiResponse::unauthorized(__('haykal-api::auth.invalid_refresh_token'));
        }

        $pair = DB::transaction(function () use ($user, $current, $tokens) {
            // Shorten the superseded pair instead of deleting it, so requests
            // already in flight — and a duplicate parallel refresh — do not 401.
            $graceUntil = now()->addSeconds((int) config('haykal-auth.tokens.rotation_grace', 60));

            $this->tokensForCurrentDevice($user, $current)
                ->where('id', '<=', $current->getKey())
                ->where('expires_at', '>', $graceUntil)
                ->update(['expires_at' => $graceUntil]);

            return $tokens->pair($user, $current->device_id);
        });

        return ApiResponse::ok(
            message: __('haykal-api::auth.token_refreshed'),
            data: new TokenResource((object) [...$pair, 'user' => $user]),
        );
    }

    /**
     * Sign out
     *
     * Revokes this device's tokens. Other devices stay signed in.
     */
    public function revoke(Request $request): JsonResponse
    {
        $user = $request->user();

        // Scoped to the presented token's own device, so signing out works
        // whether or not the client bothered to send the device header.
        $this->tokensForCurrentDevice($user, $user->currentAccessToken())->delete();

        event(new Logout($this->guard(), $user));

        return ApiResponse::ok(__('haykal-api::auth.logout_successful'));
    }

    private function guard(): string
    {
        return (string) config('haykal-auth.guard', 'sanctum');
    }
}
