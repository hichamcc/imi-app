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
        Schema::create('persons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            // IMI driver field set (mirrors API driver fields)
            $table->string('first_name', 100);
            $table->string('last_name', 100);
            $table->date('date_of_birth')->nullable();
            $table->string('document_type', 30)->nullable();
            $table->string('document_number', 50)->nullable();
            $table->string('document_issuing_country', 2)->nullable();
            $table->string('license_number', 50)->nullable();
            $table->string('address_street')->nullable();
            $table->string('address_post_code', 20)->nullable();
            $table->string('address_city', 100)->nullable();
            $table->string('address_country', 2)->nullable();
            $table->date('contract_start_date')->nullable();
            $table->string('applicable_law', 2)->nullable();

            // HR-only fields
            $table->string('position', 100)->default('Driver');
            $table->string('email')->nullable();
            $table->string('phone', 50)->nullable();
            $table->string('bank_iban', 50)->nullable();
            $table->string('bank_swift', 20)->nullable();
            $table->decimal('monthly_salary', 12, 2)->nullable();

            // Link to IMI/API driver
            $table->string('imi_driver_id')->nullable()->index();
            $table->foreignId('imi_user_id')->nullable()->constrained('users')->nullOnDelete();

            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('persons');
    }
};
