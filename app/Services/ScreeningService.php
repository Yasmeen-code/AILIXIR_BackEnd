<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class ScreeningService
{
    protected string $baseUrl;

    public function __construct()
    {
        $this->baseUrl = rtrim((string) config('services.drug_repurposing.url'), '/');
    }

    private function client()
    {
        $client = Http::timeout(300);

        $token = config('services.drug_repurposing.token');
        if ($token) {
            $client = $client->withToken($token);
        }

        return $client;
    }

    /**
     * POST /api/v1/disease-targets
     */
    public function getTargets(array $input): array
    {
        $payload = [
            'disease_name' => $input['disease_name'],
            'top_n'        => (int) ($input['top_n'] ?? 10),
        ];

        $response = $this->client()->post(
            $this->baseUrl . '/api/v1/disease-targets',
            $payload
        );

        $response->throw();

        return $response->json();
    }

    /**
     * POST /api/v1/screen
     */
    public function screen(array $payload): array
    {
        $response = $this->client()->post(
            $this->baseUrl . '/api/v1/screen',
            $payload
        );

        $response->throw();

        return $response->json();
    }
}
