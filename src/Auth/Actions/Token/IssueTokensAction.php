<?php

declare(strict_types=1);

namespace HiTaqnia\Haykal\Api\Auth\Actions\Token;

use HiTaqnia\Haykal\Api\Auth\Enums\OtpPurpose;
use HiTaqnia\Haykal\Api\Auth\Enums\TokenType;
use Illuminate\Database\Eloquent\Model;
use Laravel\Sanctum\NewAccessToken;

/**
 * Mints tokens directly on the relation rather than through `createToken()`,
 * which has no way to set `device_id`.
 *
 * The user model must use `Laravel\Sanctum\HasApiTokens`.
 */
final class IssueTokensAction
{
    /**
     * An access + refresh pair for one device.
     *
     * @return array{access: NewAccessToken, refresh: NewAccessToken}
     */
    public function pair(Model $user, ?string $deviceId = null): array
    {
        return [
            'access' => $this->mint($user, TokenType::Access, TokenType::Access->getAbilities(), $deviceId),
            'refresh' => $this->mint($user, TokenType::Refresh, TokenType::Refresh->getAbilities(), $deviceId),
        ];
    }

    /**
     * A short-lived, device-less token that may do exactly one thing.
     */
    public function otp(Model $user, OtpPurpose $purpose): NewAccessToken
    {
        $ability = TokenType::getOtpAbilityByPurpose($purpose);

        return $this->mint($user, TokenType::Otp, $ability === null ? [] : [$ability], null);
    }

    /**
     * @param  list<string>  $abilities
     */
    private function mint(Model $user, TokenType $type, array $abilities, ?string $deviceId): NewAccessToken
    {
        $plainTextToken = $user->generateTokenString();

        $token = $user->tokens()->create([
            'device_id' => $deviceId,
            'name' => $type->getName(),
            'token' => hash('sha256', $plainTextToken),
            'abilities' => $abilities,
            'expires_at' => $type->getExpirationDate(),
        ]);

        return new NewAccessToken($token, $token->getKey().'|'.$plainTextToken);
    }
}
