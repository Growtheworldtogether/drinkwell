<?php

namespace App\Exceptions;

use RuntimeException;
use Throwable;

/** A critical email (an OTP) could not be handed to the transport. */
class MailSendFailed extends RuntimeException
{
    public const MESSAGE = 'We could not send the email. Try again in a minute.';

    public function __construct(?Throwable $previous = null)
    {
        parent::__construct(self::MESSAGE, 0, $previous);
    }
}
