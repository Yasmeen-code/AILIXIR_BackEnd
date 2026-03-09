<?php

namespace App\Services;

use App\Repositories\ScientistRepository;

class ScientistService
{
    protected $repository;

    public function __construct(ScientistRepository $repository)
    {
        $this->repository = $repository;
    }

    public function getScientists($perPage)
    {
        return $this->repository->paginate($perPage);
    }

    public function getScientist($id)
    {
        return $this->repository->findWithAwards($id);
    }
}
