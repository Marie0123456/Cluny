<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::firstOrCreate(
            ['email' => 'admin@equimanage.fr'],
            [
                'name' => 'Administrateur',
                'password' => Hash::make('password'),
                'role' => Role::ADMIN,
            ]
        );

        User::firstOrCreate(
            ['email' => 'user@equimanage.fr'],
            [
                'name' => 'Utilisateur Test',
                'password' => Hash::make('password'),
                'role' => Role::USER,
            ]
        );
    }
}
