<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class AiService
{
    protected $baseUrl;

    public function __construct()
    {
        $this->baseUrl = config('services.ai.url');
    }

    private function client()
    {
        return Http::timeout(300);
    }

    public function run(array $data)
    {
        return $this->client()->post($this->baseUrl . '/run', $data)->json();
    }

    public function status(string $jobId)
    {
        return $this->client()->get($this->baseUrl . "/status/$jobId")->json();
    }

    public function preview(string $jobId)
    {
        return $this->client()->get($this->baseUrl . "/preview/$jobId")->json();
    }

    public function downloadTop(string $jobId)
    {
        return $this->client()->get($this->baseUrl . "/download/top/$jobId");
    }

    public function downloadFull(string $jobId)
    {
        return $this->client()->get($this->baseUrl . "/download/full/$jobId");
    }
}
