<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

class ScientistResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'nationality' => $this->nationality,
            'birth_year' => $this->birth_year,
            'death_year' => $this->death_year,
            'field' => $this->field,
            'images' => $this->images,
            'bio' => $this->bio,
            'short_bio' => Str::limit($this->bio, 150),
            'impact' => $this->impact,
            'awards_count' => $this->when(isset($this->awards_count), $this->awards_count),
            'awards' => \App\Http\Resources\AwardResource::collection($this->whenLoaded('awards')),
        ];
    }
}
