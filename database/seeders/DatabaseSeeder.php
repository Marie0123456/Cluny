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
        // Compte administrateur par défaut
        User::firstOrCreate(
            ['email' => 'admin@ehnc.fr'],
            [
                'name' => 'Administrateur',
                'password' => Hash::make('password'),
                'role' => Role::ADMIN,
            ]
        );
    }
}
