<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Admin
        User::create([
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
            'role' => User::ROLE_ADMIN,
        ]);

        // Creator
        User::create([
            'name' => 'Creator',
            'email' => 'creator@example.com',
            'password' => Hash::make('password'),
            'role' => User::ROLE_CREATOR,
        ]);

        // Regular user
        User::create([
            'name' => 'User',
            'email' => 'user@example.com',
            'password' => Hash::make('password'),
            'role' => User::ROLE_USER,
        ]);

        $this->call([
            PostSeeder::class,
        ]);
    }
}
