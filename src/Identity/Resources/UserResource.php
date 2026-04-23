<?php

declare(strict_types=1);

namespace HiTaqnia\Haykal\Api\Identity\Resources;

use HiTaqnia\Haykal\Core\Identity\Models\User;
use HiTaqnia\Haykal\Core\Identity\ValueObjects\PhoneNumber;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * JSON representation of the authenticated Haykal user.
 *
 * @property string $id
 * @property string $name
 * @property ?PhoneNumber $phone
 * @property ?string $email
 * @property string $locale
 * @property string $zoneinfo
 * @property string $theme
 * @property User $resource
 */
final class UserResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            // The user's unique identifier.
            // @var string
            // @format ULID
            // @example 01FZ8Z5Y6X9J3Q0G5X1A2B3C4D
            'id' => $this->id,

            // The user's full name.
            // @example Ali Al-Saadi
            'name' => $this->name,

            // The user's phone number in the national Iraqi format.
            // @example 07701234567
            'phone' => $this->phone?->getNational(),

            // The user's email address, when available.
            // @example ali@example.com
            'email' => $this->email,

            // The user's preferred UI locale.
            // @example ar
            'locale' => $this->locale,

            // The user's preferred timezone, in IANA format.
            // @example Asia/Baghdad
            'zoneinfo' => $this->zoneinfo,

            // The user's preferred UI theme.
            // @example dark
            'theme' => $this->theme,
        ];
    }
}
