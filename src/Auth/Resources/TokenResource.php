<?php

declare(strict_types=1);

namespace HiTaqnia\Haykal\Api\Auth\Resources;

use HiTaqnia\Haykal\Api\Auth\Enums\TokenType;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Laravel\Sanctum\NewAccessToken;

/**
 * The token pair handed back by login, registration and refresh.
 *
 * The `user` key only appears when `haykal-auth.user_resource` names a
 * resource class — the shape of a user is the application's business.
 *
 * @property-read NewAccessToken $access
 * @property-read NewAccessToken $refresh
 */
final class TokenResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $payload = [
            'access_token' => $this->access->plainTextToken,
            'refresh_token' => $this->refresh->plainTextToken,
            'expires_in' => TokenType::Access->getLifetime(),
        ];

        $userResource = config('haykal-auth.user_resource');

        if ($userResource !== null && isset($this->user)) {
            $payload['user'] = new $userResource($this->user);
        }

        return $payload;
    }
}
