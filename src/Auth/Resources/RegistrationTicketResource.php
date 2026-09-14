<?php

declare(strict_types=1);

namespace HiTaqnia\Haykal\Api\Auth\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property-read string $registration_token
 * @property-read int $expires_in
 */
final class RegistrationTicketResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'registration_token' => $this->registration_token,
            'expires_in' => $this->expires_in,
        ];
    }
}
