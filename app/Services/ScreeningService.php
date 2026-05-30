<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class ScreeningService
{
    protected string $baseUrl;

    public function __construct()
    {
        $this->baseUrl = config('services.screening.url');
    }

    private function client()
    {
        return Http::timeout(300);
    }

    /**
     * GET /targets/{disease_name}
     * Quick lookup: returns disease targets from the external AI service.
     */
    public function getTargets(string $diseaseName): array
    {
        $response = $this->client()->get(
            $this->baseUrl . '/screen/targets/' . urlencode($diseaseName)
        );

        return $response->json();
    }

    /**
     * POST /screen
     * Full AI drug screening – forwards the payload to the external service and
     * returns the raw decoded response array.
     */
    public function screen(array $payload): array
    {
        $response = $this->client()->post(
            $this->baseUrl . '/screen/screen',
            $payload
        );

        return $response->json();
    }
}
