<?php

declare(strict_types=1);

namespace HiTaqnia\Haykal\Api\Auth\Actions\User;

use HiTaqnia\Haykal\Api\Auth\Concerns\ResolvesAuthUser;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;

/**
 * Create an account from a verified phone number.
 *
 * `$attributes` is merged last, so an application can pass whatever else its
 * users table requires.
 */
final class CreateUserWithPasswordAction
{
    use ResolvesAuthUser;

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function execute(string $phone, string $name, string $password, array $attributes = []): Model
    {
        $model = $this->userModel();

        return $model::query()->create([
            'phone' => $this->normalizePhone($phone),
            'name' => $name,
            'password' => Hash::make($password),
            ...$attributes,
        ]);
    }
}
