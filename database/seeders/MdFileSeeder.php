<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\MdFile;
use Cloudinary\Cloudinary;

class MdFileSeeder extends Seeder
{
    private array $fileMapping = [
        // PDB
        'SYS.pdb' => 'SYS_pdb',
        'SYS_nw.pdb' => 'SYS_nw_pdb',
        'ligand_H.pdb' => 'ligand_H_pdb',
        'ligand_gaff.pdb' => 'ligand_gaff_pdb',
        'ligand_h(1).pdb' => 'ligand_h_1_pdb',
        'ligand_h_nonprot.pdb' => 'ligand_h_nonprot_pdb',
        'protein_ligand.pdb' => 'protein_ligand_pdb',
        'prot_lig_equil.pdb' => 'prot_lig_equil_pdb',
        'starting_end.pdb' => 'starting_end_pdb',

        // DCD
        'prot_lig_equil.dcd' => 'prot_lig_equil_dcd',
        'prot_lig_prod1-1_nw.dcd' => 'prot_lig_prod1_1_nw_dcd',

        // CRD
        'SYS_gaff2.crd' => 'SYS_gaff2_crd',
        'SYS_nw.crd' => 'SYS_nw_crd',

        // PRMTOP
        'SYS_gaff2.prmtop' => 'SYS_gaff2_prmtop',
        'SYS_nw.prmtop' => 'SYS_nw_prmtop',

        // RST
        'prot_lig_equil.rst' => 'prot_lig_equil_rst',

        // Input
        'prepareforleap.in' => 'prepareforleap_in',
        'tleap.in' => 'tleap_in',
        'lig.lib' => 'lig_lib',
        'ligand.frcmod' => 'ligand_frcmod',
        'ligand.mol2' => 'ligand_mol2',
        'ligand_h_renum.txt' => 'ligand_h_renum_txt',
        'ligand_h_sslink' => 'ligand_h_sslink',

        // Log
        'prot_lig_equil.log' => 'prot_lig_equil_log',

        // CSV
        '2D_rmsd.csv' => '_2D_rmsd_csv',
        'Interaction_energy_eelec.csv' => 'Interaction_energy_eelec_csv',
        'Interaction_energy_evdw.csv' => 'Interaction_energy_evdw_csv',
        'PC1.csv' => 'PC1_csv',
        'PC2.csv' => 'PC2_csv',
        'cross_correlation.csv' => 'cross_correlation_csv',
        'distance.csv' => 'distance_csv',
        'distance_select.csv' => 'distance_select_csv',
        'radius_gyration.csv' => 'radius_gyration_csv',
        'rmsd_ca.csv' => 'rmsd_ca_csv',
        'rmsf_ca.csv' => 'rmsf_ca_csv',

        // PNG
        '2D_rmsd.png' => '_2D_rmsd_png',
        'Interaction_energy.png' => 'Interaction_energy_png',
        'PCA.png' => 'PCA_png',
        'PCA_dist.png' => 'PCA_dist_png',
        'cross_correlation.png' => 'cross_correlation_png',
        'distance.png' => 'distance_png',
        'distance_select.png' => 'distance_select_png',
        'radius_gyration.png' => 'radius_gyration_png',
        'radius_gyration_dist.png' => 'radius_gyration_dist_png',
        'rmsd_ca.png' => 'rmsd_ca_png',
        'rmsd_dist.png' => 'rmsd_dist_png',
        'rmsf_ca.png' => 'rmsf_ca_png',

        // HTML
        'Interaction.html' => 'Interaction_html',
        'initial.html' => 'initial_html',

        // DAT
        'FINAL_RESULTS_MMPBSA.dat' => 'FINAL_RESULTS_MMPBSA_dat',
    ];

    public function run(): void
    {
        // التحقق من إعدادات Cloudinary
        if (!config('cloudinary.cloud_url') && !env('CLOUDINARY_API_SECRET')) {
            $this->command->error('Cloudinary not configured! Check CLOUDINARY_URL or CLOUDINARY_API_SECRET');
            return;
        }

        $cloudinary = new Cloudinary([
            'cloud' => [
                'cloud_name' => env('CLOUDINARY_CLOUD_NAME'),
                'api_key'    => env('CLOUDINARY_API_KEY'),
                'api_secret' => env('CLOUDINARY_API_SECRET'),
            ],
            'url' => ['secure' => true]
        ]);

        $sourcePath = $this->command->ask('Enter path to MD files folder', 'E:/MD_DRIVE_FILES');

        $mdFile = MdFile::firstOrCreate(
            ['experiment_name' => 'default_run'],
            ['description' => 'Uploaded on ' . now()->format('Y-m-d H:i:s')]
        );

        $uploaded = 0;
        $failed = [];
        $skipped = 0;

        $this->command->info('🚀 Starting upload to Cloudinary...');
        $this->command->info("Source: {$sourcePath}");

        foreach ($this->fileMapping as $originalName => $columnName) {
            $filePath = $sourcePath . '/' . $originalName;

            if (!file_exists($filePath)) {
                $this->command->warn("⏭️  Not found: {$originalName}");
                $skipped++;
                continue;
            }

            if ($mdFile->$columnName && !$this->command->confirm("{$originalName} exists. Overwrite?", false)) {
                $skipped++;
                continue;
            }

            try {
                $this->command->info("⬆️  Uploading: {$originalName}...");

                $extension = pathinfo($originalName, PATHINFO_EXTENSION);
                $resourceType = in_array(strtolower($extension), ['png', 'jpg', 'jpeg', 'gif'])
                    ? 'image'
                    : 'raw';

                $result = $cloudinary->uploadApi()->upload(
                    $filePath,
                    [
                        'resource_type' => $resourceType,
                        'public_id' => 'md_files/' . pathinfo($originalName, PATHINFO_FILENAME),
                        'use_filename' => true,
                        'unique_filename' => false,
                        'overwrite' => true,
                    ]
                );

                $mdFile->update([$columnName => $result['secure_url']]);

                $this->command->info("✅ Uploaded: {$result['secure_url']}");
                $uploaded++;
            } catch (\Exception $e) {
                $this->command->error("❌ Failed: {$originalName} - " . $e->getMessage());
                $failed[] = $originalName;
            }

            usleep(500000);
        }

        $this->command->newLine();
        $this->command->info("📊 Results: {$uploaded} uploaded, {$skipped} skipped, " . count($failed) . " failed");

        if (!empty($failed)) {
            $this->command->error("Failed files: " . implode(', ', $failed));
        }
    }
}
