<?php

namespace App\Domain;

/**
 * xorshift32 seeded from CRC-32 of a string key (SPEC 7.3). Deterministic and
 * identical to the Python reference. Never replace with rand() in the money path.
 */
final class Prng
{
    private int $x;

    public function __construct(string $seedKey)
    {
        $s = crc32($seedKey) & 0xFFFFFFFF;
        $this->x = $s === 0 ? 0x9E3779B9 : $s;

        for ($i = 0; $i < 4; $i++) {
            $this->next();
        }
    }

    public function next(): int
    {
        $x = $this->x;
        $x ^= ($x << 13) & 0xFFFFFFFF;
        $x ^= $x >> 17;
        $x ^= ($x << 5) & 0xFFFFFFFF;

        return $this->x = $x;
    }

    /** Uniform in [0, 1). */
    public function float(): float
    {
        return $this->next() / 4294967296.0;
    }

    /** Uniform integer in [0, $n). */
    public function below(int $n): int
    {
        return (int) floor($this->float() * $n);
    }
}
