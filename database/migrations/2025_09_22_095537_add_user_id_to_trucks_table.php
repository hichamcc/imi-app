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
            // Add user_id column as nullable first
            $table->unsignedBigInteger('user_id')->after('id')->nullable();
        });

        // Assign existing trucks to the first user (or admin user)
        $firstUser = \App\Models\User::first();
        if ($firstUser) {
            \DB::table('trucks')->whereNull('user_id')->update(['user_id' => $firstUser->id]);
        }

        Schema::table('trucks', function (Blueprint $table) {
            // Now make it required and add foreign key constraint
            $table->unsignedBigInteger('user_id')->nullable(false)->change();
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');

            // Since we're adding user_id, we need to make plate unique per user instead of globally
            $table->dropUnique(['plate']);
            $table->unique(['user_id', 'plate']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('trucks', function (Blueprint $table) {
            $table->dropUnique(['user_id', 'plate']);
            $table->unique(['plate']);
            $table->dropForeign(['user_id']);
            $table->dropColumn('user_id');
        });
    }
};
