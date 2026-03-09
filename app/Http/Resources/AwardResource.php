<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

class AwardResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'category' => $this->category,
            'images' => $this->images,
            'short_description' => Str::limit($this->description, 100),
            'scientists_count' => $this->when(isset($this->scientists_count), $this->scientists_count),
            'scientists' => \App\Http\Resources\ScientistResource::collection($this->whenLoaded('scientists')),
        ];
    }
}
