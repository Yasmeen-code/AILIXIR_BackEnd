<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('screening_results', function (Blueprint $table) {
            $table->string('status')->default('pending');
        });

        Schema::table('target_lookups', function (Blueprint $table) {
            $table->string('status')->default('pending');
        });
    }

    public function down(): void
    {
        Schema::table('screening_results', function (Blueprint $table) {
            $table->dropColumn('status');
        });

        Schema::table('target_lookups', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }
};
