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
        Schema::table('screening_results', function (Blueprint $table) {
            $table->string('status')->default('pending')->after('user_id');
            $table->json('output')->nullable()->change();
        });

        Schema::table('target_lookups', function (Blueprint $table) {
            $table->string('status')->default('pending')->after('user_id');
            $table->json('output')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('screening_results', function (Blueprint $table) {
            $table->dropColumn('status');
            $table->json('output')->nullable(false)->change();
        });

        Schema::table('target_lookups', function (Blueprint $table) {
            $table->dropColumn('status');
            $table->json('output')->nullable(false)->change();
        });
    }
};
