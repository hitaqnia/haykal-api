<?php

declare(strict_types=1);

namespace HiTaqnia\Haykal\Api\Http\Middlewares;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Set the application locale from a request header.
 *
 * Reads `Accept-Language` (or a custom header passed to the constructor)
 * and calls `app()->setLocale()` so translations, date formatting, and
 * validator messages honor the client's preference for the duration of
 * the request. No-op when the header is missing, malformed, or — when a
 * supported list is provided — not in that list.
 *
 * Not registered globally. Slot it into the middleware groups or routes
 * that should respect the header.
 */
final class SetLocaleFromHeaderMiddleware
{
    /**
     * @param  list<string>  $supported  Optional allow-list of locales.
     *                                   Empty list means accept any value.
     */
    public function __construct(
        private readonly array $supported = [],
        private readonly string $header = 'Accept-Language',
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $locale = $this->resolveLocale($request);

        if ($locale !== null) {
            app()->setLocale($locale);
        }

        return $next($request);
    }

    private function resolveLocale(Request $request): ?string
    {
        $value = $request->header($this->header);

        if (! is_string($value) || $value === '') {
            return null;
        }

        $locale = trim(explode(',', $value)[0]);

        if ($locale === '') {
            return null;
        }

        if ($this->supported !== [] && ! in_array($locale, $this->supported, true)) {
            return null;
        }

        return $locale;
    }
}
