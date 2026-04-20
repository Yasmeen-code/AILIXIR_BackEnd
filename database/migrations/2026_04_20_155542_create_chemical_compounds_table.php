<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chemical_compounds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_id')->constrained('chemical_search_jobs')->onDelete('cascade');

            $table->integer('rank');
            $table->string('smiles');
            $table->string('name')->nullable();
            $table->string('cid')->nullable();
            $table->decimal('similarity', 5, 4)->nullable();
            $table->text('explanation')->nullable();
            $table->string('image_url')->nullable();

            $table->timestamps();

            $table->index(['job_id', 'rank']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chemical_compounds');
    }
};
