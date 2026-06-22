<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('md_simulation_jobs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('remote_job_id');
            $table->string('status')->default('pending');
            $table->json('input_params')->nullable();
            $table->string('protein_original_name');
            $table->string('ligand_original_name');
            $table->json('result_meta')->nullable();
            $table->json('analysis_meta')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->index('remote_job_id');
            $table->index(['user_id', 'status']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('md_simulation_jobs');
    }
};
