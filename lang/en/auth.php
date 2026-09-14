<?php

declare(strict_types=1);

return [
    'invalid_credentials' => 'The phone number or password is incorrect.',
    'too_many_attempts' => 'Too many attempts. Try again in :seconds seconds.',
    'login_successful' => 'Signed in successfully.',
    'logout_successful' => 'Signed out successfully.',
    'registration_successful' => 'Account created successfully.',
    'token_refreshed' => 'Session refreshed.',
    'invalid_token' => 'This token cannot be used here.',
    'invalid_refresh_token' => 'Invalid refresh token.',
    'invalid_device' => 'Unknown device.',
    'invalid_reset_token' => 'Invalid password reset token.',
    'otp_sent' => 'Verification code sent.',
    'otp_rate_limit' => 'Too many codes requested. Try again in :seconds seconds.',
    'otp_verify_rate_limit' => 'Too many attempts. Try again in :seconds seconds.',
    'current_password_incorrect' => 'The current password is incorrect.',
    'password_updated_successfully' => 'Password updated successfully.',
    'password_reset_successful' => 'Password reset successfully.',

    'errors' => [
        'does_not_have_password' => 'This account does not have a password set.',
        'otp_delivery_failed' => 'Could not send the verification code.',
        'otp_storage_failed' => 'Could not issue a verification code.',
        'otp_expired' => 'The verification code has expired.',
        'otp_does_not_match' => 'The verification code you entered is incorrect.',
        'phone_already_registered' => 'This phone number already has an account.',
        'invalid_registration_token' => 'Your verification has expired. Start again.',
    ],
];
