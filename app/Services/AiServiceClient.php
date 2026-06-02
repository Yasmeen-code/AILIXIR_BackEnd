<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\Response;

class AiServiceClient
{
    private string $baseUrl;

    public function __construct()
    {
        $this->baseUrl = config('services.ai.url', 'http://localhost:8000');
    }

    public function startGeneration(array $params): Response
    {
        return Http::timeout(30)
            ->retry(5, 1000)
            ->post($this->baseUrl . '/generate', $params);
    }

    public function getJobStatus(string $jobId): Response
    {
        return Http::timeout(30)
            ->retry(5, 1000)
            ->get($this->baseUrl . "/jobs/{$jobId}");
    }

    public function fetchResults(string $jobId): Response
    {
        return Http::timeout(60)
            ->retry(5, 1000)
            ->get($this->baseUrl . "/jobs/{$jobId}/result");
    }

    public function downloadFile(string $jobId, string $filename): Response
    {
        return Http::timeout(120)
            ->retry(5, 1000)
            ->get($this->baseUrl . "/files/jobs/{$jobId}/{$filename}");
    }
}
