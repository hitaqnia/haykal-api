<?php

declare(strict_types=1);

namespace HiTaqnia\Haykal\Api;

use Dedoc\Scramble\Scramble;
use HiTaqnia\Haykal\Api\Auth\Contracts\OtpSender;
use HiTaqnia\Haykal\Api\Auth\Http\Middlewares\EnsureSessionToken;
use HiTaqnia\Haykal\Api\Auth\Models\Token;
use HiTaqnia\Haykal\Api\Auth\Sms\LogOtpSender;
use HiTaqnia\Haykal\Api\Auth\Sms\NullOtpSender;
use HiTaqnia\Haykal\Api\Auth\Sms\OtpiqOtpSender;
use HiTaqnia\Haykal\Api\Response\ApiExceptionHandler;
use HiTaqnia\Haykal\Api\Scramble\ModuleTagResolver;
use HiTaqnia\Haykal\Api\Scramble\NotFoundExceptionExtension;
use HiTaqnia\Haykal\Api\Scramble\ValidationExceptionExtension;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Foundation\Exceptions\Handler as LaravelExceptionHandler;
use Illuminate\Http\Request;
use Illuminate\Routing\Router;
use Illuminate\Support\ServiceProvider;
use Laravel\Sanctum\Sanctum;
use Throwable;

/**
 * Package service provider for haykal-api.
 *
 * Contributes two globally-applicable Scramble exception-to-response
 * extensions (validation / 404) that render the Haykal envelope, installs
 * the module-based tag resolver, and registers the ApiExceptionHandler
 * render callback so every `api/*` request uses the envelope shape on
 * framework failures. Per-API document configuration (security schemes,
 * titles, docs UI) lives in `ApiProvider` subclasses registered by the
 * consuming application.
 *
 * It also wires the phone + password + OTP auth toolkit: the token model,
 * the OTP sender binding, translations, and the publishable config and
 * tokens migration. Route registration stays with the application —
 * see `Auth\AuthRoutes`.
 */
final class HaykalApiServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/haykal-auth.php', 'haykal-auth');

        $this->registerOtpSender();
    }

    public function boot(): void
    {
        Scramble::registerExtensions([
            ValidationExceptionExtension::class,
            NotFoundExceptionExtension::class,
        ]);

        Scramble::resolveTagsUsing(new ModuleTagResolver);

        $this->registerExceptionRenderer();
        $this->registerAuth();
        $this->registerTranslations();
        $this->registerPublishables();
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

    /**
     * The sender is resolved from config so `OTP_FAKE=true` and the log driver
     * work without touching code. Bind `OtpSender` yourself to use another
     * gateway — this binding only applies when nothing else claimed it.
     */
    private function registerOtpSender(): void
    {
        $this->app->bind(OtpSender::class, function (): OtpSender {
            if (config('haykal-auth.otp.fake')) {
                return new NullOtpSender;
            }

            return match ($driver = config('haykal-auth.otp.sender', 'otpiq')) {
                'otpiq' => new OtpiqOtpSender,
                'log' => new LogOtpSender,
                'null' => new NullOtpSender,
                default => $this->app->make($driver),
            };
        });
    }

    private function registerAuth(): void
    {
        if (config('haykal-auth.tokens.use_token_model', true)) {
            Sanctum::usePersonalAccessTokenModel(Token::class);
        }

        /** @var Router $router */
        $router = $this->app->make(Router::class);

        $router->aliasMiddleware('haykal.session.token', EnsureSessionToken::class);
    }

    private function registerTranslations(): void
    {
        $this->loadTranslationsFrom(__DIR__.'/../lang', 'haykal-api');
    }

    private function registerPublishables(): void
    {
        $this->publishes([
            __DIR__.'/../config/haykal-auth.php' => config_path('haykal-auth.php'),
        ], 'haykal-auth-config');

        $this->publishes([
            __DIR__.'/../database/migrations/create_haykal_tokens_table.php.stub' => database_path(
                'migrations/'.date('Y_m_d_His').'_create_tokens_table.php'
            ),
        ], 'haykal-auth-migrations');

        $this->publishes([
            __DIR__.'/../lang' => lang_path('vendor/haykal-api'),
        ], 'haykal-api-translations');
    }
}
