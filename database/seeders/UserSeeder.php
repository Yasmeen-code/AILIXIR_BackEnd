<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Researcher;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Admin User
        $admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => Hash::make('123456'),
            'role' => 'admin',
        ]);

        // Normal User
        $normal = User::create([
            'name' => 'Normal User',
            'email' => 'normal@example.com',
            'password' => Hash::make('123456'),
            'role' => 'normal',
        ]);

        // Researcher User
        $researcherUser = User::create([
            'name' => 'Researcher User',
            'email' => 'researcher@example.com',
            'password' => Hash::make('123456'),
            'role' => 'researcher',
        ]);

        // Researcher Profile
        Researcher::create([
            'user_id' => $researcherUser->id,
            'specialization' => 'AI in Drug Discovery',
            'university' => 'Cairo University',
            'years_of_experience' => 5,
            'bio' => 'Researcher specialized in computational drug discovery.',
            'photo' => null,
        ]);
    }
}
