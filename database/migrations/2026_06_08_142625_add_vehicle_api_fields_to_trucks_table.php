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
        Schema::table('trucks', function (Blueprint $table) {
            $table->string('registration_country', 2)->nullable()->after('plate');
            $table->string('carriage_type', 30)->nullable()->after('registration_country');
            $table->string('weight_type', 10)->default('HEAVY')->after('carriage_type');
            $table->string('api_vehicle_id')->nullable()->after('weight_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('trucks', function (Blueprint $table) {
            $table->dropColumn(['registration_country', 'carriage_type', 'weight_type', 'api_vehicle_id']);
        });
    }
};
