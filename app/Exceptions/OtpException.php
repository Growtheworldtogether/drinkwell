<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * An OTP request was refused. The message states the fact and the fix (SPEC 12.4)
 * and is safe to show to the user. `reason` is for tests and logs.
 */
class OtpException extends RuntimeException
{
    public const COOLDOWN = 'cooldown';

    public const HOURLY_LIMIT = 'hourly_limit';

    public const INVALID = 'invalid';

    public const WRONG = 'wrong';

    public function __construct(public readonly string $reason, string $message, public readonly ?int $retryAfter = null)
    {
        parent::__construct($message);
    }

    public static function cooldown(int $seconds): self
    {
        return new self(self::COOLDOWN, "Wait {$seconds} seconds before asking for another code.", $seconds);
    }

    public static function hourlyLimit(): self
    {
        return new self(self::HOURLY_LIMIT, 'Too many codes were requested in the last hour. Try again later.', 3600);
    }

    /** Missing, expired, used, locked after 5 wrong tries, or issued for another purpose. */
    public static function invalid(): self
    {
        return new self(self::INVALID, 'This code is expired or already used. Ask for a new code.');
    }

    public static function wrong(int $attemptsLeft): self
    {
        return new self(
            self::WRONG,
            $attemptsLeft > 0
                ? "That code is not correct. You have {$attemptsLeft} ".($attemptsLeft === 1 ? 'try' : 'tries').' left.'
                : 'That code is not correct and is now locked. Ask for a new code.',
        );
    }
}
