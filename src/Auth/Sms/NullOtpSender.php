<?php

declare(strict_types=1);

namespace HiTaqnia\Haykal\Api\Auth\Sms;

use HiTaqnia\Haykal\Api\Auth\Contracts\OtpSender;

/**
 * Sends nothing. Used by `haykal-auth.otp.fake` and in tests.
 */
final class NullOtpSender implements OtpSender
{
    public function send(string $phone, string $code): void {}
}
