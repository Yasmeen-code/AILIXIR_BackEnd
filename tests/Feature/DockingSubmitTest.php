<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class DockingSubmitTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_request_succeeds(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $proteinPath = base_path('tests/Fixtures/docking/egfr_clean.pdbqt');
        $ligandPath = base_path('tests/Fixtures/docking/ligand.pdbqt');

        $proteinFile = new UploadedFile(
            $proteinPath,
            'egfr_clean.pdbqt',
            'application/octet-stream',
            null,
            true
        );

        $ligandFile = new UploadedFile(
            $ligandPath,
            'ligand.pdbqt',
            'application/octet-stream',
            null,
            true
        );

        $response = $this->postJson('/api/docking/submit', [
            'protein_name' => 'EGFR',
            'protein_file' => $proteinFile,
            'ligand_name' => 'Erlotinib',
            'ligand_file' => $ligandFile,
            'center_x' => 10.0,
            'center_y' => 15.0,
            'center_z' => 20.0,
            'box_size_x' => 25.0,
            'box_size_y' => 25.0,
            'box_size_z' => 25.0,
            'exhaustiveness' => 8,
            'n_poses' => 5,
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'job_id',
                    'status',
                ]
            ])
            ->assertJson([
                'success' => true,
                'message' => 'Docking Job Successfully Queued',
                'data' => [
                    'status' => 'pending',
                ]
            ]);
    }

    public function test_ligand_smiles_succeeds(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $proteinPath = base_path('tests/Fixtures/docking/egfr_clean.pdbqt');

        $proteinFile = new UploadedFile(
            $proteinPath,
            'egfr_clean.pdbqt',
            'application/octet-stream',
            null,
            true
        );

        $response = $this->postJson('/api/docking/submit', [
            'protein_name' => 'EGFR',
            'protein_file' => $proteinFile,
            'ligand_name' => 'Erlotinib',
            'ligand_smiles' => 'CC1=CC(=O)NC2=C1C=CC=C2',
            'center_x' => 10.0,
            'center_y' => 15.0,
            'center_z' => 20.0,
            'box_size_x' => 25.0,
            'box_size_y' => 25.0,
            'box_size_z' => 25.0,
            'exhaustiveness' => 8,
            'n_poses' => 5,
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'job_id',
                    'status',
                ]
            ])
            ->assertJson([
                'success' => true,
                'message' => 'Docking Job Successfully Queued',
                'data' => [
                    'status' => 'pending',
                ]
            ]);
    }

    public function test_reject_both_ligand_file_and_ligand_smiles(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $proteinPath = base_path('tests/Fixtures/docking/egfr_clean.pdbqt');
        $ligandPath = base_path('tests/Fixtures/docking/ligand.pdbqt');

        $proteinFile = new UploadedFile(
            $proteinPath,
            'egfr_clean.pdbqt',
            'application/octet-stream',
            null,
            true
        );

        $ligandFile = new UploadedFile(
            $ligandPath,
            'ligand.pdbqt',
            'application/octet-stream',
            null,
            true
        );

        $response = $this->postJson('/api/docking/submit', [
            'protein_name' => 'EGFR',
            'protein_file' => $proteinFile,
            'ligand_name' => 'Erlotinib',
            'ligand_file' => $ligandFile,
            'ligand_smiles' => 'CC1=CC(=O)NC2=C1C=CC=C2',
            'center_x' => 10.0,
            'center_y' => 15.0,
            'center_z' => 20.0,
            'box_size_x' => 25.0,
            'box_size_y' => 25.0,
            'box_size_z' => 25.0,
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Validation Error',
            ]);
    }

    public function test_reject_missing_both_ligand_file_and_ligand_smiles(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $proteinPath = base_path('tests/Fixtures/docking/egfr_clean.pdbqt');

        $proteinFile = new UploadedFile(
            $proteinPath,
            'egfr_clean.pdbqt',
            'application/octet-stream',
            null,
            true
        );

        $response = $this->postJson('/api/docking/submit', [
            'protein_name' => 'EGFR',
            'protein_file' => $proteinFile,
            'ligand_name' => 'Erlotinib',
            'center_x' => 10.0,
            'center_y' => 15.0,
            'center_z' => 20.0,
            'box_size_x' => 25.0,
            'box_size_y' => 25.0,
            'box_size_z' => 25.0,
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Validation Error',
            ]);
    }

    public function test_unauthenticated_request_fails(): void
    {
        $proteinPath = base_path('tests/Fixtures/docking/egfr_clean.pdbqt');
        $ligandPath = base_path('tests/Fixtures/docking/ligand.pdbqt');

        $proteinFile = new UploadedFile(
            $proteinPath,
            'egfr_clean.pdbqt',
            'application/octet-stream',
            null,
            true
        );

        $ligandFile = new UploadedFile(
            $ligandPath,
            'ligand.pdbqt',
            'application/octet-stream',
            null,
            true
        );

        $response = $this->postJson('/api/docking/submit', [
            'protein_name' => 'EGFR',
            'protein_file' => $proteinFile,
            'ligand_name' => 'Erlotinib',
            'ligand_file' => $ligandFile,
            'center_x' => 10.0,
            'center_y' => 15.0,
            'center_z' => 20.0,
            'box_size_x' => 25.0,
            'box_size_y' => 25.0,
            'box_size_z' => 25.0,
        ]);

        $response->assertStatus(401);
    }
}
