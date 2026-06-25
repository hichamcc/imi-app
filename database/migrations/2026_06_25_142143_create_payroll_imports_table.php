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
        Schema::create('payroll_imports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('original_filename');
            $table->string('stored_path');
            $table->string('account_number', 100)->nullable();
            $table->string('currency', 10)->nullable();
            $table->date('payroll_month');
            $table->boolean('is_payroll')->default(true);
            $table->string('status', 30)->default('pending'); // pending | reviewed | generated
            $table->unsignedInteger('total_rows')->default(0);
            $table->unsignedInteger('payslips_generated')->default(0);
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payroll_imports');
    }
};
