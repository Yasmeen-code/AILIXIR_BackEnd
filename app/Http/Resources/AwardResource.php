<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class AwardResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'category' => $this->category,
            'images' => $this->images,
            'image' => $this->images[0] ?? null,
            'year_won' => $this->pivot->year_won ?? null,
            'contribution' => $this->pivot->contribution ?? null,
        ];
    }
}
