<?php

declare(strict_types=1);

namespace HiTaqnia\Haykal\Api\Auth\Requests\Registration;

use HiTaqnia\Haykal\Core\Identity\Rules\PhoneNumberRule;
use Illuminate\Foundation\Http\FormRequest;

final class RegisterRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'phone' => ['required', 'string', new PhoneNumberRule],
            'registration_token' => ['required', 'string'],
            'name' => ['required', 'string', 'min:2', 'max:100'],
            'password' => [
                'required',
                'string',
                'min:'.config('haykal-auth.password.min', 8),
                'max:'.config('haykal-auth.password.max', 255),
                'confirmed',
            ],
        ];
    }
}
