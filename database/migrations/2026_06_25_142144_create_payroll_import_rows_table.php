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
        Schema::create('payroll_import_rows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payroll_import_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('row_index'); // index within the source file
            $table->date('date')->nullable();
            $table->date('value_date')->nullable();
            $table->text('description')->nullable();
            $table->decimal('debit', 14, 2)->default(0);
            $table->decimal('credit', 14, 2)->default(0);
            $table->decimal('balance', 14, 2)->nullable();
            $table->string('parsed_name')->nullable();
            $table->string('reference', 100)->nullable(); // e.g. HB260505070267
            $table->boolean('is_payroll')->default(false);
            $table->boolean('looks_like_payroll')->default(false); // heuristic flag for default-check
            $table->foreignId('matched_person_id')->nullable()->constrained('persons')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payroll_import_rows');
    }
};
