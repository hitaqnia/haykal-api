<?php

declare(strict_types=1);

namespace HiTaqnia\Haykal\Api\Auth\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;

/**
 * Device scoping for token queries.
 *
 * The header is read only where a token is being minted — at that point there
 * is nothing else to learn the device from. Everywhere else the presented
 * token already carries the device it was issued to, and that is what gets
 * used: it cannot be spoofed by editing a header, and it cannot go missing
 * because the client forgot to send one.
 */
trait InteractsWithDeviceTokens
{
    /**
     * The device a token is being minted for, from the request header.
     */
    protected function deviceId(Request $request): ?string
    {
        $header = (string) config('haykal-auth.device.header', 'X-Device-Id');
        $value = $request->header($header);

        return is_string($value) && $value !== '' ? $value : null;
    }

    /**
     * Every token issued to the same device as the one presented.
     *
     * A device-less token — an OTP token, or a pair minted by a client that
     * sent no header — belongs to no device, so it scopes to itself alone.
     * Matching all device-less tokens instead would let one such token revoke
     * another's, and would make a logout that misses its own pair look like
     * it succeeded.
     */
    protected function tokensForCurrentDevice(Model $user, mixed $current): Relation
    {
        $deviceId = $current->device_id;

        return $user->tokens()->when(
            $deviceId === null,
            fn ($query) => $query->whereKey($current->getKey()),
            fn ($query) => $query->where('device_id', $deviceId),
        );
    }

    /**
     * Every token NOT issued to the presented token's device — the ones a
     * password change should evict.
     */
    protected function tokensForOtherDevices(Model $user, mixed $current): Relation
    {
        $deviceId = $current->device_id;

        return $user->tokens()->when(
            $deviceId === null,
            fn ($query) => $query->whereKeyNot($current->getKey()),
            // `device_id != x` never matches NULL rows, so the device-less
            // tokens have to be named explicitly or OTP tokens would survive.
            fn ($query) => $query->where(fn ($inner) => $inner
                ->whereNull('device_id')
                ->orWhere('device_id', '!=', $deviceId)),
        );
    }
}
