<?php

declare(strict_types=1);

namespace HiTaqnia\Haykal\Api\Auth\Controllers;

use HiTaqnia\Haykal\Api\Auth\Actions\Otp\GenerateOtpAction;
use HiTaqnia\Haykal\Api\Auth\Actions\Otp\VerifyOtpAction;
use HiTaqnia\Haykal\Api\Auth\Actions\Token\IssueTokensAction;
use HiTaqnia\Haykal\Api\Auth\AuthErrors;
use HiTaqnia\Haykal\Api\Auth\Concerns\ResolvesAuthUser;
use HiTaqnia\Haykal\Api\Auth\Enums\OtpPurpose;
use HiTaqnia\Haykal\Api\Auth\Requests\Otp\RequestOtpRequest;
use HiTaqnia\Haykal\Api\Auth\Requests\Otp\VerifyOtpRequest;
use HiTaqnia\Haykal\Api\Auth\Resources\OtpResource;
use HiTaqnia\Haykal\Api\Auth\Resources\OtpTokenResource;
use HiTaqnia\Haykal\Api\Auth\Resources\RegistrationTicketResource;
use HiTaqnia\Haykal\Api\Response\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

/**
 * Requesting and verifying one-time codes.
 *
 * Verifying a `ResetPassword` code hands back a single-ability token; a
 * `Register` code hands back a registration ticket, because there is no
 * account to hang a token on yet.
 */
final class OtpController
{
    use ResolvesAuthUser;

    /**
     * Request a code
     *
     * Sends a one-time code to the phone number for the given purpose. Codes
     * are throttled per number and purpose, and requesting again replaces the
     * pending one.
     */
    public function request(RequestOtpRequest $request, GenerateOtpAction $generate): JsonResponse
    {
        $phone = $this->normalizePhone($request->string('phone')->toString());
        $purpose = $request->purpose();
        $user = $this->findUserByPhone($phone);

        if ($purpose === OtpPurpose::Register && $user !== null) {
            return ApiResponse::businessError(AuthErrors::phoneAlreadyRegistered());
        }

        if ($purpose !== OtpPurpose::Register && $user === null) {
            return ApiResponse::notFound();
        }

        $key = "haykal-auth:otp-generate:{$phone}:{$purpose->value}";
        $max = (int) config('haykal-auth.otp.max_generate_attempts_per_hour', 3);

        if ($this->throttled($key, $max)) {
            return ApiResponse::tooManyRequests(
                __('haykal-api::auth.otp_rate_limit', ['seconds' => RateLimiter::availableIn($key)])
            );
        }

        RateLimiter::hit($key, 3600);

        $result = $generate->execute($phone, $purpose);

        if ($result->isFailure()) {
            RateLimiter::clear($key);

            return ApiResponse::businessError($result->getError());
        }

        return ApiResponse::ok(
            message: __('haykal-api::auth.otp_sent'),
            data: new OtpResource((object) $result->getData()),
        );
    }

    /**
     * Verify a code
     *
     * Burns the code and returns proof of it: a single-purpose token for a
     * password reset, or a registration ticket to finish signing up with.
     */
    public function verify(VerifyOtpRequest $request, VerifyOtpAction $verify, IssueTokensAction $tokens): JsonResponse
    {
        $phone = $this->normalizePhone($request->string('phone')->toString());
        $purpose = $request->purpose();

        $key = "haykal-auth:otp-verify:{$phone}:{$purpose->value}";
        $max = (int) config('haykal-auth.otp.max_verify_attempts_per_hour', 5);

        if ($this->throttled($key, $max)) {
            return ApiResponse::tooManyRequests(
                __('haykal-api::auth.otp_verify_rate_limit', ['seconds' => RateLimiter::availableIn($key)])
            );
        }

        $result = $verify->execute($phone, $request->string('otp')->toString(), $purpose);

        if ($result->isFailure()) {
            $error = $result->getError();

            // An expired code costs more than a wrong digit: the user has to
            // request a new one anyway, so there is nothing to retry quickly.
            RateLimiter::hit($key, $error == AuthErrors::otpExpired() ? 300 : 60);

            return ApiResponse::businessError($error);
        }

        RateLimiter::clear($key);

        if ($purpose === OtpPurpose::Register) {
            return ApiResponse::ok(data: $this->registrationTicket($phone));
        }

        $user = $this->findUserByPhone($phone);

        if ($user === null) {
            return ApiResponse::notFound();
        }

        return ApiResponse::ok(data: new OtpTokenResource(
            (object) ['token' => $tokens->otp($user, $purpose)->plainTextToken]
        ));
    }

    private function registrationTicket(string $phone): RegistrationTicketResource
    {
        $token = Str::random(40);
        $expiresIn = (int) config('haykal-auth.registration.ticket_lifetime', 1800);

        Cache::store(config('haykal-auth.otp.store'))
            ->put("haykal-auth:registration:{$token}", $phone, $expiresIn);

        return new RegistrationTicketResource((object) [
            'registration_token' => $token,
            'expires_in' => $expiresIn,
        ]);
    }

    private function throttled(string $key, int $max): bool
    {
        return RateLimiter::tooManyAttempts($key, $max) && ! config('haykal-auth.otp.fake');
    }
}
