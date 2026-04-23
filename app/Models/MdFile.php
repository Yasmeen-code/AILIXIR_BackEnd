<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MdFile extends Model
{
    protected $table = 'md_files';

    protected $guarded = ['id', 'created_at', 'updated_at'];


    public function getFileUrl(string $originalName): ?string
    {
        $column = $this->mapFileNameToColumn($originalName);
        return $this->$column;
    }


    public function setFileUrl(string $originalName, string $url): void
    {
        $column = $this->mapFileNameToColumn($originalName);
        $this->update([$column => $url]);
    }

    function hasFile(string $originalName): bool
    {
        return !empty($this->getFileUrl($originalName));
    }

    public function getAllFiles(): array
    {
        $files = [];
        $mapping = $this->getColumnMapping();

        foreach ($mapping as $original => $column) {
            if ($this->$column) {
                $files[$original] = $this->$column;
            }
        }

        return $files;
    }

    public function getFilesByExtension(string $ext): array
    {
        return array_filter(
            $this->getAllFiles(),
            fn($name) => str_ends_with(strtolower($name), ".{$ext}"),
            ARRAY_FILTER_USE_KEY
        );
    }

    private function mapFileNameToColumn(string $originalName): string
    {
        $mapping = $this->getColumnMapping();

        if (!isset($mapping[$originalName])) {
            throw new \InvalidArgumentException("Unknown file: {$originalName}");
        }

        return $mapping[$originalName];
    }

    public function getColumnMapping(): array
    {
        return [
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
    }
}
