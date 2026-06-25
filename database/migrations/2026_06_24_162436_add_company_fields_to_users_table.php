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
        Schema::table('users', function (Blueprint $table) {
            $table->string('company_name')->nullable()->after('applicable_law');
            $table->string('company_registration', 100)->nullable()->after('company_name');
            $table->string('company_address_line')->nullable()->after('company_registration');
            $table->string('company_post_code', 20)->nullable()->after('company_address_line');
            $table->string('company_city', 100)->nullable()->after('company_post_code');
            $table->string('company_country', 100)->nullable()->after('company_city');
            $table->string('company_phone', 50)->nullable()->after('company_country');
            $table->string('company_email')->nullable()->after('company_phone');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'company_name', 'company_registration', 'company_address_line',
                'company_post_code', 'company_city', 'company_country',
                'company_phone', 'company_email',
            ]);
        });
    }
};
