<?php

declare(strict_types=1);

namespace HiTaqnia\Haykal\Api\Auth\Requests\Otp;

use HiTaqnia\Haykal\Api\Auth\Enums\OtpPurpose;
use HiTaqnia\Haykal\Core\Identity\Rules\PhoneNumberRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class VerifyOtpRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'phone' => ['required', 'string', new PhoneNumberRule],
            'purpose' => ['required', 'integer', Rule::enum(OtpPurpose::class)],
            'otp' => ['required', 'string', 'size:'.config('haykal-auth.otp.length', 6)],
        ];
    }

    public function purpose(): OtpPurpose
    {
        return OtpPurpose::from((int) $this->input('purpose'));
    }
}
