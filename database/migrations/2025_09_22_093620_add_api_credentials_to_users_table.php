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
            $table->string('api_key')->nullable()->after('email');
            $table->string('api_operator_id')->nullable()->after('api_key');
            $table->string('api_base_url')->nullable()->after('api_operator_id');
            $table->boolean('is_admin')->default(false)->after('api_base_url');
            $table->boolean('is_active')->default(true)->after('is_admin');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['api_key', 'api_operator_id', 'api_base_url', 'is_admin', 'is_active']);
        });
    }
};
