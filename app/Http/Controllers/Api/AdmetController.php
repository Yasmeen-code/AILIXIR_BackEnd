<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Services\AdmetService;

class AdmetController extends BaseController
{
    public function predict(Request $request, AdmetService $admetService)
    {
        try {
            $result = $admetService->predictBatchFromString($request->input('smiles'));

            return $this->successResponse('ADMET predictions generated successfully', $result);
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to generate ADMET predictions', 500);
        }
    }
}
