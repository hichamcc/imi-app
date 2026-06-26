<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('payslips', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('person_id')->constrained('persons')->cascadeOnDelete();
            $table->foreignId('payroll_import_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('payroll_import_row_id')->nullable()->constrained('payroll_import_rows')->nullOnDelete();

            // Snapshots so the payslip is stable even if Person/company data changes later
            $table->string('employee_name');
            $table->string('position', 100)->nullable();
            $table->string('bank_iban', 50)->nullable();
            $table->string('bank_swift', 20)->nullable();

            $table->date('payroll_month');
            $table->date('payment_date');
            $table->string('currency', 10)->default('EUR');

            $table->decimal('transfer_amount', 14, 2);
            $table->decimal('gross_salary', 14, 2);
            $table->decimal('per_diem', 14, 2);
            $table->decimal('income_tax', 14, 2)->default(0);
            $table->decimal('social_insurance', 14, 2)->default(0);
            $table->decimal('ghs', 14, 2)->default(0);
            $table->decimal('other_deductions', 14, 2)->default(0);
            $table->decimal('net_salary', 14, 2);

            $table->string('pdf_path')->nullable();
            $table->timestamp('pdf_generated_at')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payslips');
    }
};
