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
        Schema::create('admets', function (Blueprint $table) {
            $table->id();
            $table->string('smiles')->unique();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->float('absorption')->nullable();
            $table->float('distribution')->nullable();
            $table->float('metabolism')->nullable();
            $table->float('excretion')->nullable();
            $table->float('toxicity')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('admets');
    }
};
