<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds a long-lived bearer token column used by external integrations (e.g. wttsystem.dk)
 * to authenticate when calling the public truck→driver→declarations lookup endpoint.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('external_api_token', 80)->nullable()->unique()->after('api_operator_id');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('external_api_token');
        });
    }
};
