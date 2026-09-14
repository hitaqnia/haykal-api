<?php

declare(strict_types=1);

namespace HiTaqnia\Haykal\Api\Auth\Requests;

use HiTaqnia\Haykal\Core\Identity\Rules\PhoneNumberRule;
use Illuminate\Foundation\Http\FormRequest;

final class CheckAccountRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'phone' => ['required', 'string', new PhoneNumberRule],
        ];
    }
}
