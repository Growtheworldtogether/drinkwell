<?php

namespace App\Support;

use Stringable;

/**
 * Money is integer cents in code. This is the only place that turns cents into UI text.
 *
 * `format()` and `plain()` are static and accept either raw cents or a Money, so
 * `Money::format()` stays the single formatting entry point named by the domain
 * rules while model accessors can hand a Money straight to a view.
 */
final readonly class Money implements Stringable
{
    public function __construct(public int $cents) {}

    public static function of(int $cents): self
    {
        return new self($cents);
    }

    /** 105500 becomes "$1,055.00". Negative amounts become "-$1.00". */
    public static function format(int|self $value): string
    {
        $cents = $value instanceof self ? $value->cents : $value;
        $sign = $cents < 0 ? '-' : '';
        $abs = abs($cents);
        $dollars = preg_replace('/\B(?=(\d{3})+(?!\d))/', ',', (string) intdiv($abs, 100));

        return sprintf('%s$%s.%02d', $sign, $dollars, $abs % 100);
    }

    /** 105500 becomes "1055.00": no symbol and no separators, for inputs, CSV and JSON. */
    public static function plain(int|self $value): string
    {
        $cents = $value instanceof self ? $value->cents : $value;

        return sprintf('%s%d.%02d', $cents < 0 ? '-' : '', intdiv(abs($cents), 100), abs($cents) % 100);
    }

    public function __toString(): string
    {
        return self::format($this->cents);
    }
}
