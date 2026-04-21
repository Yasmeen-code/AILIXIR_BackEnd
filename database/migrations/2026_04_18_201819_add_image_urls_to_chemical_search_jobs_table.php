<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('chemical_search_jobs', function (Blueprint $table) {
            $table->json('image_urls')->nullable()->after('results');
        });
    }

    public function down(): void
    {
        Schema::table('chemical_search_jobs', function (Blueprint $table) {
            $table->dropColumn('image_urls');
        });
    }
};
