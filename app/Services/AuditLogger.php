<?php

namespace App\Services;

use App\Models\AdminAuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Append-only writer for admin_audit_logs. Every admin mutation calls this
 * (SPEC 9.4). Rows are never updated or deleted.
 */
class AuditLogger
{
    /**
     * @param  Model|null  $subject  The row acted on. A model with a non-integer key (settings) is logged
     *                               by type only; put its key in $before and $after.
     * @param  array<string, mixed>|null  $before
     * @param  array<string, mixed>|null  $after
     */
    public static function log(User $admin, string $action, ?Model $subject = null, ?array $before = null, ?array $after = null): AdminAuditLog
    {
        $key = $subject?->getKey();

        return AdminAuditLog::create([
            'admin_id' => $admin->id,
            'action' => $action,
            'subject_type' => $subject ? Str::snake(class_basename($subject)) : null,
            'subject_id' => is_int($key) ? $key : null,
            'before' => $before,
            'after' => $after,
            'ip' => request()->ip() ?? 'cli',
        ]);
    }
}
