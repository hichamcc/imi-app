<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Persist manual IMI driver → MAPON driver mappings created from the admin
 * Tacho Activity report's "Accept suggestion" button. Lets us skip name matching
 * for drivers where names don't line up (middle names, transliteration, typos).
 *
 * Scoped by user_id since IMI driver IDs are per-org and MAPON is a single global
 * account — the mapping is "within this org, driver X is MAPON Y".
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('driver_mapon_links', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('imi_driver_id', 64);
            $table->string('mapon_driver_id', 64);
            $table->string('mapon_name', 191)->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'imi_driver_id']);
            $table->index(['user_id', 'mapon_driver_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('driver_mapon_links');
    }
};
