<?php

declare(strict_types=1);

namespace HiTaqnia\Haykal\Api\Auth\Actions\Otp;

use HiTaqnia\Haykal\Api\Auth\Actions\Otp\Concerns\OtpStore;
use HiTaqnia\Haykal\Api\Auth\AuthErrors;
use HiTaqnia\Haykal\Api\Auth\Contracts\OtpSender;
use HiTaqnia\Haykal\Api\Auth\Enums\OtpPurpose;
use HiTaqnia\Haykal\Core\ResultPattern\Result;
use Throwable;

/**
 * Mint a code, store it, then send it.
 *
 * Stored before sending, and dropped again if sending throws, so a user is
 * never handed a code the store does not know about.
 */
final class GenerateOtpAction
{
    use OtpStore;

    public function __construct(private readonly OtpSender $sender) {}

    /**
     * @param  string  $phone  E.164.
     * @return Result<array{expires_in: int}>
     */
    public function execute(string $phone, OtpPurpose $purpose): Result
    {
        $code = $this->generateCode();
        $expiresIn = (int) config('haykal-auth.otp.expiry_minutes', 5) * 60;
        $key = $this->otpKey($phone, $purpose);

        if (! $this->otpStore()->put($key, $code, $expiresIn)) {
            return Result::failure(AuthErrors::otpStorageFailed());
        }

        try {
            $this->sender->send($phone, $code);
        } catch (Throwable $e) {
            report($e);
            $this->otpStore()->forget($key);

            return Result::failure(AuthErrors::otpDeliveryFailed());
        }

        return Result::success(['expires_in' => $expiresIn]);
    }

    private function generateCode(): string
    {
        if (FakeOtp::enabled()) {
            return str_repeat('1', (int) config('haykal-auth.otp.length', 6));
        }

        $length = (int) config('haykal-auth.otp.length', 6);

        return str_pad((string) random_int(0, 10 ** $length - 1), $length, '0', STR_PAD_LEFT);
    }
}
