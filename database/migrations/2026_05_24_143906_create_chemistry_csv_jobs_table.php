<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chemistry_csv_jobs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('job_id')->unique(); // من الـ AI API
            $table->string('filename');
            $table->enum('analysis_type', ['full', 'quick', 'admet', 'classify']);
            $table->integer('total_rows');
            $table->integer('completed_rows')->default(0);
            $table->integer('failed_rows')->default(0);
            $table->integer('progress_percent')->default(0);
            $table->enum('status', ['queued', 'running', 'done', 'failed'])->default('queued');
            $table->string('result_file_path')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chemistry_csv_jobs');
    }
};
