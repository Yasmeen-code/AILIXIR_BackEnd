<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;
use App\Models\Admet;

class AdmetService
{
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
            return array_slice(array_values($result), 0, 6);
        }

        $input = preg_replace('/[;|\\t\\n\\r\\\\\\/:\\-_ ]+/', ',', $input);

        $smiles = explode(',', $input);
        $smiles = array_map('trim', $smiles);
        $smiles = array_filter($smiles, function ($value) {
            return $value !== '' && $value !== null;
        });

        $smiles = array_values(array_unique($smiles));

        return array_slice($smiles, 0, 6);
    }

    public function predictBatchFromString(string $input)
    {
        $smilesList = $this->parseSmiles($input);

        if (empty($smilesList)) {
            throw new \Exception('No valid SMILES provided');
        }

        $requestId = Str::uuid()->toString();

        Redis::rpush($this->queueKey, json_encode([
            'id' => $requestId,
            'smiles_list' => $smilesList,
            'user_id' => Auth::id(),
        ]));

        $this->tryProcessBatch();

        $timeout = 30;
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

    protected function tryProcessBatch()
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

    protected function processBatch()
    {
        $requests = [];

        while (($item = Redis::lpop($this->queueKey)) !== null) {
            $requests[] = json_decode($item, true);
        }

        if (empty($requests)) {
            return;
        }

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

        if (empty($allSmiles)) {
            return;
        }

        try {
            $response = Http::timeout(120)->post(
                config('services.ai.admet_url') . '/predict/batch',
                ['smiles_list' => $allSmiles]
            );

            if (!$response->successful()) {
                throw new \Exception('Batch request failed: ' . $response->body());
            }

            $json = $response->json();
            $results = $json['results'] ?? [];

            $resultsPerRequest = [];

            foreach ($results as $resultIndex => $result) {
                if (!isset($smilesToRequestMap[$resultIndex])) {
                    continue;
                }

                $map = $smilesToRequestMap[$resultIndex];
                $requestId = $map['request_id'];
                $smiles = $map['smiles'];
                $predictions = $result['predictions'] ?? [];

                try {
                    $admet = Admet::updateOrCreate(
                        [
                            'smiles' => $smiles,
                            'user_id' => $map['user_id']
                        ],
                        [
                            'absorption' => $predictions['Absorption'] ?? null,
                            'distribution' => $predictions['Distribution'] ?? null,
                            'metabolism' => $predictions['Metabolism'] ?? null,
                            'excretion' => $predictions['Excretion'] ?? null,
                            'toxicity' => $predictions['Toxicity'] ?? null,
                        ]
                    );

                    if (!isset($resultsPerRequest[$requestId])) {
                        $resultsPerRequest[$requestId] = [];
                    }

                    $resultsPerRequest[$requestId][] = [
                        'smiles' => $smiles,
                        'absorption' => (float) $admet->absorption,
                        'distribution' => (float) $admet->distribution,
                        'metabolism' => (float) $admet->metabolism,
                        'excretion' => (float) $admet->excretion,
                        'toxicity' => (float) $admet->toxicity,
                    ];
                } catch (\Exception $e) {
                    \Log::error('Failed to save ADMET result for SMILES: ' . $smiles, [
                        'error' => $e->getMessage(),
                        'user_id' => $map['user_id']
                    ]);

                    if (!isset($resultsPerRequest[$requestId])) {
                        $resultsPerRequest[$requestId] = [];
                    }

                    $resultsPerRequest[$requestId][] = [
                        'smiles' => $smiles,
                        'error' => 'Failed to save to database: ' . $e->getMessage(),
                        'predictions' => $predictions
                    ];
                }
            }

            foreach ($smilesToRequestMap as $index => $map) {
                $found = false;
                foreach ($results as $resultIndex => $result) {
                    if ($resultIndex == $index) {
                        $found = true;
                        break;
                    }
                }

                if (!$found) {
                    $requestId = $map['request_id'];

                    if (!isset($resultsPerRequest[$requestId])) {
                        $resultsPerRequest[$requestId] = [];
                    }

                    $resultsPerRequest[$requestId][] = [
                        'smiles' => $map['smiles'],
                        'error' => 'No prediction received from AI service',
                        'absorption' => null,
                        'distribution' => null,
                        'metabolism' => null,
                        'excretion' => null,
                        'toxicity' => null,
                    ];
                }
            }

            foreach ($resultsPerRequest as $requestId => $results) {
                Cache::put("admet_result_$requestId", $results, 60);
            }

            foreach ($requests as $req) {
                if (!isset($resultsPerRequest[$req['id']])) {
                    Cache::put("admet_result_{$req['id']}", [
                        'error' => 'No predictions received for any SMILES',
                        'smiles_list' => $req['smiles_list']
                    ], 60);
                }
            }
        } catch (\Exception $e) {
            \Log::error('ADMET batch processing failed: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            foreach ($requests as $req) {
                Cache::put("admet_result_{$req['id']}", [
                    'error' => 'Batch processing failed: ' . $e->getMessage()
                ], 60);
            }

            throw $e;
        }
    }

    public function predictSingle(string $smiles)
    {
        $existing = Admet::where('smiles', $smiles)
            ->where('user_id', Auth::id())
            ->first();

        if ($existing) {
            return [
                'smiles' => $existing->smiles,
                'absorption' => (float) $existing->absorption,
                'distribution' => (float) $existing->distribution,
                'metabolism' => (float) $existing->metabolism,
                'excretion' => (float) $existing->excretion,
                'toxicity' => (float) $existing->toxicity,
            ];
        }

        return $this->predictBatchFromString($smiles);
    }

    public function getBatchStatus(string $requestId)
    {
        return Cache::get("admet_result_$requestId");
    }

    public function clearOldResults(int $olderThanMinutes = 60)
    {
        $pattern = 'admet_result_*';
        $keys = Redis::keys($pattern);

        foreach ($keys as $key) {
            $ttl = Redis::ttl($key);
            if ($ttl > 0 && $ttl < ($olderThanMinutes * 60)) {
                continue;
            }
            Redis::del($key);
        }
    }
}
