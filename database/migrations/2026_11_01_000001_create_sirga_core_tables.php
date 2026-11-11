<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->unique()->constrained()->nullOnDelete();
            $table->string('nip', 32)->unique();
            $table->string('full_name');
            $table->string('academic_title')->nullable();
            $table->string('employment_status', 8)->index(); // PNS | PPPK
            $table->string('rank', 32)->nullable();
            $table->string('position')->nullable();
            $table->string('unit')->nullable();
            $table->string('bank_account_number', 64)->nullable();
            $table->string('npwp', 32)->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });

        Schema::create('school_settings', function (Blueprint $table) {
            $table->id();
            $table->string('school_name')->default('SMA Negeri 1 Turen');
            $table->text('school_address')->nullable();
            $table->string('school_logo')->nullable();
            $table->string('school_letterhead')->nullable();
            $table->string('treasurer_name')->nullable();
            $table->string('treasurer_nip', 32)->nullable();
            $table->string('treasurer_signature_qr')->nullable();
            $table->text('default_bank_information')->nullable();
            $table->boolean('show_zero_deductions')->default(true);
            $table->timestamps();
        });

        Schema::create('payment_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('bank_name');
            $table->string('account_number', 64);
            $table->string('account_holder');
            $table->text('payment_instruction')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('announcements', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('content');
            $table->string('target_type', 16)->default('all'); // all | pns | pppk | individual
            $table->unsignedBigInteger('payroll_period_id')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('announcement_recipients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('announcement_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->unique(['announcement_id', 'employee_id']);
        });

        Schema::create('user_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('type', 48);
            $table->string('title');
            $table->text('body')->nullable();
            $table->string('url')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['user_id', 'read_at']);
        });

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action', 64)->index();
            $table->string('entity_type')->nullable();
            $table->unsignedBigInteger('entity_id')->nullable();
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->text('reason')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 255)->nullable();
            $table->timestamp('created_at')->useCurrent()->index();
            $table->index(['entity_type', 'entity_id']);
        });
    }

    public function down(): void
    {
        foreach (['audit_logs', 'user_notifications', 'announcement_recipients', 'announcements', 'payment_accounts', 'school_settings', 'employees'] as $t) {
            Schema::dropIfExists($t);
        }
    }
};
