<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plans', function (
            Blueprint $table
        ) {

            $table->id();

            $table->string('name');

            $table->enum('type', ['free', 'pro', 'max'])->default('free');

            $table->enum('billing_period', ['month', 'year'])->nullable();

            $table->decimal('price', 10, 2);

            $table->string('currency')->default('usd');

            $table->string('stripe_price_id')->nullable();

            $table->boolean('is_active')->default(true);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plans');
    }
};
