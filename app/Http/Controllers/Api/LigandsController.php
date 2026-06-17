<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Models\LigandExport;
use Illuminate\Support\Facades\Auth;

class LigandsController extends BaseController
{

    private function aiServiceUrl(): string
    {
        return rtrim(config('services.generation.url', 'http://generation:8000'), '/');
    }

    public function exportLigands(Request $request)
    {
        $validated = $request->validate([
            'smiles' => 'required|string',
            'format' => 'required|string|in:pdbqt,pdb,mol2',
        ]);

        $response = $this->callAiService('/ligands/export', $validated);

        return $response;
    }

    public function callAiService($endpoint, $validated)
    {
        $url = $this->aiServiceUrl() . $endpoint;
        $response = Http::timeout(120)
            ->retry(5, 1000)
            ->post($url, $validated);
        if ($response->failed()) {
            return response()->json([
                'success' => false,
                'message' => 'AI service error: ' . $response->body(),
            ], $response->status());
        }
        $aiData = $response->json();

        LigandExport::create([
            'user_id' => Auth::id(),
            'job_id' => $aiData['job_id'],
            'status' => $aiData['status'],
            'format' => $aiData['file']['format'],
            'filename' => $aiData['file']['filename'],
            'smiles' => $aiData['canonical_smiles']
        ]);

        return response()->json([
            'success' => true,
            'data' => [
                'job_id' => $aiData['job_id'],
                'status' => $aiData['status'],
                'smiles' => $aiData['canonical_smiles'],
                'format' => $aiData['file']['format'],
                'filename' => $aiData['file']['filename'],
                'created_at' => now()->toDateTimeString()
            ]
        ]);
    }
}
