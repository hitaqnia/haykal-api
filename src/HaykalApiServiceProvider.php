<?php

declare(strict_types=1);

namespace HiTaqnia\Haykal\Api;

use Dedoc\Scramble\Scramble;
use HiTaqnia\Haykal\Api\Response\ApiExceptionHandler;
use HiTaqnia\Haykal\Api\Scramble\ModuleTagResolver;
use HiTaqnia\Haykal\Api\Scramble\NotFoundExceptionExtension;
use HiTaqnia\Haykal\Api\Scramble\ValidationExceptionExtension;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Foundation\Exceptions\Handler as LaravelExceptionHandler;
use Illuminate\Http\Request;
use Illuminate\Support\ServiceProvider;
use Throwable;

/**
 * Package service provider for haykal-api.
 *
 * Contributes two globally-applicable Scramble exception-to-response
 * extensions (validation / 404) that render the Haykal envelope, and
 * publishes the Identity routes stub. Per-API document configuration
 * (security schemes, titles, docs UI) lives in ApiProvider subclasses
 * registered by the consuming application.
 */
final class HaykalApiServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        Scramble::registerExtensions([
            ValidationExceptionExtension::class,
            NotFoundExceptionExtension::class,
        ]);

        Scramble::resolveTagsUsing(new ModuleTagResolver);

        $this->registerExceptionRenderer();

        $this->publishes([
            __DIR__.'/../routes/identity-api.stub.php' => base_path('routes/api/identity-api.php'),
        ], 'haykal-api-routes');
    }

    /**
     * Register `ApiExceptionHandler` as a render callback on Laravel's
     * exception handler so every `api/*` route returns the Haykal envelope
     * shape on framework failures (validation, 404, auth, throttle, …).
     *
     * Non-API requests fall through to Laravel's defaults.
     */
    private function registerExceptionRenderer(): void
    {
        $handler = $this->app->make(ExceptionHandler::class);

        if (! $handler instanceof LaravelExceptionHandler) {
            return;
        }

        $handler->renderable(function (Throwable $e, Request $request) {
            return ApiExceptionHandler::handle($e, $request);
        });
    }
}
