<?php

declare(strict_types=1);

namespace HiTaqnia\Haykal\Api\Auth\Enums;

use Illuminate\Support\Carbon;

/**
 * The three kinds of token this package issues, all living on one table and
 * told apart by `name` — not by abilities.
 *
 * Access tokens hold `['*']`, which satisfies every `can()` check, so an
 * ability test alone would let an access token pass for a refresh or an OTP
 * token. Always compare `$token->name` against `getName()`.
 */
enum TokenType: string
{
    case Access = 'access';
    case Refresh = 'refresh';
    case Otp = 'otp';

    public function getName(): string
    {
        return (string) config("haykal-auth.tokens.{$this->value}.name");
    }

    /**
     * @return list<string>
     */
    public function getAbilities(): array
    {
        return config("haykal-auth.tokens.{$this->value}.abilities", []);
    }

    public function getLifetime(): int
    {
        return (int) config("haykal-auth.tokens.{$this->value}.lifetime");
    }

    public function getExpirationDate(): Carbon
    {
        return now()->addSeconds($this->getLifetime());
    }

    public static function getOtpAbilityByPurpose(OtpPurpose $purpose): ?string
    {
        return config("haykal-auth.tokens.otp.abilities.{$purpose->value}");
    }
}
