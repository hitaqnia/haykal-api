<?php

declare(strict_types=1);

namespace HiTaqnia\Haykal\Api\Auth\Sms;

use HiTaqnia\Haykal\Api\Auth\Contracts\OtpSender;
use Illuminate\Support\Facades\Log;

/**
 * Writes the code to the log instead of sending it. For local development.
 */
final class LogOtpSender implements OtpSender
{
    public function send(string $phone, string $code): void
    {
        Log::info("OTP for {$phone}: {$code}");
    }
}
