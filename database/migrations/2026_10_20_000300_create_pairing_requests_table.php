<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // One row per member who asked to be paired. An administrator fills in the co-partner's payment details.
        Schema::create('pairing_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->restrictOnDelete();
            $table->string('tier', 24);
            $table->string('status', 16)->default('finding')->index();
            $table->string('partner_name', 120)->nullable();
            $table->string('partner_city', 80)->nullable();
            $table->string('account_holder', 120)->nullable();
            $table->string('bank_name', 120)->nullable();
            $table->text('routing_number')->nullable();
            $table->text('account_number')->nullable();
            $table->string('account_last4', 4)->nullable();
            $table->string('reference', 40)->nullable();
            $table->string('note', 255)->nullable();
            $table->timestamp('paired_at')->nullable();
            $table->timestamp('deposit_reported_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pairing_requests');
    }
};
