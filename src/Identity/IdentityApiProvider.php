<?php

declare(strict_types=1);

namespace HiTaqnia\Haykal\Api\Identity;

use HiTaqnia\Haykal\Api\ApiProvider;

/**
 * Registers the Identity API module with Scramble.
 *
 * Serves the `GET /api/identity/me` endpoint under its own OpenAPI spec
 * reachable at `/docs/identity-api` (UI) and `/docs/identity-api.json`
 * (raw spec).
 *
 * Applications register this provider from their `bootstrap/providers.php`:
 *
 *     return [
 *         // ...
 *         HiTaqnia\Haykal\Api\Identity\IdentityApiProvider::class,
 *     ];
 *
 * Subclass and override the hooks if a different URL prefix, title,
 * or security scheme set is needed.
 */
class IdentityApiProvider extends ApiProvider
{
    protected function name(): string
    {
        return 'identity-api';
    }

    protected function path(): string
    {
        return 'api/identity';
    }

    protected function title(): string
    {
        return 'Identity API';
    }

    protected function description(): string
    {
        return 'Authenticated profile access for the current Huwiya user.';
    }
}
