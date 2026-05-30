<?php

namespace App\Services;

use App\Models\ChemicalSearchJob;
use App\Models\ChemicalCompound;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ChemicalSearchService
{
    public function search(string $smiles, int $topK, int $userId): array
    {
        return $this->callAiService($smiles, $topK, $userId, '/search/retrieval-only', false);
    }

    public function fullRag(string $smiles, int $topK, int $userId): array
    {
        return $this->callAiService($smiles, $topK, $userId, '/search/full-rag', true);
    }

    private function callAiService(
        string $smiles,
        int $topK,
        int $userId,
        string $endpoint,
        bool $includeReason
    ): array {
        $url = config('services.chemical_ai.url') . $endpoint;
        $startTime = microtime(true);

        try {
            $response = Http::withOptions([
                'verify' => false,
            ])->timeout(120)->post($url, [
                'smiles' => $smiles,
                'top_k' => $topK,
            ]);

            if (!$response->successful()) {
                throw new \Exception("AI Service error: " . $response->status());
            }

            $data = $response->json();
            $searchTimeMs = round((microtime(true) - $startTime) * 1000, 2);

            $compounds = [];
            foreach ($data['results'] ?? [] as $index => $result) {
                $compounds[] = [
                    'rank' => $index + 1,
                    'smiles' => $result['smiles'],
                    'name' => $result['name'] ?? null,
                    'cid' => $result['cid'] ?? null,
                    'similarity' => isset($result['similarity_score'])
                        ? round((float)$result['similarity_score'], 4)
                        : null,
                    'explanation' => $includeReason ? ($result['explanation'] ?? null) : null,
                    'image_url' => $result['image'] ?? null,
                ];
            }

            // Optional: Save to DB for history (بدون job status)
            $job = ChemicalSearchJob::create([
                'user_id' => $userId,
                'query_smiles' => $smiles,
                'top_k' => $topK,
                'status' => 'completed',
                'results' => $data['results'] ?? [],
                'image_urls' => array_column($data['results'] ?? [], 'image'),
                'metadata' => [
                    'total_results' => count($data['results'] ?? []),
                    'search_time_ms' => $searchTimeMs,
                    'similarity_metric' => 'Tanimoto',
                    'fingerprint' => 'Morgan (2048, radius=2)',
                    'source' => $includeReason ? 'full_rag' : 'retrieval',
                ],
                'started_at' => now(),
                'completed_at' => now(),
            ]);

            // Save compounds if you still want DB records
            foreach ($compounds as $compoundData) {
                ChemicalCompound::create([
                    'job_id' => $job->id,
                    ...$compoundData,
                ]);
            }

            return [
                'success' => true,
                'compounds' => $compounds,
                'metadata' => $job->metadata,
            ];
        } catch (\Exception $e) {
            Log::error('Chemical Search Failed', [
                'smiles' => $smiles,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }
}
