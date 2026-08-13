<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    // Seed the application's database.
    public function run(): void
    {
        // User::factory(10)->create();

        \App\Models\User::updateOrCreate(
            ['email' => 'admin@gmail.com'],
            [
                'name' => 'Admin',
                'password' => \Illuminate\Support\Facades\Hash::make('Leminhvu9124@'),
                'is_active' => 1,
            ]
        );

        \App\Models\User::updateOrCreate(
            ['email' => 'User@gmail.com'],
            [
                'name' => 'User',
                'password' => \Illuminate\Support\Facades\Hash::make('Leminhvu9124@'),
                'is_active' => 1,
            ]
        );
    }
}
