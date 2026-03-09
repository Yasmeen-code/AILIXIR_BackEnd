<?php

namespace App\Services;

use App\Repositories\AwardRepository;

class AwardService
{
    protected $repository;

    public function __construct(AwardRepository $repository)
    {
        $this->repository = $repository;
    }

    public function getAwards($perPage)
    {
        return $this->repository->paginate($perPage);
    }

    public function getAward($id)
    {
        return $this->repository->findWithScientists($id);
    }
}
