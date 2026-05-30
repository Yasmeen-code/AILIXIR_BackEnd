<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Services\AdmetService;

class AdmetController extends BaseController
{
    public function predict(Request $request, AdmetService $admetService)
    {
        try {
            if ($request->hasFile('file')) {
                $file = $request->file('file');
                $extension = $file->getClientOriginalExtension();
                $content = file_get_contents($file->getRealPath());
                $result = $admetService->predictFromFile($content, $extension);
                return $this->successResponse('ADMET predictions generated successfully', $result);
            } elseif ($request->has('smiles') && !empty($request->input('smiles'))) {
                $result = $admetService->predictBatchFromString($request->input('smiles'));
                return $this->successResponse('ADMET predictions generated successfully', $result);
            } else {
                return $this->errorResponse('Please provide either a file or SMILES string', 400);
            }
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to generate ADMET predictions: ' . $e->getMessage(), 500);
        }
    }
}
