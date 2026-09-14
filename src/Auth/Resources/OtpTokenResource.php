<?php

declare(strict_types=1);

namespace HiTaqnia\Haykal\Api\Auth\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property-read string $token
 */
final class OtpTokenResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return ['token' => $this->token];
    }
}
