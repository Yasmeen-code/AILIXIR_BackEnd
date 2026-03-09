<?php

namespace App\Repositories;

use App\Models\Award;

class AwardRepository
{
    public function paginate($perPage)
    {
        return Award::query()
            ->with(['scientists:id,name,nationality,images,field'])
            ->withCount('scientists')
            ->paginate($perPage);
    }

    public function findWithScientists($id)
    {
        return Award::with(['scientists:id,name,nationality,images,field'])->findOrFail($id);
    }
}
