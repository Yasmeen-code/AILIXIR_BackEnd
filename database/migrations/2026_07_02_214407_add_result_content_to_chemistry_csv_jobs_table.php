<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('chemistry_csv_jobs', function (Blueprint $table) {
            $table->longText('result_content')->nullable()->after('result_file_path');
        });
    }

    public function down(): void
    {
        Schema::table('chemistry_csv_jobs', function (Blueprint $table) {
            $table->dropColumn('result_content');
        });
    }
};
