<?php

declare(strict_types=1);

namespace HiTaqnia\Haykal\Api\Auth\Enums;

/**
 * What an OTP was issued for.
 *
 * The value is part of the cache key and of the verify payload, so a code
 * minted for one purpose can never be replayed against another.
 */
enum OtpPurpose: int
{
    case ResetPassword = 1;
    case Register = 2;
}
