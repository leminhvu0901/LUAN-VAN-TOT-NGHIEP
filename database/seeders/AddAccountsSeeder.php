<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class AddAccountsSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@gmail.com'],
            [
                'name' => 'Admin',
                'password' => Hash::make('Leminhvu9124@'),
                'is_active' => 1,
            ]
        );

        User::updateOrCreate(
            ['email' => 'User@gmail.com'],
            [
                'name' => 'User',
                'password' => Hash::make('Leminhvu9124@'),
                'is_active' => 1,
            ]
        );

        $this->command->info('Done: admin@gmail.com and User@gmail.com created.');
    }
}
