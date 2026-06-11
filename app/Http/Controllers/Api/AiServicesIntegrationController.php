<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Http;

class AiServicesIntegrationController extends Controller
{
    public function health(): JsonResponse
    {
        $checks = [
            'admet' => config('services.admet.url'),
            'chemical_rag' => config('services.chemical_ai.url'),
            'drug_repurposing' => config('services.drug_repurposing.url'),
        ];

        $results = [];

        foreach ($checks as $name => $baseUrl) {
            $results[$name] = $this->probeHealth($baseUrl);
            usleep(200_000);
        }

        $allHealthy = collect($results)->every(
            fn (array $result) => ($result['status'] ?? '') === 'healthy'
        );

        return response()->json([
            'success' => $allHealthy,
            'services' => $results,
        ], $allHealthy ? 200 : 503);
    }

    public function testAdmet(Request $request): JsonResponse
    {
        $baseUrl = rtrim((string) config('services.admet.url'), '/');
        $smiles = (string) $request->input('smiles', 'c1ccccc1');

        $response = Http::timeout(180)->post("{$baseUrl}/predict", [
            'smiles' => $smiles,
        ]);

        return $this->proxyResponse('admet', $response);
    }

    public function testChemicalSearch(Request $request): JsonResponse
    {
        $baseUrl = rtrim((string) config('services.chemical_ai.url'), '/');
        $smiles = (string) $request->input('smiles', 'CCO');
        $topK = (int) $request->input('top_k', 3);

        $response = Http::timeout(180)->post("{$baseUrl}/search/retrieval-only", [
            'smiles' => $smiles,
            'top_k' => $topK,
        ]);

        return $this->proxyResponse('chemical_rag', $response);
    }

    public function testDrugRepurposing(): JsonResponse
    {
        $baseUrl = rtrim((string) config('services.drug_repurposing.url'), '/');

        $health = Http::timeout(60)->retry(3, 500)->withUserAgent('AILIXIR-Internal/1.0')->get("{$baseUrl}/health");
        usleep(200_000);
        $modelStatus = Http::timeout(60)->retry(3, 500)->withUserAgent('AILIXIR-Internal/1.0')->get("{$baseUrl}/api/v1/model-status");

        $success = $health->successful() && $modelStatus->successful();

        return response()->json([
            'success' => $success,
            'service' => 'drug_repurposing',
            'health' => [
                'status' => $health->status(),
                'body' => $health->json(),
            ],
            'model_status' => [
                'status' => $modelStatus->status(),
                'body' => $modelStatus->json(),
            ],
        ], $success ? 200 : 502);
    }

    private function probeHealth(?string $baseUrl): array
    {
        if (empty($baseUrl)) {
            return [
                'status' => 'misconfigured',
                'error' => 'URL not configured',
            ];
        }

        try {
            $response = Http::timeout(60)->retry(3, 500)->withUserAgent('AILIXIR-Internal/1.0')->get(rtrim($baseUrl, '/').'/health');

            return [
                'status' => $response->successful() ? 'healthy' : 'unhealthy',
                'http_status' => $response->status(),
                'body' => $response->json() ?? $response->body(),
            ];
        } catch (\Throwable $e) {
            return [
                'status' => 'unreachable',
                'error' => $e->getMessage(),
            ];
        }
    }

    private function proxyResponse(string $service, \Illuminate\Http\Client\Response $response): JsonResponse
    {
        return response()->json([
            'success' => $response->successful(),
            'service' => $service,
            'upstream_status' => $response->status(),
            'data' => $response->json(),
        ], $response->successful() ? 200 : 502);
    }
}
