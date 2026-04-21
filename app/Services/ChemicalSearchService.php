<?php

namespace App\Services;

use App\Models\ChemicalSearchJob;
use App\Models\ChemicalCompound;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ChemicalSearchService
{
    public function search(ChemicalSearchJob $job): array
    {
        return $this->callAiService($job, '/search/retrieval-only');
    }

    public function fullRag(ChemicalSearchJob $job): array
    {
        return $this->callAiService($job, '/search/full-rag', true);
    }

    private function callAiService(ChemicalSearchJob $job, string $endpoint, bool $includeReason = false): array
    {
        $url = config('services.chemical_ai.url') . $endpoint;
        $startTime = microtime(true);

        try {
            $job->update(['status' => 'processing', 'started_at' => now()]);

            $response = Http::withOptions([
                'verify' => false,
            ])->timeout(120)->post($url, [
                'smiles' => $job->query_smiles,
                'top_k' => $job->top_k,
            ]);

            if (!$response->successful()) {
                throw new \Exception("AI Service error: " . $response->status());
            }

            $data = $response->json();
            $searchTimeMs = round((microtime(true) - $startTime) * 1000, 2);

            $job->update([
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
                'completed_at' => now(),
            ]);

            foreach ($data['results'] ?? [] as $index => $result) {
                ChemicalCompound::create([
                    'job_id' => $job->id,
                    'rank' => $index + 1,
                    'smiles' => $result['smiles'],
                    'name' => $result['name'] ?? null,
                    'cid' => $result['cid'] ?? null,
                    'similarity' => isset($result['similarity_score'])
                        ? round((float)$result['similarity_score'], 4)
                        : null,
                    'explanation' => $includeReason ? ($result['explanation'] ?? null) : null,
                    'image_url' => $result['image'] ?? null,
                ]);
            }

            return ['success' => true, 'job_id' => $job->id];
        } catch (\Exception $e) {
            Log::error('Chemical Search Failed', [
                'job_id' => $job->id,
                'error' => $e->getMessage(),
            ]);

            $job->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
                'completed_at' => now(),
            ]);

            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}
