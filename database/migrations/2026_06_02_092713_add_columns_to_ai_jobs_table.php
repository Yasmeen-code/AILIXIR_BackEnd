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
            // add columns "preset" , "num_molecules", "return_top_k", "docking_mode", "dock_top_k" , "summary", "files"  and indexes "status", ["user_id", "job_id"]
            $table->string('preset')->default("egfr_generator")->after('status');
            $table->integer('num_molecules')->after('preset');
            $table->integer('return_top_k')->after('num_molecules');
            $table->enum('docking_mode', ['top_k', 'off', 'all'])->after('return_top_k');
            $table->integer('dock_top_k')->after('docking_mode');
            $table->json('summary')->nullable()->after('dock_top_k');
            $table->json('files')->nullable()->after('summary');

            $table->index(['user_id', 'job_id']);
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ai_jobs', function (Blueprint $table) {
            /* drop columns "preset", "num_molecules", "return_top_k", "docking_mode", "dock_top_k" , "summary", "files"  and indexes "status", ["user_id", "job_id"] */
            $table->dropColumn('preset');
            $table->dropColumn('num_molecules');
            $table->dropColumn('return_top_k');
            $table->dropColumn('docking_mode');
            $table->dropColumn('dock_top_k');
            $table->dropColumn('summary');
            $table->dropColumn('files');

            $table->dropIndex(['user_id', 'job_id']);
            $table->dropIndex('status');
        });
    }
};
