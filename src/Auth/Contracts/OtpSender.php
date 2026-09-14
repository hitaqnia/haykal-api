<?php

declare(strict_types=1);

namespace HiTaqnia\Haykal\Api\Auth\Contracts;

/**
 * Delivers a one-time code to a phone number.
 *
 * Bind your own implementation in a service provider to use a different
 * gateway; the shipped drivers are selected by `haykal-auth.otp.sender`.
 */
interface OtpSender
{
    /**
     * @param  string  $phone  E.164, with the leading `+`.
     *
     * @throws \Throwable when delivery fails.
     */
    public function send(string $phone, string $code): void;
}
