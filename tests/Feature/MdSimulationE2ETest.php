<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MdSimulationE2ETest extends TestCase
{
    use RefreshDatabase;

    private const TEST_EMAIL = 'md-e2e@ailixir.test';
    private const TEST_PASSWORD = 'test-password-123';
    private const TEST_NAME = 'MD E2E Test User';

    protected function setUp(): void
    {
        parent::setUp();
        config(['queue.default' => 'sync']);
    }

    // ── Direct MD service health (no auth) ─────────────────────

    public function test_health_check_proxies_to_md_service(): void
    {
        $this->requireMdSimulationService();

        $response = Http::timeout(10)
            ->retry(3, 1000)
            ->get($this->mdBaseUrl() . '/health');

        $response->assertSuccessful();
        $this->assertSame('ok', $response->json('status'));
    }

    // ── Auth rejection (all protected endpoints) ───────────────

    public function test_unauthenticated_access_rejected(): void
    {
        $endpoints = [
            ['GET', '/api/md-simulation/history'],
            ['POST', '/api/md-simulation/process'],
            ['GET', '/api/md-simulation/status/test-123'],
            ['POST', '/api/md-simulation/analyze/test-123'],
            ['GET', '/api/md-simulation/download/test-123'],
            ['GET', '/api/md-simulation/download-analysis/test-123'],
        ];

        foreach ($endpoints as [$method, $uri]) {
            $response = match ($method) {
                'GET' => $this->getJson($uri),
                'POST' => $this->postJson($uri, []),
            };
            $response->assertUnauthorized();
        }
    }

    // ── Laravel proxy health (no auth, through controller) ────

    public function test_laravel_health_endpoint_returns_ok(): void
    {
        $this->requireMdSimulationService();

        $response = $this->getJson('/api/md-simulation/health');

        $response->assertOk();
        $this->assertSame('ok', $response->json('status'));
    }

    // ── Real auth flow: create user → login → get token → use ──

    public function test_auth_flow_and_history_returns_empty_results(): void
    {
        $this->createVerifiedUser();

        $loginResponse = $this->postJson('/api/user/login', [
            'email' => self::TEST_EMAIL,
            'password' => self::TEST_PASSWORD,
        ]);

        $loginResponse
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['data' => ['token', 'user']]);

        $token = $loginResponse->json('data.token');
        $this->assertNotNull($token);

        $historyResponse = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/md-simulation/history');

        $historyResponse
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['data' => ['results', 'pagination']])
            ->assertJsonCount(0, 'data.results');
    }

    // ── Validation: process without files ─────────────────────

    public function test_process_validation_requires_files(): void
    {
        $this->createVerifiedUser();
        $token = $this->loginAndGetToken();

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/md-simulation/process', []);

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['protein', 'ligand']);
    }

    // ── Validation: wrong file type ──────────────────────────

    public function test_process_rejects_invalid_file_type(): void
    {
        $this->createVerifiedUser();
        $token = $this->loginAndGetToken();

        $badFile = new UploadedFile(
            __FILE__,
            'not_a_pdb.txt',
            'text/plain',
            null,
            true
        );

        $ligandPath = base_path('scripts/ligand.pdb');
        $ligandFile = new UploadedFile(
            $ligandPath,
            'ligand.pdb',
            'chemical/pdb',
            null,
            true
        );

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/md-simulation/process', [
                'protein' => $badFile,
                'ligand' => $ligandFile,
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['protein']);
    }

    // ── Full submit + status flow (requires real MD service) ──

    public function test_full_submit_and_status_flow(): void
    {
        $this->requireMdSimulationService();
        $this->createVerifiedUser();
        $token = $this->loginAndGetToken();

        $proteinPath = base_path('scripts/protein.pdb');
        $ligandPath = base_path('scripts/ligand.pdb');

        $proteinFile = new UploadedFile(
            $proteinPath,
            'protein.pdb',
            'chemical/pdb',
            null,
            true
        );

        $ligandFile = new UploadedFile(
            $ligandPath,
            'ligand.pdb',
            'chemical/pdb',
            null,
            true
        );

        $submitResponse = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/md-simulation/process', [
                'protein' => $proteinFile,
                'ligand' => $ligandFile,
                'force_field' => 'ff19SB',
                'sim_time_ns' => 0.01,
                'equil_time_ns' => 0.01,
                'temperature_k' => 300,
            ]);

        $submitResponse
            ->assertStatus(202)
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['data' => ['remote_job_id', 'status', 'created_at']]);

        $remoteJobId = $submitResponse->json('data.remote_job_id');
        $this->assertNotNull($remoteJobId);
        $this->assertSame('processing', $submitResponse->json('data.status'));

        // Single status check — verifies the endpoint works without waiting for completion
        $statusResponse = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson("/api/md-simulation/status/{$remoteJobId}");

        $statusResponse
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.remote_job_id', $remoteJobId)
            ->assertJsonStructure(['data' => ['status', 'protein', 'ligand', 'created_at']]);
    }

    // ── Helpers ───────────────────────────────────────────────

    private function createVerifiedUser(): void
    {
        User::create([
            'name' => self::TEST_NAME,
            'email' => self::TEST_EMAIL,
            'password' => Hash::make(self::TEST_PASSWORD),
            'role' => 'normal',
            'is_verified' => true,
            'email_verified_at' => now(),
        ]);
    }

    private function loginAndGetToken(): string
    {
        $response = $this->postJson('/api/user/login', [
            'email' => self::TEST_EMAIL,
            'password' => self::TEST_PASSWORD,
        ]);

        $response->assertOk();
        return $response->json('data.token');
    }

    private function mdBaseUrl(): string
    {
        return rtrim(
            (string) config('services.md_simulation.url', 'http://protein-ligand-md:5005'),
            '/'
        );
    }

    private function requireMdSimulationService(): void
    {
        $baseUrl = $this->mdBaseUrl();

        if (empty($baseUrl)) {
            $this->markTestSkipped('MD_SIMULATION_URL is not configured.');
        }

        try {
            $response = Http::timeout(10)
                ->retry(3, 1000)
                ->get("{$baseUrl}/health");

            $healthy = $response->successful();
        } catch (\Throwable $e) {
            $this->markTestSkipped(
                "MD simulation service unreachable at {$baseUrl}: {$e->getMessage()}"
            );
        }

        if (! ($healthy ?? false)) {
            $this->markTestSkipped(
                "MD simulation service unhealthy at {$baseUrl}/health"
            );
        }
    }
}
