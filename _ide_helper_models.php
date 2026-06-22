<?php

// @formatter:off
// phpcs:ignoreFile
/**
 * A helper file for your Eloquent Models
 * Copy the phpDocs from this file to the correct Model,
 * And remove them from this file, to prevent double declarations.
 *
 * @author Barry vd. Heuvel <barryvdh@gmail.com>
 */


namespace App\Models{
/**
 * @property int $id
 * @property string $smiles
 * @property int $user_id
 * @property float|null $absorption
 * @property float|null $distribution
 * @property float|null $metabolism
 * @property float|null $excretion
 * @property float|null $toxicity
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property-read \App\Models\User $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Admet newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Admet newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Admet query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Admet whereAbsorption($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Admet whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Admet whereDistribution($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Admet whereExcretion($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Admet whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Admet whereMetabolism($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Admet whereSmiles($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Admet whereToxicity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Admet whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Admet whereUserId($value)
 */
	class Admet extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $user_id
 * @property string $job_id
 * @property string $status
 * @property string $preset
 * @property int $num_molecules
 * @property int $return_top_k
 * @property string $docking_mode
 * @property int $dock_top_k
 * @property array<array-key, mixed>|null $summary
 * @property array<array-key, mixed>|null $files
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property array<array-key, mixed>|null $ligands
 * @property-read \App\Models\User $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AiJob completed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AiJob failed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AiJob newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AiJob newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AiJob query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AiJob running()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AiJob whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AiJob whereDockTopK($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AiJob whereDockingMode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AiJob whereFiles($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AiJob whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AiJob whereJobId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AiJob whereLigands($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AiJob whereNumMolecules($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AiJob wherePreset($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AiJob whereReturnTopK($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AiJob whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AiJob whereSummary($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AiJob whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AiJob whereUserId($value)
 */
	class AiJob extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $name
 * @property string|null $category
 * @property array<array-key, mixed>|null $images
 * @property string|null $description
 * @property string|null $notable_winners
 * @property string|null $country
 * @property int|null $year_started
 * @property string|null $website
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Scientist> $scientists
 * @property-read int|null $scientists_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Award newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Award newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Award query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Award whereCategory($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Award whereCountry($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Award whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Award whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Award whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Award whereImages($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Award whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Award whereNotableWinners($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Award whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Award whereWebsite($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Award whereYearStarted($value)
 */
	class Award extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $job_id
 * @property int $rank
 * @property string $smiles
 * @property string|null $name
 * @property string|null $cid
 * @property float|null $similarity
 * @property string|null $explanation
 * @property string|null $image_url
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\ChemicalSearchJob $job
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChemicalCompound newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChemicalCompound newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChemicalCompound query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChemicalCompound whereCid($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChemicalCompound whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChemicalCompound whereExplanation($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChemicalCompound whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChemicalCompound whereImageUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChemicalCompound whereJobId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChemicalCompound whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChemicalCompound whereRank($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChemicalCompound whereSimilarity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChemicalCompound whereSmiles($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChemicalCompound whereUpdatedAt($value)
 */
	class ChemicalCompound extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $user_id
 * @property string $query_smiles
 * @property int $top_k
 * @property string $status
 * @property array<array-key, mixed>|null $results
 * @property array<array-key, mixed>|null $reason
 * @property array<array-key, mixed>|null $image_urls
 * @property array<array-key, mixed>|null $metadata
 * @property string|null $error_message
 * @property \Illuminate\Support\Carbon|null $started_at
 * @property \Illuminate\Support\Carbon|null $completed_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ChemicalCompound> $compounds
 * @property-read int|null $compounds_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChemicalSearchJob newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChemicalSearchJob newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChemicalSearchJob query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChemicalSearchJob whereCompletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChemicalSearchJob whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChemicalSearchJob whereErrorMessage($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChemicalSearchJob whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChemicalSearchJob whereImageUrls($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChemicalSearchJob whereMetadata($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChemicalSearchJob whereQuerySmiles($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChemicalSearchJob whereReason($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChemicalSearchJob whereResults($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChemicalSearchJob whereStartedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChemicalSearchJob whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChemicalSearchJob whereTopK($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChemicalSearchJob whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChemicalSearchJob whereUserId($value)
 */
	class ChemicalSearchJob extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $user_id
 * @property int|null $chemistry_thread_id
 * @property string $type
 * @property string $input_data
 * @property string|null $response
 * @property array<array-key, mixed>|null $properties
 * @property array<array-key, mixed>|null $drug_likeness
 * @property array<array-key, mixed>|null $admet
 * @property int|null $processing_time_ms
 * @property string $status
 * @property string|null $error_message
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\ChemistryThread|null $thread
 * @property-read \App\Models\User $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChemistryAnalysis newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChemistryAnalysis newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChemistryAnalysis query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChemistryAnalysis whereAdmet($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChemistryAnalysis whereChemistryThreadId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChemistryAnalysis whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChemistryAnalysis whereDrugLikeness($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChemistryAnalysis whereErrorMessage($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChemistryAnalysis whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChemistryAnalysis whereInputData($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChemistryAnalysis whereProcessingTimeMs($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChemistryAnalysis whereProperties($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChemistryAnalysis whereResponse($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChemistryAnalysis whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChemistryAnalysis whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChemistryAnalysis whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChemistryAnalysis whereUserId($value)
 */
	class ChemistryAnalysis extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $user_id
 * @property string $job_id
 * @property string $filename
 * @property string $analysis_type
 * @property int $total_rows
 * @property int $completed_rows
 * @property int $failed_rows
 * @property int $progress_percent
 * @property string $status
 * @property string|null $result_file_path
 * @property string|null $error_message
 * @property \Illuminate\Support\Carbon|null $started_at
 * @property \Illuminate\Support\Carbon|null $completed_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\User $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChemistryCsvJob newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChemistryCsvJob newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChemistryCsvJob query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChemistryCsvJob whereAnalysisType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChemistryCsvJob whereCompletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChemistryCsvJob whereCompletedRows($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChemistryCsvJob whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChemistryCsvJob whereErrorMessage($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChemistryCsvJob whereFailedRows($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChemistryCsvJob whereFilename($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChemistryCsvJob whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChemistryCsvJob whereJobId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChemistryCsvJob whereProgressPercent($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChemistryCsvJob whereResultFilePath($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChemistryCsvJob whereStartedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChemistryCsvJob whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChemistryCsvJob whereTotalRows($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChemistryCsvJob whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChemistryCsvJob whereUserId($value)
 */
	class ChemistryCsvJob extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $user_id
 * @property string $thread_id
 * @property string|null $title
 * @property string $status
 * @property \Illuminate\Support\Carbon|null $last_used_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ChemistryAnalysis> $analyses
 * @property-read int|null $analyses_count
 * @property-read \App\Models\User $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChemistryThread newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChemistryThread newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChemistryThread query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChemistryThread whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChemistryThread whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChemistryThread whereLastUsedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChemistryThread whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChemistryThread whereThreadId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChemistryThread whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChemistryThread whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ChemistryThread whereUserId($value)
 */
	class ChemistryThread extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $user_id
 * @property string $input_type
 * @property string|null $smiles
 * @property string|null $protein_name
 * @property string|null $ligand_name
 * @property string $protein_path
 * @property string $ligand_path
 * @property string $status
 * @property array<array-key, mixed>|null $result_data
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\User $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DockingJob newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DockingJob newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DockingJob query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DockingJob whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DockingJob whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DockingJob whereInputType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DockingJob whereLigandName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DockingJob whereLigandPath($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DockingJob whereProteinName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DockingJob whereProteinPath($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DockingJob whereResultData($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DockingJob whereSmiles($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DockingJob whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DockingJob whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DockingJob whereUserId($value)
 */
	class DockingJob extends \Eloquent {}
}

namespace App\Models{
/**
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LigandExport newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LigandExport newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LigandExport query()
 */
	class LigandExport extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $experiment_name
 * @property string|null $description
 * @property string|null $SYS_pdb
 * @property string|null $SYS_nw_pdb
 * @property string|null $ligand_H_pdb
 * @property string|null $ligand_gaff_pdb
 * @property string|null $ligand_h_1_pdb
 * @property string|null $ligand_h_nonprot_pdb
 * @property string|null $protein_ligand_pdb
 * @property string|null $prot_lig_equil_pdb
 * @property string|null $starting_end_pdb
 * @property string|null $prot_lig_equil_dcd
 * @property string|null $prot_lig_prod1_1_nw_dcd
 * @property string|null $SYS_gaff2_crd
 * @property string|null $SYS_nw_crd
 * @property string|null $SYS_gaff2_prmtop
 * @property string|null $SYS_nw_prmtop
 * @property string|null $prot_lig_equil_rst
 * @property string|null $prepareforleap_in
 * @property string|null $tleap_in
 * @property string|null $lig_lib
 * @property string|null $ligand_frcmod
 * @property string|null $ligand_mol2
 * @property string|null $ligand_h_renum_txt
 * @property string|null $ligand_h_sslink
 * @property string|null $prot_lig_equil_log
 * @property string|null $_2D_rmsd_csv
 * @property string|null $Interaction_energy_eelec_csv
 * @property string|null $Interaction_energy_evdw_csv
 * @property string|null $PC1_csv
 * @property string|null $PC2_csv
 * @property string|null $cross_correlation_csv
 * @property string|null $distance_csv
 * @property string|null $distance_select_csv
 * @property string|null $radius_gyration_csv
 * @property string|null $rmsd_ca_csv
 * @property string|null $rmsf_ca_csv
 * @property string|null $_2D_rmsd_png
 * @property string|null $Interaction_energy_png
 * @property string|null $PCA_png
 * @property string|null $PCA_dist_png
 * @property string|null $cross_correlation_png
 * @property string|null $distance_png
 * @property string|null $distance_select_png
 * @property string|null $radius_gyration_png
 * @property string|null $radius_gyration_dist_png
 * @property string|null $rmsd_ca_png
 * @property string|null $rmsd_dist_png
 * @property string|null $rmsf_ca_png
 * @property string|null $Interaction_html
 * @property string|null $initial_html
 * @property string|null $FINAL_RESULTS_MMPBSA_dat
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MdFile newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MdFile newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MdFile query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MdFile where2DRmsdCsv($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MdFile where2DRmsdPng($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MdFile whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MdFile whereCrossCorrelationCsv($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MdFile whereCrossCorrelationPng($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MdFile whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MdFile whereDistanceCsv($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MdFile whereDistancePng($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MdFile whereDistanceSelectCsv($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MdFile whereDistanceSelectPng($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MdFile whereExperimentName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MdFile whereFINALRESULTSMMPBSADat($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MdFile whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MdFile whereInitialHtml($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MdFile whereInteractionEnergyEelecCsv($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MdFile whereInteractionEnergyEvdwCsv($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MdFile whereInteractionEnergyPng($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MdFile whereInteractionHtml($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MdFile whereLigLib($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MdFile whereLigandFrcmod($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MdFile whereLigandGaffPdb($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MdFile whereLigandH1Pdb($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MdFile whereLigandHNonprotPdb($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MdFile whereLigandHPdb($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MdFile whereLigandHRenumTxt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MdFile whereLigandHSslink($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MdFile whereLigandMol2($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MdFile wherePC1Csv($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MdFile wherePC2Csv($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MdFile wherePCADistPng($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MdFile wherePCAPng($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MdFile wherePrepareforleapIn($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MdFile whereProtLigEquilDcd($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MdFile whereProtLigEquilLog($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MdFile whereProtLigEquilPdb($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MdFile whereProtLigEquilRst($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MdFile whereProtLigProd11NwDcd($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MdFile whereProteinLigandPdb($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MdFile whereRadiusGyrationCsv($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MdFile whereRadiusGyrationDistPng($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MdFile whereRadiusGyrationPng($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MdFile whereRmsdCaCsv($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MdFile whereRmsdCaPng($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MdFile whereRmsdDistPng($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MdFile whereRmsfCaCsv($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MdFile whereRmsfCaPng($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MdFile whereSYSGaff2Crd($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MdFile whereSYSGaff2Prmtop($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MdFile whereSYSNwCrd($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MdFile whereSYSNwPdb($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MdFile whereSYSNwPrmtop($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MdFile whereSYSPdb($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MdFile whereStartingEndPdb($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MdFile whereTleapIn($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MdFile whereUpdatedAt($value)
 */
	class MdFile extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $title
 * @property string|null $summary
 * @property string $source
 * @property string $url
 * @property \Illuminate\Support\Carbon|null $published_at
 * @property string|null $category
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|News newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|News newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|News query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|News whereCategory($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|News whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|News whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|News wherePublishedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|News whereSource($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|News whereSummary($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|News whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|News whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|News whereUrl($value)
 */
	class News extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $user_id
 * @property string|null $specialization
 * @property string|null $university
 * @property int|null $years_of_experience
 * @property string|null $bio
 * @property string|null $photo
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\User $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Researcher newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Researcher newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Researcher query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Researcher whereBio($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Researcher whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Researcher whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Researcher wherePhoto($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Researcher whereSpecialization($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Researcher whereUniversity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Researcher whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Researcher whereUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Researcher whereYearsOfExperience($value)
 */
	class Researcher extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $user_id
 * @property int $news_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\News $news
 * @property-read \App\Models\User $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SavedArticle newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SavedArticle newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SavedArticle query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SavedArticle whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SavedArticle whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SavedArticle whereNewsId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SavedArticle whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SavedArticle whereUserId($value)
 */
	class SavedArticle extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $name
 * @property string|null $nationality
 * @property int|null $birth_year
 * @property int|null $death_year
 * @property array<array-key, mixed>|null $images
 * @property string $bio
 * @property string|null $impact
 * @property string|null $field
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Award> $awards
 * @property-read int|null $awards_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Scientist newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Scientist newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Scientist query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Scientist whereBio($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Scientist whereBirthYear($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Scientist whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Scientist whereDeathYear($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Scientist whereField($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Scientist whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Scientist whereImages($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Scientist whereImpact($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Scientist whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Scientist whereNationality($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Scientist whereUpdatedAt($value)
 */
	class Scientist extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $user_id
 * @property string $status
 * @property array<array-key, mixed> $input
 * @property array<array-key, mixed>|null $output
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\User $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ScreeningResult newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ScreeningResult newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ScreeningResult query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ScreeningResult whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ScreeningResult whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ScreeningResult whereInput($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ScreeningResult whereOutput($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ScreeningResult whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ScreeningResult whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ScreeningResult whereUserId($value)
 */
	class ScreeningResult extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $user_id
 * @property string $status
 * @property array<array-key, mixed> $input
 * @property array<array-key, mixed>|null $output
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\User $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TargetLookup newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TargetLookup newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TargetLookup query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TargetLookup whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TargetLookup whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TargetLookup whereInput($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TargetLookup whereOutput($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TargetLookup whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TargetLookup whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TargetLookup whereUserId($value)
 */
	class TargetLookup extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property \Illuminate\Support\Carbon|null $email_verified_at
 * @property \Illuminate\Support\Carbon|null $last_otp_sent_at
 * @property string $password
 * @property string $role
 * @property string|null $remember_token
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string|null $email_verification_otp
 * @property \Illuminate\Support\Carbon|null $email_verification_otp_expires_at
 * @property bool $is_verified
 * @property string|null $password_reset_otp
 * @property \Illuminate\Support\Carbon|null $password_reset_otp_expires_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Admet> $admets
 * @property-read int|null $admets_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\AiJob> $aiJobs
 * @property-read int|null $ai_jobs_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ChemistryAnalysis> $chemistryAnalyses
 * @property-read int|null $chemistry_analyses_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ChemistryCsvJob> $chemistryCsvJobs
 * @property-read int|null $chemistry_csv_jobs_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ChemistryThread> $chemistryThreads
 * @property-read int|null $chemistry_threads_count
 * @property-read \Illuminate\Notifications\DatabaseNotificationCollection<int, \Illuminate\Notifications\DatabaseNotification> $notifications
 * @property-read int|null $notifications_count
 * @property-read \App\Models\Researcher|null $researcher
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Laravel\Sanctum\PersonalAccessToken> $tokens
 * @property-read int|null $tokens_count
 * @method static \Database\Factories\UserFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereEmailVerificationOtp($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereEmailVerificationOtpExpiresAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereEmailVerifiedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereIsVerified($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereLastOtpSentAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User wherePassword($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User wherePasswordResetOtp($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User wherePasswordResetOtpExpiresAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereRememberToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereRole($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereUpdatedAt($value)
 */
	class User extends \Eloquent {}
}

