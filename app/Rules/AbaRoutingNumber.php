<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * A US ABA routing number: nine digits whose weighted sum is a multiple of ten (SPEC 6.1).
 * 3(d1 + d4 + d7) + 7(d2 + d5 + d8) + (d3 + d6 + d9) mod 10 = 0
 */
class AbaRoutingNumber implements ValidationRule
{
    public static function passes(string $value): bool
    {
        if (! preg_match('/^\d{9}$/', $value)) {
            return false;
        }

        $d = array_map('intval', str_split($value));
        $sum = 3 * ($d[0] + $d[3] + $d[6]) + 7 * ($d[1] + $d[4] + $d[7]) + ($d[2] + $d[5] + $d[8]);

        return $sum % 10 === 0;
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $value = is_scalar($value) ? (string) $value : '';

        if (! preg_match('/^\d{9}$/', $value)) {
            $fail('Routing number must be nine digits. Check the number and try again.');

            return;
        }

        if (! self::passes($value)) {
            $fail('Routing number failed the checksum. Check the nine digits and try again.');
        }
    }
}
