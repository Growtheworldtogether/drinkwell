<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class InviteCode extends Model
{
    protected $fillable = ['code', 'note', 'max_uses', 'uses', 'expires_at', 'revoked_at', 'created_by'];

    protected function casts(): array
    {
        return ['max_uses' => 'integer', 'uses' => 'integer', 'expires_at' => 'datetime', 'revoked_at' => 'datetime'];
    }

    /** A new code like DW-7KQ4-M2XP. Letters and digits that are easy to tell apart when read aloud (no 0/O, 1/I/L). */
    public static function generate(): string
    {
        $alphabet = 'ABCDEFGHJKMNPQRSTUVWXYZ23456789';
        do {
            $part = fn () => implode('', array_map(fn () => $alphabet[random_int(0, strlen($alphabet) - 1)], range(1, 4)));
            $code = 'DW-'.$part().'-'.$part();
        } while (self::query()->where('code', $code)->exists());

        return $code;
    }

    /** What people type: case, spaces and missing dashes do not matter. */
    public static function normalize(string $input): string
    {
        $raw = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $input) ?? '');
        if (str_starts_with($raw, 'DW') && strlen($raw) === 10) {
            return 'DW-'.substr($raw, 2, 4).'-'.substr($raw, 6, 4);
        }

        return strtoupper(trim($input));
    }

    public static function findUsable(string $input): ?self
    {
        $code = self::query()->where('code', self::normalize($input))->first();

        return $code && $code->isUsable() ? $code : null;
    }

    public function isUsable(): bool
    {
        return $this->revoked_at === null
            && ($this->expires_at === null || $this->expires_at->isFuture())
            && ($this->max_uses === null || $this->uses < $this->max_uses);
    }

    public function status(): string
    {
        return match (true) {
            $this->revoked_at !== null => 'Turned off',
            $this->expires_at !== null && ! $this->expires_at->isFuture() => 'Expired',
            $this->max_uses !== null && $this->uses >= $this->max_uses => 'Used up',
            default => 'Active',
        };
    }

    /** Counts one sign-up against the code. Returns false if it was used up a moment ago by someone else. */
    public function consume(): bool
    {
        return (bool) DB::table('invite_codes')->where('id', $this->id)->whereNull('revoked_at')
            ->where(fn ($q) => $q->whereNull('max_uses')->orWhereColumn('uses', '<', 'max_uses'))
            ->increment('uses');
    }

    /** @return HasMany<User, $this> */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
