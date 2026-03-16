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
        Schema::table('docking_jobs', function (Blueprint $table) {
            $table->string('protein_name')->nullable()->after('user_id');
            $table->string('ligand_name')->nullable()->after('protein_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('docking_jobs', function (Blueprint $table) {
            $table->dropColumn(['protein_name', 'ligand_name']);
        });
    }
};
