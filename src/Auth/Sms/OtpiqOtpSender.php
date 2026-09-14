<?php

declare(strict_types=1);

namespace HiTaqnia\Haykal\Api\Auth\Sms;

use HiTaqnia\Haykal\Api\Auth\Contracts\OtpSender;
use Rstacode\Otpiq\Facades\Otpiq;

/**
 * Sends verification codes through OTPIQ.
 *
 * The provider string decides the delivery cascade; the default
 * `whatsapp-telegram-sms` tries the cheap channels before falling back to SMS.
 */
final class OtpiqOtpSender implements OtpSender
{
    public function send(string $phone, string $code): void
    {
        Otpiq::sendSms([
            'phoneNumber' => ltrim($phone, '+'),
            'smsType' => 'verification',
            'verificationCode' => $code,
            'provider' => (string) config('haykal-auth.otpiq.provider', 'whatsapp-telegram-sms'),
        ]);
    }
}
