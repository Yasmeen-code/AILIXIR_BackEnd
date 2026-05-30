<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chemistry_analyses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('chemistry_thread_id')->nullable()->constrained()->onDelete('set null');
            $table->enum('type', ['smiles', 'compare', 'docking', 'chat']);
            $table->text('input_data'); // SMILES, docking data, أو chat message
            $table->longText('response')->nullable();
            $table->json('properties')->nullable(); // MW, LogP, HBD, etc
            $table->json('drug_likeness')->nullable(); // Lipinski, Veber, etc
            $table->json('admet')->nullable();
            $table->integer('processing_time_ms')->nullable();
            $table->enum('status', ['pending', 'success', 'failed'])->default('pending');
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'type', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chemistry_analyses');
    }
};
