<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('chemistry_csv_jobs', function (Blueprint $table) {
            $table->foreignId('chemistry_thread_id')
                ->nullable()
                ->after('user_id')
                ->constrained('chemistry_threads')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('chemistry_csv_jobs', function (Blueprint $table) {
            $table->dropForeign(['chemistry_thread_id']);
            $table->dropColumn('chemistry_thread_id');
        });
    }
};
