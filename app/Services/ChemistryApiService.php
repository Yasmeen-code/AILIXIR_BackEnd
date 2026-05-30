<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;

class ChemistryApiService
{
    protected string $baseUrl;
    protected int $timeout;

    public function __construct()
    {
        $this->baseUrl = rtrim((string) config('services.chemistry.base_url') ?: '', '/');
        $this->timeout = (int) config('services.chemistry.timeout', 30);
    }

    // ==================== Health ====================
    public function healthCheck(): array
    {
        return $this->request('get', '/health');
    }

    // ==================== Threads ====================
    public function createThread(): array
    {
        return $this->request('post', '/thread/new');
    }

    // ==================== Chat ====================
    public function chat(string $message, ?string $threadId = null): array
    {
        return $this->request('post', '/chat', [
            'message' => $message,
            'thread_id' => $threadId,
        ]);
    }

    // ==================== Analyze SMILES ====================
    public function analyzeSmiles(string $smiles, ?string $threadId = null): array
    {
        $query = ['smiles' => $smiles];

        if ($threadId) {
            $query['thread_id'] = $threadId;
        }

        $queryString = http_build_query($query);

        $response = Http::timeout($this->timeout)
            ->withHeaders(['Content-Type' => 'application/json'])
            ->post("{$this->baseUrl}/analyze/smiles?{$queryString}");

        return $this->handleResponse($response);
    }

    // ==================== Compare ====================
    public function compareMolecules(array $smilesList, ?string $threadId = null): array
    {
        return $this->request('post', '/analyze/compare', [
            'message' => implode(', ', $smilesList),
            'thread_id' => $threadId,
        ]);
    }

    // ==================== Docking ====================
    public function analyzeDocking(string $dockingData, ?string $threadId = null): array
    {
        return $this->request('post', '/analyze/docking', [
            'docking_data' => $dockingData,
            'thread_id' => $threadId,
        ]);
    }

    // ==================== CSV ====================
    public function uploadCsv(UploadedFile $file, string $analysisType = 'full'): array
    {
        return $this->request('post', "/csv/upload?analysis_type={$analysisType}", [], [
            'file' => $file,
        ]);
    }

    public function getCsvStatus(string $jobId): array
    {
        return $this->request('get', "/csv/status/{$jobId}");
    }

    public function getCsvResults(string $jobId): string|array
    {
        $response = Http::timeout($this->timeout)
            ->get("{$this->baseUrl}/csv/results/{$jobId}");

        if ($response->successful()) {
            return $response->body();
        }

        return $this->handleResponse($response);
    }

    public function listCsvJobs(): array
    {
        return $this->request('get', '/csv/jobs');
    }

    public function deleteCsvJob(string $jobId): array
    {
        return $this->request('delete', "/csv/jobs/{$jobId}");
    }

    // ==================== Helper Methods ====================
    protected function request(string $method, string $endpoint, array $json = [], array $multipart = []): array
    {
        $url = "{$this->baseUrl}{$endpoint}";

        $http = Http::timeout($this->timeout);

        if (!empty($multipart)) {
            foreach ($multipart as $key => $value) {
                $http = $http->attach($key, file_get_contents($value->getRealPath()), $value->getClientOriginalName());
            }
            $response = $http->{$method}($url);
        } else {
            $response = $http->withHeaders([
                'Content-Type' => 'application/json',
            ])->{$method}($url, $json);
        }

        return $this->handleResponse($response);
    }

    protected function handleResponse($response): array
    {
        if ($response->successful()) {
            return [
                'success' => true,
                'data' => $response->json() ?? $response->body(),
                'status' => $response->status(),
            ];
        }

        return [
            'success' => false,
            'error' => $response->json('detail') ?? $response->body(),
            'status' => $response->status(),
        ];
    }
}
