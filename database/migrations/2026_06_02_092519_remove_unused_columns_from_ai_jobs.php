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
        Schema::table('ai_jobs', function (Blueprint $table) {
            // Remove the 'parameters' and 'preview' columns as they are no longer needed
            $table->dropColumn(['parameters', 'preview']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ai_jobs', function (Blueprint $table) {
            // Re-add the 'parameters' and 'preview' columns if the migration is rolled back
            $table->json('parameters')->nullable();
            $table->json('preview')->nullable();
        });
    }
};
