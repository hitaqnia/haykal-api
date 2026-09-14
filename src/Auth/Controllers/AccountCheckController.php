<?php

declare(strict_types=1);

namespace HiTaqnia\Haykal\Api\Auth\Controllers;

use HiTaqnia\Haykal\Api\Auth\AuthErrors;
use HiTaqnia\Haykal\Api\Auth\Concerns\ResolvesAuthUser;
use HiTaqnia\Haykal\Api\Auth\Requests\CheckAccountRequest;
use HiTaqnia\Haykal\Api\Response\ApiResponse;
use Illuminate\Http\JsonResponse;

/**
 * Tell a client whether a phone number can be signed in with a password.
 *
 * This is an enumeration oracle by design — it exists so the app can show the
 * right screen — so keep a tight per-IP throttle on the route.
 */
final class AccountCheckController
{
    use ResolvesAuthUser;

    /**
     * Check account
     *
     * Whether this phone number has an account that can be signed in to. Lets
     * a client show the sign-in or the sign-up screen without guessing.
     */
    public function __invoke(CheckAccountRequest $request): JsonResponse
    {
        $user = $this->findUserByPhone($request->string('phone')->toString());

        if ($user === null) {
            return ApiResponse::notFound();
        }

        if ($user->password === null) {
            return ApiResponse::businessError(AuthErrors::doesNotHavePassword());
        }

        return ApiResponse::ok();
    }
}
