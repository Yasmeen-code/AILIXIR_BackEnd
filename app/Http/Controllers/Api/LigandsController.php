<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Ai\ExportLigandsRequest;
use App\Models\LigandExport;
use Illuminate\Support\Facades\Http;
use Throwable;

class LigandsController extends BaseController
{
    private function aiServiceUrl(): string
    {
        return rtrim(config('services.generation.url', 'http://generation:8000'), '/');
    }

    public function exportLigands(ExportLigandsRequest $request)
    {
        $validated = $request->validated();

        $ligandExport = LigandExport::where('smiles', $validated['smiles'])
            ->where('file_format', $validated['format'])
            ->first();

        if ($ligandExport) {
            return $this->getLigand($ligandExport);
        }

        return $this->callAiService('/ligands/export', $validated);
    }

    private function callAiService(string $endpoint, array $validated)
    {
        try {
            $response = Http::timeout(120)
                ->retry(5, 1000)
                ->post(
                    $this->aiServiceUrl() . $endpoint,
                    $validated
                );

            if ($response->failed()) {
                return $this->errorResponse(
                    'AI service error: ' . $response->body(),
                    $response->status()
                );
            }

            $aiData = $response->json();

            if (!isset($aiData['job_id'], $aiData['status'], $aiData['canonical_smiles'], $aiData['file']['format'], $aiData['file']['filename'], $aiData['file']['download_url'])) {
                return $this->errorResponse('Invalid AI service response', 500);
            }

            $ligandExport = LigandExport::firstOrCreate(
                [
                    'smiles' => $aiData['canonical_smiles'],
                    'file_format' => $aiData['file']['format'],
                ],
                [
                    'job_id' => $aiData['job_id'],
                    'status' => $aiData['status'],
                    'filename' => $aiData['file']['filename'],
                    'download_url' => $aiData['file']['download_url'],
                ]
            );

            return $this->successResponse(
                'Ligands exported successfully',
                $this->formatResponse($ligandExport)
            );
        } catch (Throwable $e) {
            return $this->errorResponse(
                'Failed to connect to AI service',
                500
            );
        }
    }

    private function getLigand(LigandExport $ligandExport)
    {
        return $this->successResponse(
            'Ligands exported successfully',
            $this->formatResponse($ligandExport)
        );
    }

    private function formatResponse(LigandExport $ligandExport): array
    {
        return [
            'job_id' => $ligandExport->job_id,
            'status' => $ligandExport->status,
            'format' => $ligandExport->file_format,
            'filename' => $ligandExport->filename,
            'smiles' => $ligandExport->smiles,
            'download_url' => $ligandExport->download_url,
        ];
    }
}
