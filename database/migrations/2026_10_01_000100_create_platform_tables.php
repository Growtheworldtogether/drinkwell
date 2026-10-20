<?php

use App\Enums\OtpPurpose;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->string('key', 64)->primary();
            $table->json('value');
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('updated_at')->nullable();
        });

        // Append-only: no code path updates or deletes a row here.
        Schema::create('admin_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('admin_id')->constrained('users')->restrictOnDelete();
            $table->string('action', 64);
            $table->string('subject_type', 64)->nullable();
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->json('before')->nullable();
            $table->json('after')->nullable();
            $table->string('ip', 45);
            $table->timestamp('created_at')->nullable();

            $table->index(['action', 'created_at']);
            $table->index(['subject_type', 'subject_id']);
        });

        Schema::create('mail_log', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('to_email');
            $table->string('template', 64);
            $table->string('subject');
            // "simulated" is a message to a simulated co-partner. It is logged, never sent.
            $table->enum('status', ['sent', 'failed', 'simulated']);
            $table->text('error')->nullable();
            $table->longText('payload')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index(['status', 'created_at']);
        });

        Schema::create('otp_codes', function (Blueprint $table) {
            $table->id();
            $table->string('email', 190);
            $table->enum('purpose', array_column(OtpPurpose::cases(), 'value'));
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->char('code_hash', 64);
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->timestamp('expires_at');
            $table->timestamp('consumed_at')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index(['email', 'purpose', 'created_at']);
        });

        Schema::create('payout_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->string('holder_name');
            $table->string('bank_name');
            $table->text('routing_number');
            $table->text('account_number');
            $table->char('last4', 4);
            $table->boolean('is_default')->default(false);
            $table->timestamps();

            $table->index('user_id');
        });
    }

    public function down(): void
    {
        foreach (['payout_accounts', 'otp_codes', 'mail_log', 'admin_audit_logs', 'settings'] as $t) {
            Schema::dropIfExists($t);
        }
    }
};
