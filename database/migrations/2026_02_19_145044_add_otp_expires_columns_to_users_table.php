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
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('email_verification_otp_expires_at')->nullable()->after('email_verification_otp');
            $table->timestamp('password_reset_otp_expires_at')->nullable()->after('password_reset_otp');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'email_verification_otp_expires_at',
                'password_reset_otp_expires_at',
            ]);
        });
    }
};
