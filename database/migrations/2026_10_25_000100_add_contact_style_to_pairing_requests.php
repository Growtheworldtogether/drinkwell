<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Deposit style 2 ("contact your co-partner"): the co-partner's email on each pairing, and when the member read the payment instruction. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pairing_requests', function (Blueprint $table) {
            $table->string('partner_email', 190)->nullable()->after('partner_city');
            $table->timestamp('instructions_read_at')->nullable()->after('deposit_reported_at');
        });
    }

    public function down(): void
    {
        Schema::table('pairing_requests', function (Blueprint $table) {
            $table->dropColumn(['partner_email', 'instructions_read_at']);
        });
    }
};
