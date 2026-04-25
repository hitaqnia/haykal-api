# hitaqnia/haykal-api

API support layer for HiTaqnia Laravel applications.

`haykal-api` is a **utility package**. It gives every HiTaqnia API project the same response shape, the same error translation, and the same Scramble plumbing — and stops there. It does **not** ship any endpoints, controllers, route files, or concrete API providers. Those are owned by the consuming application.

Three things:

1. **A response envelope.** `ApiResponse` factories and `ApiExceptionHandler` so every endpoint returns a consistent JSON shape, on success and on failure.
2. **Scramble integrations.** Exception-to-response extensions plus a module tag resolver so the generated OpenAPI spec matches the envelope and groups endpoints by application module automatically.
3. **A provider-based API composition pattern.** An abstract `ApiProvider` you subclass per API module to register it with Scramble, declare its security schemes, and expose its docs UI. Applications compose as many providers as they need.

Controllers, Form Requests, and Resources are written per-project. Laravel already supplies the right primitives; `haykal-api` does not ship base classes for them.

---

## Table of contents

- [Requirements](#requirements)
- [What this package provides](#what-this-package-provides)
- [Installation](#installation)
- [Defining APIs](#defining-apis)
    - [Anatomy of an API provider](#anatomy-of-an-api-provider)
    - [What the provider wires up](#what-the-provider-wires-up)
    - [Route files](#route-files)
    - [Versioning](#versioning)
    - [Automatic tag resolution](#automatic-tag-resolution)
- [Usage](#usage)
    - [Success responses](#success-responses)
    - [Paginated responses](#paginated-responses)
    - [Error responses](#error-responses)
    - [Business errors](#business-errors)
    - [Locale from request header](#locale-from-request-header)
- [Conventions](#conventions)
    - [Controller docblocks](#controller-docblocks)
    - [Controllers](#controllers)
    - [Form Requests](#form-requests)
    - [Resources](#resources)
    - [Module layout](#module-layout)
- [Customization](#customization)
- [Testing](#testing)

---

## Requirements

- PHP 8.3 or later
- Laravel 13 or later
- `hitaqnia/haykal-core` (shared kernel — pulled transitively)
- `dedoc/scramble` (pulled transitively)

---

## What this package provides

### Response layer

| Class | Purpose |
|---|---|
| `HiTaqnia\Haykal\Api\Response\ApiResponse` | Static factory for the Haykal JSON envelope: `ok()`, `created()`, `accepted()`, `paginated()`, `noContent()`, plus the full 4xx and 5xx set. `businessError(Error)` surfaces a domain-level `ResultPattern\Error` to the client. |
| `HiTaqnia\Haykal\Api\Response\PaginatedResource` | Wraps a `LengthAwarePaginator` into `{ items, pagination }`. Consumed by `ApiResponse::paginated()`. |
| `HiTaqnia\Haykal\Api\Response\ApiExceptionHandler` | Translates common framework exceptions (validation, 404, authentication, authorization, throttle) into the envelope for any request matching `api/*`. |
| `HiTaqnia\Haykal\Api\Response\Concerns\InteractsWithResponseMaker` | Trait used by `ApiResponse` to centralize envelope construction. |

### Scramble integrations

| Class | Purpose |
|---|---|
| `HiTaqnia\Haykal\Api\Scramble\ValidationExceptionExtension` | Documents 422 responses in the Haykal envelope shape. |
| `HiTaqnia\Haykal\Api\Scramble\NotFoundExceptionExtension` | Documents 404 responses for both Eloquent and route-level not-found exceptions. |
| `HiTaqnia\Haykal\Api\Scramble\ModuleTagResolver` | Derives OpenAPI operation tags from the `App\Apis\<Module>\Controllers\*` namespace so endpoints group by module in the docs UI automatically. |
| `HiTaqnia\Haykal\Api\Scramble\EnvelopeResponseSchema` | Helper builder used by the exception extensions so the envelope shape stays consistent. |

### API composition

| Class | Purpose |
|---|---|
| `HiTaqnia\Haykal\Api\ApiProvider` | Abstract base service provider for API modules. Registers the module with Scramble, installs the Huwiya bearer security scheme, and exposes the docs UI. Subclass this for every API you ship. |

### Middleware

| Class | Purpose |
|---|---|
| `HiTaqnia\Haykal\Api\Http\Middlewares\SetLocaleFromHeaderMiddleware` | Sets `app()->setLocale()` from an inbound request header (`Accept-Language` by default). Not registered globally — slot it into the route groups that should respect the header. |

No concrete providers, controllers, resources, or routes are shipped. Those belong in the consuming application.

---

## Installation

`haykal-api` is pulled in transitively by the `hitaqnia/haykal` metapackage. To consume it directly:

```bash
composer require hitaqnia/haykal-api
```

Auto-discovered via `HaykalApiServiceProvider`, which:

- Registers `ValidationExceptionExtension` and `NotFoundExceptionExtension` with Scramble.
- Installs `ModuleTagResolver` as the default tag resolver.
- Registers `ApiExceptionHandler::handle` as a `renderable` callback on Laravel's exception handler so every `api/*` route surfaces validation, 404, auth, and throttle failures through the Haykal envelope. Non-API requests fall through to Laravel's defaults.

No configuration files to publish, no routes to include, no providers to register by default. Per-API metadata (security schemes, titles, docs UI) lives in `ApiProvider` subclasses you write in the application.

---

## Defining APIs

Every API module in a Haykal application is declared by a subclass of `HiTaqnia\Haykal\Api\ApiProvider`. The provider owns the module's Scramble registration, security schemes, and docs UI — applications compose as many providers as they need, one per API module.

### Anatomy of an API provider

Subclass `ApiProvider` and declare the four required identity hooks. Everything else is optional.

```php
namespace App\Providers\Apis;

use Dedoc\Scramble\Support\Generator\SecurityScheme;
use HiTaqnia\Haykal\Api\ApiProvider;

final class PropertiesApiProvider extends ApiProvider
{
    protected function name(): string
    {
        return 'properties-api';
    }

    protected function path(): string
    {
        return 'api/properties';
    }

    protected function title(): string
    {
        return 'Properties API';
    }

    protected function description(): string
    {
        return 'Property management endpoints for the admin dashboard.';
    }

    // Optional hooks below.

    protected function version(): string
    {
        return '1.2.0';
    }

    protected function logo(): ?string
    {
        return asset('logo.png');
    }

    protected function primaryColor(): ?string
    {
        return '#4432d2';
    }

    /**
     * Security schemes to register in addition to the Huwiya bearer scheme
     * (which is always installed). Typical additions are header-based
     * tenant or profile selectors.
     *
     * @return array<string, SecurityScheme>
     */
    protected function additionalSecuritySchemes(): array
    {
        return [
            'complex' => SecurityScheme::apiKey('header', 'X-Complex-Id'),
        ];
    }
}
```

Register the provider in `bootstrap/providers.php`:

```php
return [
    App\Providers\AppServiceProvider::class,

    App\Providers\Apis\PropertiesApiProvider::class,
];
```

### What the provider wires up

Registering the provider gives the application, automatically:

| Behavior | Derived from |
|---|---|
| Scramble discovers every route matching `api_path` and groups them under the module's OpenAPI spec. | `path()` |
| The spec's `info.version` and `info.description` are populated. | `version()`, `description()` |
| The Scramble docs UI is served at `docs/<name>` (default) with the JSON spec at `docs/<name>.json`. | `docsPath()` — override to move it. |
| The docs UI is titled and optionally branded with a logo and primary color. | `title()`, `logo()`, `primaryColor()` |
| The `bearer` security scheme for Huwiya JWTs is added to the spec as the default requirement for every operation. | Always applied. |
| Any additional schemes declared by the provider are merged into the spec. | `additionalSecuritySchemes()` |

### Route files

Route files remain conventional Laravel — the provider does not manage routing. Create `routes/api/properties-api.php` and include it from `routes/api.php`:

```php
// routes/api.php
require __DIR__.'/api/properties-api.php';
```

```php
// routes/api/properties-api.php
use App\Apis\Properties\Controllers\CreatePropertyController;
use App\Apis\Properties\Controllers\ListPropertiesController;
use Illuminate\Support\Facades\Route;

Route::prefix('properties')
    ->middleware(['auth:huwiya-api'])
    ->group(function () {
        Route::get('/', ListPropertiesController::class);
        Route::post('/', CreatePropertyController::class);
    });
```

Scramble matches the `api_path` declared on the provider (`api/properties`) against the routes defined here and groups them under the `properties-api` spec.

### Versioning

Many API modules evolve across breaking versions that must run in parallel for migration periods. `ApiProvider` supports this natively: override `versions()` instead of `path()` and return a map of version identifier to URL prefix.

```php
final class PropertiesApiProvider extends ApiProvider
{
    protected function name(): string        { return 'properties-api'; }
    protected function title(): string       { return 'Properties API'; }
    protected function description(): string { return 'Property management endpoints.'; }

    protected function versions(): array
    {
        return [
            'v1' => 'api/v1/properties',
            'v2' => 'api/v2/properties',
        ];
    }

    protected function version(string $versionId = self::DEFAULT_VERSION): string
    {
        return match ($versionId) {
            'v1' => '1.4.0',
            'v2' => '2.0.0',
            default => '1.0.0',
        };
    }
}
```

Every entry in the map produces an independent Scramble registration:

| Version key | Scramble API id | Docs UI | JSON spec |
|---|---|---|---|
| `v1` | `properties-api-v1` | `docs/properties-api-v1` | `docs/properties-api-v1.json` |
| `v2` | `properties-api-v2` | `docs/properties-api-v2` | `docs/properties-api-v2.json` |

The shared metadata — title, description, logo, primary color, additional security schemes — applies uniformly to every version. Override `version($versionId)` (as shown above) to publish distinct `info.version` strings per API version.

For single-version APIs, keep using `path()` and leave `versions()` alone. The default implementation forwards `path()` as a single `default` entry, producing an unsuffixed Scramble id (`properties-api`, `docs/properties-api`).

Route files sit under version-specific subdirectories for clarity:

```
routes/api/properties-api/v1.php
routes/api/properties-api/v2.php
```

Each is included from `routes/api.php` alongside the corresponding version prefix.

### Automatic tag resolution

`HaykalApiServiceProvider` installs `ModuleTagResolver` globally. Any controller living under `App\Apis\<Module>\Controllers\*` is automatically tagged `<Module>` in the generated OpenAPI spec, so the Scramble docs UI groups every endpoint in a module together without per-controller `@tags` annotations. Pascal-case module names are humanized — `PropertyManagement/Controllers/*` is tagged `Property Management`.

Override the derived tag on individual operations by adding an explicit `@tags` entry to the controller's docblock (see [Controller docblocks](#controller-docblocks)). Applications with a different directory layout can swap the resolver by calling `Scramble::resolveTagsUsing(...)` in their own service provider's `boot()` after ours.

---

## Usage

### Success responses

```php
use HiTaqnia\Haykal\Api\Response\ApiResponse;

return ApiResponse::ok(message: 'Profile retrieved.', data: new UserResource($user));
return ApiResponse::created(message: 'Reservation booked.', data: $reservation);
return ApiResponse::noContent();
```

### Paginated responses

```php
return ApiResponse::paginated(
    message: 'Units retrieved.',
    data: Unit::query()->paginate($request->integer('per_page', 15)),
);
```

The response body payload becomes:

```json
{
    "success": 1,
    "code": 200,
    "message": "Units retrieved.",
    "data": {
        "items": [ ... ],
        "pagination": { "page": 1, "per_page": 15, "total": 42 }
    },
    "errors": null
}
```

### Error responses

```php
return ApiResponse::notFound();
return ApiResponse::forbidden('You may not access this complex.');
return ApiResponse::validationError(errors: $validator->errors());
```

### Business errors

`ApiResponse::businessError()` accepts any `HiTaqnia\Haykal\Core\ResultPattern\Error` and emits it through the envelope. Codes above 999 are surfaced as `code` in the envelope while the HTTP status is mapped to 409 Conflict so the transport layer stays HTTP-valid.

```php
use HiTaqnia\Haykal\Core\ResultPattern\Error;

return ApiResponse::businessError(
    Error::make(code: 4001, message: 'Booking overlaps an existing reservation.'),
);
```

### Locale from request header

Apply `SetLocaleFromHeaderMiddleware` to the route groups that should honor the client's locale. Reads `Accept-Language` by default; pass an allow-list to reject unsupported values, or a custom header name when `Accept-Language` is reserved for content negotiation.

```php
use HiTaqnia\Haykal\Api\Http\Middlewares\SetLocaleFromHeaderMiddleware;

// bootstrap/app.php
$middleware->appendToGroup('api', [
    new SetLocaleFromHeaderMiddleware(supported: ['en', 'ar']),
]);

// or a custom header:
new SetLocaleFromHeaderMiddleware(supported: ['en', 'ar'], header: 'X-Locale');
```

The middleware is not registered globally and ships no alias — instantiate it where you need it.

---

## Conventions

Laravel already provides first-class abstractions for Controllers, Form Requests, and Resources. `haykal-api` does not ship base classes for them; the conventions below are the ones every HiTaqnia project follows.

### Controller docblocks

Scramble reads PHPDoc annotations to enrich the generated OpenAPI spec. Every Haykal controller method is expected to carry the annotations below. A complete example is shown in the [Controllers](#controllers) subsection that follows.

| Annotation | Purpose |
|---|---|
| `@summary` or first PHPDoc line | Short title of the operation. The first paragraph is used as the summary and any following paragraphs become the description. |
| `@tags <Module>` | Override the automatically derived module tag when grouping an endpoint under a different heading (for example, a cross-module utility endpoint). |
| `@unauthenticated` | Mark a public endpoint — Scramble removes the default bearer security requirement from its spec entry. |
| `@response <Class>` | Pin the response payload to a concrete Resource class when the controller's return type inference is too loose (typical when returning through `ApiResponse`). |
| `@throws <ExceptionClass>` | Declare exceptions the operation may raise. Scramble's registered exception extensions (validation, not-found) turn these into documented error responses. |

### Controllers

Controllers are single-action or per-resource classes that delegate to Actions or services. They return through `ApiResponse` exclusively.

```php
namespace App\Apis\Properties\Controllers;

use App\Apis\Properties\Requests\CreatePropertyRequest;
use App\Apis\Properties\Resources\PropertyResource;
use App\Domain\Properties\Actions\CreatePropertyAction;
use HiTaqnia\Haykal\Api\Response\ApiResponse;
use Illuminate\Http\JsonResponse;

final class CreatePropertyController
{
    public function __construct(
        private readonly CreatePropertyAction $createProperty,
    ) {}

    /**
     * Create Property
     *
     * Register a new property in the active tenant.
     *
     * @response PropertyResource
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function __invoke(CreatePropertyRequest $request): JsonResponse
    {
        $result = $this->createProperty->execute($request->validated());

        if ($result->isFailure()) {
            return ApiResponse::businessError($result->getError());
        }

        return ApiResponse::created(
            message: 'Property created.',
            data: new PropertyResource($result->getData()),
        );
    }
}
```

### Form Requests

Form Requests carry validation rules, authorization, and any input transformations. `authorize()` should return `true` only when the check is cheap and always required — finer-grained policy checks belong in the controller or action.

```php
namespace App\Apis\Properties\Requests;

use HiTaqnia\Haykal\Core\Identity\Rules\PhoneNumberRule;
use Illuminate\Foundation\Http\FormRequest;

final class CreatePropertyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('properties.create');
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'owner_phone' => ['required', new PhoneNumberRule],
        ];
    }
}
```

### Resources

Resources transform a model into a JSON representation. Annotate every key with a PHPDoc line so Scramble generates a complete schema.

```php
namespace App\Apis\Properties\Resources;

use App\Domain\Properties\Models\Property;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property Property $resource
 */
final class PropertyResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            // The property's unique identifier.
            // @var string
            // @format ULID
            'id' => $this->id,

            // The property's display name.
            'name' => $this->name,

            // ISO-8601 timestamp of when the property was registered.
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}
```

### Module layout

Each API module lives under `app/Apis/<ModuleName>/` and contains its own `Controllers/`, `Requests/`, and `Resources/`. Routes are registered under `routes/api/<module-name>-api.php` and included from `routes/api.php`.

```
app/Apis/Properties/
    Controllers/
        CreatePropertyController.php
        ListPropertiesController.php
    Requests/
        CreatePropertyRequest.php
    Resources/
        PropertyResource.php

routes/api/properties-api.php
```

---

## Customization

### Scramble

`HaykalApiServiceProvider` registers the shipped Scramble extensions. Applications add their own exception-to-response extensions by calling `Scramble::registerExtension(...)` in their own service provider's `boot()`.

### Custom response envelopes

Prefer composing new factories on `ApiResponse` rather than subclassing. Any factory that funnels through `InteractsWithResponseMaker::make()` inherits the envelope shape automatically.

---

## Testing

The monorepo ships test helpers on `HiTaqnia\Haykal\Tests\Api\ApiTestCase` that feature tests inherit:

| Helper | Purpose |
|---|---|
| `authenticateAs(User $user, string $guard = 'huwiya-api'): User` | Authenticate the given user against a Huwiya-driven guard for the remainder of the test. Wraps `Huwiya::actingAs()`. |
| `assertApiSuccess(TestResponse $response, int $code = 200): void` | Assert the response carries the Haykal success envelope: correct HTTP status, `success = 1`, `code = <code>`, `errors = null`, and the five canonical keys. |
| `assertApiError(TestResponse $response, int $code, ?int $expectedHttpStatus = null): void` | Assert the response carries the Haykal error envelope. Pass `expectedHttpStatus` for business errors (codes > 999) that map to HTTP 409. |

Example:

```php
public function test_create_property_returns_the_created_resource(): void
{
    $user = User::factory()->create();
    $this->authenticateAs($user);

    $response = $this->postJson('/api/properties', [
        'name' => 'Al-Mansour Tower',
        'owner_phone' => '+9647701234567',
    ]);

    $this->assertApiSuccess($response, code: 201);
    $response->assertJsonPath('data.name', 'Al-Mansour Tower');
}
```

For tests that exercise the full token round-trip, the monorepo's `FakeHuwiyaIdP` fixture (under `tests/Fixtures/`) issues RS256-signed JWTs that the real Huwiya SDK accepts.

Run the monorepo suite from the repository root:

```bash
composer test
```
