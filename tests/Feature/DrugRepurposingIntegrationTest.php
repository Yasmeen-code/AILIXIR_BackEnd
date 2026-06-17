<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\ScreeningService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Timeout;
use Tests\TestCase;

class DrugRepurposingIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private const DISEASE = 'Type 2 Diabetes';

    protected function setUp(): void
    {
        parent::setUp();

        // Docker sets QUEUE_CONNECTION=database and DB_HOST=mysql on the container,
        // which overrides phpunit.xml. The queue worker uses MySQL while tests use
        // in-memory SQLite, so async jobs never run during tests. Sync forces the
        // job to finish before the status endpoint is called.
        config(['queue.default' => 'sync']);
    }

    public function test_unauthenticated_target_lookup_is_rejected(): void
    {
        $this->postJson('/api/drug-repurposing/targets', [
            'disease_name' => self::DISEASE,
            'top_n'        => 2,
        ])->assertUnauthorized();
    }

    public function test_target_lookup_requires_disease_name(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->postJson('/api/drug-repurposing/targets', [
            'top_n' => 2,
        ])
            ->assertUnprocessable()
            ->assertJsonPath('success', false)
            ->assertJsonValidationErrors(['disease_name']);
    }

    public function test_laravel_can_reach_drug_repurposing_service(): void
    {
        $this->requireDrugRepurposingService();

        $baseUrl = rtrim((string) config('services.drug_repurposing.url'), '/');

        $response = Http::timeout(30)->get("{$baseUrl}/health");

        $this->assertTrue($response->successful(), $response->body());
        $this->assertSame('healthy', $response->json('status'));
    }

    public function test_screening_service_fetches_disease_targets(): void
    {
        $this->requireDrugRepurposingService();

        $output = app(ScreeningService::class)->getTargets([
            'disease_name' => self::DISEASE,
            'top_n'        => 2,
        ]);

        $this->assertSame(self::DISEASE, $output['disease'] ?? null);
        $this->assertGreaterThanOrEqual(1, $output['total_targets'] ?? 0);
        $this->assertNotEmpty($output['targets'] ?? []);
        $this->assertArrayHasKey('symbol', $output['targets'][0]);
    }

    #[Group('drug-repurposing-integration')]
    public function test_target_lookup_api_returns_completed_targets(): void
    {
        $this->requireDrugRepurposingService();

        Sanctum::actingAs(User::factory()->create());

        $create = $this->postJson('/api/drug-repurposing/targets', [
            'disease_name' => self::DISEASE,
            'top_n'        => 2,
        ]);

        $create->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'data' => ['job_id', 'status'],
            ]);

        $jobId = $create->json('data.job_id');

        $status = $this->getJson("/api/drug-repurposing/targets/{$jobId}");
        $status->assertOk()
            ->assertJsonPath('data.status', 'completed');

        $output = $status->json('data.output');
        $this->assertSame(self::DISEASE, $output['disease'] ?? null);
        $this->assertNotEmpty($output['targets'] ?? []);

        $this->assertDatabaseHas('target_lookups', [
            'id'     => $jobId,
            'status' => 'completed',
        ]);
    }

    #[Group('drug-repurposing-integration')]
    #[Timeout(600)]
    public function test_screening_api_returns_completed_results(): void
    {
        $this->requireDrugRepurposingService();

        Sanctum::actingAs(User::factory()->create());

        $create = $this->postJson('/api/drug-repurposing/screen', [
            'disease_name'   => self::DISEASE,
            'min_score'      => 0.5,
            'top_n_targets'  => 2,
            'known_drugs'    => ['Metformin'],
        ]);

        $create->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'data' => ['job_id', 'status'],
            ]);

        $jobId = $create->json('data.job_id');

        $status = $this->getJson("/api/drug-repurposing/screen/{$jobId}");
        $status->assertOk()
            ->assertJsonPath('data.status', 'completed');

        $output = $status->json('data.output');
        $this->assertTrue($output['success'] ?? false);
        $this->assertSame(self::DISEASE, $output['disease'] ?? null);
        $this->assertNotEmpty($output['top_results'] ?? []);

        $this->assertDatabaseHas('screening_results', [
            'id'     => $jobId,
            'status' => 'completed',
        ]);
    }

    private function requireDrugRepurposingService(): void
    {
        $baseUrl = rtrim((string) config('services.drug_repurposing.url'), '/');

        if ($baseUrl === '') {
            $this->markTestSkipped('DRUG_REPURPOSING_URL is not configured.');
        }

        try {
            $healthy = Http::timeout(30)
                ->retry(3, 500)
                ->get("{$baseUrl}/health")
                ->successful();
        } catch (\Throwable $e) {
            $this->markTestSkipped("Drug repurposing service unreachable at {$baseUrl}: {$e->getMessage()}");
        }

        if (! $healthy) {
            $this->markTestSkipped("Drug repurposing service unhealthy at {$baseUrl}/health");
        }
    }
}
