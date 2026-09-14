<?php

declare(strict_types=1);

namespace HiTaqnia\Haykal\Api\Auth\Actions\Otp\Concerns;

use HiTaqnia\Haykal\Api\Auth\Enums\OtpPurpose;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Support\Facades\Cache;

/**
 * Where issued codes live between request and verify.
 *
 * Codes are keyed by phone and purpose, so one purpose can never verify
 * another's code, and a re-request simply overwrites the pending code.
 */
trait OtpStore
{
    protected function otpStore(): Repository
    {
        return Cache::store(config('haykal-auth.otp.store'));
    }

    protected function otpKey(string $phone, OtpPurpose $purpose): string
    {
        return "haykal-auth:otp:{$phone}:{$purpose->value}";
    }
}
