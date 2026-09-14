<?php

declare(strict_types=1);

namespace HiTaqnia\Haykal\Api\Auth\Requests\Password;

use Illuminate\Foundation\Http\FormRequest;

final class ResetPasswordRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
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
