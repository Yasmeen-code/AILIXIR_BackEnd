<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('docking_jobs', function (Blueprint $table) {
            $table->string('ligand_path')->nullable()->change();
            $table->json('vina_scores')->nullable()->after('result_data');

            $table->index(['user_id', 'input_type', 'protein_name', 'created_at'], 'docking_jobs_list_idx');
            $table->index(['user_id', 'status', 'created_at'], 'docking_jobs_status_idx');
        });
    }

    public function down(): void
    {
        Schema::table('docking_jobs', function (Blueprint $table) {
            $table->string('ligand_path')->nullable(false)->change();
            $table->dropColumn('vina_scores');
            $table->dropIndex('docking_jobs_list_idx');
            $table->dropIndex('docking_jobs_status_idx');
        });
    }
};
