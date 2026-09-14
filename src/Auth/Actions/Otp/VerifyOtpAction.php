<?php

declare(strict_types=1);

namespace HiTaqnia\Haykal\Api\Auth\Actions\Otp;

use HiTaqnia\Haykal\Api\Auth\Actions\Otp\Concerns\OtpStore;
use HiTaqnia\Haykal\Api\Auth\AuthErrors;
use HiTaqnia\Haykal\Api\Auth\Enums\OtpPurpose;
use HiTaqnia\Haykal\Core\ResultPattern\Result;

/**
 * Check a submitted code and burn it. Codes are single use.
 */
final class VerifyOtpAction
{
    use OtpStore;

    /**
     * @param  string  $phone  E.164.
     * @return Result<null>
     */
    public function execute(string $phone, string $code, OtpPurpose $purpose): Result
    {
        $key = $this->otpKey($phone, $purpose);
        $stored = $this->otpStore()->get($key);

        if ($stored === null) {
            return Result::failure(AuthErrors::otpExpired());
        }

        if (! hash_equals((string) $stored, $code)) {
            return Result::failure(AuthErrors::otpDoesNotMatch());
        }

        $this->otpStore()->forget($key);

        return Result::success();
    }
}
