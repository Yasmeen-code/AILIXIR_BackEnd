<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class TestUserSeeder extends Seeder
{
    public function run(): void
    {
        User::firstOrCreate(
            ['email' => 'test@example.com'],
            [
                'name' => 'Test User',
                'password' => Hash::make('password123'),
                'is_verified' => true,
                'email_verified_at' => now(),
            ]
        );

        User::firstOrCreate(
            ['email' => 'salehyasmeen080@gmail.com'],
            [
                'name' => 'Saleh Yasmeen',
                'password' => Hash::make('123456789'),
                'role' => 'normal',
                'is_verified' => true,
                'email_verified_at' => now(),
            ]
        );
    }
}
