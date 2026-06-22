<?php

namespace App\Services;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class MdSimulationService
{
    private string $baseUrl;

    private int $timeout;

    public function __construct()
    {
        $this->baseUrl = rtrim(config('services.md_simulation.url', 'http://protein-ligand-md:5005'), '/');
        $this->timeout = (int) config('services.md_simulation.timeout', 3600);
    }

    public function submitJob(string $proteinPath, string $proteinName, string $ligandPath, string $ligandName, array $params): Response
    {
        $http = Http::timeout(120)->retry(3, 2000);

        $http = $http->attach(
            'protein',
            fopen($proteinPath, 'r'),
            $proteinName
        );

        $http = $http->attach(
            'ligand',
            fopen($ligandPath, 'r'),
            $ligandName
        );

        return $http->post($this->baseUrl.'/process', $params);
    }

    public function getJobStatus(string $remoteJobId): Response
    {
        return Http::timeout(30)
            ->retry(3, 1000)
            ->get($this->baseUrl."/status/{$remoteJobId}");
    }

    public function runAnalysis(string $remoteJobId, array $params = []): Response
    {
        return Http::timeout($this->timeout)
            ->retry(3, 2000)
            ->post($this->baseUrl.'/analyze', array_merge($params, [
                'job_id' => $remoteJobId,
            ]));
    }

    public function downloadResults(string $remoteJobId): Response
    {
        return Http::timeout($this->timeout)
            ->retry(2, 5000)
            ->get($this->baseUrl."/download/{$remoteJobId}");
    }

    public function downloadAnalysis(string $remoteJobId): Response
    {
        return Http::timeout($this->timeout)
            ->retry(2, 5000)
            ->get($this->baseUrl."/download_analysis/{$remoteJobId}");
    }

    public function checkHealth(): Response
    {
        return Http::timeout(10)->get($this->baseUrl.'/health');
    }

    private function statusFromRemote(string $remoteStatus): string
    {
        if (str_starts_with($remoteStatus, 'Success:')) {
            return 'completed';
        }
        if (str_starts_with($remoteStatus, 'Failed:')) {
            return 'failed';
        }

        return 'processing';
    }
}
