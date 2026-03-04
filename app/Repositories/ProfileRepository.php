<?php

namespace App\Repositories;

use App\Models\User;
use App\Models\Researcher;

class ProfileRepository
{
    public function saveUser(User $user): User
    {
        $user->save();
        return $user;
    }

    public function getUserResearcher(User $user): ?Researcher
    {
        return $user->researcher;
    }

    public function createResearcher(array $data): Researcher
    {
        return Researcher::create($data);
    }

    public function updateResearcher(Researcher $researcher, array $data): Researcher
    {
        $researcher->update($data);
        return $researcher;
    }
}
