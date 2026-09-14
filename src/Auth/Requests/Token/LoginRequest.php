<?php

declare(strict_types=1);

namespace HiTaqnia\Haykal\Api\Auth\Requests\Token;

use HiTaqnia\Haykal\Core\Identity\Rules\PhoneNumberRule;
use Illuminate\Foundation\Http\FormRequest;

final class LoginRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'phone' => ['required', 'string', new PhoneNumberRule],
            // No minimum length here: this password is being checked, not
            // chosen, and a short wrong guess should come back as a failed
            // sign-in rather than a validation error.
            'password' => ['required', 'string', 'max:'.config('haykal-auth.password.max', 255)],
        ];
    }
}
