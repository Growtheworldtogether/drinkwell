<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('withdrawals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->unsignedBigInteger('amount_cents');
            // A copy of the payout account at request time, so later edits never change an old request.
            $table->string('bank_name');
            $table->string('holder_name');
            $table->char('account_last4', 4);
            $table->string('status', 32)->default('awaiting_confirmation');
            $table->char('reference', 16)->unique();
            $table->timestamp('confirmed_at')->nullable();
            $table->foreignId('decided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('decided_at')->nullable();
            $table->text('admin_note')->nullable();
            $table->string('paid_reference', 64)->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
            $table->index(['user_id', 'created_at']);
        });

        // The code table lists its purposes in a fixed enum. A plain string lets "withdrawal" in without a column rebuild later.
        Schema::table('otp_codes', function (Blueprint $table) {
            $table->string('purpose', 32)->change();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('withdrawals');
    }
};
