<?php

declare(strict_types=1);

namespace HiTaqnia\Haykal\Api\Auth;

use HiTaqnia\Haykal\Core\ResultPattern\Error;

/**
 * Business errors raised by the auth flows, in the 1000-1099 band.
 *
 * Codes above 999 come back as HTTP 409 with the real code in the body —
 * see `InteractsWithResponseMaker`.
 */
final class AuthErrors
{
    public static function doesNotHavePassword(): Error
    {
        return Error::make(1000, __('haykal-api::auth.errors.does_not_have_password'));
    }

    public static function otpDeliveryFailed(): Error
    {
        return Error::make(1002, __('haykal-api::auth.errors.otp_delivery_failed'));
    }

    public static function otpStorageFailed(): Error
    {
        return Error::make(1003, __('haykal-api::auth.errors.otp_storage_failed'));
    }

    public static function otpExpired(): Error
    {
        return Error::make(1004, __('haykal-api::auth.errors.otp_expired'));
    }

    public static function otpDoesNotMatch(): Error
    {
        return Error::make(1005, __('haykal-api::auth.errors.otp_does_not_match'));
    }

    public static function phoneAlreadyRegistered(): Error
    {
        return Error::make(1006, __('haykal-api::auth.errors.phone_already_registered'));
    }

    public static function invalidRegistrationToken(): Error
    {
        return Error::make(1007, __('haykal-api::auth.errors.invalid_registration_token'));
    }
}
