<?php

namespace App\Services;

use App\Models\ChemicalSearchJob;
use Illuminate\Support\Facades\Log;

class ChemicalSearchService
{
    public function search(ChemicalSearchJob $job): array
    {
        $url = config('services.chemical_ai.url') . '/search';
        $startTime = microtime(true);

        Log::info('Starting chemical search', [
            'job_id' => $job->id,
            'url' => $url,
        ]);

        try {
            $job->update(['status' => 'processing', 'started_at' => now()]);

            $smiles = escapeshellarg($job->query_smiles);
            $command = "curl -s -X POST " . escapeshellarg($url) .
                " -H \"Content-Type: application/json\"" .
                " -d '{\"smiles\":$smiles,\"top_k\":{$job->top_k}}'" .
                " -k --max-time 120 --connect-timeout 60";

            Log::info('Executing command', ['command' => $command]);

            $output = shell_exec($command);

            if ($output === null || empty($output)) {
                throw new \Exception('Empty response from AI service');
            }

            Log::info('Raw response', ['output' => substr($output, 0, 500)]);

            $data = json_decode($output, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception('Invalid JSON response: ' . json_last_error_msg());
            }

            $searchTimeMs = round((microtime(true) - $startTime) * 1000, 2);

            $results = [];
            $imageUrls = [];

            $rawResults = $data['results'] ?? [];

            foreach ($rawResults as $index => $result) {
                $imageUrl = $this->resolveImageUrl($result['image'] ?? null);

                $results[] = [
                    'rank' => $index + 1,
                    'smiles' => $result['smiles'],
                    'similarity' => round($result['similarity_score'], 2),
                    'image_url' => $imageUrl,
                ];

                if ($imageUrl) {
                    $imageUrls[] = $imageUrl;
                }
            }

            $metadata = [
                'total_results' => count($rawResults),
                'filtered_results' => count($rawResults),
                'search_time_ms' => $searchTimeMs,
                'similarity_metric' => 'Tanimoto',
                'fingerprint' => 'Morgan (2048, radius=2)',
            ];

            $job->update([
                'status' => 'completed',
                'results' => $results,
                'image_urls' => $imageUrls,
                'metadata' => $metadata,
                'completed_at' => now(),
            ]);

            return [
                'success' => true,
                'job_id' => $job->id,
            ];
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

    private function resolveImageUrl(?string $imagePath): ?string
    {
        if (!$imagePath) return null;
        if (str_starts_with($imagePath, 'http')) return $imagePath;
        return config('services.chemical_ai.url') . $imagePath;
    }
}
