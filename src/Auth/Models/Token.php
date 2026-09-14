<?php

declare(strict_types=1);

namespace HiTaqnia\Haykal\Api\Auth\Models;

use Laravel\Sanctum\PersonalAccessToken;

/**
 * Sanctum personal access token with a device binding.
 *
 * `device_id` is the id of whatever row the application uses to represent one
 * app install or browser — this package never touches that table, it only
 * stamps and compares the id, so logout and refresh can be scoped to a single
 * device.
 */
class Token extends PersonalAccessToken
{
    /** @var list<string> */
    protected $fillable = [
        'device_id',
        'name',
        'token',
        'abilities',
        'expires_at',
    ];

    public function getTable(): string
    {
        return (string) config('haykal-auth.tokens.table', 'tokens');
    }
}
