<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('simulations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            $table->string('protein');
            $table->string('ligand')->nullable();

            $table->enum('status', [
                'pending',
                'preparing',
                'running',
                'completed',
                'failed',
                'cancelled'
            ])->default('pending');

            $table->integer('progress')->default(0);
            $table->text('error_message')->nullable();

            $table->string('trajectory')->nullable();
            $table->string('log_file')->nullable();
            $table->json('analysis')->nullable();

            $table->string('force_field')->default('ff14SB');
            $table->float('temperature')->default(298);
            $table->float('simulation_time_ns')->default(10);
            $table->integer('box_size')->default(12);

            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('simulations');
    }
};
