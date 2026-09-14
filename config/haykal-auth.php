<?php

declare(strict_types=1);

use HiTaqnia\Haykal\Api\Auth\Enums\OtpPurpose;

return [

    /*
    |--------------------------------------------------------------------------
    | Authenticatable
    |--------------------------------------------------------------------------
    |
    | The model the auth endpoints look up and create. Leave null to follow the
    | model behind the default auth provider in `config/auth.php`. It must use
    | `Laravel\Sanctum\HasApiTokens` and have `phone` and `password` columns.
    |
    | `user_resource` is an optional API resource class. When set, login,
    | registration and refresh embed the caller under a `user` key; the shape
    | of a user belongs to the application, so nothing is embedded by default.
    |
    */

    'user_model' => null,

    'user_resource' => null,

    'guard' => 'sanctum',

    /*
    |--------------------------------------------------------------------------
    | Device binding
    |--------------------------------------------------------------------------
    |
    | Tokens are stamped with the id of whatever row the application uses for
    | one app install or browser, read from this header. That is what makes
    | "sign out this device" and per-device refresh possible. This package
    | never reads or writes the devices table itself.
    |
    */

    'device' => [
        'header' => env('HAYKAL_AUTH_DEVICE_HEADER', 'X-Device-Id'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Tokens
    |--------------------------------------------------------------------------
    |
    | All three kinds live on one table and are told apart by `name`. Access
    | tokens hold `*`, so ability checks alone can never distinguish them.
    |
    | `rotation_grace` is how long a superseded pair stays valid after a
    | refresh, so in-flight requests and duplicate parallel refreshes survive.
    |
    */

    'tokens' => [

        'table' => 'tokens',

        // Register the package's token model with Sanctum. Turn off if the
        // application registers its own subclass.
        'use_token_model' => true,

        'rotation_grace' => (int) env('TOKEN_ROTATION_GRACE_IN_SECONDS', 60),

        'access' => [
            'name' => 'api-access-token',
            'lifetime' => (int) env('ACCESS_TOKEN_LIFETIME_IN_SECONDS', 60 * 60 * 24),
            'abilities' => ['*'],
        ],

        'refresh' => [
            'name' => 'api-refresh-token',
            'lifetime' => (int) env('REFRESH_TOKEN_LIFETIME_IN_SECONDS', 60 * 60 * 24 * 90),
            'abilities' => ['refresh'],
        ],

        'otp' => [
            'name' => 'api-otp-token',
            'lifetime' => (int) env('OTP_TOKEN_LIFETIME_IN_SECONDS', 60 * 30),
            'abilities' => [
                OtpPurpose::ResetPassword->value => 'reset-password',
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Login throttling
    |--------------------------------------------------------------------------
    |
    | Counted per phone + IP, so one attacker cannot lock every account out and
    | one account cannot be brute-forced from a single address.
    |
    */

    'login' => [
        'max_attempts' => (int) env('LOGIN_MAX_ATTEMPTS', 5),
        'decay_seconds' => (int) env('LOGIN_DECAY_SECONDS', 60),
    ],

    'password' => [
        'min' => 8,
        'max' => 255,
    ],

    /*
    |--------------------------------------------------------------------------
    | One-time codes
    |--------------------------------------------------------------------------
    |
    | `fake` makes every code a repeated 1 (111111), skips delivery, and lifts
    | the throttles. Never enable it outside local development.
    |
    | `store` is a cache store name; null uses the default store.
    | `sender` is `otpiq`, `log`, `null`, or a class implementing OtpSender.
    |
    */

    'otp' => [
        'fake' => env('OTP_FAKE', false),
        'length' => (int) env('OTP_LENGTH', 6),
        'expiry_minutes' => (int) env('OTP_EXPIRY_MINUTES', 5),
        'max_generate_attempts_per_hour' => (int) env('OTP_MAX_GENERATE_ATTEMPTS_PER_HOUR', 3),
        'max_verify_attempts_per_hour' => (int) env('OTP_MAX_VERIFY_ATTEMPTS_PER_HOUR', 5),
        'store' => env('OTP_CACHE_STORE'),
        'sender' => env('OTP_SENDER', 'otpiq'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Registration
    |--------------------------------------------------------------------------
    |
    | Verifying a registration code returns a ticket rather than a token —
    | there is no account to hang a token on yet. The ticket is bound to the
    | phone it was minted for and is single use.
    |
    */

    'registration' => [
        'ticket_lifetime' => (int) env('REGISTRATION_TICKET_LIFETIME_IN_SECONDS', 60 * 30),
    ],

    /*
    |--------------------------------------------------------------------------
    | OTPIQ
    |--------------------------------------------------------------------------
    |
    | The default provider cascade tries the cheap channels before SMS.
    |
    */

    'otpiq' => [
        'provider' => env('OTPIQ_PROVIDER', 'whatsapp-telegram-sms'),
    ],

];
