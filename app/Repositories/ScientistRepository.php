<?php

namespace App\Repositories;

use App\Models\Scientist;

class ScientistRepository
{
    public function paginate($perPage)
    {
        return Scientist::query()
            ->select('id', 'name', 'images', 'bio', 'field')
            ->with(['awards:id,name,images'])
            ->withCount('awards')
            ->paginate($perPage);
    }

    public function findWithAwards($id)
    {
        return Scientist::with([
            'awards:id,name,category,images'
        ])->findOrFail($id);
    }
}
