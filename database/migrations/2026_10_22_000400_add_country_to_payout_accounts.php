<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Members can have a US or a Canadian bank account. Existing accounts are US. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payout_accounts', function (Blueprint $table) {
            $table->string('country', 2)->default('US')->after('user_id');
        });
    }

    public function down(): void
    {
        Schema::table('payout_accounts', fn (Blueprint $table) => $table->dropColumn('country'));
    }
};
