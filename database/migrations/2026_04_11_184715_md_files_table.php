<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('md_files', function (Blueprint $table) {
            $table->id();
            $table->string('experiment_name')->default('default_run');
            $table->string('description')->nullable();

            // === PDB Files ===
            $table->string('SYS_pdb')->nullable();
            $table->string('SYS_nw_pdb')->nullable();
            $table->string('ligand_H_pdb')->nullable();
            $table->string('ligand_gaff_pdb')->nullable();
            $table->string('ligand_h_1_pdb')->nullable();
            $table->string('ligand_h_nonprot_pdb')->nullable();
            $table->string('protein_ligand_pdb')->nullable();
            $table->string('prot_lig_equil_pdb')->nullable();
            $table->string('starting_end_pdb')->nullable();

            // === DCD Files ===
            $table->string('prot_lig_equil_dcd')->nullable();
            $table->string('prot_lig_prod1_1_nw_dcd')->nullable();

            // === CRD Files ===
            $table->string('SYS_gaff2_crd')->nullable();
            $table->string('SYS_nw_crd')->nullable();

            // === PRMTOP Files ===
            $table->string('SYS_gaff2_prmtop')->nullable();
            $table->string('SYS_nw_prmtop')->nullable();

            // === RST Files ===
            $table->string('prot_lig_equil_rst')->nullable();

            // === Input Files ===
            $table->string('prepareforleap_in')->nullable();
            $table->string('tleap_in')->nullable();
            $table->string('lig_lib')->nullable();
            $table->string('ligand_frcmod')->nullable();
            $table->string('ligand_mol2')->nullable();
            $table->string('ligand_h_renum_txt')->nullable();
            $table->string('ligand_h_sslink')->nullable();

            // === Log Files ===
            $table->string('prot_lig_equil_log')->nullable();

            // === CSV Files ===
            $table->string('_2D_rmsd_csv')->nullable();
            $table->string('Interaction_energy_eelec_csv')->nullable();
            $table->string('Interaction_energy_evdw_csv')->nullable();
            $table->string('PC1_csv')->nullable();
            $table->string('PC2_csv')->nullable();
            $table->string('cross_correlation_csv')->nullable();
            $table->string('distance_csv')->nullable();
            $table->string('distance_select_csv')->nullable();
            $table->string('radius_gyration_csv')->nullable();
            $table->string('rmsd_ca_csv')->nullable();
            $table->string('rmsf_ca_csv')->nullable();

            // === PNG Files ===
            $table->string('_2D_rmsd_png')->nullable();
            $table->string('Interaction_energy_png')->nullable();
            $table->string('PCA_png')->nullable();
            $table->string('PCA_dist_png')->nullable();
            $table->string('cross_correlation_png')->nullable();
            $table->string('distance_png')->nullable();
            $table->string('distance_select_png')->nullable();
            $table->string('radius_gyration_png')->nullable();
            $table->string('radius_gyration_dist_png')->nullable();
            $table->string('rmsd_ca_png')->nullable();
            $table->string('rmsd_dist_png')->nullable();
            $table->string('rmsf_ca_png')->nullable();

            // === HTML Files ===
            $table->string('Interaction_html')->nullable();
            $table->string('initial_html')->nullable();

            // === DAT Files ===
            $table->string('FINAL_RESULTS_MMPBSA_dat')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('md_files');
    }
};
