<?php

declare(strict_types=1);

namespace HiTaqnia\Haykal\Api\Auth\Controllers;

use HiTaqnia\Haykal\Api\Auth\Concerns\InteractsWithDeviceTokens;
use HiTaqnia\Haykal\Api\Auth\Enums\OtpPurpose;
use HiTaqnia\Haykal\Api\Auth\Enums\TokenType;
use HiTaqnia\Haykal\Api\Auth\Requests\Password\ResetPasswordRequest;
use HiTaqnia\Haykal\Api\Auth\Requests\Password\UpdatePasswordRequest;
use HiTaqnia\Haykal\Api\Response\ApiResponse;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;

/**
 * Changing a password with the current one, and resetting it with an OTP token.
 */
final class PasswordController
{
    use InteractsWithDeviceTokens;

    /**
     * Change the password of a signed-in user.
     */
    public function update(UpdatePasswordRequest $request): JsonResponse
    {
        $user = $request->user();
        $current = $user->currentAccessToken();

        if (! Hash::check($request->string('password')->toString(), $user->password)) {
            return ApiResponse::unauthorized(__('haykal-api::auth.current_password_incorrect'));
        }

        $user->update(['password' => Hash::make($request->string('new_password')->toString())]);

        // Sign every other device out, and drop any device-less OTP tokens,
        // but leave the pair this request came in on alone.
        $user->tokens()
            ->where(fn ($query) => $query
                ->where('device_id', '!=', $current->device_id)
                ->orWhereNull('device_id'))
            ->delete();

        return ApiResponse::ok(__('haykal-api::auth.password_updated_successfully'));
    }

    /**
     * Set a new password with the token minted by verifying a reset OTP.
     */
    public function reset(ResetPasswordRequest $request): JsonResponse
    {
        $user = $request->user();
        $current = $user->currentAccessToken();
        $ability = TokenType::getOtpAbilityByPurpose(OtpPurpose::ResetPassword);

        // The name check is what makes this safe: an access token holds `*`,
        // so the ability check alone would let it reset the password without
        // presenting the current one.
        if ($current->name !== TokenType::Otp->getName() || ! $current->can($ability)) {
            return ApiResponse::unauthorized(__('haykal-api::auth.invalid_reset_token'));
        }

        $user->update(['password' => Hash::make($request->string('password')->toString())]);
        $user->tokens()->delete();

        event(new PasswordReset($user));

        return ApiResponse::ok(__('haykal-api::auth.password_reset_successful'));
    }
}
