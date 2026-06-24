<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MdSimulationE2ETest extends TestCase
{
    private string $baseUrl;
    private string $email;
    private string $password;
    private string $name;

    protected function setUp(): void
    {
        parent::setUp();

        $this->baseUrl = rtrim(
            (string) getenv('MD_E2E_BASE_URL') ?: 'http://localhost:8000/api',
            '/'
        );
        $this->email = (string) getenv('MD_E2E_EMAIL') ?: 'test@example.com';
        $this->password = (string) getenv('MD_E2E_PASSWORD') ?: 'password123';
        $this->name = (string) getenv('MD_E2E_NAME') ?: 'Test User';
    }

    // ── Health check (no auth) ────────────────────────────────

    public function test_health_check(): void
    {
        $response = Http::timeout(30)->get("{$this->baseUrl}/md-simulation/health");

        $this->assertTrue(
            $response->successful(),
            'Health check failed: ' . $response->body()
        );

        $this->assertSame('ok', $response->json('status'));
    }

    // ── Auth rejection (all protected endpoints) ──────────────

    public function test_unauthenticated_access_rejected(): void
    {
        $endpoints = [
            ['GET', '/md-simulation/history'],
            ['POST', '/md-simulation/process'],
            ['GET', '/md-simulation/status/test-123'],
            ['POST', '/md-simulation/analyze/test-123'],
            ['GET', '/md-simulation/download/test-123'],
            ['GET', '/md-simulation/download-analysis/test-123'],
        ];

        foreach ($endpoints as [$method, $uri]) {
            $response = match ($method) {
                'GET' => Http::get("{$this->baseUrl}{$uri}"),
                'POST' => Http::post("{$this->baseUrl}{$uri}", []),
            };

            $this->assertEquals(
                401,
                $response->status(),
                "Expected 401 for {$method} {$uri}, got {$response->status()}: {$response->body()}"
            );
        }
    }

    // ── Auth flow: login → token → authenticated request ─────

    public function test_auth_flow_and_history(): void
    {
        $token = $this->authenticate();

        $response = Http::withHeaders([
            'Authorization' => "Bearer {$token}",
            'Accept' => 'application/json',
        ])->get("{$this->baseUrl}/md-simulation/history");

        $this->assertTrue(
            $response->successful(),
            'History request failed: ' . $response->body()
        );

        $data = $response->json();
        $this->assertTrue($data['success'] ?? false);
        $this->assertArrayHasKey('results', $data['data'] ?? []);
        $this->assertArrayHasKey('pagination', $data['data'] ?? []);
    }

    // ── Validation: process without files ─────────────────────

    public function test_process_validation_no_files(): void
    {
        $token = $this->authenticate();

        $response = Http::withHeaders([
            'Authorization' => "Bearer {$token}",
            'Accept' => 'application/json',
        ])->post("{$this->baseUrl}/md-simulation/process", []);

        $this->assertEquals(
            422,
            $response->status(),
            'Expected 422, got ' . $response->status() . ': ' . $response->body()
        );
    }

    // ── Validation: wrong file type ──────────────────────────

    public function test_process_rejects_invalid_file_type(): void
    {
        $token = $this->authenticate();

        $phpFile = base_path('tests/Feature/MdSimulationE2ETest.php');
        $ligandPath = base_path('scripts/ligand.pdb');

        $this->assertFileExists($phpFile);
        $this->assertFileExists($ligandPath);

        $response = Http::withHeaders([
            'Authorization' => "Bearer {$token}",
        ])->timeout(30)->attach(
            'protein', fopen($phpFile, 'r'), 'not_a_pdb.txt'
        )->attach(
            'ligand', fopen($ligandPath, 'r'), 'ligand.pdb'
        )->post("{$this->baseUrl}/md-simulation/process", []);

        $this->assertEquals(
            422,
            $response->status(),
            'Expected 422, got ' . $response->status() . ': ' . $response->body()
        );
    }

    // ── Full submit + status flow ────────────────────────────

    public function test_full_submit_and_status_flow(): void
    {
        $token = $this->authenticate();

        $proteinPath = base_path('scripts/protein.pdb');
        $ligandPath = base_path('scripts/ligand.pdb');

        $this->assertFileExists($proteinPath);
        $this->assertFileExists($ligandPath);

        $submitResponse = Http::withHeaders([
            'Authorization' => "Bearer {$token}",
        ])->timeout(120)->attach(
            'protein', fopen($proteinPath, 'r'), 'protein.pdb'
        )->attach(
            'ligand', fopen($ligandPath, 'r'), 'ligand.pdb'
        )->post("{$this->baseUrl}/md-simulation/process", [
            'force_field' => 'ff19SB',
            'sim_time_ns' => 0.01,
            'equil_time_ns' => 0.1,
            'temperature_k' => 300,
        ]);

        $this->assertEquals(
            202,
            $submitResponse->status(),
            'Submit failed: ' . $submitResponse->body()
        );

        $submitData = $submitResponse->json();
        $this->assertTrue($submitData['success'] ?? false);
        $this->assertArrayHasKey('remote_job_id', $submitData['data'] ?? []);
        $this->assertArrayHasKey('status', $submitData['data'] ?? []);

        $remoteJobId = $submitData['data']['remote_job_id'];
        $this->assertNotNull($remoteJobId);

        // Single status check — proves the endpoint works without waiting
        $statusResponse = Http::withHeaders([
            'Authorization' => "Bearer {$token}",
        ])->timeout(30)->get("{$this->baseUrl}/md-simulation/status/{$remoteJobId}");

        $this->assertTrue(
            $statusResponse->successful(),
            'Status check failed: ' . $statusResponse->body()
        );

        $statusData = $statusResponse->json();
        $this->assertTrue($statusData['success'] ?? false);
        $this->assertSame(
            $remoteJobId,
            $statusData['data']['remote_job_id'] ?? null
        );
    }

    // ── Helpers ───────────────────────────────────────────────

    private function authenticate(): string
    {
        $loginResponse = Http::timeout(30)->post("{$this->baseUrl}/user/login", [
            'email' => $this->email,
            'password' => $this->password,
        ]);

        if ($loginResponse->successful()) {
            $token = $loginResponse->json('data.token');
            $this->assertNotNull($token, 'Login succeeded but no token returned');
            return $token;
        }

        // User may not exist yet — try registering
        $registerResponse = Http::timeout(30)->post(
            "{$this->baseUrl}/user/register",
            [
                'name' => $this->name,
                'email' => $this->email,
                'password' => $this->password,
                'password_confirmation' => $this->password,
            ]
        );

        $this->assertTrue(
            $registerResponse->successful(),
            'Cannot authenticate: login returned ' . $loginResponse->status()
            . ', register returned ' . $registerResponse->status()
            . ': ' . $registerResponse->body()
        );

        // Login after successful registration
        $loginResponse = Http::timeout(30)->post("{$this->baseUrl}/user/login", [
            'email' => $this->email,
            'password' => $this->password,
        ]);

        $this->assertTrue(
            $loginResponse->successful(),
            'Login failed after registration: ' . $loginResponse->body()
        );

        $token = $loginResponse->json('data.token');
        $this->assertNotNull($token);

        return $token;
    }
}
