<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ChemistryResponseResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'success' => $this['success'] ?? false,
            'data' => $this['data'] ?? null,
            'status' => $this['status'] ?? null,
            'error' => $this['error'] ?? null,
        ];
    }
}
