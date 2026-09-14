<?php

declare(strict_types=1);

namespace HiTaqnia\Haykal\Api\Auth\Controllers;

use HiTaqnia\Haykal\Api\Auth\Actions\Token\IssueTokensAction;
use HiTaqnia\Haykal\Api\Auth\Actions\User\CreateUserWithPasswordAction;
use HiTaqnia\Haykal\Api\Auth\AuthErrors;
use HiTaqnia\Haykal\Api\Auth\Concerns\InteractsWithDeviceTokens;
use HiTaqnia\Haykal\Api\Auth\Concerns\ResolvesAuthUser;
use HiTaqnia\Haykal\Api\Auth\Requests\Registration\RegisterRequest;
use HiTaqnia\Haykal\Api\Auth\Resources\TokenResource;
use HiTaqnia\Haykal\Api\Response\ApiResponse;
use Illuminate\Auth\Events\Registered;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

/**
 * Finish a signup that already proved possession of the phone number.
 *
 * The ticket is what carries that proof between `otp/verify` and here — it is
 * bound to the phone it was minted for and is single use.
 */
final class RegistrationController
{
    use InteractsWithDeviceTokens;
    use ResolvesAuthUser;

    /**
     * Register
     *
     * Creates the account from a phone number already proved by a
     * registration ticket, and signs it in.
     */
    public function __invoke(
        RegisterRequest $request,
        CreateUserWithPasswordAction $createUser,
        IssueTokensAction $tokens,
    ): JsonResponse {
        $deviceId = $this->deviceId($request);

        if ($deviceId === null) {
            return ApiResponse::badRequest(__('haykal-api::auth.device_required'));
        }

        $phone = $this->normalizePhone($request->string('phone')->toString());
        $ticketKey = 'haykal-auth:registration:'.$request->string('registration_token')->toString();
        $store = Cache::store(config('haykal-auth.otp.store'));

        // Read, not taken: a mismatched phone must not burn a ticket that is
        // still good. The insert below is what enforces single use.
        if ($store->get($ticketKey) !== $phone) {
            return ApiResponse::businessError(AuthErrors::invalidRegistrationToken());
        }

        if ($this->phoneIsTaken($phone)) {
            return ApiResponse::businessError(AuthErrors::phoneAlreadyRegistered());
        }

        try {
            $user = $createUser->execute(
                phone: $phone,
                name: $request->string('name')->toString(),
                password: $request->string('password')->toString(),
            );
        } catch (UniqueConstraintViolationException) {
            // Two requests can clear the check above at the same time — a
            // double submit, or a race with any other writer. The unique index
            // is the real arbiter, so let it decide rather than 500.
            $store->forget($ticketKey);

            return ApiResponse::businessError(AuthErrors::phoneAlreadyRegistered());
        }

        // Consumed only once it has actually produced an account.
        $store->forget($ticketKey);

        event(new Registered($user));

        $pair = $tokens->pair($user, $deviceId);

        return ApiResponse::created(
            message: __('haykal-api::auth.registration_successful'),
            data: new TokenResource((object) [...$pair, 'user' => $user]),
        );
    }
}
