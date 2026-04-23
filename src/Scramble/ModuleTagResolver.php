<?php

declare(strict_types=1);

namespace HiTaqnia\Haykal\Api\Scramble;

use Dedoc\Scramble\Support\Generator\Operation;
use Dedoc\Scramble\Support\RouteInfo;
use Illuminate\Support\Str;

/**
 * Derive Scramble operation tags from the Haykal API module namespace.
 *
 * Controllers living under `App\Apis\<Module>\Controllers\...` are tagged
 * with the module name so the Scramble docs UI groups them together
 * automatically — no per-controller `@tags` annotation required.
 *
 * Apps that use a different directory layout can register their own
 * resolver via `Scramble::resolveTagsUsing()` to replace this one.
 */
final class ModuleTagResolver
{
    /**
     * @return string[]
     */
    public function __invoke(RouteInfo $routeInfo, Operation $operation): array
    {
        $className = $routeInfo->className();

        if ($className === null) {
            return [];
        }

        if (preg_match('#\\\\Apis\\\\([^\\\\]+)\\\\Controllers\\\\#', $className, $match) === 1) {
            return [Str::headline($match[1])];
        }

        return [];
    }
}
