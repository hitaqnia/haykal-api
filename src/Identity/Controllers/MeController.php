<?php

declare(strict_types=1);

namespace HiTaqnia\Haykal\Api\Identity\Controllers;

use HiTaqnia\Haykal\Api\Identity\Resources\UserResource;
use HiTaqnia\Haykal\Api\Response\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

final class MeController
{
    /**
     * Me
     *
     * Return the currently authenticated Huwiya user's profile.
     */
    public function __invoke(): JsonResponse
    {
        return ApiResponse::ok(
            message: 'Profile retrieved successfully.',
            data: new UserResource(Auth::user()),
        );
    }
}
