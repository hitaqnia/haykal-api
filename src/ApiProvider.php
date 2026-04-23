<?php

declare(strict_types=1);

namespace HiTaqnia\Haykal\Api;

use Dedoc\Scramble\Scramble;
use Dedoc\Scramble\Support\Generator\OpenApi;
use Dedoc\Scramble\Support\Generator\SecurityRequirement;
use Dedoc\Scramble\Support\Generator\SecurityScheme;
use Illuminate\Support\ServiceProvider;
use LogicException;

/**
 * Base class for every API module in a Haykal application.
 *
 * A Haykal app typically composes several independent APIs (identity,
 * management, residents, …). Each lives under its own URL prefix, has
 * its own Scramble-generated OpenAPI document, and its own docs UI.
 *
 * Subclass this provider for every new API module, declare the three
 * identity hooks — `name`, `title`, `description` — and either a single
 * `path()` or a multi-entry `versions()` map. Optional hooks cover
 * branding (`logo`, `primaryColor`), documentation paths (`docsPath`),
 * the OpenAPI `info.version` string (`version`), and any module-specific
 * security schemes (`additionalSecuritySchemes`).
 *
 * For each declared version the provider:
 *   1. Registers the API with Scramble under a derived identifier.
 *   2. Installs the Huwiya bearer security scheme as the default
 *      requirement and appends any extra schemes.
 *   3. Exposes the docs UI and raw JSON spec routes.
 *
 * Route files are intentionally not managed here — they live under
 * `routes/api/<module>-api.php` in the application and are included
 * from `routes/api.php` as usual.
 */
abstract class ApiProvider extends ServiceProvider
{
    /**
     * Sentinel value used as the key of the single, unversioned entry
     * in the versions map. Keyed entries other than this sentinel
     * produce versioned Scramble APIs (e.g., `{name}-v1`).
     */
    public const DEFAULT_VERSION = 'default';

    /**
     * The base Scramble API identifier. For unversioned APIs this is
     * the identifier Scramble uses directly. For versioned APIs the
     * version key is appended (e.g., `identity-api-v1`). Should be
     * kebab-case and unique within the application.
     */
    abstract protected function name(): string;

    /**
     * Human-readable title shown in the Scramble UI and the OpenAPI spec.
     */
    abstract protected function title(): string;

    /**
     * Short description of the API shown in the generated OpenAPI spec.
     */
    abstract protected function description(): string;

    /**
     * URL prefix for the default (single-version) API.
     *
     * Providers that expose only one version override this; the default
     * `versions()` implementation forwards it as `['default' => path()]`.
     * Providers that override `versions()` directly do not need to
     * implement `path()`.
     */
    protected function path(): ?string
    {
        return null;
    }

    /**
     * Versions of this API to register.
     *
     * Returns a map keyed by version identifier, with each value the URL
     * prefix where that version lives. Use `ApiProvider::DEFAULT_VERSION`
     * (the string `'default'`) as the key for a single unversioned API.
     *
     * @return array<string, string>
     */
    protected function versions(): array
    {
        $path = $this->path();

        if ($path === null) {
            throw new LogicException(sprintf(
                '%s must override either path() or versions() to declare URL paths.',
                static::class,
            ));
        }

        return [self::DEFAULT_VERSION => $path];
    }

    /**
     * OpenAPI `info.version` string for the given version identifier.
     *
     * Defaults to `1.0.0` for every version. Override and branch on
     * `$versionId` to publish distinct spec versions per API version.
     */
    protected function version(string $versionId = self::DEFAULT_VERSION): string
    {
        return '1.0.0';
    }

    /**
     * Optional logo URL shown in the Scramble UI.
     */
    protected function logo(): ?string
    {
        return null;
    }

    /**
     * Optional primary brand color for the Scramble UI, in any CSS-valid form.
     */
    protected function primaryColor(): ?string
    {
        return null;
    }

    /**
     * URL path for the docs UI of the given version.
     *
     * Defaults to `docs/{apiId}` where `apiId` is the suffixed name.
     * The JSON spec is always served at `<docsPath>.json`.
     */
    protected function docsPath(string $versionId = self::DEFAULT_VERSION): string
    {
        return 'docs/'.$this->apiIdFor($versionId);
    }

    /**
     * Extra security schemes to register on this API in addition to the
     * always-present Huwiya bearer scheme.
     *
     * Typical additions are tenant or profile header schemes. Override
     * and return an associative array keyed by scheme name:
     *
     *     return [
     *         'complex' => SecurityScheme::apiKey('header', 'X-Complex-Id'),
     *         'profile' => SecurityScheme::apiKey('header', 'X-Profile-Id'),
     *     ];
     *
     * @return array<string, SecurityScheme>
     */
    protected function additionalSecuritySchemes(): array
    {
        return [];
    }

    final public function boot(): void
    {
        foreach ($this->versions() as $versionId => $path) {
            $this->registerVersion((string) $versionId, $path);
        }
    }

    private function registerVersion(string $versionId, string $path): void
    {
        $apiId = $this->apiIdFor($versionId);

        Scramble::registerApi($apiId, [
            'api_path' => $path,
            'info' => [
                'version' => $this->version($versionId),
                'description' => $this->description(),
            ],
            'ui' => $this->buildUiConfig(),
        ])->withDocumentTransformers(function (OpenApi $openApi): void {
            $openApi->components->securitySchemes['bearer'] = SecurityScheme::http('bearer', 'JWT')
                ->setDescription(
                    'Huwiya-issued JWT bearer token. Obtain via the Huwiya OAuth2 authorization flow '.
                    'and send in the `Authorization` header as `Bearer <token>`.',
                );

            foreach ($this->additionalSecuritySchemes() as $schemeName => $scheme) {
                $openApi->components->securitySchemes[$schemeName] = $scheme;
            }

            $openApi->security[] = new SecurityRequirement(['bearer' => []]);
        });

        $docsPath = $this->docsPath($versionId);

        Scramble::registerUiRoute(path: $docsPath, api: $apiId);
        Scramble::registerJsonSpecificationRoute(path: $docsPath.'.json', api: $apiId);
    }

    /**
     * Compose the Scramble API identifier for a given version.
     */
    private function apiIdFor(string $versionId): string
    {
        return $versionId === self::DEFAULT_VERSION
            ? $this->name()
            : $this->name().'-'.$versionId;
    }

    /**
     * @return array<string, string>
     */
    private function buildUiConfig(): array
    {
        $ui = ['title' => $this->title()];

        if ($this->logo() !== null) {
            $ui['logo'] = $this->logo();
        }

        if ($this->primaryColor() !== null) {
            $ui['primary'] = $this->primaryColor();
        }

        return $ui;
    }
}
