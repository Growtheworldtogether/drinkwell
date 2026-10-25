<?php

use App\Support\DbCheck;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/** Membership is no longer limited to people 51 and over: any adult (18+) may join. */
return new class extends Migration
{
    public function up(): void
    {
        $driver = DB::getDriverName();
        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            // MariaDB and MySQL 8.0.19+ accept DROP CONSTRAINT; older MySQL 8.0 uses DROP CHECK.
            try {
                DB::statement('ALTER TABLE `partner_profiles` DROP CONSTRAINT `chk_partner_age`');
            } catch (Throwable) {
                try {
                    DB::statement('ALTER TABLE `partner_profiles` DROP CHECK `chk_partner_age`');
                } catch (Throwable) {
                    // No rule there (it never got added): nothing to remove.
                }
            }
            DbCheck::add('partner_profiles', 'chk_partner_age', '`age_at_signup` >= 18');
        }

        // The old default tagline said "Senior"; replace it only if it was never changed.
        DB::table('settings')->where('key', 'brand.tagline')->where('value', json_encode('Senior co-partner vending platform.'))
            ->update(['value' => json_encode('Co-partner vending platform.')]);
        Cache::forget('settings.all');
        Cache::forget('brand');
    }

    public function down(): void
    {
        // Not reversed: members who joined under 51 would break the old rule.
    }
};
