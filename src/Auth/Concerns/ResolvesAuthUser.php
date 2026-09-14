<?php

declare(strict_types=1);

namespace HiTaqnia\Haykal\Api\Auth\Concerns;

use HiTaqnia\Haykal\Core\Identity\ValueObjects\PhoneNumber;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

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

    /**
     * Whether the phone number is spoken for, soft-deleted rows included.
     *
     * `findUserByPhone` deliberately cannot see trashed users — they must not
     * be able to authenticate. But the unique index still covers their row, so
     * registration has to ask this instead, or it would pass every check and
     * then die on the constraint.
     */
    protected function phoneIsTaken(string $phone): bool
    {
        $model = $this->userModel();
        $query = $model::query()->where('phone', $this->normalizePhone($phone));

        if (in_array(SoftDeletes::class, class_uses_recursive($model), true)) {
            $query->withTrashed();
        }

        return $query->exists();
    }

    protected function normalizePhone(string $phone): string
    {
        return (new PhoneNumber($phone))->getInternational();
    }
}
