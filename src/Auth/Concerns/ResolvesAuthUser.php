<?php

declare(strict_types=1);

namespace HiTaqnia\Haykal\Api\Auth\Concerns;

use HiTaqnia\Haykal\Core\Identity\ValueObjects\PhoneNumber;
use Illuminate\Database\Eloquent\Model;

/**
 * Looks the authenticatable up by phone number.
 *
 * The model comes from `haykal-auth.user_model`, falling back to the model
 * behind the default auth provider. Phones are matched in the E.164 form that
 * `PhoneNumberCast` writes — casts do not apply inside where clauses, so the
 * normalization has to happen here.
 */
trait ResolvesAuthUser
{
    /**
     * @return class-string<Model>
     */
    protected function userModel(): string
    {
        return config('haykal-auth.user_model')
            ?? config('auth.providers.'.config('auth.defaults.provider', 'users').'.model')
            ?? config('auth.providers.users.model');
    }

    protected function findUserByPhone(string $phone): ?Model
    {
        $model = $this->userModel();

        return $model::query()->where('phone', $this->normalizePhone($phone))->first();
    }

    protected function normalizePhone(string $phone): string
    {
        return (new PhoneNumber($phone))->getInternational();
    }
}
