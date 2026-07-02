<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {

            $table->boolean('subscription_paused')
                ->default(false);

            $table->timestamp('last_payment_at')
                ->nullable();

            $table->timestamp('last_payment_failed_at')
                ->nullable();

            $table->string('subscription_status')
                ->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {

            $table->dropColumn([
                'subscription_paused',
                'last_payment_at',
                'last_payment_failed_at',
                'subscription_status',
            ]);
        });
    }
};
