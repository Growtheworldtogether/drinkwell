<?php

namespace App\Console\Commands;

use App\Models\OtpCode;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('drinkwell:purge')]
#[Description('Delete expired one-time codes')]
class PurgeCommand extends Command
{
    public function handle(): int
    {
        // A code is kept for an hour after it expires because the hourly cap counts rows (SPEC 5.2).
        $codes = OtpCode::query()->where('expires_at', '<', now()->subHour())->delete();

        $this->components->info("Purged {$codes} expired codes.");

        return self::SUCCESS;
    }
}
