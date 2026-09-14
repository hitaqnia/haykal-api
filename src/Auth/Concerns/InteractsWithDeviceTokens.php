<?php

declare(strict_types=1);

namespace HiTaqnia\Haykal\Api\Auth\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;

/**
 * Reads the caller's device id and scopes token queries to it.
 *
 * The device id is whatever the application already sends on every request
 * (`X-Device-Id` by default); this package only stamps and compares it.
 */
trait InteractsWithDeviceTokens
{
    protected function deviceId(Request $request): ?string
    {
        $header = (string) config('haykal-auth.device.header', 'X-Device-Id');
        $value = $request->header($header);

        return is_string($value) && $value !== '' ? $value : null;
    }

    protected function tokensForDevice(Model $user, ?string $deviceId): Relation
    {
        return $user->tokens()->when(
            $deviceId === null,
            fn ($query) => $query->whereNull('device_id'),
            fn ($query) => $query->where('device_id', $deviceId),
        );
    }
}
