<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\File;

class AdmetsTableSeeder extends Seeder
{
    public function run(): void
    {
        Schema::disableForeignKeyConstraints();
        DB::table('admets')->truncate();
        Schema::enableForeignKeyConstraints();

        $csvFile = database_path('seeders/data/parallel_admet_report.csv');

        if (!File::exists($csvFile)) {
            $this->command->error("CSV file not found at: {$csvFile}");
            return;
        }

        $file = fopen($csvFile, 'r');

        $header = fgetcsv($file);

        $data = [];
        while (($row = fgetcsv($file)) !== false) {
            $data[] = [
                'smiles'       => $row[0],
                'user_id'      => 1,
                'absorption'   => (float) $row[1],
                'distribution' => (float) $row[2],
                'metabolism'   => (float) $row[3],
                'excretion'    => (float) $row[4],
                'toxicity'     => (float) $row[5],
                'created_at'   => now(),
                'updated_at'   => now(),
            ];
        }

        fclose($file);

        if (!empty($data)) {
            DB::table('admets')->insert($data);
            $this->command->info('Successfully imported ' . count($data) . ' records from CSV.');
        } else {
            $this->command->warn('No data found in CSV file.');
        }
    }
}
