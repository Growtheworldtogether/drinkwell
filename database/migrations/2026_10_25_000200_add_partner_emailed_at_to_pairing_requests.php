<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Deposit style 2: when the member marked "I have emailed my co-partner" on their progress page. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pairing_requests', function (Blueprint $table) {
            $table->timestamp('partner_emailed_at')->nullable()->after('instructions_read_at');
        });
    }

    public function down(): void
    {
        Schema::table('pairing_requests', function (Blueprint $table) {
            $table->dropColumn('partner_emailed_at');
        });
    }
};
