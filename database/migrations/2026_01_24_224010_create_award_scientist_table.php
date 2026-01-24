<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('award_scientist', function (Blueprint $table) {
            $table->id();
            $table->foreignId('award_id')->constrained()->onDelete('cascade');
            $table->foreignId('scientist_id')->constrained()->onDelete('cascade');
            $table->year('year_won')->nullable();
            $table->text('contribution')->nullable();
            $table->timestamps();

            $table->unique(['award_id', 'scientist_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('award_scientist');
    }
};
