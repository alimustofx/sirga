<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payroll_periods', function (Blueprint $table) {
            $table->id();
            $table->unsignedTinyInteger('period_month');
            $table->unsignedSmallInteger('period_year');
            $table->string('status', 16)->default('draft')->index(); // draft | review | published | closed
            $table->text('notes')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['period_month', 'period_year']);
        });

        Schema::create('earning_components', function (Blueprint $table) {
            $table->id();
            $table->string('code', 96)->unique(); // e:<key> | d:<key>
            $table->string('name');
            $table->string('component_type', 24); // earning | official_deduction
            $table->string('applicable_status', 8)->default('all'); // all | PNS | PPPK
            $table->unsignedInteger('display_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('school_deduction_types', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('default_amount', 15, 2)->default(0);
            $table->boolean('is_recurring')->default(true);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('display_order')->default(0);
            $table->timestamps();
        });

        Schema::create('period_deduction_configs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payroll_period_id')->constrained()->cascadeOnDelete();
            $table->foreignId('school_deduction_type_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('default_amount', 15, 2)->default(0);
            $table->unsignedInteger('display_order')->default(0);
            $table->boolean('is_special')->default(false);
            $table->timestamps();
        });

        Schema::create('import_batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payroll_period_id')->constrained()->cascadeOnDelete();
            $table->string('employment_status', 8);
            $table->string('original_filename');
            $table->string('stored_path');
            $table->string('source_title')->nullable();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedInteger('total_rows')->default(0);
            $table->unsignedInteger('valid_rows')->default(0);
            $table->unsignedInteger('invalid_rows')->default(0);
            $table->string('import_status', 16)->default('previewed'); // previewed | imported | cancelled
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('payroll_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payroll_period_id')->constrained()->restrictOnDelete();
            $table->foreignId('employee_id')->constrained()->restrictOnDelete();
            $table->decimal('gross_salary', 15, 2)->default(0);
            $table->decimal('official_deductions_total', 15, 2)->default(0);
            $table->decimal('net_salary', 15, 2)->default(0);
            $table->decimal('school_deductions_total', 15, 2)->default(0);
            $table->decimal('take_home_pay', 15, 2)->default(0);
            $table->foreignId('source_file_id')->nullable()->constrained('import_batches')->nullOnDelete();
            $table->string('validation_status', 16)->default('valid'); // valid | mismatch | copied | invalid
            $table->decimal('source_gross', 15, 2)->nullable();
            $table->decimal('source_official_deductions', 15, 2)->nullable();
            $table->decimal('source_net', 15, 2)->nullable();
            $table->text('validation_note')->nullable();
            $table->timestamps();
            $table->unique(['payroll_period_id', 'employee_id']);
            $table->index(['employee_id', 'payroll_period_id']);
        });

        Schema::create('payroll_record_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payroll_record_id')->constrained()->cascadeOnDelete();
            $table->foreignId('earning_component_id')->nullable()->constrained()->nullOnDelete();
            $table->string('component_name_snapshot');
            $table->string('component_type', 24);
            $table->decimal('amount', 15, 2)->default(0);
            $table->decimal('source_value', 15, 2)->nullable();
            $table->unsignedInteger('display_order')->default(0);
            $table->timestamps();
            $table->index('payroll_record_id');
        });

        Schema::create('payroll_deductions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payroll_record_id')->constrained()->cascadeOnDelete();
            $table->foreignId('school_deduction_type_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('period_deduction_config_id')->nullable()->constrained()->nullOnDelete();
            $table->string('deduction_name_snapshot');
            $table->decimal('amount', 15, 2)->default(0);
            $table->string('description')->nullable();
            $table->unsignedInteger('display_order')->default(0);
            $table->timestamps();
            $table->index('payroll_record_id');
        });

        Schema::create('payroll_record_revisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payroll_record_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('version');
            $table->json('snapshot');
            $table->text('reason')->nullable();
            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('import_rows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('import_batch_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('row_number');
            $table->string('nip', 64)->nullable();
            $table->string('name')->nullable();
            $table->string('status', 12)->default('valid'); // valid | error | skipped
            $table->json('data');
        });

        Schema::create('import_errors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('import_batch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('import_row_id')->nullable()->constrained()->cascadeOnDelete();
            $table->unsignedInteger('row_number')->default(0);
            $table->string('nip', 64)->nullable();
            $table->string('error_type', 32);
            $table->text('error_message');
            $table->boolean('is_blocking')->default(true);
            $table->timestamp('resolved_at')->nullable();
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payroll_record_id')->unique()->constrained()->restrictOnDelete();
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->string('status', 24)->default('belum_dibayar')->index();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('rejection_reason')->nullable();
            $table->timestamps();
        });

        Schema::create('payment_proofs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_id')->constrained()->cascadeOnDelete();
            $table->string('file_path');
            $table->string('original_filename');
            $table->string('mime_type', 64);
            $table->unsignedInteger('file_size');
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('uploaded_at');
            $table->string('verification_status', 16)->default('pending'); // pending | approved | rejected
            $table->text('rejection_reason')->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        foreach (['payment_proofs', 'payments', 'import_errors', 'import_rows', 'payroll_record_revisions', 'payroll_deductions', 'payroll_record_items', 'payroll_records', 'import_batches', 'period_deduction_configs', 'school_deduction_types', 'earning_components', 'payroll_periods'] as $t) {
            Schema::dropIfExists($t);
        }
    }
};
