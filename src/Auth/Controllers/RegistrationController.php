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
        $phone = $this->normalizePhone($request->string('phone')->toString());
        $ticketKey = 'haykal-auth:registration:'.$request->string('registration_token')->toString();
        $store = Cache::store(config('haykal-auth.otp.store'));

        if ($store->get($ticketKey) !== $phone) {
            return ApiResponse::businessError(AuthErrors::invalidRegistrationToken());
        }

        if ($this->findUserByPhone($phone) !== null) {
            $store->forget($ticketKey);

            return ApiResponse::businessError(AuthErrors::phoneAlreadyRegistered());
        }

        $user = $createUser->execute(
            phone: $phone,
            name: $request->string('name')->toString(),
            password: $request->string('password')->toString(),
        );

        $store->forget($ticketKey);

        event(new Registered($user));

        $pair = $tokens->pair($user, $this->deviceId($request));

        return ApiResponse::created(
            message: __('haykal-api::auth.registration_successful'),
            data: new TokenResource((object) [...$pair, 'user' => $user]),
        );
    }
}
