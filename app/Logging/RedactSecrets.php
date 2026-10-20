<?php

namespace App\Logging;

use Illuminate\Log\Logger;
use Monolog\LogRecord;

/** Strips secrets from every log record's context and message (SPEC 17.2). */
class RedactSecrets
{
    public const KEYS = ['password', 'password_confirmation', 'current_password', 'account_number', 'routing_number', 'code', 'otp', 'token', '_token'];

    public function __invoke(Logger $logger): void
    {
        foreach ($logger->getLogger()->getHandlers() as $handler) {
            $handler->pushProcessor(function (LogRecord $record): LogRecord {
                $message = (string) preg_replace('/\b(password|password_confirmation|account_number|routing_number|code|otp)\b(["\']?\s*[:=]\s*["\']?)[^\s"\',;&]+/i', '$1$2[redacted]', $record->message);

                return $record->with(message: $message, context: self::scrub($record->context));
            });
        }
    }

    /** @param array<mixed> $data */
    public static function scrub(array $data): array
    {
        foreach ($data as $key => $value) {
            if (is_string($key) && in_array(strtolower($key), self::KEYS, true)) {
                $data[$key] = '[redacted]';
            } elseif (is_array($value)) {
                $data[$key] = self::scrub($value);
            }
        }

        return $data;
    }
}
