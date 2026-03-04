<?php

namespace App\Repositories;

use App\Models\User;

class UserRepository
{
    public function create(array $data): User
    {
        return User::create($data);
    }

    public function findByEmail(string $email): ?User
    {
        return User::where('email', $email)->first();
    }

    public function firstOrCreate(array $attributes, array $values = []): User
    {
        return User::firstOrCreate($attributes, $values);
    }

    public function save(User $user): User
    {
        $user->save();
        return $user;
    }
}
