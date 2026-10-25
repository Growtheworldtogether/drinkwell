<?php

use App\Enums\AssignmentStatus;
use App\Enums\HardwareState;
use App\Enums\SettlementStatus;
use App\Enums\Tier;
use App\Support\DbCheck;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('partner_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->restrictOnDelete();
            $table->date('date_of_birth');
            $table->unsignedTinyInteger('age_at_signup');
            $table->string('phone', 20);
            $table->string('city', 80)->nullable();
            // Simulated identity check. No document is collected.
            $table->enum('kyc_status', ['pending', 'simulated_verified'])->default('pending');
            $table->timestamp('kyc_verified_at')->nullable();
            // True for the fake co-partners the pairing step hands out.
            $table->boolean('is_simulated')->default(false);
            $table->timestamps();
        });
        DbCheck::add('partner_profiles', 'chk_partner_age', '`age_at_signup` >= 18');

        Schema::create('vending_machines', function (Blueprint $table) {
            $table->id();
            $table->string('serial_no', 24)->unique();
            $table->enum('tier', array_column(Tier::cases(), 'value'));
            $table->unsignedSmallInteger('capacity');
            $table->string('facility_name', 120);
            $table->string('facility_type', 40);
            $table->string('city', 80);
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->unsignedSmallInteger('stock');
            $table->decimal('temp_c', 4, 1)->default(3.5);
            $table->enum('hardware_state', array_column(HardwareState::cases(), 'value'))->default(HardwareState::Online->value);
            $table->enum('assignment_status', array_column(AssignmentStatus::cases(), 'value'))->default(AssignmentStatus::Open->value);
            // The machine's own clock: a business date and the minute of that day (0 to 1439).
            $table->date('operating_date');
            $table->unsignedSmallInteger('day_minute')->default(0);
            $table->timestamp('clock_synced_at')->nullable();
            $table->timestamps();

            $table->index(['assignment_status', 'tier']);
            $table->index('hardware_state');
        });
        DbCheck::add('vending_machines', 'chk_machine_capacity', '`capacity` IN (160, 347)');
        DbCheck::add('vending_machines', 'chk_machine_stock', '`stock` <= `capacity`');
        DbCheck::add('vending_machines', 'chk_machine_minute', '`day_minute` BETWEEN 0 AND 1439');

        Schema::create('machine_ownerships', function (Blueprint $table) {
            $table->id();
            $table->foreignId('machine_id')->constrained('vending_machines')->restrictOnDelete();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->unsignedTinyInteger('slot_no');
            $table->unsignedInteger('capital_cents');
            $table->enum('settlement_status', array_column(SettlementStatus::cases(), 'value'))->default(SettlementStatus::PendingSettlement->value);
            // Earning starts on this business date.
            $table->date('earning_from');
            $table->timestamp('settled_at')->nullable();
            $table->timestamps();

            $table->unique(['machine_id', 'slot_no']);
            $table->unique(['machine_id', 'user_id']);
            $table->index(['user_id', 'settlement_status']);
        });
        DbCheck::add('machine_ownerships', 'chk_slot_no', '`slot_no` IN (1, 2)');

        // One row per machine per business day: the settled day.
        Schema::create('machine_days', function (Blueprint $table) {
            $table->id();
            $table->foreignId('machine_id')->constrained('vending_machines')->restrictOnDelete();
            $table->date('operating_date');
            $table->unsignedSmallInteger('units_sold');
            $table->unsignedInteger('gross_cents');
            $table->unsignedInteger('fee_cents');
            $table->unsignedInteger('pool_cents');
            $table->timestamp('created_at')->nullable();

            $table->unique(['machine_id', 'operating_date']);
        });
        DbCheck::add('machine_days', 'chk_day_pool', '`pool_cents` = `gross_cents` - `fee_cents` AND `pool_cents` % 2 = 0');

        // One payout per owner per machine per day. The unique key is the duplicate-payout guard.
        Schema::create('daily_distributions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('machine_day_id')->constrained('machine_days')->restrictOnDelete();
            $table->foreignId('machine_id')->constrained('vending_machines')->restrictOnDelete();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('ownership_id')->constrained('machine_ownerships')->restrictOnDelete();
            $table->date('distribution_date');
            $table->unsignedInteger('payout_cents');
            $table->string('ach_ref', 32)->unique();
            $table->timestamp('created_at')->nullable();

            $table->unique(['machine_id', 'user_id', 'distribution_date'], 'uniq_payout_per_day');
            $table->index(['user_id', 'distribution_date']);
        });

        Schema::create('telemetry_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('machine_id')->constrained('vending_machines')->cascadeOnDelete();
            $table->string('event', 32);
            $table->string('message', 190);
            $table->json('context')->nullable();
            $table->timestamp('logged_at');

            $table->index(['machine_id', 'logged_at']);
        });
    }

    public function down(): void
    {
        foreach (['telemetry_logs', 'daily_distributions', 'machine_days', 'machine_ownerships', 'vending_machines', 'partner_profiles'] as $t) {
            Schema::dropIfExists($t);
        }
    }
};
