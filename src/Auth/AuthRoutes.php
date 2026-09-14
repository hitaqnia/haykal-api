<?php

declare(strict_types=1);

namespace HiTaqnia\Haykal\Api\Auth;

use HiTaqnia\Haykal\Api\Auth\Controllers\AccountCheckController;
use HiTaqnia\Haykal\Api\Auth\Controllers\OtpController;
use HiTaqnia\Haykal\Api\Auth\Controllers\PasswordController;
use HiTaqnia\Haykal\Api\Auth\Controllers\RegistrationController;
use HiTaqnia\Haykal\Api\Auth\Controllers\TokenController;
use HiTaqnia\Haykal\Api\Auth\Http\Middlewares\EnsureSessionToken;
use Illuminate\Routing\Route as RouteInstance;
use Illuminate\Support\Facades\Route;

/**
 * The auth endpoints, registered inside whatever group the application wraps
 * them in — it owns the prefix, the version, and the outer middleware:
 *
 *     Route::prefix('identity')
 *         ->middleware([SetLocaleFromHeaderMiddleware::class])
 *         ->group(fn () => AuthRoutes::register());
 *
 * The individual `register*` methods exist so an application can take only
 * part of the set, or throttle the parts differently — `accountCheck` in
 * particular answers whether a phone number has an account, so it usually
 * wants a tighter limit than sign-in does.
 */
final class AuthRoutes
{
    public static function register(): void
    {
        self::registerPublic();
        self::registerProtected();
    }

    public static function registerPublic(): void
    {
        self::registerAccountCheck();
        self::registerOtp();
        self::registerRegistration();
        self::registerSignIn();
    }

    /**
     * Returns the route so the application can pin its own throttle on it.
     */
    public static function registerAccountCheck(): RouteInstance
    {
        return Route::post('check', AccountCheckController::class);
    }

    public static function registerOtp(): void
    {
        Route::post('otp/request', [OtpController::class, 'request']);
        Route::post('otp/verify', [OtpController::class, 'verify']);
    }

    public static function registerRegistration(): RouteInstance
    {
        return Route::post('register', RegistrationController::class);
    }

    public static function registerSignIn(): RouteInstance
    {
        return Route::post('token', [TokenController::class, 'create']);
    }

    public static function registerProtected(): void
    {
        $guard = 'auth:'.config('haykal-auth.guard', 'sanctum');

        // Password reset sits outside the session-token group on purpose: it is
        // the one endpoint the single-purpose OTP token is allowed to reach.
        Route::middleware([$guard])->post('password/reset', [PasswordController::class, 'reset']);

        Route::middleware([$guard, EnsureSessionToken::class])->group(function (): void {
            Route::post('token/refresh', [TokenController::class, 'refresh']);
            Route::post('token/revoke', [TokenController::class, 'revoke']);
            Route::put('password/update', [PasswordController::class, 'update']);
        });
    }
}
