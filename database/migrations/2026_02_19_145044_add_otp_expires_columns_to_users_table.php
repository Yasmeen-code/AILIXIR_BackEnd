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
            // أعمدة OTP مع وقت الانتهاء
            $table->timestamp('email_verification_otp_expires_at')->nullable()->after('email_verification_otp');
            $table->timestamp('password_reset_otp_expires_at')->nullable()->after('password_reset_otp');
            
            // تعديل أعمدة OTP لتكون string بدلاً من integer (أفضل للتخزين)
            // ملاحظة: إذا كانت الأعمدة موجودة بالفعل، علقِ هذا السطر أو استخدم change()
            // $table->string('email_verification_otp', 6)->nullable()->change();
            // $table->string('password_reset_otp', 6)->nullable()->change();
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