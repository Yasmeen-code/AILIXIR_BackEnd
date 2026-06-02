<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Redis;
use App\Models\Admet;

class AdmetService
{
    protected int $batchSize = 100;
    protected int $singleBatchLimit = 6;
    protected int $timeout = 120;
    protected string $queueKey = 'admet_batch_queue';
    protected string $lockKey = 'admet_batch_lock';

    protected function parseSmiles(string $input): array
    {
        if (empty($input)) {
            return [];
        }

        $decoded = json_decode($input, true);
        if (is_array($decoded)) {
            $result = array_filter(array_map('trim', $decoded));
            return array_slice(array_values($result), 0, $this->singleBatchLimit);
        }

        $input = preg_replace('/[;|\\t\\n\\r\\\\\\/:\\-_ ]+/', ',', $input);
        $smiles = explode(',', $input);
        $smiles = array_map('trim', $smiles);
        $smiles = array_filter($smiles, fn($value) => $value !== '' && $value !== null);
        $smiles = array_values(array_unique($smiles));

        return array_slice($smiles, 0, $this->singleBatchLimit);
    }

    protected function parseFileContent(string $content, string $extension): array
    {
        $lines = explode("\n", $content);
        $smilesList = [];

        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line) || str_starts_with($line, '#')) continue;

            if ($extension === 'csv') {
                if (str_contains(strtolower($line), 'smiles')) continue;
                $row = str_getcsv($line);
                $smiles = trim($row[0] ?? '');
            } else {
                $smiles = $line;
            }

            if (!empty($smiles)) {
                $smilesList[] = $smiles;
            }
        }

        return array_slice(array_values(array_unique($smilesList)), 0, $this->batchSize);
    }

    public function predictFromFile(string $fileContent, string $extension): array
    {
        $smilesList = $this->parseFileContent($fileContent, $extension);

        if (empty($smilesList)) {
            throw new \Exception('No valid SMILES found in the uploaded file');
        }

        return $this->processSmilesList($smilesList, $extension);
    }

    public function predictBatchFromString(string $input)
    {
        $smilesList = $this->parseSmiles($input);

        if (empty($smilesList)) {
            throw new \Exception('No valid SMILES provided');
        }

        // Fully synchronous: get database results + fetch missing from API
        return $this->processSmilesList($smilesList);
    }

    public function predictSingle(string $smiles)
    {
        $existing = Admet::where('smiles', $smiles)
            ->where('user_id', Auth::id())
            ->first();

        if ($existing) {
            return $this->formatResult($existing, $smiles);
        }

        return $this->predictBatchFromString($smiles);
    }

    protected function processSmilesList(array $smilesList, ?string $fileType = null): array
    {
        [$dbResults, $missingSmiles] = $this->getExistingPredictions($smilesList);

        $apiResults = [];
        if (!empty($missingSmiles)) {
            $apiResults = $this->fetchFromApi($missingSmiles);
        }

        $result = [
            'total_processed' => count($dbResults) + count($apiResults),
            'total_smiles' => count($smilesList),
            'results' => array_merge($dbResults, $apiResults)
        ];

        if ($fileType) {
            $result['file_type'] = $fileType;
        }

        return $result;
    }

    protected function getExistingPredictions(array $smilesList): array
    {
        $dbResults = [];
        $missingSmiles = [];

        foreach ($smilesList as $smiles) {
            $existing = Admet::where('smiles', $smiles)
                ->where('user_id', Auth::id())
                ->first();

            if ($existing) {
                $dbResults[] = array_merge($this->formatResult($existing, $smiles), ['source' => 'database']);
            } else {
                $missingSmiles[] = $smiles;
            }
        }

        return [$dbResults, $missingSmiles];
    }

    protected function fetchFromApi(array $smilesList): array
    {
        if (empty($smilesList)) {
            return [];
        }
        try {
            $response = Http::timeout($this->timeout)->post(
                config('services.admet.url') . '/predict/batch',
                ['smiles_list' => $smilesList]
            );

            if (!$response->successful()) {
                \Log::error('ADMET API request failed', [
                    'status' => $response->status(),
                    'body'   => $response->body(),
                ]);
                return $this->createErrorResults($smilesList, 'API request failed: ' . $response->status());
            }

            $data = $response->json();

            // Handle both direct results array and wrapped results object
            $results_data = isset($data['results']) ? $data : ['results' => $data];
            return $this->saveApiResults($results_data, $smilesList);
        } catch (\Exception $e) {
            \Log::error('ADMET API exception', ['error' => $e->getMessage()]);
            return $this->createErrorResults($smilesList, $e->getMessage());
        }
    }

    protected function saveApiResults(array $data, array $smilesList): array
    {
        $results = [];

        if (isset($data['results']) && is_array($data['results'])) {
            foreach ($data['results'] as $index => $result) {
                if (!isset($smilesList[$index])) continue;

                $smiles = $smilesList[$index];

                // Handle PredictionResponse objects from ADMET API
                $predictions_data = $result['predictions'] ?? $result;

                // Skip invalid SMILES that the Python service rejected
                if (!empty($result['error']) || empty($predictions_data)) {
                    $results[] = [
                        'smiles' => $smiles,
                        'error'  => $result['error'] ?? 'No predictions returned',
                        'source' => 'api',
                    ];
                    continue;
                }

                $admet = $this->saveToDatabase($smiles, $predictions_data);
                $results[] = array_merge($this->formatResult($admet, $smiles), ['source' => 'api']);
            }
        } else {
            \Log::warning('Unexpected ADMET API response format', ['data' => $data]);
            return $this->createErrorResults($smilesList, 'Invalid response format from API');
        }

        return $results;
    }

    protected function saveToDatabase(string $smiles, array $predictions): Admet
    {
        return Admet::updateOrCreate(
            [
                'smiles' => $smiles,
                'user_id' => Auth::id()
            ],
            [
                'absorption' => $predictions['Absorption'] ?? $predictions['absorption'] ?? null,
                'distribution' => $predictions['Distribution'] ?? $predictions['distribution'] ?? null,
                'metabolism' => $predictions['Metabolism'] ?? $predictions['metabolism'] ?? null,
                'excretion' => $predictions['Excretion'] ?? $predictions['excretion'] ?? null,
                'toxicity' => $predictions['Toxicity'] ?? $predictions['toxicity'] ?? null,
            ]
        );
    }

    protected function createErrorResults(array $smilesList, string $error): array
    {
        $errors = [];
        foreach ($smilesList as $smiles) {
            $errors[] = [
                'smiles' => $smiles,
                'error' => $error,
                'source' => 'error'
            ];
        }
        return $errors;
    }

    protected function formatResult(Admet $admet, string $smiles): array
    {
        return [
            'smiles' => $smiles,
            'absorption' => (float) ($admet->absorption ?? 0),
            'distribution' => (float) ($admet->distribution ?? 0),
            'metabolism' => (float) ($admet->metabolism ?? 0),
            'excretion' => (float) ($admet->excretion ?? 0),
            'toxicity' => (float) ($admet->toxicity ?? 0),
        ];
    }

    // ==================== نظام قائمة الانتظار ====================

    protected function waitForResult(string $requestId, int $timeout = 30): array
    {
        $start = microtime(true);

        while (true) {
            $result = Cache::get("admet_result_$requestId");

            if ($result !== null) {
                Cache::forget("admet_result_$requestId");
                return $result;
            }

            if ((microtime(true) - $start) > $timeout) {
                throw new \Exception('Timeout waiting for result after ' . $timeout . ' seconds');
            }

            usleep(100000);
        }
    }

    protected function tryProcessBatch(): void
    {
        $lock = Cache::lock($this->lockKey, 60);

        if ($lock->get()) {
            try {
                usleep(10000);
                $this->processBatch();
            } finally {
                $lock->release();
            }
        }
    }

    protected function processBatch(): void
    {
        $requests = [];
        while (($item = Redis::lpop($this->queueKey)) !== null) {
            $requests[] = json_decode($item, true);
        }

        if (empty($requests)) {
            return;
        }

        [$allSmiles, $smilesToRequestMap] = $this->buildRequestMap($requests);

        if (empty($allSmiles)) {
            return;
        }

        try {
            $response = Http::timeout($this->timeout)->post(
                config('services.admet.url') . '/predict/batch',
                ['smiles_list' => $allSmiles]
            );

            if (!$response->successful()) {
                throw new \Exception('Batch request failed: ' . $response->body());
            }

            $data = $response->json();
            $apiResults = $data['results'] ?? [];

            $resultsPerRequest = $this->distributeBatchResults($apiResults, $smilesToRequestMap);

            // Wrap results in the standard structure the controller expects
            foreach ($resultsPerRequest as $requestId => $requestResults) {
                $payload = [
                    'total_processed' => count($requestResults),
                    'total_smiles'    => count($requestResults),
                    'results'         => $requestResults,
                ];
                Cache::put("admet_result_$requestId", $payload, 60);
            }

            $this->handleMissingRequests($requests, $resultsPerRequest);
        } catch (\Exception $e) {
            \Log::error('ADMET batch processing failed: ' . $e->getMessage());

            foreach ($requests as $req) {
                Cache::put("admet_result_{$req['id']}", [
                    'error' => 'Batch processing failed: ' . $e->getMessage()
                ], 60);
            }

            throw $e;
        }
    }

    protected function buildRequestMap(array $requests): array
    {
        $smilesToRequestMap = [];
        $allSmiles = [];

        foreach ($requests as $reqIndex => $req) {
            foreach ($req['smiles_list'] as $smilesIndex => $smiles) {
                $smilesKey = count($allSmiles);
                $allSmiles[] = $smiles;
                $smilesToRequestMap[$smilesKey] = [
                    'request_id' => $req['id'],
                    'request_index' => $reqIndex,
                    'smiles_index' => $smilesIndex,
                    'smiles' => $smiles,
                    'user_id' => $req['user_id']
                ];
            }
        }

        return [$allSmiles, $smilesToRequestMap];
    }

    protected function distributeBatchResults(array $apiResults, array $smilesToRequestMap): array
    {
        $resultsPerRequest = [];

        foreach ($apiResults as $resultIndex => $result) {
            if (!isset($smilesToRequestMap[$resultIndex])) {
                continue;
            }

            $map = $smilesToRequestMap[$resultIndex];
            $requestId = $map['request_id'];
            $predictions = $result['predictions'] ?? $result;

            $admet = $this->saveToDatabase($map['smiles'], $predictions);
            $resultsPerRequest[$requestId][] = $this->formatResult($admet, $map['smiles']);
        }

        return $resultsPerRequest;
    }

    protected function handleMissingRequests(array $requests, array $resultsPerRequest): void
    {
        foreach ($requests as $req) {
            if (!isset($resultsPerRequest[$req['id']])) {
                Cache::put("admet_result_{$req['id']}", [
                    'error' => 'No predictions received for any SMILES',
                    'smiles_list' => $req['smiles_list']
                ], 60);
            }
        }
    }

    public function getBatchStatus(string $requestId)
    {
        return Cache::get("admet_result_$requestId");
    }

    public function clearOldResults(int $olderThanMinutes = 60): void
    {
        $keys = Redis::keys('admet_result_*');

        if (empty($keys)) {
            return;
        }

        foreach ($keys as $key) {
            $ttl = Redis::ttl($key);
            if ($ttl < 0 || $ttl > ($olderThanMinutes * 60)) {
                Redis::del($key);
            }
        }
    }
}
