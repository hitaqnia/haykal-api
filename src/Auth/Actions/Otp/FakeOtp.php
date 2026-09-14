<?php

declare(strict_types=1);

namespace HiTaqnia\Haykal\Api\Auth\Actions\Otp;

/**
 * Whether the fake-OTP shortcut is active.
 *
 * Fake mode fixes every code to a repeated 1 and lifts both throttles, so a
 * deployment that reaches production with `OTP_FAKE=true` has no working
 * authentication at all — anyone can take any account by typing 111111.
 * The environment check is deliberately not configurable: a setting that can
 * turn this back on in production would defeat the point of having it.
 */
final class FakeOtp
{
    public static function enabled(): bool
    {
        return (bool) config('haykal-auth.otp.fake')
            && app()->environment('local', 'testing');
    }
}
