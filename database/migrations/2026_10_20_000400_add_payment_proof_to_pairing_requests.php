<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pairing_requests', function (Blueprint $table) {
            $table->string('proof_path')->nullable();
            $table->string('proof_name', 190)->nullable();
            $table->date('proof_sent_on')->nullable();
            $table->string('proof_note', 255)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('pairing_requests', function (Blueprint $table) {
            $table->dropColumn(['proof_path', 'proof_name', 'proof_sent_on', 'proof_note']);
        });
    }
};
